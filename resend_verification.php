<?php
require_once 'session_manager.php';
require_once 'dbConnect.php';
require_once 'email_config.php';

// Start session with timeout management
SessionManager::startSession();

require 'vendor/autoload.php';

// Check if user is coming from verify page
if (!isset($_SESSION['verify_email'])) {
    header('Location: signup.php');
    exit();
}

$email = $_SESSION['verify_email'];

// Generate new OTP
$otp = rand(100000, 999999);

// Update OTP in database
$update_query = "UPDATE users SET otp = ? WHERE email = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ss", $otp, $email);
$stmt->execute();

// Send verification email
$email_sent = EmailConfig::sendVerificationEmail($email, $otp);

if ($email_sent) {
    header('Location: verify.php?resent=1');
    exit();
} else {
    header('Location: verify.php?error=1');
    exit();
}
?>