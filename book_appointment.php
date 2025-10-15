<?php
session_start();
require_once 'dbConnect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to book an appointment']);
    exit;
}

try {
    // Log POST data
    error_log("Appointment Data: " . print_r($_POST, true));

    // Validate required fields
    $required_fields = ['doctor_id', 'hospital_id', 'appointment_date', 'appointment_time', 'full_name'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Get form data
    $patient_id = $_SESSION['user_id'];
    $patient_name = $_POST['full_name'];
    $doctor_id = intval($_POST['doctor_id']);
    $hospital_id = intval($_POST['hospital_id']);
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $notes = $_POST['reason'] ?? '';
    $appointment_status = 'pending';

    // Format timeslot
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $timeslot = date('Y-m-d H:i:s', strtotime("$appointment_date $appointment_time"));

    $conn->begin_transaction();

    // Insert appointment with proper parameter order
    $query = "INSERT INTO appointments (
        patient_id, patient_name, doctor_id, hospital_id,
        notes, phone, email, timeslot, 
        report_file, appointment_status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $report_file = null; // Handle file upload separately if needed

    $stmt->bind_param(
        "issiisssss",
        $patient_id,
        $patient_name,
        $doctor_id,
        $hospital_id,
        $notes,
        $phone,
        $email,
        $timeslot,
        $report_file,
        $appointment_status
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $appointment_id = $conn->insert_id;

    // Calculate serial number
    $serial_query = "SELECT COUNT(*) as serial_no 
                    FROM appointments 
                    WHERE doctor_id = ? 
                    AND hospital_id = ? 
                    AND DATE(timeslot) = DATE(?)";

    $serial_stmt = $conn->prepare($serial_query);
    $serial_stmt->bind_param("iis", $doctor_id, $hospital_id, $timeslot);
    $serial_stmt->execute();
    $serial_result = $serial_stmt->get_result();
    $serial_no = $serial_result->fetch_assoc()['serial_no'];

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => "Appointment booked successfully! Your serial number is {$serial_no}.",
        'appointment_id' => $appointment_id,
        'serial_no' => $serial_no,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    error_log("Appointment Error: " . $e->getMessage());
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
