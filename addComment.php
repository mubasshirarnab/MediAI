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
$comment = trim($data['comment'] ?? '');

if (!$post_id || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO comments (post_id, commentor, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $post_id, $user_id, $comment);
$stmt->execute();

// Get username
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();

// Get new comment count
$stmt = $conn->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$stmt->bind_result($comment_count);
$stmt->fetch();

echo json_encode(['success' => true, 'username' => $username, 'comments' => $comment_count]);
?> 