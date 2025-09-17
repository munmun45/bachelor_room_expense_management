<?php
session_start();
require_once '../config/config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_password'])) {
    // Get form data
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';
    
    // Validate inputs
    if (empty($phone) || empty($token) || empty($password) || empty($confirm_password)) {
        header("Location: ../reset-password.php?phone=" . urlencode($phone) . "&token=" . urlencode($token) . "&error=All fields are required");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../reset-password.php?phone=" . urlencode($phone) . "&token=" . urlencode($token) . "&error=Passwords do not match");
        exit();
    }
    
    // Validate password length
    if (strlen($password) < 6) {
        header("Location: ../reset-password.php?phone=" . urlencode($phone) . "&token=" . urlencode($token) . "&error=Password must be at least 6 characters long");
        exit();
    }
    
    // Get user_id from phone number
    $user_query = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $user_query->bind_param("s", $phone);
    $user_query->execute();
    $user_result = $user_query->get_result();
    
    if ($user_result->num_rows == 0) {
        header("Location: ../forgot-password.php?error=User not found. Please try again.");
        exit();
    }
    
    $user_row = $user_result->fetch_assoc();
    $user_id = $user_row['id'];
    
    // Verify token from password_reset table
    $stmt = $conn->prepare("SELECT * FROM password_reset WHERE user_id = ? AND reset_token = ? AND is_used = 0 AND expires_at > NOW()");
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        header("Location: ../forgot-password.php?error=Invalid or expired reset token. Please try again.");
        exit();
    }
    
    // Hash the new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update user password
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user_id);
    $update_result = $update_stmt->execute();
    
    if ($update_result) {
        // Mark token as used
        $mark_used = $conn->prepare("UPDATE password_reset SET is_used = 1 WHERE user_id = ? AND reset_token = ?");
        $mark_used->bind_param("is", $user_id, $token);
        $mark_used->execute();
        
        // Redirect to login with success message
        header("Location: ../login.php?success=Password has been reset successfully. You can now login with your new password.");
        exit();
    } else {
        header("Location: ../reset-password.php?phone=" . urlencode($phone) . "&token=" . urlencode($token) . "&error=Failed to reset password. Please try again.");
        exit();
    }
} else {
    // If accessed directly without form submission
    header("Location: ../forgot-password.php");
    exit();
}
?>