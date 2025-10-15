<?php
session_start();
require_once '../dbConnect.php';

header('Content-Type: application/json');

// Check if user is logged in and has hospital privileges
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'hospital' && $_SESSION['user_role'] !== 'admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get hospital information
$user_id = $_SESSION['user_id'];
$query = "SELECT h.user_id as hospital_id FROM hospitals h WHERE h.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$hospital_data = $result->fetch_assoc();

if (!$hospital_data) {
    echo json_encode(['success' => false, 'message' => 'Hospital not found']);
    exit();
}

$hospital_id = $hospital_data['hospital_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_settings':
            getSettings($conn, $hospital_id);
            break;
        case 'save_settings':
            saveSettings($conn, $hospital_id);
            break;
        case 'upload_logo':
            uploadLogo($hospital_id);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function getSettings($conn, $hospital_id) {
    $settings = [];
    
    // Get all settings for this hospital
    $stmt = $conn->prepare("SELECT setting_category, setting_data FROM hospital_settings WHERE hospital_id = ?");
    $stmt->bind_param("i", $hospital_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_category']] = json_decode($row['setting_data'], true);
    }
    
    echo json_encode(['success' => true, 'settings' => $settings]);
}

function saveSettings($conn, $hospital_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $section = $input['section'] ?? '';
    $data = $input['data'] ?? [];
    
    if (empty($section) || empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }
    
    // Save settings to database
    $json_data = json_encode($data);
    $stmt = $conn->prepare("
        INSERT INTO hospital_settings (hospital_id, setting_category, setting_data) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE setting_data = VALUES(setting_data), updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->bind_param("iss", $hospital_id, $section, $json_data);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
    }
}

function uploadLogo($hospital_id) {
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        return;
    }
    
    $upload_dir = '../uploads/logos/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
    
    if (!in_array(strtolower($file_extension), $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        return;
    }
    
    $filename = 'hospital_' . $hospital_id . '_logo.' . $file_extension;
    $file_path = $upload_dir . $filename;
    
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $file_path)) {
        echo json_encode(['success' => true, 'message' => 'Logo uploaded successfully', 'logo_url' => 'uploads/logos/' . $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload logo']);
    }
}
?>