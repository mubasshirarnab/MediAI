<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['post_id'] ?? null;

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'No post ID']);
    exit;
}

// Check if already liked
$stmt = $conn->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Unlike
    $conn->query("DELETE FROM post_likes WHERE post_id = $post_id AND user_id = $user_id");
} else {
    // Like
    $stmt = $conn->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $post_id, $user_id);
    $stmt->execute();
}

// Get new like count
$stmt = $conn->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($like_count);
$stmt->fetch();

echo json_encode(['success' => true, 'likes' => $like_count]);
?> 