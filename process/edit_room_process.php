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

if ($room_id && isset($_POST['edit_room'])) {
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
    
    // Only admin can edit the room
    if (!$member['is_admin']) {
        header("Location: ../room-details.php?id=$room_id&error=Only room admins can edit the room");
        exit();
    }
    
    // Get form data
    $room_name = trim($_POST['room_name']);
    $room_description = trim($_POST['room_description'] ?? '');
    $room_address = trim($_POST['room_address']);
    $latitude = trim($_POST['latitude'] ?? null);
    $longitude = trim($_POST['longitude'] ?? null);
    $rental_date = !empty($_POST['rental_date']) ? $_POST['rental_date'] : null;
    $monthly_rent = !empty($_POST['monthly_rent']) ? $_POST['monthly_rent'] : null;
    $advance_payment = !empty($_POST['advance_payment']) ? $_POST['advance_payment'] : null;
    
    // Validate inputs
    if (empty($room_name) || empty($room_address)) {
        header("Location: ../room-details.php?id=$room_id&error=Room name and address are required");
        exit();
    }
    
    // Update room
    $stmt = $conn->prepare("UPDATE rooms SET name = ?, description = ?, address = ?, latitude = ?, longitude = ?, rental_date = ?, monthly_rent = ?, advance_payment = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt === false) {
        header("Location: ../room-details.php?id=$room_id&error=Failed to prepare update statement");
        exit();
    }
    
    $stmt->bind_param("ssssssssi", $room_name, $room_description, $room_address, $latitude, $longitude, $rental_date, $monthly_rent, $advance_payment, $room_id);
    
    if ($stmt->execute()) {
        header("Location: ../room-details.php?id=$room_id&success=Room information updated successfully");
        exit();
    } else {
        header("Location: ../room-details.php?id=$room_id&error=Failed to update room information");
        exit();
    }
} else {
    header("Location: ../room-details.php?id=$room_id");
    exit();
}
?>