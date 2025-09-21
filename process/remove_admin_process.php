<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$group_id = intval($_POST['group_id']);
$target_admin_id = intval($_POST['admin_id']);
$admin_name = $_POST['admin_name'] ?? '';

// Validate inputs
if (empty($group_id) || empty($target_admin_id)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Log user activity for attempting to remove admin
    logUserActivity($conn, $user_id, 'admin_removal_attempt', 'remove_admin_process.php', 'Attempting to remove admin ID: ' . $target_admin_id . ' from group ID: ' . $group_id);
    
    // Check permissions of the acting user (admin of room OR leader of this group)
    $stmt = $conn->prepare("SELECT
                                rg.room_id AS room_id,
                                rg.name AS group_name,
                                COALESCE((SELECT rm2.is_admin FROM room_members rm2 WHERE rm2.room_id = rg.room_id AND rm2.user_id = ?), 0) AS is_admin,
                                COALESCE((SELECT gm2.is_leader FROM group_members gm2 WHERE gm2.group_id = rg.id AND gm2.user_id = ?), 0) AS is_leader
                            FROM room_groups rg
                            WHERE rg.id = ?");
    $stmt->bind_param("iii", $user_id, $user_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Only room admins or group leaders can remove room admins from groups');
    }
    
    $user_info = $result->fetch_assoc();
    $is_admin = intval($user_info['is_admin']) === 1;
    $is_leader = intval($user_info['is_leader']) === 1;
    
    // Check if user has permission (admin OR group leader)
    if (!$is_admin && !$is_leader) {
        throw new Exception('Only room admins or group leaders can remove room admins from groups');
    }
    
    $room_id = intval($user_info['room_id']);
    $group_name = $user_info['group_name'];
    
    // Check if the target admin is actually a room admin and member of this group
    $stmt = $conn->prepare("SELECT rm.is_admin, gm.*, u.full_name 
                           FROM room_members rm
                           JOIN group_members gm ON rm.user_id = gm.user_id
                           JOIN users u ON rm.user_id = u.id
                           WHERE rm.room_id = ? AND rm.user_id = ? AND gm.group_id = ? AND rm.is_admin = 1");
    $stmt->bind_param("iii", $room_id, $target_admin_id, $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User is not a room admin or not a member of this group');
    }
    
    $admin_info = $result->fetch_assoc();
    $target_admin_name = $admin_info['full_name'];
    
    // Prevent removing yourself
    if ($target_admin_id == $user_id) {
        throw new Exception('You cannot remove yourself from the group');
    }
    
    // Get current user's name for the notification message
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user_info = $result->fetch_assoc();
    $current_user_name = $current_user_info['full_name'];
    
    // If the target admin is currently a group leader, revoke leadership first
    if (intval($admin_info['is_leader']) === 1) {
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_admin_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to revoke leadership before removal');
        }
    }

    // Remove the admin from the group
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_admin_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to remove admin from group');
    }
    
    // Add notification message to group
    $user_role = $is_admin ? 'Room Admin' : 'Group Leader';
    $notification_message = "🚫 **Admin Removed from Group**\n\n" . 
                           "**" . htmlspecialchars($current_user_name) . "** ($user_role) has removed **" . htmlspecialchars($target_admin_name) . "** (Room Admin) from this group.\n\n" .
                           "The admin no longer has access to this group but retains their room admin privileges.";
    
    $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
    $stmt->execute();
    
    // Log successful admin removal
    logUserActivity($conn, $user_id, 'admin_removed', 'remove_admin_process.php', 'Successfully removed admin ' . $target_admin_name . ' (ID: ' . $target_admin_id . ') from group ID: ' . $group_id);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Admin removed from group successfully!',
        'removed_admin' => $target_admin_name
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Log error
    logUserActivity($conn, $user_id, 'admin_removal_failed', 'remove_admin_process.php', 'Failed to remove admin ID: ' . $target_admin_id . ' from group ID: ' . $group_id . ' - Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>