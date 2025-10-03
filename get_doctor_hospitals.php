<?php
session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$doctorUserId = $_SESSION['user_id'];

// doctor_hospital.doctor_id references doctors.user_id
$sql = "SELECT u.id AS id, u.name AS name
        FROM doctor_hospital dh
        JOIN users u ON u.id = dh.hospital_id
        WHERE dh.doctor_id = ?
        ORDER BY u.name";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to prepare statement']);
    exit;
}

$stmt->bind_param('i', $doctorUserId);
$stmt->execute();
$result = $stmt->get_result();

$hospitals = [];
while ($row = $result->fetch_assoc()) {
    $hospitals[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
    ];
}

echo json_encode($hospitals);
exit;
