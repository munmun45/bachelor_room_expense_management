<?php
session_start();
require_once '../config/config.php';
require_once '../sms/twilio_sms.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {
    // Get phone number from form
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // Validate phone number
    if (empty($phone)) {
        header("Location: ../forgot-password.php?error=Phone number is required");
        exit();
    }
    
    // Check if phone number exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
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
    
    // Redirect to OTP verification page
    header("Location: ../forgot-password.php?phone=" . urlencode($phone) . "&success=OTP sent to your phone number");
    exit();
} else {
    // If accessed directly without form submission
    header("Location: ../forgot-password.php");
    exit();
}
?>