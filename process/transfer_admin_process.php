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
$new_admin_id = isset($_POST['new_admin_id']) ? intval($_POST['new_admin_id']) : 0;

if ($room_id && $new_admin_id) {
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
    
    // Only admin can transfer admin rights
    if (!$member['is_admin']) {
        header("Location: ../room-details.php?id=$room_id&error=Only room admins can transfer admin rights");
        exit();
    }
    
    // Check if the new admin is a member of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $new_admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../room-details.php?id=$room_id&error=Selected user is not a member of this room");
        exit();
    }
    
    // Check if trying to transfer to self
    if ($user_id == $new_admin_id) {
        header("Location: ../room-details.php?id=$room_id&error=You cannot transfer admin rights to yourself");
        exit();
    }
    
    // Begin transaction for admin transfer
    $conn->begin_transaction();
    
    try {
        // Remove admin rights from current admin
        $stmt = $conn->prepare("UPDATE room_members SET is_admin = 0 WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $user_id);
        $stmt->execute();
        
        // Give admin rights to new user
        $stmt = $conn->prepare("UPDATE room_members SET is_admin = 1 WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $new_admin_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header("Location: ../room-details.php?id=$room_id&success=Admin rights transferred successfully. You are now a regular member of this room.");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Admin transfer error: " . $e->getMessage());
        header("Location: ../room-details.php?id=$room_id&error=Failed to transfer admin rights: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: ../room-details.php?id=$room_id&error=Invalid request");
    exit();
}
?>