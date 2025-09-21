<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Log user activity - unauthorized access
    if (isset($_SESSION['user_id'])) {
        logUserActivity($conn, $_SESSION['user_id'], 'unauthorized_access', 'accept_invitation_process', 'User attempted to access accept_invitation_process without login');
    }
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Log user activity - invitation action attempt
logUserActivity($conn, $user_id, 'invitation_action', 'accept_invitation_process', 'User attempting to ' . $action . ' invitation for room ID: ' . $room_id);

// Validate inputs
if ($room_id === 0 || !in_array($action, ['accept', 'reject'])) {
    // Log user activity - invalid request
    logUserActivity($conn, $user_id, 'invalid_request', 'accept_invitation_process', 'Invalid request for room ID: ' . $room_id . ', action: ' . $action);
    header("Location: ../index.php?error=Invalid request");
    exit();
}

// Check if user has a pending invitation for this room
$stmt = $conn->prepare("SELECT id FROM room_members WHERE room_id = ? AND user_id = ? AND invitation_status = 'pending'");
$stmt->bind_param("ii", $room_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Log user activity - no invitation found
    logUserActivity($conn, $user_id, 'invitation_not_found', 'accept_invitation_process', 'No pending invitation found for room ID: ' . $room_id);
    header("Location: ../index.php?error=No pending invitation found for this room");
    exit();
}

$conn->begin_transaction();

try {
    if ($action === 'accept') {
        // Update invitation status to accepted and set joined_at timestamp
        $stmt = $conn->prepare("UPDATE room_members SET invitation_status = 'accepted', joined_at = NOW(), last_activity = NOW() WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $user_id);
        $stmt->execute();
        
        // Get room name for success message
        $stmt = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $room_name = $result->fetch_assoc()['name'];
        
        $conn->commit();
        
        // Log user activity - invitation accepted
        logUserActivity($conn, $user_id, 'invitation_accepted', 'accept_invitation_process', 'User accepted invitation for room: ' . $room_name);
        
        header("Location: ../index.php?success=Successfully joined room: " . urlencode($room_name));
        exit();
        
    } elseif ($action === 'reject') {
        // Update invitation status to rejected
        $stmt = $conn->prepare("UPDATE room_members SET invitation_status = 'rejected' WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $user_id);
        $stmt->execute();
        
        // Get room name for success message
        $stmt = $conn->prepare("SELECT name FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $room_name = $result->fetch_assoc()['name'];
        
        $conn->commit();
        
        // Log user activity - invitation rejected
        logUserActivity($conn, $user_id, 'invitation_rejected', 'accept_invitation_process', 'User rejected invitation for room: ' . $room_name);
        
        header("Location: ../index.php?success=Room invitation rejected: " . urlencode($room_name));
        exit();
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Accept invitation error: " . $e->getMessage());
    
    // Log user activity - invitation action failed
    logUserActivity($conn, $user_id, 'invitation_action_failed', 'accept_invitation_process', 'Failed to process invitation for room ID: ' . $room_id . ', error: ' . $e->getMessage());
    
    header("Location: ../index.php?error=Failed to process invitation: " . urlencode($e->getMessage()));
    exit();
}
?>