<?php
require_once 'session_manager.php';
require_once 'dbConnect.php';

header('Content-Type: application/json');

// Extend session by updating activity
if (SessionManager::startSession()) {
    SessionManager::updateActivity();
    echo json_encode(['success' => true, 'message' => 'Session extended']);
} else {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
}
?>
