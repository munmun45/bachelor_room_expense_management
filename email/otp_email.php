<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendOtpEmail($to, $otp, $name) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com';
        $mail->Port = 587;
        $mail->SMTPDebug = 0; // Set to 2 for debugging
        $mail->SMTPAuth = true;
        $mail->Username = 'info@satyameducation.org.in';
        $mail->Password = 'Qwertyuiop1@';

        // Recipients
        $mail->setFrom('info@satyameducation.org.in', 'Satyam Education');
        $mail->addAddress($to, $name);

        // Email content
        $subject = "Your OTP for Application Status";
        $message = "
        <html>
        <head>
            <title>Your OTP for Application Status</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .otp-box { 
                    background: #f4f4f4; 
                    padding: 10px 15px; 
                    display: inline-block; 
                    font-size: 24px; 
                    font-weight: bold;
                    letter-spacing: 2px;
                    margin: 10px 0;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>OTP for Application Status</h2>
                <p>Hello $name,</p>
                <p>Your One Time Password (OTP) for accessing your application status is:</p>
                <div class='otp-box'>$otp</div>
                <p>This OTP is valid for 5 minutes.</p>
                <p>If you didn't request this OTP, please ignore this email.</p>
                <p>Best regards,<br>Admission Team - Satyam Education</p>
            </div>
        </body>
        </html>
        ";

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
