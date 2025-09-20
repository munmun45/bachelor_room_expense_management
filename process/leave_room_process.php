<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;

if ($room_id) {
    // Check if user is admin
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: ../index.php?error=You are not a member of this room");
        exit();
    }
    
    $member = $result->fetch_assoc();
    
    // If user is admin, prevent them from leaving
    if ($member['is_admin']) {
        header("Location: ../room-details.php?id=$room_id&error=As an admin, you cannot leave the room. You can only transfer admin rights to another member or delete the room.");
        exit();
    }
    
    // Remove user from all groups in the room
    $stmt = $conn->prepare("DELETE gm FROM group_members gm 
                           JOIN room_groups rg ON gm.group_id = rg.id 
                           WHERE rg.room_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    
    // Remove user from room
    $stmt = $conn->prepare("DELETE FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    
    header("Location: ../index.php?success=You have left the room");
    exit();
} else {
    header("Location: ../index.php");
    exit();
}
?>