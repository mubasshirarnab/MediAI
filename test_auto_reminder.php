<?php
/**
 * Test Auto Medication Reminder System
 * This script tests the automatic medication reminder functionality
 */

// Set timezone to Bangladesh time
date_default_timezone_set('Asia/Dhaka');

// Include database connection
require_once 'dbConnect.php';

echo "<h2>🧪 Testing Auto Medication Reminder System</h2>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Check if the reminders sent table exists
$table_check = $conn->query("SHOW TABLES LIKE 'medication_reminders_sent'");
if ($table_check->num_rows == 0) {
    echo "<p style='color: orange;'>⚠️ Creating medication_reminders_sent table...</p>";
    
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
    
    if ($conn->query($create_table_query)) {
        echo "<p style='color: green;'>✅ Table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✅ medication_reminders_sent table exists</p>";
}

// Check user 16's medications
echo "<h3>📋 User 16's Medications:</h3>";
$user_query = "
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
    WHERE m.user_id = 16
    ORDER BY mt.dose_time
";

$user_result = $conn->query($user_query);

if ($user_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Medicine</th><th>Time</th><th>Meal</th><th>Date Range</th><th>Email</th>";
    echo "</tr>";
    
    while ($row = $user_result->fetch_assoc()) {
        $is_due = false;
        $current_time = date('H:i:00');
        $current_date = date('Y-m-d');
        
        // Check if medication is due today
        if ($row['begin_date'] <= $current_date && $row['end_date'] >= $current_date) {
            $time_diff = abs(strtotime($current_time) - strtotime($row['dose_time']));
            if ($time_diff <= 300) { // Within 5 minutes
                $is_due = true;
            }
        }
        
        $row_style = $is_due ? "background: #ffeb3b;" : "";
        
        echo "<tr style='$row_style'>";
        echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
        echo "<td>" . $row['dose_time'] . "</td>";
        echo "<td>" . $row['meal_time'] . "</td>";
        echo "<td>" . $row['begin_date'] . " to " . $row['end_date'] . "</td>";
        echo "<td>" . htmlspecialchars($row['user_email']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No medications found for user 16</p>";
}

// Test the auto reminder system
echo "<h3>🔍 Testing Auto Reminder System:</h3>";

// Include the auto reminder script
ob_start();
include 'auto_medication_reminder.php';
$output = ob_get_clean();

echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px; font-family: monospace;'>";
echo "<strong>Output:</strong><br>";
echo nl2br(htmlspecialchars($output));
echo "</div>";

// Check recent reminders sent
echo "<h3>📧 Recent Reminders Sent:</h3>";
$recent_query = "
    SELECT 
        mrs.id,
        m.medicine_name,
        mrs.dose_time,
        mrs.sent_at,
        u.name as user_name
    FROM medication_reminders_sent mrs
    JOIN medication m ON mrs.medication_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE mrs.sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY mrs.sent_at DESC
    LIMIT 10
";

$recent_result = $conn->query($recent_query);

if ($recent_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Medicine</th><th>Time</th><th>Sent At</th><th>User</th>";
    echo "</tr>";
    
    while ($row = $recent_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['medicine_name']) . "</td>";
        echo "<td>" . $row['dose_time'] . "</td>";
        echo "<td>" . $row['sent_at'] . "</td>";
        echo "<td>" . htmlspecialchars($row['user_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>ℹ️ No recent reminders sent in the last hour</p>";
}

// Manual test button
echo "<h3>🧪 Manual Test:</h3>";
echo "<form method='post' style='margin: 20px 0;'>";
echo "<button type='submit' name='test_reminder' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "🔔 Test Send Reminder Now";
echo "</button>";
echo "</form>";

if (isset($_POST['test_reminder'])) {
    echo "<div style='background: #e8f5e8; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Manual Test Results:</strong><br>";
    
    // Force send a test reminder
    ob_start();
    include 'auto_medication_reminder.php';
    $test_output = ob_get_clean();
    
    echo nl2br(htmlspecialchars($test_output));
    echo "</div>";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f9f9f9;
}
h2, h3 {
    color: #333;
    border-bottom: 2px solid #4CAF50;
    padding-bottom: 10px;
}
table {
    margin: 10px 0;
}
th, td {
    padding: 8px;
    text-align: left;
}
</style> 