<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'assign_group_leader_process', 'User attempted to access assign_group_leader_process without login');
    }
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Log user activity - leader assignment attempt
logUserActivity($conn, $user_id, 'leader_assignment_attempt', 'assign_group_leader_process', 'User attempting to assign/remove group leader');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Log user activity - invalid method
    logUserActivity($conn, $user_id, 'invalid_method', 'assign_group_leader_process', 'Invalid HTTP method used');
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$group_id = intval($_POST['group_id']);
$target_user_id = intval($_POST['user_id']);
$action = $_POST['action']; // 'assign', 'remove', or 'remove_and_assign_admin'
$member_name = $_POST['member_name'] ?? '';

// Log user activity - processing leader action
logUserActivity($conn, $user_id, 'leader_action_processing', 'assign_group_leader_process', 'Processing leader action: ' . $action . ' for group ID: ' . $group_id . ' on user ID: ' . $target_user_id);

// Validate inputs
if (empty($group_id) || empty($target_user_id) || !in_array($action, ['assign', 'remove', 'remove_and_assign_admin'])) {
    // Log user activity - invalid parameters
    logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Invalid parameters for leader action');
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if current user is admin of the room that contains this group OR is a group leader
    $stmt = $conn->prepare("SELECT rg.room_id, rm.is_admin, gm.is_leader, r.name as room_name, rg.name as group_name
                           FROM room_groups rg
                           JOIN room_members rm ON rg.room_id = rm.room_id
                           JOIN group_members gm ON rg.id = gm.group_id
                           JOIN rooms r ON rg.room_id = r.id
                           WHERE rg.id = ? AND rm.user_id = ? AND gm.user_id = ?");
    $stmt->bind_param("iii", $group_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - not authorized
        logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'User not authorized to manage group leaders for group ID: ' . $group_id);
        throw new Exception('You are not authorized to manage group leaders');
    }
    
    $user_info = $result->fetch_assoc();
    $room_id = $user_info['room_id'];
    $is_admin = $user_info['is_admin'] == 1;
    $is_leader = $user_info['is_leader'] == 1;
    $group_name = $user_info['group_name'];
    
    // Check if user has permission (admin OR group leader)
    if (!$is_admin && !$is_leader) {
        // Log user activity - insufficient permissions
        logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Insufficient permissions to manage group leaders for group ID: ' . $group_id);
        throw new Exception('Only room admins or group leaders can manage group leadership');
    }
    
    // Check if the target user is actually a member of this group
    $stmt = $conn->prepare("SELECT gm.*, u.full_name 
                           FROM group_members gm 
                           JOIN users u ON gm.user_id = u.id
                           WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $group_id, $target_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - user not member
        logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Target user not member of group ID: ' . $group_id);
        throw new Exception('User is not a member of this group');
    }
    
    $member_info = $result->fetch_assoc();
    $target_user_name = $member_info['full_name'];
    $current_leader_status = $member_info['is_leader'];
    
    // Get current user's name for the notification message
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user_info = $result->fetch_assoc();
    $current_user_name = $current_user_info['full_name'];
    
    if ($action === 'assign') {
        // Check if user is already a leader
        if ($current_leader_status == 1) {
            // Log user activity - already leader
            logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'User already leader of group ID: ' . $group_id);
            throw new Exception('User is already a group leader');
        }
        
        // Remove leadership from current leader (only one leader allowed)
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND is_leader = 1");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Assign leadership to new user
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 1 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_user_id);
        $stmt->execute();
        
        // Add notification message to group
        $notification_message = "👑 **New Group Leader Assigned**\n\n" . 
                               "**" . htmlspecialchars($current_user_name) . "** has made **" . htmlspecialchars($target_user_name) . "** the new group leader.\n\n" .
                               "⚠️ **Note**: Only one member can be a group leader at a time.\n\n" .
                               "Group leaders can:\n" .
                               "• Add/remove group members\n" .
                               "• Create settlements\n" .
                               "• Remove room admins from group\n" .
                               "• Manage all group activities";
        
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
        $stmt->execute();
        
        // Log user activity - leader assigned
        logUserActivity($conn, $user_id, 'leader_assigned', 'assign_group_leader_process', 'Leader assigned to group ID: ' . $group_id . ' for user ID: ' . $target_user_id);
        
        $success_message = "Leadership assigned successfully!";
        
    } else if ($action === 'remove') {
        // Check if user is actually a leader
        if ($current_leader_status != 1) {
            // Log user activity - not leader
            logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Target user not leader of group ID: ' . $group_id);
            throw new Exception('User is not a group leader');
        }
        
        // Prevent removing leadership from yourself if you're not an admin
        if ($target_user_id == $user_id && !$is_admin) {
            // Log user activity - cannot remove self
            logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'User cannot remove own leadership from group ID: ' . $group_id);
            throw new Exception('You cannot remove your own leadership. Only room admins can do this.');
        }
        
        // Remove leadership
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_user_id);
        $stmt->execute();
        
        // Add notification message to group
        $notification_message = "👑 **Leadership Removed**\n\n" . 
                               "**" . htmlspecialchars($current_user_name) . "** has removed leadership from **" . htmlspecialchars($target_user_name) . "**.\n\n" .
                               "They no longer have leader privileges but remain in the group.";
        
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
        $stmt->execute();
        
        // Log user activity - leader removed
        logUserActivity($conn, $user_id, 'leader_removed', 'assign_group_leader_process', 'Leader removed from group ID: ' . $group_id . ' for user ID: ' . $target_user_id);
        
        $success_message = "Leadership removed successfully!";
        
    } else if ($action === 'remove_and_assign_admin') {
        // Check if user is actually a leader
        if ($current_leader_status != 1) {
            // Log user activity - not leader
            logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Target user not leader of group ID: ' . $group_id);
            throw new Exception('User is not a group leader');
        }
        
        // Check if current user is admin
        if (!$is_admin) {
            // Log user activity - not admin
            logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Only admins can transfer leadership for group ID: ' . $group_id);
            throw new Exception('Only room admins can remove leaders and become new leaders');
        }
        
        // Remove leadership from current leader
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $target_user_id);
        $stmt->execute();
        
        // Assign leadership to current admin user
        $stmt = $conn->prepare("UPDATE group_members SET is_leader = 1 WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        
        // Add notification message to group
        $notification_message = "👑 **Leadership Transferred**\n\n" . 
                               "**" . htmlspecialchars($current_user_name) . "** (Room Admin) has removed leadership from **" . htmlspecialchars($target_user_name) . "** and taken over as the new group leader.\n\n" .
                               "**Previous Leader:** " . htmlspecialchars($target_user_name) . "\n" .
                               "**New Leader:** " . htmlspecialchars($current_user_name) . "\n" .
                               "**Date:** " . date('M d, Y \a\t g:i A') . "\n\n" .
                               "The new leader now has full control of the group.";
        
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
        $stmt->execute();
        
        // Log user activity - leadership transferred
        logUserActivity($conn, $user_id, 'leadership_transferred', 'assign_group_leader_process', 'Leadership transferred in group ID: ' . $group_id . ' from user ID: ' . $target_user_id);
        
        $success_message = "Leadership transferred successfully!";
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $success_message,
        'action' => $action,
        'target_user' => $target_user_name
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    // Log user activity - leader action failed
    logUserActivity($conn, $user_id, 'leader_action_failed', 'assign_group_leader_process', 'Leader action failed for group ID: ' . $group_id . ', error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>