<?php
session_start();
require_once '../config/config.php';
require_once '../sms/twilio_sms.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {
    // Get phone number from form
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Log user activity - forgot password attempt
    logUserActivity($conn, 0, 'forgot_password_attempt', 'forgot_password_process', 'Forgot password attempt for phone: ' . $phone);
    
    // Validate phone number
    if (empty($phone)) {
        // Log user activity - missing phone
        logUserActivity($conn, 0, 'forgot_password_failed', 'forgot_password_process', 'Forgot password failed: Phone number is required');
        header("Location: ../forgot-password.php?error=Phone number is required");
        exit();
    }
    
    // Check if phone number exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Log user activity - phone not found
        logUserActivity($conn, 0, 'forgot_password_failed', 'forgot_password_process', 'Forgot password failed: No account found with phone: ' . $phone);
        header("Location: ../forgot-password.php?error=No account found with this phone number");
        exit();
    }
    
    // Generate and send OTP
    $otp = generateOTP();
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store OTP in database
    storeOTP($conn, $phone, $otp, $expiry, 'password_reset');
    
    // Send OTP via SMS
    $message = "Your password reset OTP is: $otp. Valid for 10 minutes.";
    sendOTP($phone, $message);
    
    // Log user activity - OTP sent
    logUserActivity($conn, 0, 'otp_sent', 'forgot_password_process', 'OTP sent for password reset to phone: ' . $phone);
    
    // Redirect to OTP verification page
    header("Location: ../forgot-password.php?phone=" . urlencode($phone) . "&success=OTP sent to your phone number");
    exit();
} else {
    // If accessed directly without form submission
    // Log user activity - unauthorized access
    logUserActivity($conn, 0, 'unauthorized_access', 'forgot_password_process', 'Unauthorized access to forgot_password_process');
    header("Location: ../forgot-password.php");
    exit();
}
?>