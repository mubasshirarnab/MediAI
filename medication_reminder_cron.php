<?php
/**
 * Medication Reminder Cron Job
 * This script should be run every minute via cron job
 * 
 * Cron job command: * * * * * php /path/to/your/project/medication_reminder_cron.php
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
ini_set('error_log', 'medication_reminder_errors.log');

/**
 * Send medication reminder email
 */
function sendMedicationReminder($user_email, $user_name, $medicine_name, $dose_time, $meal_time) {
    global $conn;
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mubasshirahmed263@gmail.com'; // Replace with your email
        $mail->Password = 'xeen nwrp mclu mqcf'; // Replace with your app password
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
        
        // Plain text version
        $mail->AltBody = "
        MediAI - Medication Reminder
        
        Hello $user_name,
        
        It's time to take your medication:
        
        Medicine: $medicine_name
        Time: $formatted_time
        Instructions: $meal_time
        
        Please take your medication now.
        
        Best regards,
        MediAI Team";
        
        $mail->send();
        
        // Log successful email
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
    
    try {
        // Get current time in HH:MM format
        $current_time = date('H:i:00');
        $current_date = date('Y-m-d');
        
        // Query to get all medications that are due now
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
                mt.dose_time = ? 
                AND m.begin_date <= ? 
                AND m.end_date >= ?
                AND u.email IS NOT NULL
                AND u.email != ''
        ";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare statement: " . $conn->error);
            return;
        }
        
        $stmt->bind_param("sss", $current_time, $current_date, $current_date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reminders_sent = 0;
        $errors = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Check if we already sent a reminder for this medication at this time today
            $check_query = "
                SELECT id FROM medication_reminders_sent 
                WHERE medication_id = ? 
                AND dose_time = ? 
                AND DATE(sent_at) = ?
            ";
            
            $check_stmt = $conn->prepare($check_query);
            if ($check_stmt) {
                $check_stmt->bind_param("iss", $row['medication_id'], $current_time, $current_date);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                // If no reminder was sent today for this medication at this time
                if ($check_result->num_rows == 0) {
                    // Send the reminder
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
                        if ($log_stmt) {
                            $log_stmt->bind_param("is", $row['medication_id'], $current_time);
                            $log_stmt->execute();
                        }
                        
                        $reminders_sent++;
                    } else {
                        $errors++;
                    }
                }
                $check_stmt->close();
            }
        }
        
        $stmt->close();
        
        // Log summary
        if ($reminders_sent > 0 || $errors > 0) {
            error_log("Medication reminder cron completed: $reminders_sent reminders sent, $errors errors at " . date('Y-m-d H:i:s'));
        }
        
    } catch (Exception $e) {
        error_log("Error in medication reminder cron: " . $e->getMessage());
    }
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
    // Create the reminders sent table if it doesn't exist
    createRemindersSentTable();
    
    // Check and send reminders
    checkAndSendReminders();
    
    echo "Medication reminder cron completed successfully at " . date('Y-m-d H:i:s') . "\n";
    
} catch (Exception $e) {
    error_log("Fatal error in medication reminder cron: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}

// Close database connection
$conn->close();
?> 