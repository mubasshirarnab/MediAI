<?php
session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3001');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is logged in and has admin role
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        echo json_encode([
            'authenticated' => false,
            'message' => 'User not logged in'
        ]);
        exit();
    }

    // Get user information with role details
    $user_id = $_SESSION['user_id'];
    $query = "SELECT u.*, r.role_name FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check if user has admin role (role_id = 4)
        if ($user['role_id'] == 4 && $user['status'] == 'authorized') {
            echo json_encode([
                'authenticated' => true,
                'role_id' => $user['role_id'],
                'role_name' => $user['role_name'],
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'status' => $user['status']
                ],
                'message' => 'Admin access granted'
            ]);
        } else {
            echo json_encode([
                'authenticated' => false,
                'message' => 'Access denied. Admin role required.'
            ]);
        }
    } else {
        echo json_encode([
            'authenticated' => false,
            'message' => 'User not found'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'authenticated' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
