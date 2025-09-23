<?php
/**
 * Test Email Sending Immediately
 * This script sends a test email right now to verify the email system works
 */

session_start();
require_once 'dbConnect.php';

// Set timezone to Bangladesh time
date_default_timezone_set('Asia/Dhaka');

// Include PHPMailer
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';
require 'mail/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get the logged-in user's email
$user_email = '';
$user_name = 'Test User';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_email = $row['email'];
        $user_name = $row['name'];
    }
    $stmt->close();
}

// If no user is logged in, use a default email for testing
if (empty($user_email)) {
    $user_email = 'schowdhury222152@bscse.uiu.ac.bd'; // Using the email from your database
    $user_name = 'Shahin Chowdhury';
}

echo "=== Testing Email System ===\n";
echo "Sending test email to: $user_email\n";
echo "User name: $user_name\n\n";

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'mubasshirahmed263@gmail.com';
    $mail->Password = 'xeen nwrp mclu mqcf';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    
    // Enable debug output
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {
        echo "SMTP Debug: $str\n";
    };
    
    // Recipients
    $mail->setFrom('mubasshirahmed263@gmail.com', 'MediAI - Test');
    $mail->addAddress($user_email, $user_name);
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = "TEST - MediAI Medication Reminder System";
    
    // HTML Email Template
    $mail->Body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Test Medication Reminder</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #e3f2fd;
            }
            .logo {
                color: #2196F3;
                font-size: 28px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .test-banner {
                background: #4CAF50;
                color: white;
                padding: 10px;
                border-radius: 5px;
                text-align: center;
                margin-bottom: 20px;
                font-weight: bold;
            }
            .reminder-box {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 25px;
                border-radius: 10px;
                text-align: center;
                margin: 20px 0;
            }
            .medicine-name {
                font-size: 24px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .time-info {
                font-size: 18px;
                margin-bottom: 15px;
            }
            .meal-info {
                font-size: 16px;
                opacity: 0.9;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #eee;
                color: #666;
                font-size: 14px;
            }
            .button {
                display: inline-block;
                background: #4CAF50;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 20px;
                font-weight: bold;
            }
            .button:hover {
                background: #45a049;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='test-banner'>✅ SUCCESS - Email System is Working!</div>
            
            <div class='header'>
                <div class='logo'>MediAI</div>
                <h2>Medication Reminder Test</h2>
            </div>
            
            <div class='reminder-box'>
                <div class='medicine-name'>Test Medicine</div>
                <div class='time-info'>⏰ Time: " . date('g:i A') . "</div>
                <div class='meal-info'>🍽️ Before Meal</div>
            </div>
            
            <p>Hello <strong>$user_name</strong>,</p>
            
            <p>🎉 <strong>Great news!</strong> The MediAI medication reminder email system is working perfectly!</p>
            
            <p>This test email confirms that:</p>
            <ul>
                <li>✅ Email configuration is correct</li>
                <li>✅ PHPMailer is working</li>
                <li>✅ Gmail SMTP is accessible</li>
                <li>✅ HTML email templates are rendering properly</li>
            </ul>
            
            <p>Your medication reminders will now be sent automatically when medications are due.</p>
            
            <p style='text-align: center;'>
                <a href='http://localhost/MediAI/mediReminder.php' class='button'>View My Medications</a>
            </p>
            
            <div class='footer'>
                <p>This is a test email from the MediAI medication reminder system.</p>
                <p>Test completed at: " . date('Y-m-d H:i:s') . "</p>
                <p>© " . date('Y') . " MediAI. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Plain text version
    $mail->AltBody = "
    MediAI - Medication Reminder Test
    
    Hello $user_name,
    
    SUCCESS! The MediAI medication reminder email system is working perfectly!
    
    This test email confirms that the email system is properly configured and working.
    
    Your medication reminders will now be sent automatically when medications are due.
    
    Test completed at: " . date('Y-m-d H:i:s') . "
    
    Best regards,
    MediAI Team";
    
    $mail->send();
    echo "✅ Email sent successfully!\n";
    echo "Check your email inbox for the test message.\n";
    
} catch (Exception $e) {
    echo "❌ Failed to send email: " . $e->getMessage() . "\n";
}

$conn->close();
echo "\n=== Test Complete ===\n";
?> 