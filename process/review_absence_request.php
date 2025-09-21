<?php
header('Content-Type: application/json');
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($request_id === 0 || !in_array($action, ['approved', 'rejected'])) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit();
    }
    
    try {
        // Log user activity for reviewing absence request
        logUserActivity($conn, $user_id, 'absence_request_reviewed', 'review_absence_request.php', 'User reviewed absence request ID: ' . $request_id . ' with action: ' . $action);
        
        // First, get the absence request and check if user is admin of the room
        $stmt = $conn->prepare("SELECT ar.*, rm.is_admin 
                               FROM absence_requests ar
                               JOIN room_members rm ON ar.room_id = rm.room_id AND rm.user_id = ?
                               WHERE ar.id = ? AND rm.is_admin = 1");
        $stmt->bind_param("ii", $user_id, $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['error' => 'Absence request not found or you are not authorized to review it']);
            exit();
        }
        
        $absence_request = $result->fetch_assoc();
        
        if ($absence_request['status'] !== 'pending') {
            echo json_encode(['error' => 'This request has already been reviewed']);
            exit();
        }
        
        // Update the absence request
        $stmt = $conn->prepare("UPDATE absence_requests 
                               SET status = ?, reviewed_by = ?, reviewed_at = NOW()
                               WHERE id = ?");
        $stmt->bind_param("sii", $action, $user_id, $request_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Absence request ' . $action . ' successfully'
            ]);
        } else {
            // Log error
            logUserActivity($conn, $user_id, 'absence_request_review_failed', 'review_absence_request.php', 'Failed to update absence request ID: ' . $request_id);
            echo json_encode(['error' => 'Failed to update absence request']);
        }
        
    } catch (Exception $e) {
        error_log("Review absence request error: " . $e->getMessage());
        
        // Log error
        logUserActivity($conn, $user_id, 'absence_request_review_error', 'review_absence_request.php', 'Error reviewing absence request ID: ' . $request_id . ' - Error: ' . $e->getMessage());
        echo json_encode(['error' => 'Failed to process request']);
    }
    
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>