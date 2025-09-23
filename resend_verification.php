<?php
session_start();
require_once 'dbConnect.php';
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
$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@gmail.com'; // Replace with your Gmail
    $mail->Password = 'your-app-password'; // Replace with your app password
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('your-email@gmail.com', 'MediAI');
    $mail->addAddress($email);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'MediAI - New Verification Code';
    $mail->Body = "
        <h2>New Verification Code</h2>
        <p>Your new verification code is: <strong>$otp</strong></p>
        <p>Please enter this code on the verification page to complete your registration.</p>
        <p>If you didn't request this code, please ignore this email.</p>
    ";

    $mail->send();
    header('Location: verify.php?resent=1');
    exit();
} catch (Exception $e) {
    header('Location: verify.php?error=1');
    exit();
}
?> 