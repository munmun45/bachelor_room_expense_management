<?php
// Twilio SMS integration for OTP verification
// Using Twilio API to send real SMS messages

function sendOTP($phone_number, $message) {
    // IMPORTANT: Replace these with your actual Twilio credentials from your Twilio dashboard
    // 1. Log in to https://www.twilio.com/console
    // 2. Copy your Account SID and Auth Token from the dashboard
    // 3. Get your Twilio phone number from the Phone Numbers section
    $account_sid = 'ACa429237c227ace7a675449e15a682472'; // Replace with your actual Twilio Account SID
    $auth_token = '2f46f90212002ad0a3758ab96db94a2d'; // Replace with your actual Twilio Auth Token
    $twilio_number = '+16812009967'; // Replace with your Twilio phone number (with + country code)
    
    // Format phone number to ensure it has country code
    if (substr($phone_number, 0, 1) !== '+') {
        // Add India country code if not present
        $phone_number = '+91' . ltrim($phone_number, '0');
    }
    
    // Log the attempt for debugging
    $log_message = "Sending SMS to $phone_number: $message\n";
    file_put_contents(__DIR__ . '/sms_log.txt', $log_message, FILE_APPEND);
    
    // Use cURL to send the SMS via Twilio API directly
    $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
    
    $data = array(
        'From' => $twilio_number,
        'To' => $phone_number,
        'Body' => $message
    );
    
    $post = http_build_query($data);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_USERPWD, "$account_sid:$auth_token");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for testing
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log the response for debugging
    $log_response = "Twilio Response (Status: $status_code): $response\nError: $error\n";
    file_put_contents(__DIR__ . '/sms_log.txt', $log_response, FILE_APPEND);
    
    return ($status_code == 201 || $status_code == 200);
}

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