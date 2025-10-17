<?php
// filepath: c:\xampp\htdocs\MediAI\update_appointment_status.php
session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['appointment_id']) || !isset($data['status'])) {
        throw new Exception('Missing required parameters');
    }

    $appointment_id = intval($data['appointment_id']);
    $status = $data['status'];
    $doctor_id = $_SESSION['user_id'];

    // Verify this appointment belongs to the logged-in doctor
    $stmt = $conn->prepare("UPDATE appointments 
                           SET appointment_status = ? 
                           WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param('sii', $status, $appointment_id, $doctor_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception('No appointment found or not authorized');
        }
    } else {
        throw new Exception('Failed to update appointment status');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
