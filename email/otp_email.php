<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendOtpEmail($to, $otp, $name) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'maharanamunmun@gmail.com'; 
        $mail->Password = 'qjuy ndxc zwdz lchx'; // 👈 paste Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        //Recipients
        $mail->setFrom('maharanamunmun@gmail.com', 'Bachelor Room Expense Management');
        $mail->addAddress($to, $name);

        // Email content
        $subject = "Your OTP for Application Status";
        $message = "
        <html>
        <head>
        <title>Your OTP for Application Status</title>
        <style>
            .otp-box{
                background:#f2f2f2;
                padding:10px 15px;
                font-size:22px;
                font-weight:bold;
                border-radius:5px;
                display:inline-block;
            }
        </style>
        </head>
        <body>
        <p>Hello $name,</p>
        <p>Your OTP is:</p>
        <div class='otp-box'>$otp</div>
        <p>Valid for 5 minutes.</p>
        <p>If not requested, ignore this mail.</p>
        <br>Regards,<br>Bachelor Room Expense Management Team
        </body>
        </html>";

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
