<?php
require_once 'dbConnect.php';

// Check if table exists and what columns it has
$result = $conn->query("DESCRIBE hospital_settings");
if ($result) {
    echo "Table exists. Columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Table does not exist or error: " . $conn->error . "\n";
}

// Drop and recreate table with correct structure
$conn->query("DROP TABLE IF EXISTS hospital_settings");

$sql = "CREATE TABLE hospital_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    setting_category VARCHAR(50) NOT NULL,
    setting_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hospital_category (hospital_id, setting_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "\nTable recreated successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

// Insert default settings for hospital_id = 7
$default_settings = [
    'general' => [
        'hospital_name' => 'United Hospital',
        'hospital_address' => 'Plot 15, Road 71, Gulshan Dhaka 1212, Bangladesh',
        'hospital_phone' => '+880-123-456-789',
        'hospital_email' => 'info@unitedhospital.com',
        'hospital_website' => 'https://unitedhospital.com',
        'operating_hours' => [
            'monday' => '24/7',
            'tuesday' => '24/7',
            'wednesday' => '24/7',
            'thursday' => '24/7',
            'friday' => '24/7',
            'saturday' => '24/7',
            'sunday' => '24/7'
        ],
        'timezone' => 'Asia/Dhaka',
        'currency' => 'BDT',
        'language' => 'en',
        'theme' => 'light',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s'
    ],
    'billing' => [
        'tax_rate' => 0.00,
        'service_charge' => 0.00,
        'currency' => 'BDT',
        'auto_billing_enabled' => false
    ],
    'notifications' => [
        'email_enabled' => true,
        'sms_enabled' => true,
        'push_enabled' => true
    ],
    'system' => [
        'maintenance_mode' => false,
        'backup_frequency' => 'daily',
        'audit_log_enabled' => true
    ]
];

foreach ($default_settings as $category => $data) {
    $json_data = json_encode($data);
    $stmt = $conn->prepare("
        INSERT INTO hospital_settings (hospital_id, setting_category, setting_data) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_data = VALUES(setting_data)
    ");
    $stmt->bind_param("iss", $hospital_id, $category, $json_data);
    $hospital_id = 7; // United Hospital ID
    
    if ($stmt->execute()) {
        echo "Inserted settings for category: $category\n";
    } else {
        echo "Error inserting $category: " . $stmt->error . "\n";
    }
}

echo "Database setup completed!\n";
?>