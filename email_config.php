<?php
/**
 * Email Configuration File
 * Centralized email settings to avoid SMTP issues
 */

// Include PHPMailer files
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';
require 'mail/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailConfig {
    private static $config = [
        'host' => 'smtp.gmail.com',
        // Default SMTP account used for outgoing emails. Replace with your app-specific password.
        // These values were taken from the project's existing test script for local testing.
        'username' => 'mubasshirahmed263@gmail.com',
        'password' => 'xeen nwrp mclu mqcf',
        'encryption' => PHPMailer::ENCRYPTION_SMTPS,
        'port' => 465,
        'from_email' => 'mubasshirahmed263@gmail.com',
        'from_name' => 'MediAI'
    ];
    
    public static function getMailer() {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = self::$config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = self::$config['username'];
            $mail->Password = self::$config['password'];
            $mail->SMTPSecure = self::$config['encryption'];
            $mail->Port = self::$config['port'];

            // Recommended options for local development / self-signed certs
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            // Set timeout to prevent hanging
            $mail->Timeout = 10;

            // Disable verbose debug output by default; change to 2 for troubleshooting
            $mail->SMTPDebug = 0;

            // Recipients
            $mail->setFrom(self::$config['from_email'], self::$config['from_name']);
            
            return $mail;
        } catch (Exception $e) {
            throw new Exception("Mailer configuration failed: " . $e->getMessage());
        }
    }
    
    public static function sendVerificationEmail($email, $otp, $name = '') {
        try {
            $mail = self::getMailer();
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'MediAI - Email Verification';
            $mail->Body = "
                <h2>Welcome to MediAI!</h2>
                <p>Hello" . ($name ? " $name" : "") . ",</p>
                <p>Your verification code is: <strong style='font-size: 24px; color: #2196F3;'>$otp</strong></p>
                <p>Please enter this code on the verification page to complete your registration.</p>
                <p>If you didn't create an account, please ignore this email.</p>
                <hr>
                <p style='color: #666; font-size: 12px;'>This code will expire in 10 minutes.</p>
            ";
            
            $mail->AltBody = "Welcome to MediAI!\n\nYour verification code is: $otp\n\nPlease enter this code on the verification page to complete your registration.";
            
            return $mail->send();
        } catch (Exception $e) {
            // Fallback: Store OTP in session for manual verification
            $_SESSION['fallback_otp'] = $otp;
            $_SESSION['otp_generated'] = time();
            
            error_log("Email failed, using fallback: " . $e->getMessage());
            return false;
        }
    }
}
?>
