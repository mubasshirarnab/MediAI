<?php
session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');

if (!isset($_GET['doctor_id'])) {
    echo json_encode(['error' => 'Doctor ID is required']);
    exit;
}

$doctor_id = intval($_GET['doctor_id']);

// Get hospitals where this doctor works
$query = "SELECT u.id, u.name 
          FROM users u 
          JOIN doctor_hospital dh ON u.id = dh.hospital_id 
          WHERE dh.doctor_id = ? 
          ORDER BY u.name";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $hospitals = [];
    while ($row = $result->fetch_assoc()) {
        $hospitals[] = [
            'id' => (int)$row['id'],
            'name' => $row['name']
        ];
    }

    echo json_encode($hospitals);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
