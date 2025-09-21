<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'delete_group_process', 'User attempted to access delete_group_process without login');
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Log user activity - group deletion attempt
logUserActivity($conn, $user_id, 'group_deletion_attempt', 'delete_group_process', 'User attempting to delete group');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Log user activity - invalid method
    logUserActivity($conn, $user_id, 'invalid_method', 'delete_group_process', 'Invalid HTTP method used');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$group_id = intval($_POST['group_id']);
$action = $_POST['action']; // 'delete_direct' or 'request_deletion'
$room_id = intval($_POST['room_id']);

// Log user activity - processing group deletion
logUserActivity($conn, $user_id, 'group_deletion_processing', 'delete_group_process', 'Processing group deletion: ' . $action . ' for group ID: ' . $group_id . ' in room ID: ' . $room_id);

// Validate inputs
if (empty($group_id) || !in_array($action, ['delete_direct', 'request_deletion'])) {
    // Log user activity - invalid parameters
    logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'Invalid parameters for group deletion');
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if current user is admin of the room
    $stmt = $conn->prepare("SELECT rm.is_admin, r.name as room_name
                           FROM room_members rm
                           JOIN rooms r ON rm.room_id = r.id
                           WHERE rm.room_id = ? AND rm.user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - not authorized
        logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'User not authorized to manage groups in room ID: ' . $room_id);
        throw new Exception('You are not authorized to manage groups in this room');
    }
    
    $user_info = $result->fetch_assoc();
    $is_admin = $user_info['is_admin'] == 1;
    $room_name = $user_info['room_name'];
    
    if (!$is_admin) {
        // Log user activity - not admin
        logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'User not admin of room ID: ' . $room_id);
        throw new Exception('Only room admins can delete groups');
    }
    
    // Get group information
    $stmt = $conn->prepare("SELECT rg.*, 
                           (SELECT gm.is_leader FROM group_members gm WHERE gm.group_id = rg.id AND gm.user_id = ?) as is_leader,
                           (SELECT u.full_name FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = rg.id AND gm.is_leader = 1) as leader_name
                           FROM room_groups rg 
                           WHERE rg.id = ? AND rg.room_id = ?");
    $stmt->bind_param("iii", $user_id, $group_id, $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - group not found
        logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'Group not found: ' . $group_id);
        throw new Exception('Group not found');
    }
    
    $group_info = $result->fetch_assoc();
    $group_name = $group_info['name'];
    $is_leader = $group_info['is_leader'] == 1;
    $leader_name = $group_info['leader_name'];
    
    // Get current user's name
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user_info = $result->fetch_assoc();
    $current_user_name = $current_user_info['full_name'];
    
    if ($action === 'delete_direct') {
        // Admin is also the group leader - delete directly
        if (!$is_leader) {
            // Log user activity - not leader
            logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'User not leader of group ID: ' . $group_id . ' for direct deletion');
            throw new Exception('You can only delete groups where you are the leader');
        }
        
        // Delete group messages first
        $stmt = $conn->prepare("DELETE FROM group_messages WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Delete settlements
        $stmt = $conn->prepare("DELETE FROM settlements WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Delete group members
        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Delete the group
        $stmt = $conn->prepare("DELETE FROM room_groups WHERE id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Log user activity - group deleted successfully
        logUserActivity($conn, $user_id, 'group_deleted', 'delete_group_process', 'Group ID: ' . $group_id . ' deleted successfully');
        
        $success_message = "Group deleted successfully!";
        
    } else if ($action === 'request_deletion') {
        // Admin is not the leader - send deletion request
        if ($is_leader) {
            // Log user activity - already leader
            logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'User is leader of group ID: ' . $group_id . ', should use direct deletion');
            throw new Exception('You are the group leader. Use direct deletion instead.');
        }
        
        // Create deletion request message in group chat
        $notification_message = "🗑️ **Group Deletion Request**\n\n" . 
                               "**" . htmlspecialchars($current_user_name) . "** (Room Admin) has requested to delete this group.\n\n" .
                               "**Group:** " . htmlspecialchars($group_name) . "\n" .
                               "⚠️ **This action will permanently delete:**\n" .
                               "• All group messages\n" .
                               "• All settlements\n" .
                               "• All member data\n" .
                               "• The group itself\n\n" .
                               "**Group Leader:** " . htmlspecialchars($leader_name) . "\n" .
                               "**Requested by:** " . htmlspecialchars($current_user_name) . "\n" .
                               "**Date:** " . date('M d, Y \a\t g:i A') . "\n\n" .
                               "👑 **Action Required:** The group leader must approve this request for the group to be deleted.";
        
        // Insert the notification message
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
        $stmt->execute();
        
        // Log user activity - deletion request sent
        logUserActivity($conn, $user_id, 'deletion_request_sent', 'delete_group_process', 'Deletion request sent for group ID: ' . $group_id);
        
        $success_message = "Deletion request sent to group leader successfully!";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $success_message,
        'action' => $action,
        'group_name' => $group_name
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Log user activity - group deletion failed
    logUserActivity($conn, $user_id, 'group_deletion_failed', 'delete_group_process', 'Group deletion failed for group ID: ' . $group_id . ', error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>