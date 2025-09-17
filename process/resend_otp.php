<?php
// Resend OTP process
session_start();
require_once '../config/config.php';
require_once '../sms/twilio_sms.php';

if (isset($_GET['phone'])) {
    $phone = trim($_GET['phone']);
    $purpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : 'registration';
    $email = isset($_GET['email']) ? $_GET['email'] : null;
    
    // Generate new OTP
    $otp = generateOTP();
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $otp_stored = storeOTP($conn, $phone, $email, $otp, $purpose);
    
    if ($otp_stored) {
        // Send OTP via SMS
        $message = "Your verification code is: $otp. Valid for 10 minutes.";
        $sent = sendOTP($phone, $message);
        
        if ($sent) {
            // Redirect back with success message
            if ($purpose == 'registration') {
                header("Location: ../register.php?phone=" . urlencode($phone) . "&success=New OTP sent to your phone number");
            } elseif ($purpose == 'password_reset') {
                header("Location: ../forgot-password.php?phone=" . urlencode($phone) . "&success=New OTP sent to your phone number");
            }
            exit();
        } else {
            // Failed to send OTP
            if ($purpose == 'registration') {
                header("Location: ../register.php?phone=" . urlencode($phone) . "&error=Failed to send OTP. Please try again.");
            } elseif ($purpose == 'password_reset') {
                header("Location: ../forgot-password.php?phone=" . urlencode($phone) . "&error=Failed to send OTP. Please try again.");
            }
            exit();
        }
    } else {
        // Failed to store OTP
        if ($purpose == 'registration') {
            header("Location: ../register.php?error=Something went wrong. Please try again.");
        } elseif ($purpose == 'password_reset') {
            header("Location: ../forgot-password.php?error=Something went wrong. Please try again.");
        }
        exit();
    }
} else {
    // If accessed directly without required parameters
    header("Location: ../index.php");
    exit();
}
?>