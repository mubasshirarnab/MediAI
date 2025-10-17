<?php
session_start();
header('Content-Type: application/json');
require_once 'dbConnect.php';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Authentication required. Please log in.';
    echo json_encode($response);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

$doctor_id = $_SESSION['user_id'];
$patient_id = isset($_POST['patient_id']) ? trim($_POST['patient_id']) : '';
$meeting_code = isset($_POST['meeting_code']) ? trim($_POST['meeting_code']) : '';

if (empty($patient_id) || empty($meeting_code)) {
    $response['message'] = 'Patient ID and meeting code are required.';
    echo json_encode($response);
    exit();
}

// Check if a code already exists for this patient from this doctor
$sql_check = "SELECT id FROM meeting_code WHERE patient_id = ? AND doctor_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $patient_id, $doctor_id);
$stmt_check->execute();
$result = $stmt_check->get_result();

if ($result->num_rows > 0) {
    // If exists, update it
    $sql = "UPDATE meeting_code SET meeting_code = ? WHERE patient_id = ? AND doctor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $meeting_code, $patient_id, $doctor_id);
} else {
    // If not exists, insert a new one
    $sql = "INSERT INTO meeting_code (doctor_id, patient_id, meeting_code) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iis", $doctor_id, $patient_id, $meeting_code);
}

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Meeting code saved successfully!';
} else {
    $response['message'] = 'Database error: ' . $stmt->error;
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>
