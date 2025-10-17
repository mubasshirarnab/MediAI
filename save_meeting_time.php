<?php
session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $patient_id = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
    $meeting_code = isset($_POST['meeting_code']) ? trim($_POST['meeting_code']) : '';

    if ($patient_id && $meeting_code) {
        $stmt = $conn->prepare("INSERT INTO meeting_code (patient_id, doctor_id, meeting_code) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $patient_id, $doctor_id, $meeting_code);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>