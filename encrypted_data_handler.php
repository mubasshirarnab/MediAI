<?php
/**
 * Database Encryption Integration
 * Handles encryption for medical reports, meeting codes, and patient notes
 */

require_once 'encryption_helper.php';
require_once 'dbConnect.php';

class EncryptedDataHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Store encrypted medical report
     * @param int $patient_id - Patient ID
     * @param string $report_data - Medical report content
     * @param string $report_type - Type of report (lab, xray, etc.)
     * @return int - Report ID
     */
    public function storeMedicalReport($patient_id, $report_data, $report_type = 'general') {
        $encrypted = EncryptionHelper::encryptData($report_data);
        
        $stmt = $this->conn->prepare("INSERT INTO medical_reports (patient_id, report_data, report_type, encrypted_data, iv, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("issss", $patient_id, $report_data, $report_type, $encrypted['encrypted'], $encrypted['iv']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to store medical report: " . $stmt->error);
        }
        
        return $stmt->insert_id;
    }
    
    /**
     * Retrieve and decrypt medical report
     * @param int $report_id - Report ID
     * @param int $patient_id - Patient ID (for security check)
     * @return string - Decrypted report data
     */
    public function getMedicalReport($report_id, $patient_id) {
        $stmt = $this->conn->prepare("SELECT encrypted_data, iv FROM medical_reports WHERE id = ? AND patient_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("ii", $report_id, $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Report not found or access denied");
        }
        
        $row = $result->fetch_assoc();
        return EncryptionHelper::decryptData($row['encrypted_data'], $row['iv']);
    }
    
    /**
     * Store encrypted meeting code
     * @param int $doctor_id - Doctor ID
     * @param int $patient_id - Patient ID
     * @param string $meeting_code - Meeting code
     * @param string $meeting_time - Meeting time
     * @return int - Meeting record ID
     */
    public function storeMeetingCode($doctor_id, $patient_id, $meeting_code, $meeting_time) {
        $encrypted = EncryptionHelper::encryptData($meeting_code);
        
        $stmt = $this->conn->prepare("INSERT INTO meeting_codes (doctor_id, patient_id, meeting_code, encrypted_code, iv, meeting_time, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("iissss", $doctor_id, $patient_id, $meeting_code, $encrypted['encrypted'], $encrypted['iv'], $meeting_time);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to store meeting code: " . $stmt->error);
        }
        
        return $stmt->insert_id;
    }
    
    /**
     * Retrieve and decrypt meeting code
     * @param int $meeting_id - Meeting ID
     * @param int $user_id - User ID (doctor or patient)
     * @param string $user_role - User role ('doctor' or 'patient')
     * @return string - Decrypted meeting code
     */
    public function getMeetingCode($meeting_id, $user_id, $user_role) {
        $where_clause = ($user_role === 'doctor') ? "doctor_id = ?" : "patient_id = ?";
        
        $stmt = $this->conn->prepare("SELECT encrypted_code, iv FROM meeting_codes WHERE id = ? AND $where_clause");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("ii", $meeting_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Meeting code not found or access denied");
        }
        
        $row = $result->fetch_assoc();
        return EncryptionHelper::decryptData($row['encrypted_code'], $row['iv']);
    }
    
    /**
     * Store encrypted patient notes
     * @param int $patient_id - Patient ID
     * @param int $doctor_id - Doctor ID
     * @param string $notes - Confidential patient notes
     * @return int - Note ID
     */
    public function storePatientNotes($patient_id, $doctor_id, $notes) {
        $encrypted = EncryptionHelper::encryptData($notes);
        
        $stmt = $this->conn->prepare("INSERT INTO patient_notes (patient_id, doctor_id, notes, encrypted_notes, iv, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("iisss", $patient_id, $doctor_id, $notes, $encrypted['encrypted'], $encrypted['iv']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to store patient notes: " . $stmt->error);
        }
        
        return $stmt->insert_id;
    }
    
    /**
     * Retrieve and decrypt patient notes
     * @param int $note_id - Note ID
     * @param int $doctor_id - Doctor ID (for security check)
     * @return string - Decrypted notes
     */
    public function getPatientNotes($note_id, $doctor_id) {
        $stmt = $this->conn->prepare("SELECT encrypted_notes, iv FROM patient_notes WHERE id = ? AND doctor_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        
        $stmt->bind_param("ii", $note_id, $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Notes not found or access denied");
        }
        
        $row = $result->fetch_assoc();
        return EncryptionHelper::decryptData($row['encrypted_notes'], $row['iv']);
    }
    
    /**
     * Create database tables for encrypted data if they don't exist
     */
    public function createEncryptedTables() {
        // Medical reports table
        $this->conn->query("CREATE TABLE IF NOT EXISTS medical_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            report_data TEXT,
            report_type VARCHAR(50) DEFAULT 'general',
            encrypted_data TEXT NOT NULL,
            iv VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_patient (patient_id),
            INDEX idx_type (report_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Meeting codes table
        $this->conn->query("CREATE TABLE IF NOT EXISTS meeting_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            doctor_id INT NOT NULL,
            patient_id INT NOT NULL,
            meeting_code VARCHAR(255),
            encrypted_code TEXT NOT NULL,
            iv VARCHAR(255) NOT NULL,
            meeting_time DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_doctor (doctor_id),
            INDEX idx_patient (patient_id),
            INDEX idx_time (meeting_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        
        // Patient notes table
        $this->conn->query("CREATE TABLE IF NOT EXISTS patient_notes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            doctor_id INT NOT NULL,
            notes TEXT,
            encrypted_notes TEXT NOT NULL,
            iv VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_patient (patient_id),
            INDEX idx_doctor (doctor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }
}
?>
