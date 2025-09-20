<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

if ($room_id && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../index.php?error=You are not a member of this room");
        exit();
    }
    
    $member = $result->fetch_assoc();
    
    // Only admin can delete the room
    if (!$member['is_admin']) {
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
        
        header("Location: ../index.php?success=Room and all associated data deleted successfully");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Room deletion error: " . $e->getMessage());
        header("Location: ../room-details.php?id=$room_id&error=Failed to delete room: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../room-details.php?id=$room_id");
    exit();
}
?>