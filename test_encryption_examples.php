<?php
/**
 * Example Usage of Medical Data Encryption
 * Demonstrates how to use the encryption system in your application
 */

require_once 'dbConnect.php';
require_once 'encrypted_data_handler.php';

// Initialize the encrypted data handler
$handler = new EncryptedDataHandler($conn);

echo "=== Medical Data Encryption Examples ===\n\n";

// Example 1: Store and retrieve medical report
echo "1. Medical Report Example:\n";
$patient_id = 1;
$medical_report = "Patient Sarah Johnson (45F) presents with elevated blood pressure: 145/95 mmHg. 
Prescribed Lisinopril 10mg daily. Follow-up in 4 weeks. 
Lab results: Creatinine 0.9 mg/dL, Potassium 4.2 mmol/L.";

try {
    // Store encrypted medical report
    $report_id = $handler->storeMedicalReport($patient_id, $medical_report, 'blood_pressure');
    echo "   ✓ Medical report stored with ID: $report_id\n";
    
    // Retrieve and decrypt medical report
    $retrieved_report = $handler->getMedicalReport($report_id, $patient_id);
    echo "   ✓ Medical report retrieved successfully\n";
    echo "   ✓ Data integrity: " . ($retrieved_report === $medical_report ? "VERIFIED" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 2: Store and retrieve meeting code
echo "2. Meeting Code Example:\n";
$doctor_id = 2;
$patient_id = 1;
$meeting_code = "MED-CONSULT-" . strtoupper(substr(md5(time()), 0, 8));
$meeting_time = date('Y-m-d H:i:s', strtotime('+2 days'));

try {
    // Store encrypted meeting code
    $meeting_id = $handler->storeMeetingCode($doctor_id, $patient_id, $meeting_code, $meeting_time);
    echo "   ✓ Meeting code stored with ID: $meeting_id\n";
    echo "   ✓ Original code: $meeting_code\n";
    
    // Retrieve and decrypt meeting code (as doctor)
    $retrieved_code = $handler->getMeetingCode($meeting_id, $doctor_id, 'doctor');
    echo "   ✓ Meeting code retrieved by doctor: $retrieved_code\n";
    echo "   ✓ Data integrity: " . ($retrieved_code === $meeting_code ? "VERIFIED" : "FAILED") . "\n";
    
    // Retrieve and decrypt meeting code (as patient)
    $retrieved_code_patient = $handler->getMeetingCode($meeting_id, $patient_id, 'patient');
    echo "   ✓ Meeting code retrieved by patient: $retrieved_code_patient\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 3: Store and retrieve patient notes
echo "3. Patient Notes Example:\n";
$doctor_id = 2;
$patient_notes = "Patient reports improvement in symptoms after medication adjustment.
Current medications: Metformin 500mg twice daily, Atorvastatin 20mg nightly.
Patient education provided on diet and exercise recommendations.
Mental health assessment: Patient appears optimistic about treatment progress.
Family history: Father had type 2 diabetes, mother had hypertension.";

try {
    // Store encrypted patient notes
    $note_id = $handler->storePatientNotes($patient_id, $doctor_id, $patient_notes);
    echo "   ✓ Patient notes stored with ID: $note_id\n";
    
    // Retrieve and decrypt patient notes
    $retrieved_notes = $handler->getPatientNotes($note_id, $doctor_id);
    echo "   ✓ Patient notes retrieved successfully\n";
    echo "   ✓ Data integrity: " . ($retrieved_notes === $patient_notes ? "VERIFIED" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Example 4: Show database storage format
echo "4. Database Storage Format:\n";
echo "   Checking how data is stored in database...\n";

try {
    $stmt = $conn->prepare("SELECT id, encrypted_data, iv FROM medical_reports WHERE patient_id = ? LIMIT 1");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "   ✓ Report ID: " . $row['id'] . "\n";
        echo "   ✓ Encrypted data (first 50 chars): " . substr($row['encrypted_data'], 0, 50) . "...\n";
        echo "   ✓ IV (full): " . $row['iv'] . "\n";
        echo "   ✓ Encrypted data length: " . strlen($row['encrypted_data']) . " characters\n";
        echo "   ✓ IV length: " . strlen($row['iv']) . " characters\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Examples Complete ===\n";
echo "All sensitive healthcare data is now encrypted at rest using AES-256-CBC!\n";
?>
