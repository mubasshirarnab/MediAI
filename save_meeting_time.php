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
    $meeting_time = isset($_POST['meeting_time']) ? trim($_POST['meeting_time']) : '';

    if ($patient_id && $meeting_time) {
        // Check if a meeting time already exists for this patient-doctor pair
        $sql_check = "SELECT id FROM time_for_meeting WHERE patient_id = ? AND doctor_id = ?";
        $stmt_check = $conn->prepare($sql_check);
        if (!$stmt_check) {
            echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
            exit();
        }
        $stmt_check->bind_param("ii", $patient_id, $doctor_id);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result && $result->num_rows > 0) {
            // Update existing meeting time
            $sql_update = "UPDATE time_for_meeting SET meeting_time = ? WHERE patient_id = ? AND doctor_id = ?";
            $stmt = $conn->prepare($sql_update);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("sii", $meeting_time, $patient_id, $doctor_id);
        } else {
            // Insert new meeting time
            $sql_insert = "INSERT INTO time_for_meeting (patient_id, doctor_id, meeting_time) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'DB prepare error: ' . $conn->error]);
                exit();
            }
            $stmt->bind_param("iis", $patient_id, $doctor_id, $meeting_time);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Meeting time saved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $stmt->error]);
        }
        $stmt->close();
        $stmt_check->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>