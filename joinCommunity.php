<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$community_id = $data['community_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$community_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Community ID is required']);
    exit();
}

try {
    // Check if user is already a member
    $stmt = $conn->prepare("SELECT id FROM community_members WHERE user_id = ? AND community_id = ?");
    $stmt->bind_param("ii", $user_id, $community_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Already a member']);
        exit();
    }
    
    // Add user as member
    $stmt = $conn->prepare("INSERT INTO community_members (user_id, community_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $community_id);
    $stmt->execute();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 