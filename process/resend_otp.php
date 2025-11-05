<?php
// Resend OTP process
session_start();
require_once '../config/config.php';
require_once '../sms/twilio_sms.php';
require_once '../email/otp_email.php';

if (isset($_GET['phone'])) {
    $phone = trim($_GET['phone']);
    $purpose = isset($_GET['purpose']) ? trim($_GET['purpose']) : 'registration';
    $email = isset($_GET['email']) ? $_GET['email'] : null;
    
    // Log user activity for OTP resend attempt
    logUserActivity($conn, 0, 'otp_resend_attempt', 'resend_otp.php', 'OTP resend attempt for phone: ' . $phone . ', purpose: ' . $purpose);
    
    // Generate new OTP
    $otp = generateOTP();
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $otp_stored = storeOTP($conn, $phone, $email, $otp, $purpose);
    
    if ($otp_stored) {
        // Send OTP via SMS
        $message = "Your verification code is: $otp. Valid for 10 minutes.";
        // $sent = sendOTP($phone, $message);
        $sent = sendOtpEmail($phone, $otp, $full_name);
        
        if ($sent) {
            // Log successful OTP resend
            logUserActivity($conn, 0, 'otp_resend_success', 'resend_otp.php', 'OTP successfully resent to phone: ' . $phone . ', purpose: ' . $purpose);
            
            // Redirect back with success message
            if ($purpose == 'registration') {
                header("Location: ../register.php?phone=" . urlencode($phone) . "&success=New OTP sent to your phone number");
            } elseif ($purpose == 'password_reset') {
                header("Location: ../forgot-password.php?phone=" . urlencode($phone) . "&success=New OTP sent to your phone number");
            }
            exit();
        } else {
            // Log failed OTP resend
            logUserActivity($conn, 0, 'otp_resend_failed', 'resend_otp.php', 'Failed to send OTP to phone: ' . $phone . ', purpose: ' . $purpose);
            
            // Failed to send OTP
            if ($purpose == 'registration') {
                header("Location: ../register.php?phone=" . urlencode($phone) . "&error=Failed to send OTP. Please try again.");
            } elseif ($purpose == 'password_reset') {
                header("Location: ../forgot-password.php?phone=" . urlencode($phone) . "&error=Failed to send OTP. Please try again.");
            }
            exit();
        }
    } else {
        // Log failed OTP storage
        logUserActivity($conn, 0, 'otp_resend_failed', 'resend_otp.php', 'Failed to store OTP for phone: ' . $phone . ', purpose: ' . $purpose);
        
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