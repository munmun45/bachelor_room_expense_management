<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$member_name = isset($_POST['member_name']) ? trim($_POST['member_name']) : '';

// Debug logging
error_log("Remove member request - Admin ID: $admin_id, Group ID: $group_id, Target User ID: $user_id, Member Name: $member_name");

// Validate input
if ($group_id === 0 || $user_id === 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid group or user ID']);
    exit();
}

// Prevent admin from removing themselves
if ($admin_id === $user_id) {
    echo json_encode(['success' => false, 'error' => 'You cannot remove yourself from the group']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Log user activity for attempting to remove member
    logUserActivity($conn, $admin_id, 'member_removal_attempt', 'remove_group_member_process.php', 'Attempting to remove member ID: ' . $user_id . ' from group ID: ' . $group_id);
    
    // Check permissions of the acting user (admin of room OR leader of this group)
    $stmt = $conn->prepare("SELECT
                                rg.room_id AS room_id,
                                rg.name AS group_name,
                                COALESCE((SELECT rm2.is_admin FROM room_members rm2 WHERE rm2.room_id = rg.room_id AND rm2.user_id = ?), 0) AS is_admin,
                                COALESCE((SELECT gm2.is_leader FROM group_members gm2 WHERE gm2.group_id = rg.id AND gm2.user_id = ?), 0) AS is_leader
                            FROM room_groups rg
                            WHERE rg.id = ?");
    $stmt->bind_param("iii", $admin_id, $admin_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Group not found');
    }

    $user_info = $result->fetch_assoc();
    $is_admin = intval($user_info['is_admin']) === 1;
    $is_leader = intval($user_info['is_leader']) === 1;
    
    // Debug logging
    error_log("Permission check - Is Admin: " . ($is_admin ? 'YES' : 'NO') . ", Is Leader: " . ($is_leader ? 'YES' : 'NO'));
    
    // Check if user has permission (admin OR group leader)
    if (!$is_admin && !$is_leader) {
        error_log("Permission denied - User is neither admin nor leader");
        throw new Exception('Only room admins or group leaders can remove members');
    }
    
    $room_id = intval($user_info['room_id']);
    $group_name = $user_info['group_name'];
    
    // Check if the user to be removed is actually a member of this group
    $stmt = $conn->prepare("SELECT gm.*, u.full_name, 
                                   COALESCE((SELECT rm2.is_admin FROM room_members rm2 WHERE rm2.room_id = ? AND rm2.user_id = gm.user_id), 0) AS target_is_room_admin
                           FROM group_members gm 
                           JOIN users u ON gm.user_id = u.id
                           WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("iii", $room_id, $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User is not a member of this group');
    }
    
    $member_info = $result->fetch_assoc();
    $member_full_name = $member_info['full_name'];
    $target_is_leader = intval($member_info['is_leader']) === 1;
    $target_is_room_admin = intval($member_info['target_is_room_admin']) === 1;

    // If target is a room admin, guide to use the dedicated admin removal flow
    if ($target_is_room_admin) {
        throw new Exception('This member is a room admin. Use the "Remove Admin" action.');
    }
    
    // Get admin's name for the notification message
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_info = $result->fetch_assoc();
    $admin_name = $admin_info['full_name'];
    
    // If target is current group leader, revoke leadership first
    if ($target_is_leader) {
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to revoke leadership before removal');
        }
    }

    // Remove the user from the group
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to remove member from group');
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Member was not found in the group');
    }
    
    // Create a notification message in the group chat
    $notification_message = "👤 **Member Removed**\n\n";
    $notification_message .= "📋 **Action:** Member removed from group\n";
    $notification_message .= "👨‍💼 **Removed by:** {$admin_name}\n";
    $notification_message .= "👤 **Member:** {$member_full_name}\n";
    $notification_message .= "📅 **Date:** " . date('M d, Y \a\t g:i A') . "\n\n";
    $notification_message .= "ℹ️ This member no longer has access to this group.";
    
    // Insert the notification message
    $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $group_id, $admin_id, $notification_message);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create notification message');
    }
    
    $message_id = $conn->insert_id;
    
    // Log successful member removal
    logUserActivity($conn, $admin_id, 'member_removed', 'remove_group_member_process.php', 'Successfully removed member ' . $member_full_name . ' (ID: ' . $user_id . ') from group ID: ' . $group_id);
    
    // Commit the transaction
    $conn->commit();
    
    // Log the action for admin records
    error_log("Group member removed - Admin: {$admin_name} (ID: {$admin_id}), Removed: {$member_full_name} (ID: {$user_id}), Group: {$group_name} (ID: {$group_id})");
    
    $response = [
        'success' => true,
        'message' => 'Member removed successfully',
        'notification_message_id' => $message_id,
        'removed_user' => $member_full_name,
        'admin' => $admin_name
    ];
    
    error_log("Success response: " . json_encode($response));
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log error
    logUserActivity($conn, $admin_id, 'member_removal_failed', 'remove_group_member_process.php', 'Failed to remove member ID: ' . $user_id . ' from group ID: ' . $group_id . ' - Error: ' . $e->getMessage());
    
    error_log("Error removing group member: " . $e->getMessage());
    error_log("Error trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>