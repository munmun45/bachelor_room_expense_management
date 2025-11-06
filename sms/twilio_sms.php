<?php
// Twilio SMS integration for OTP verification
// Using Twilio API to send real SMS messages


// Function to generate a random OTP
function generateOTP($length = 6) {
    $digits = '0123456789';
    $otp = '';
    
    for ($i = 0; $i < $length; $i++) {
        $otp .= $digits[rand(0, 9)];
    }
    
    return $otp;
}

// Function to store OTP in database
function storeOTP($conn, $phone, $email, $otp, $purpose, $user_id = null) {
    // First, invalidate any existing OTPs for this phone/purpose
    $stmt = $conn->prepare("UPDATE otp_verification SET expires_at = NOW() WHERE phone = ? AND purpose = ? AND expires_at > NOW()");
    $stmt->bind_param("ss", $phone, $purpose);
    $stmt->execute();
    
    // Set expiry time (15 minutes from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO otp_verification (user_id, phone, email, otp, purpose, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $phone, $email, $otp, $purpose, $expires_at);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    } else {
        return false;
    }
}

// Function to verify OTP
function verifyOTP($conn, $phone, $otp, $purpose) {
    $stmt = $conn->prepare("SELECT id FROM otp_verification WHERE phone = ? AND otp = ? AND purpose = ? AND expires_at > NOW() AND is_verified = 0");
    $stmt->bind_param("sss", $phone, $otp, $purpose);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $otp_id = $row['id'];
        
        // Mark OTP as verified
        $update = $conn->prepare("UPDATE otp_verification SET is_verified = 1 WHERE id = ?");
        $update->bind_param("i", $otp_id);
        $update->execute();
        
        return true;
    }
    
    return false;
}
?>