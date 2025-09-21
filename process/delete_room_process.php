<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'delete_room_process', 'User attempted to access delete_room_process without login');
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

// Log user activity - room deletion attempt
logUserActivity($conn, $user_id, 'room_deletion_attempt', 'delete_room_process', 'User attempting to delete room ID: ' . $room_id);

if ($room_id && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - not a member
        logUserActivity($conn, $user_id, 'room_deletion_failed', 'delete_room_process', 'User not a member of room ID: ' . $room_id);
        header("Location: ../index.php?error=You are not a member of this room");
        exit();
    }
    
    $member = $result->fetch_assoc();
    
    // Only admin can delete the room
    if (!$member['is_admin']) {
        // Log user activity - not admin
        logUserActivity($conn, $user_id, 'room_deletion_failed', 'delete_room_process', 'User not admin of room ID: ' . $room_id);
        header("Location: ../room-details.php?id=$room_id&error=Only room admins can delete the room");
        exit();
    }
    
    // Begin transaction for complete room deletion
    $conn->begin_transaction();
    
    try {
        // Delete all group members first
        $stmt = $conn->prepare("DELETE gm FROM group_members gm 
                               JOIN room_groups rg ON gm.group_id = rg.id 
                               WHERE rg.room_id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Delete all group messages
        $stmt = $conn->prepare("DELETE gm FROM group_messages gm 
                               JOIN room_groups rg ON gm.group_id = rg.id 
                               WHERE rg.room_id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Delete all groups in the room
        $stmt = $conn->prepare("DELETE FROM room_groups WHERE room_id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Delete all member invitations for the room
        $stmt = $conn->prepare("DELETE FROM member_invitations WHERE room_id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Delete all room members
        $stmt = $conn->prepare("DELETE FROM room_members WHERE room_id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Finally, delete the room itself
        $stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Log user activity - room deleted successfully
        logUserActivity($conn, $user_id, 'room_deleted', 'delete_room_process', 'Room ID: ' . $room_id . ' deleted successfully');
        
        header("Location: ../index.php?success=Room and all associated data deleted successfully");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Room deletion error: " . $e->getMessage());
        
        // Log user activity - room deletion failed
        logUserActivity($conn, $user_id, 'room_deletion_failed', 'delete_room_process', 'Failed to delete room ID: ' . $room_id . ', error: ' . $e->getMessage());
        
        header("Location: ../room-details.php?id=$room_id&error=Failed to delete room: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Log user activity - deletion not confirmed
    logUserActivity($conn, $user_id, 'room_deletion_cancelled', 'delete_room_process', 'Room deletion not confirmed for room ID: ' . $room_id);
    header("Location: ../room-details.php?id=$room_id");
    exit();
}
?>