<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'edit_room_process', 'User attempted to access edit_room_process without login');
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;

// Log user activity - room edit attempt
logUserActivity($conn, $user_id, 'room_edit_attempt', 'edit_room_process', 'User attempting to edit room ID: ' . $room_id);

if ($room_id && isset($_POST['edit_room'])) {
    // Check if user is admin of the room
    $stmt = $conn->prepare("SELECT is_admin FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Log user activity - not a member
        logUserActivity($conn, $user_id, 'room_edit_failed', 'edit_room_process', 'User not a member of room ID: ' . $room_id);
        header("Location: ../index.php?error=You are not a member of this room");
        exit();
    }
    
    $member = $result->fetch_assoc();
    
    // Only admin can edit the room
    if (!$member['is_admin']) {
        // Log user activity - not admin
        logUserActivity($conn, $user_id, 'room_edit_failed', 'edit_room_process', 'User not admin of room ID: ' . $room_id);
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
    
    // Log user activity - processing room edit
    logUserActivity($conn, $user_id, 'room_edit_processing', 'edit_room_process', 'Processing room edit for room ID: ' . $room_id . ' with name: ' . $room_name);
    
    // Validate inputs
    if (empty($room_name) || empty($room_address)) {
        // Log user activity - missing required fields
        logUserActivity($conn, $user_id, 'room_edit_failed', 'edit_room_process', 'Room edit failed: Name or address missing for room ID: ' . $room_id);
        header("Location: ../room-details.php?id=$room_id&error=Room name and address are required");
        exit();
    }
    
    // Update room
    $stmt = $conn->prepare("UPDATE rooms SET name = ?, description = ?, address = ?, latitude = ?, longitude = ?, rental_date = ?, monthly_rent = ?, advance_payment = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt === false) {
        // Log user activity - database error
        logUserActivity($conn, $user_id, 'room_edit_failed', 'edit_room_process', 'Room edit failed: Database prepare error for room ID: ' . $room_id);
        header("Location: ../room-details.php?id=$room_id&error=Failed to prepare update statement");
        exit();
    }
    
    $stmt->bind_param("ssssssssi", $room_name, $room_description, $room_address, $latitude, $longitude, $rental_date, $monthly_rent, $advance_payment, $room_id);
    
    if ($stmt->execute()) {
        // Log user activity - room updated successfully
        logUserActivity($conn, $user_id, 'room_updated', 'edit_room_process', 'Room ID: ' . $room_id . ' updated successfully');
        header("Location: ../room-details.php?id=$room_id&success=Room information updated successfully");
        exit();
    } else {
        // Log user activity - update failed
        logUserActivity($conn, $user_id, 'room_edit_failed', 'edit_room_process', 'Room edit failed for room ID: ' . $room_id . ', error: ' . $stmt->error);
        header("Location: ../room-details.php?id=$room_id&error=Failed to update room information");
        exit();
    }
} else {
    // Log user activity - invalid request
    logUserActivity($conn, $user_id, 'room_edit_failed', 'edit_room_process', 'Invalid request to edit room ID: ' . $room_id);
    header("Location: ../room-details.php?id=$room_id");
    exit();
}
?>