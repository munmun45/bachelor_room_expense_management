<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['request_absence'])) {
    $room_id = intval($_POST['room_id']);
    $from_date = $_POST['from_date'];
    $to_date = $_POST['to_date'];
    $reason = trim($_POST['reason'] ?? '');
    
    // Log user activity for absence request
    logUserActivity($conn, $user_id, 'absence_request_submitted', 'request_absence_process.php', 'User submitted absence request for room ID: ' . $room_id . ', from: ' . $from_date . ' to: ' . $to_date);
    
    // Validate dates
    if (empty($from_date) || empty($to_date)) {
        $_SESSION['error_msg'] = "Please provide both from and to dates.";
        header("Location: ../index.php");
        exit();
    }
    
    if (strtotime($from_date) > strtotime($to_date)) {
        $_SESSION['error_msg'] = "From date cannot be after to date.";
        header("Location: ../index.php");
        exit();
    }
    
    // Check if user is a member of the room
    $stmt = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ? AND invitation_status = 'accepted'");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error_msg'] = "You are not a member of this room.";
        header("Location: ../index.php");
        exit();
    }
    
    // Check for overlapping absence requests
    $stmt = $conn->prepare("SELECT id FROM absence_requests 
                           WHERE room_id = ? AND user_id = ? 
                           AND status IN ('pending', 'approved')
                           AND (
                               (from_date <= ? AND to_date >= ?) OR
                               (from_date <= ? AND to_date >= ?) OR
                               (from_date >= ? AND to_date <= ?)
                           )");
    $stmt->bind_param("iissssss", $room_id, $user_id, $from_date, $from_date, $to_date, $to_date, $from_date, $to_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error_msg'] = "You already have an overlapping absence request for this period.";
        header("Location: ../index.php");
        exit();
    }
    
    try {
        // Insert absence request
        $stmt = $conn->prepare("INSERT INTO absence_requests (room_id, user_id, from_date, to_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $room_id, $user_id, $from_date, $to_date, $reason);
        $stmt->execute();
        
        $_SESSION['success_msg'] = "Absence request submitted successfully. Waiting for admin approval.";
        
    } catch (Exception $e) {
        error_log("Absence request error: " . $e->getMessage());
        $_SESSION['error_msg'] = "Failed to submit absence request. Please try again.";
        
        // Log error
        logUserActivity($conn, $user_id, 'absence_request_failed', 'request_absence_process.php', 'Failed to submit absence request for room ID: ' . $room_id . ' - Error: ' . $e->getMessage());
    }
}

header("Location: ../index.php");
exit();
?>