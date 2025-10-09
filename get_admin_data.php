<?php
session_start();
require_once 'dbConnect.php';

// Check if user is authenticated (optional - remove if not needed)
// if (!isset($_SESSION['user_id']) && !isset($_SESSION['role_id']) && $_SESSION['role_id'] != 4) {
//     http_response_code(401);
//     echo json_encode(array('error' => 'Unauthorized access'));
//     exit();
// }

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3001');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check if user is authenticated as admin (temporarily disabled for development)
// if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
//     http_response_code(401);
//     echo json_encode(array('error' => 'Unauthorized access'));
//     exit();
// }

// Verify admin role (temporarily disabled for development)
// $query = "SELECT role_id FROM users WHERE id = ?";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("i", $_SESSION['user_id']);
// $stmt->execute();
// $result = $stmt->get_result();
// $user = $result->fetch_assoc();

// if (!$user || $user['role_id'] != 4) {
//     http_response_code(403);
//     echo json_encode(array('error' => 'Admin access required'));
//     exit();
// }

try {
    $data = array();
    
    // Fetch all users
    $users_query = "SELECT * FROM users ORDER BY created_at DESC";
    $users_result = $conn->query($users_query);
    $data['users'] = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all patients
    $patients_query = "SELECT p.*, u.name as user_name, u.email FROM patients p 
                      LEFT JOIN users u ON p.user_id = u.id 
                      ORDER BY p.user_id DESC";
    $patients_result = $conn->query($patients_query);
    $data['patients'] = $patients_result ? $patients_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all doctors
    $doctors_query = "SELECT d.*, u.name as user_name, u.email FROM doctors d 
                     LEFT JOIN users u ON d.user_id = u.id 
                     ORDER BY d.user_id DESC";
    $doctors_result = $conn->query($doctors_query);
    $data['doctors'] = $doctors_result ? $doctors_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all hospitals
    $hospitals_query = "SELECT h.*, u.name as user_name, u.email FROM hospitals h 
                       LEFT JOIN users u ON h.user_id = u.id 
                       ORDER BY h.user_id DESC";
    $hospitals_result = $conn->query($hospitals_query);
    $data['hospitals'] = $hospitals_result ? $hospitals_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all admins
    $admins_query = "SELECT a.*, u.name as user_name, u.email FROM admins a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    ORDER BY a.user_id DESC";
    $admins_result = $conn->query($admins_query);
    $data['admins'] = $admins_result ? $admins_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all appointments
    $appointments_query = "SELECT ap.*, 
                          pu.name as patient_name, 
                          du.name as doctor_name 
                          FROM appointments ap 
                          LEFT JOIN users pu ON ap.patient_id = pu.id 
                          LEFT JOIN users du ON ap.doctor_id = du.id 
                          ORDER BY ap.id DESC";
    $appointments_result = $conn->query($appointments_query);
    $data['appointments'] = $appointments_result ? $appointments_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all posts
    $posts_query = "SELECT p.*, u.name as creator_name FROM posts p 
                   LEFT JOIN users u ON p.post_creator = u.id 
                   ORDER BY p.created_at DESC";
    $posts_result = $conn->query($posts_query);
    $data['posts'] = $posts_result ? $posts_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all medications
    $medications_query = "SELECT m.*, u.name as user_name FROM medications m 
                         LEFT JOIN users u ON m.user_id = u.id 
                         ORDER BY m.id DESC";
    $medications_result = $conn->query($medications_query);
    $data['medications'] = $medications_result ? $medications_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all feedback
    $feedback_query = "SELECT f.*, 
                      pu.name as patient_name, 
                      du.name as doctor_name 
                      FROM feedback f 
                      LEFT JOIN users pu ON f.patient_id = pu.id 
                      LEFT JOIN users du ON f.doctor_id = du.id 
                      ORDER BY f.submitted_at DESC";
    $feedback_result = $conn->query($feedback_query);
    $data['feedback'] = $feedback_result ? $feedback_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all AI conversations
    $ai_conversations_query = "SELECT ac.*, u.name as user_name FROM ai_conversations ac 
                              LEFT JOIN users u ON ac.user_id = u.id 
                              ORDER BY ac.created_at DESC";
    $ai_conversations_result = $conn->query($ai_conversations_query);
    $data['ai_conversations'] = $ai_conversations_result ? $ai_conversations_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all community data
    $community_query = "SELECT c.*, u.name as creator_name FROM community c 
                      LEFT JOIN users u ON c.community_creator = u.id 
                      ORDER BY c.id DESC";
    $community_result = $conn->query($community_query);
    $data['community'] = $community_result ? $community_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all roles
    $roles_query = "SELECT * FROM roles ORDER BY id ASC";
    $roles_result = $conn->query($roles_query);
    $data['roles'] = $roles_result ? $roles_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all inventory items
    $inventory_query = "SELECT ii.*, u.name as hospital_name FROM inventory_items ii 
                       LEFT JOIN users u ON ii.hospital_id = u.id 
                       ORDER BY ii.id DESC";
    $inventory_result = $conn->query($inventory_query);
    $data['inventory_items'] = $inventory_result ? $inventory_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Fetch all disease predictions
    $disease_query = "SELECT dp.*, u.name as patient_name FROM disease_predictions dp 
                      LEFT JOIN users u ON dp.patient_id = u.id 
                      ORDER BY dp.created_at DESC";
    $disease_result = $conn->query($disease_query);
    $data['disease_predictions'] = $disease_result ? $disease_result->fetch_all(MYSQLI_ASSOC) : array();
    
    // Add database statistics
    $stats = array(
        'total_users' => count($data['users']),
        'total_patients' => count($data['patients']),
        'total_doctors' => count($data['doctors']),
        'total_hospitals' => count($data['hospitals']),
        'total_admins' => count($data['admins']),
        'total_appointments' => count($data['appointments']),
        'total_posts' => count($data['posts']),
        'total_medications' => count($data['medications']),
        'total_feedback' => count($data['feedback']),
        'total_ai_conversations' => count($data['ai_conversations']),
        'total_communities' => count($data['community']),
        'total_inventory_items' => count($data['inventory_items']),
        'total_disease_predictions' => count($data['disease_predictions'])
    );
    $data['stats'] = $stats;
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'error' => 'Database error: ' . $e->getMessage(),
        'details' => error_get_last()
    ));
}

$conn->close();
?>
