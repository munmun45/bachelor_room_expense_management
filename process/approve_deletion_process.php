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
$action = $_POST['action']; // 'approve' or 'reject'

// Validate inputs
if (empty($group_id) || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if user is group leader
    $stmt = $conn->prepare("SELECT gm.is_leader, rg.name as group_name, rg.room_id
                           FROM group_members gm
                           JOIN room_groups rg ON gm.group_id = rg.id
                           WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('You are not a member of this group');
    }
    
    $group_info = $result->fetch_assoc();
    if ($group_info['is_leader'] != 1) {
        throw new Exception('Only group leaders can approve/reject deletion requests');
    }
    
    $group_name = $group_info['group_name'];
    $room_id = $group_info['room_id'];
    
    // Get current user's name
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_user_info = $result->fetch_assoc();
    $current_user_name = $current_user_info['full_name'];
    
    if ($action === 'approve') {
        // Add approval message before deletion
        $approval_message = "✅ **Group Deletion Request Approved**\n\n" . 
                           "**" . htmlspecialchars($current_user_name) . "** (Group Leader) has approved the deletion request for this group.\n\n" .
                           "⚠️ **The group will now be deleted permanently.**\n\n" .
                           "**Group:** " . htmlspecialchars($group_name) . "\n" .
                           "**Approved by:** " . htmlspecialchars($current_user_name) . "\n" .
                           "**Date:** " . date('M d, Y \a\t g:i A');
        
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $approval_message);
        $stmt->execute();
        
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
        
        $success_message = "Group deleted successfully!";
        
    } else if ($action === 'reject') {
        // Add rejection message to group chat
        $notification_message = "❌ **Group Deletion Request Rejected**\n\n" . 
                               "**" . htmlspecialchars($current_user_name) . "** (Group Leader) has rejected the deletion request for this group.\n\n" .
                               "The group will remain active and continue to function normally.\n\n" .
                               "**Group:** " . htmlspecialchars($group_name) . "\n" .
                               "**Decision by:** " . htmlspecialchars($current_user_name) . "\n" .
                               "**Date:** " . date('M d, Y \a\t g:i A');
        
        $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $group_id, $user_id, $notification_message);
        $stmt->execute();
        
        $success_message = "Deletion request rejected successfully!";
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
    
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}
?>
