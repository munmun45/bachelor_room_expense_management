<?php
session_start();
require_once '../config/config.php';
require_once '../sms/twilio_sms.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {
    // Get OTP from form
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $purpose = isset($_POST['purpose']) ? trim($_POST['purpose']) : 'registration';
    
    // Validate inputs
    if (empty($otp) || empty($phone)) {
        $redirect_page = ($purpose == 'password_reset') ? 'forgot-password.php' : 'register.php';
        header("Location: ../$redirect_page?error=OTP and phone number are required");
        exit();
    }
    
    // Log user activity for OTP verification attempt
    logUserActivity($conn, 0, 'otp_verification_attempt', 'verify_otp_process.php', 'OTP verification attempt for phone: ' . $phone . ', purpose: ' . $purpose);
    
    // Verify OTP
    $is_valid = verifyOTP($conn, $phone, $otp, $purpose);
    
    if (!$is_valid) {
        // Log failed OTP verification
        logUserActivity($conn, 0, 'otp_verification_failed', 'verify_otp_process.php', 'Invalid or expired OTP for phone: ' . $phone . ', purpose: ' . $purpose);
        
        $redirect_page = ($purpose == 'password_reset') ? 'forgot-password.php' : 'register.php';
        header("Location: ../$redirect_page?phone=" . urlencode($phone) . "&error=Invalid or expired OTP");
        exit();
    }
    
    // Handle based on purpose
    if ($purpose == 'password_reset') {
        // For password reset, generate a token and redirect to reset password page
        $token = bin2hex(random_bytes(16)); // Generate a secure token
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Get user_id from phone number
        $user_query = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $user_query->bind_param("s", $phone);
        $user_query->execute();
        $user_result = $user_query->get_result();
        
        if ($user_result->num_rows > 0) {
            $user_row = $user_result->fetch_assoc();
            $user_id = $user_row['id'];
            
            // Store token in password_reset table
            $stmt = $conn->prepare("INSERT INTO password_reset (user_id, reset_token, is_used, expires_at) VALUES (?, ?, 0, ?)");
            $stmt->bind_param("iss", $user_id, $token, $expiry);
            $result = $stmt->execute();
        
            if ($result) {
                // Log successful OTP verification for password reset
                logUserActivity($conn, $user_id, 'otp_verified_password_reset', 'verify_otp_process.php', 'OTP verified successfully for password reset, redirecting to reset page');
                
                // Redirect to reset password page with token
                header("Location: ../reset-password.php?phone=" . urlencode($phone) . "&token=" . urlencode($token));
                exit();
            } else {
                // Log error
                logUserActivity($conn, $user_id, 'password_reset_token_failed', 'verify_otp_process.php', 'Failed to generate password reset token for user ID: ' . $user_id);
                
                header("Location: ../forgot-password.php?error=Failed to process password reset. Please try again.");
                exit();
            }
        } else {
            header("Location: ../forgot-password.php?error=User not found. Please try again.");
            exit();
        }
    } else {
        // For registration, create user account
        if (isset($_SESSION['registration'])) {
            $userData = $_SESSION['registration'];
            
            // Log user activity for registration OTP verification
            logUserActivity($conn, 0, 'otp_verified_registration', 'verify_otp_process.php', 'OTP verified successfully for registration, creating account for phone: ' . $userData['phone']);
            
            // Insert user into database
            $stmt = $conn->prepare("INSERT INTO users (full_name, phone, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $userData['full_name'], $userData['phone'], $userData['email'], $userData['password']);
            $result = $stmt->execute();
            
            if ($result) {
                // Clear session data
                unset($_SESSION['registration']);
                
                // Log successful registration
                logUserActivity($conn, 0, 'user_registered', 'verify_otp_process.php', 'New user registered successfully with phone: ' . $userData['phone']);
                
                // Redirect to login page with success message
                header("Location: ../login.php?success=Registration successful! You can now login.");
                exit();
            } else {
                // Log registration error
                logUserActivity($conn, 0, 'registration_failed', 'verify_otp_process.php', 'Failed to create account for phone: ' . $userData['phone']);
                
                header("Location: ../register.php?error=Failed to create account. Please try again.");
                exit();
            }
        } else {
            header("Location: ../register.php?error=Registration data not found. Please try again.");
            exit();
        }
    }
} else {
    // If accessed directly without form submission
    header("Location: ../index.php");
    exit();
}
?>