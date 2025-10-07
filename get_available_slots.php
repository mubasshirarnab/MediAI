<?php

session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
$hospital_id = isset($_GET['hospital_id']) ? (int)$_GET['hospital_id'] : 0;
if ($doctor_id <= 0 || $hospital_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// fetch available_hours for this doctor+hospital
$q = "SELECT day_of_week, start_time, end_time FROM available_hours
      WHERE user_id = ? AND hospital_id = ?
      ORDER BY day_of_week, start_time";
$stmt = $conn->prepare($q);
$stmt->bind_param("ii", $doctor_id, $hospital_id);
$stmt->execute();
$res = $stmt->get_result();

$days = []; // unique days
$time_map = []; // keyed by day_db (1..7) => array of start times (HH:MM:SS)
while ($row = $res->fetch_assoc()) {
    $db_day = (int)$row['day_of_week']; // 1..7 (DB)
    // convert to JS day 0..6:
    $js_day = ($db_day - 1 + 7) % 7;
    if (!isset($days[$db_day])) {
        $days[$db_day] = ['day_db' => $db_day, 'day_js' => $js_day, 'day_name' => date('l', strtotime("Sunday +" . ($js_day) . " days"))];
    }
    // push start_time (use start_time for slot display)
    $time_map[$db_day][] = substr($row['start_time'], 0, 8);
}

// reindex days array
$available_days = array_values($days);

echo json_encode(['success' => true, 'available_days' => $available_days, 'time_map' => $time_map]);
