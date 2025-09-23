<?php
/**
 * Auto Medication Reminder System
 * This script can be called via AJAX or run manually to check and send reminders
 * It will automatically find and send missed reminders
 */

// Set timezone to Bangladesh time
date_default_timezone_set('Asia/Dhaka');

// Include database connection
require_once 'dbConnect.php';

// Include PHPMailer
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';
require 'mail/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Enable error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'medication_reminder_auto.log');

/**
 * Send medication reminder email
 */
function sendMedicationReminder($user_email, $user_name, $medicine_name, $dose_time, $meal_time) {
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
        
        // Recipients
        $mail->setFrom('mubasshirahmed263@gmail.com', 'MediAI - Medication Reminder');
        $mail->addAddress($user_email, $user_name);
        
        // Format time for display
        $formatted_time = date('g:i A', strtotime($dose_time));
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "MediAI - Time to take your medication!";
        
        // HTML Email Template
        $mail->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Medication Reminder</title>
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
                <div class='header'>
                    <div class='logo'>MediAI</div>
                    <h2>Medication Reminder</h2>
                </div>
                
                <div class='reminder-box'>
                    <div class='medicine-name'>$medicine_name</div>
                    <div class='time-info'>⏰ Time: $formatted_time</div>
                    <div class='meal-info'>🍽️ $meal_time</div>
                </div>
                
                <p>Hello <strong>$user_name</strong>,</p>
                
                <p>This is your medication reminder from MediAI. It's time to take your medication:</p>
                
                <ul>
                    <li><strong>Medicine:</strong> $medicine_name</li>
                    <li><strong>Time:</strong> $formatted_time</li>
                    <li><strong>Instructions:</strong> $meal_time</li>
                </ul>
                
                <p style='text-align: center;'>
                    <a href='http://localhost/MediAI/mediReminder.php' class='button'>View My Medications</a>
                </p>
                
                <div class='footer'>
                    <p>This is an automated reminder from MediAI.</p>
                    <p>If you have any questions, please contact your healthcare provider.</p>
                    <p>© " . date('Y') . " MediAI. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->send();
        error_log("Medication reminder sent successfully to $user_email for $medicine_name at $dose_time");
        return true;
        
    } catch (Exception $e) {
        error_log("Failed to send medication reminder to $user_email: " . $e->getMessage());
        return false;
    }
}

/**
 * Check for due medications and send reminders
 */
function checkAndSendReminders() {
    global $conn;
    
    $current_time = date('H:i:00');
    $current_date = date('Y-m-d');
    
    // Check for medications due in the last 5 minutes (to catch missed ones)
    $five_minutes_ago = date('H:i:s', strtotime('-5 minutes'));
    
    $query = "
        SELECT 
            m.id as medication_id,
            m.medicine_name,
            m.meal_time,
            m.begin_date,
            m.end_date,
            mt.dose_time,
            u.name as user_name,
            u.email as user_email
        FROM medication m
        JOIN medication_times mt ON m.id = mt.medication_id
        JOIN users u ON m.user_id = u.id
        WHERE 
            m.begin_date <= ? 
            AND m.end_date >= ?
            AND u.email IS NOT NULL
            AND u.email != ''
            AND mt.dose_time BETWEEN ? AND ?
            AND NOT EXISTS (
                SELECT 1 FROM medication_reminders_sent mrs 
                WHERE mrs.medication_id = m.id 
                AND mrs.dose_time = mt.dose_time 
                AND DATE(mrs.sent_at) = ?
            )
        ORDER BY mt.dose_time DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $current_date, $current_date, $five_minutes_ago, $current_time, $current_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reminders_sent = 0;
    $errors = 0;
    
    while ($row = $result->fetch_assoc()) {
        if (sendMedicationReminder(
            $row['user_email'],
            $row['user_name'],
            $row['medicine_name'],
            $row['dose_time'],
            $row['meal_time']
        )) {
            // Log that we sent the reminder
            $log_query = "
                INSERT INTO medication_reminders_sent 
                (medication_id, dose_time, sent_at) 
                VALUES (?, ?, NOW())
            ";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("is", $row['medication_id'], $row['dose_time']);
            $log_stmt->execute();
            
            $reminders_sent++;
        } else {
            $errors++;
        }
    }
    
    $stmt->close();
    
    if ($reminders_sent > 0 || $errors > 0) {
        error_log("Auto medication reminder: $reminders_sent reminders sent, $errors errors at " . date('Y-m-d H:i:s'));
    }
    
    return $reminders_sent;
}

// Create the reminders sent table if it doesn't exist
function createRemindersSentTable() {
    global $conn;
    
    $create_table_query = "
        CREATE TABLE IF NOT EXISTS medication_reminders_sent (
            id INT AUTO_INCREMENT PRIMARY KEY,
            medication_id INT NOT NULL,
            dose_time TIME NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_medication_time (medication_id, dose_time, sent_at),
            FOREIGN KEY (medication_id) REFERENCES medication(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ";
    
    if (!$conn->query($create_table_query)) {
        error_log("Failed to create medication_reminders_sent table: " . $conn->error);
    }
}

// Main execution
try {
    createRemindersSentTable();
    $sent_count = checkAndSendReminders();
    
    // Silent operation - no output needed for production
    // Logs are written to medication_reminder_auto.log for debugging if needed
    
} catch (Exception $e) {
    error_log("Error in auto medication reminder: " . $e->getMessage());
}

$conn->close();
?> 