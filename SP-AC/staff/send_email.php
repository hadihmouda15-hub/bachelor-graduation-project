<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $message) {
    require 'vendor/autoload.php'; // Path to autoload.php
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'alichebli525@gmail.com'; // SMTP username
        $mail->Password   = 'bzdrafhmknneedmc';   // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587; // TCP port to connect to
        
        // Recipients
        $mail->setFrom('alichebli525@gmail.com', 'Healthcare System');
        $mail->addAddress($to); // Add a recipient
        
        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message); // Convert newlines to <br> for HTML
        $mail->AltBody = $message; // Plain text version
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log the error (don't show to users)
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}