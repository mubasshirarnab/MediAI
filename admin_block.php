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

if (!$input || !isset($input['id']) || !isset($input['table']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$id = intval($input['id']);
$table = $input['table'];
$action = $input['action'];

// Validate action
if (!in_array($action, ['block', 'unblock'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

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
    
    $newStatus = ($action === 'block') ? 'blocked' : 'authorized';
    
    // Handle different table blocking/unblocking
    switch ($table) {
        case 'users':
        case 'patients':
        case 'doctors':
        case 'hospitals':
        case 'admins':
            // Update users table status
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            break;
            
        case 'community':
            // For communities, we can add a status field or use description
            // Assuming we'll add a status field to communities table
            $stmt = $conn->prepare("UPDATE communities SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            break;
            
        case 'appointments':
            // For appointments, we can add a status field
            $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            break;
            
        case 'posts':
            // For posts, we can add a status field
            $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            break;
            
        case 'medications':
            // For medications, we can add a status field
            $stmt = $conn->prepare("UPDATE medications SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            break;
            
        case 'feedback':
            // For feedback, we can add a status field
            $stmt = $conn->prepare("UPDATE feedback SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            break;
    }
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        $actionText = ($action === 'block') ? 'blocked' : 'unblocked';
        echo json_encode([
            'success' => true, 
            'message' => ucfirst($table) . ' record ' . $actionText . ' successfully'
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'success' => false, 
            'message' => 'No record found with the given ID or no changes made'
        ]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Block/Unblock error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred'
    ]);
}

$conn->close();
?>
