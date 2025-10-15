<?php

session_start();
require_once 'dbConnect.php';

// Prevent any unwanted output
ob_clean();
header('Content-Type: application/json');

if (!isset($_GET['doctor_id']) || !isset($_GET['hospital_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing doctor_id or hospital_id'
    ]);
    exit;
}

$doctor_id = intval($_GET['doctor_id']);
$hospital_id = intval($_GET['hospital_id']);

try {
    // Verify doctor-hospital association first
    $verify = $conn->prepare("SELECT 1 FROM doctor_hospital 
                             WHERE doctor_id = ? AND hospital_id = ?");
    $verify->bind_param("ii", $doctor_id, $hospital_id);
    $verify->execute();
    if ($verify->get_result()->num_rows === 0) {
        throw new Exception("Invalid doctor-hospital combination");
    }

    // Get available hours
    $query = "SELECT day_of_week, start_time, end_time 
              FROM available_hours 
              WHERE user_id = ? AND hospital_id = ? 
              ORDER BY day_of_week, start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $doctor_id, $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $available_days = [];
    $time_map = [];

    while ($row = $result->fetch_assoc()) {
        $db_day = (int)$row['day_of_week'];
        $js_day = ($db_day - 1); // Convert 1-7 to 0-6 for JavaScript

        // Add to available days if not already present
        if (!isset($available_days[$db_day])) {
            $available_days[$db_day] = [
                'day_db' => $db_day,
                'day_js' => $js_day,
                'day_name' => date('l', strtotime("Sunday +{$js_day} days"))
            ];
        }

        // Add time to the time map
        if (!isset($time_map[$db_day])) {
            $time_map[$db_day] = [];
        }
        $time_map[$db_day][] = substr($row['start_time'], 0, 5);
    }

    echo json_encode([
        'success' => true,
        'available_days' => array_values($available_days),
        'time_map' => $time_map
    ]);
} catch (Exception $e) {
    error_log("Availability error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching availability: ' . $e->getMessage()
    ]);
}
exit;
