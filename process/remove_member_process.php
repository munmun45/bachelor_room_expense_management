<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Validate input
if ($room_id === 0 || $user_id === 0) {
    header("Location: ../room-details.php?id={$room_id}&error=Invalid room or user ID");
    exit();
}

// Prevent admin from removing themselves
if ($admin_id === $user_id) {
    header("Location: ../room-details.php?id={$room_id}&error=You cannot remove yourself from the room");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if current user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0 || $result->fetch_assoc()['is_admin'] != 1) {
        throw new Exception('Only room admins can remove members');
    }
    
    // Check if the user to be removed is actually a member of this room
    $stmt = $conn->prepare("SELECT rm.*, u.full_name FROM room_members rm JOIN users u ON rm.user_id = u.id WHERE rm.room_id = ? AND rm.user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User is not a member of this room');
    }
    
    $member_info = $result->fetch_assoc();
    $member_full_name = $member_info['full_name'];
    
    // Get admin's name for the notification message
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_info = $result->fetch_assoc();
    $admin_name = $admin_info['full_name'];
    
    // Get all groups in this room
    $stmt = $conn->prepare("SELECT id, name FROM room_groups WHERE room_id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $groups_result = $stmt->get_result();
    
    // Process each group
    while ($group = $groups_result->fetch_assoc()) {
        $group_id = $group['id'];
        $group_name = $group['name'];
        
        // Check if the user is a member of this group
        $stmt = $conn->prepare("SELECT is_leader FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $group_member_result = $stmt->get_result();
        
        if ($group_member_result->num_rows > 0) {
            $group_member = $group_member_result->fetch_assoc();
            $is_leader = $group_member['is_leader'] == 1;
            
            // If user is a leader, transfer leadership to admin
            if ($is_leader) {
                // First, remove leadership from the user
                $stmt = $conn->prepare("UPDATE group_members SET is_leader = 0 WHERE group_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $group_id, $user_id);
                $stmt->execute();
                
                // Then, make the admin the leader (if they're a member of the group)
                $stmt = $conn->prepare("UPDATE group_members SET is_leader = 1 WHERE group_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $group_id, $admin_id);
                $stmt->execute();
                
                // Add notification about leadership transfer
                $notification_message = "👑 **Leadership Transferred**\n\n" . 
                                       "**" . htmlspecialchars($admin_name) . "** (Room Admin) has automatically become the new group leader after **" . htmlspecialchars($member_full_name) . "** was removed from the room.\n\n" .
                                       "**Previous Member:** " . htmlspecialchars($member_full_name) . "\n" .
                                       "**New Leader:** " . htmlspecialchars($admin_name) . "\n" .
                                       "**Date:** " . date('M d, Y \a\t g:i A');
                
                $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iis", $group_id, $admin_id, $notification_message);
                $stmt->execute();
            }
            
            // Remove the user from the group
            $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $group_id, $user_id);
            $stmt->execute();
            
            // Add notification about member removal from group
            $notification_message = "👤 **Member Removed from Group**\n\n" . 
                                   "**" . htmlspecialchars($member_full_name) . "** has been removed from this group because they were removed from the room.\n\n" .
                                   "**Removed by:** " . htmlspecialchars($admin_name) . " (Room Admin)\n" .
                                   "**Date:** " . date('M d, Y \a\t g:i A');
            
            $stmt = $conn->prepare("INSERT INTO group_messages (group_id, user_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $group_id, $admin_id, $notification_message);
            $stmt->execute();
        }
    }
    
    // Remove the user from the room
    $stmt = $conn->prepare("DELETE FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    
    // Commit the transaction
    $conn->commit();
    
    // Redirect with success message
    header("Location: ../room-details.php?id={$room_id}&success=" . urlencode("Member removed successfully from room and all groups"));
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Redirect with error message
    header("Location: ../room-details.php?id={$room_id}&error=" . urlencode($e->getMessage()));
    exit();
}

$conn->close();
?>