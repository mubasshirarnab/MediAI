<?php
session_start();
require_once 'dbConnect.php';
require_once 'check_admin_auth.php';

// Check if user is authenticated admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id']) || !isset($input['table'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id = intval($input['id']);
$table = $input['table'];

// Validate table name to prevent SQL injection
$allowedTables = [
    'users', 'patients', 'doctors', 'hospitals', 'admins', 
    'community', 'appointments', 'posts', 'medications', 'feedback'
];

if (!in_array($table, $allowedTables)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid table name']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // Handle different table deletions
    switch ($table) {
        case 'users':
            // Delete from users table and related tables
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'patients':
            // Delete from patients table
            $stmt = $conn->prepare("DELETE FROM patients WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'doctors':
            // Delete from doctors table
            $stmt = $conn->prepare("DELETE FROM doctors WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'hospitals':
            // Delete from hospitals table
            $stmt = $conn->prepare("DELETE FROM hospitals WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'admins':
            // Delete from admins table
            $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'community':
            // Delete from communities table
            $stmt = $conn->prepare("DELETE FROM communities WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'appointments':
            // Delete from appointments table
            $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'posts':
            // Delete from posts table
            $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'medications':
            // Delete from medications table
            $stmt = $conn->prepare("DELETE FROM medications WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
            
        case 'feedback':
            // Delete from feedback table
            $stmt = $conn->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            break;
    }
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($table) . ' record permanently deleted successfully'
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'No record found with the given ID'
        ]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
}

$conn->close();
?>
