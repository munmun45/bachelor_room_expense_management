<?php
// Registration process
session_start();
require_once '../config/config.php';
require_once '../sms/twilio_sms.php';

if (isset($_POST['register'])) {
    // Get form data
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']) ?: null; // Optional
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($full_name) || empty($phone) || empty($password)) {
        header("Location: ../register.php?error=All fields except email are required");
        exit();
    }
    
    // Validate phone number format
    if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        header("Location: ../register.php?error=Invalid phone number format");
        exit();
    }
    
    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: ../register.php?error=Passwords do not match");
        exit();
    }
    
    // Check if phone already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: ../register.php?error=Phone number already registered");
        exit();
    }
    
    // Check if email already exists (if provided)
    if ($email) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            header("Location: ../register.php?error=Email already registered");
            exit();
        }
    }
    
    // Store registration data in session for later use after OTP verification
    $_SESSION['registration'] = [
        'full_name' => $full_name,
        'phone' => $phone,
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT)
    ];
    
    // Generate and send OTP
    $otp = generateOTP();
    $otp_stored = storeOTP($conn, $phone, $email, $otp, 'registration');
    
    if ($otp_stored) {
        // Log user activity for registration attempt
        logUserActivity($conn, 0, 'registration_attempt', 'register_process.php', 'Registration attempt for phone: ' . $phone . ', name: ' . $full_name);
        
        // Send OTP via SMS
        $sent = sendOTP($phone, $otp);
        
        if ($sent) {
            // Redirect to OTP verification page
            header("Location: ../register.php?phone=" . urlencode($phone) . "&success=OTP sent to your phone");
            exit();
        } else {
            header("Location: ../register.php?error=Failed to send OTP. Please try again.");
            exit();
        }
    } else {
        header("Location: ../register.php?error=Something went wrong. Please try again.");
        exit();
    }
} else {
    // If accessed directly without form submission
    header("Location: ../register.php");
    exit();
}
?>