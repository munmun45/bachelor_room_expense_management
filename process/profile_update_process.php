<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['status' => 'error', 'message' => 'Update failed'];

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $room_number = $_POST['room_number'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    
    // Update profile image if uploaded
    $profile_image_name = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload_dir = '../uploads/profile/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $profile_image_name = $user_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $profile_image_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            // Update profile image in database
            $sql = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $profile_image_name, $user_id);
            $stmt->execute();
        }
    }
    
    // Update KYC document if uploaded
    if (isset($_FILES['kyc_document']) && $_FILES['kyc_document']['error'] === 0) {
        $upload_dir = '../uploads/kyc/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['kyc_document']['name'], PATHINFO_EXTENSION);
        $kyc_document_name = $user_id . '_kyc_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $kyc_document_name;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['kyc_document']['tmp_name'], $target_file)) {
            // Update KYC status to pending
            $sql = "UPDATE users SET kyc_document = ?, kyc_status = 'pending' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $kyc_document_name, $user_id);
            $stmt->execute();
        }
    }
    
    // Update user profile information
    $sql = "UPDATE users SET full_name = ?, email = ?, room_number = ?, occupation = ?, emergency_contact = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $full_name, $email, $room_number, $occupation, $emergency_contact, $user_id);
    
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Profile updated successfully'];
    } else {
        $response = ['status' => 'error', 'message' => 'Update failed: ' . $conn->error];
    }
} else {
    $response = ['status' => 'error', 'message' => 'No update action specified'];
}

// Return JSON response for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Redirect for normal form submissions
header('Location: ../profile.php?status=' . $response['status'] . '&message=' . urlencode($response['message']));
exit;
?>