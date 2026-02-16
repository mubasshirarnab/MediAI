<?php
require_once 'session_manager.php';
require_once 'dbConnect.php';

header('Content-Type: application/json');

// Start session and check status
SessionManager::startSession();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'expired']);
    exit();
}

$lastActivity = $_SESSION['last_activity'] ?? time();
$inactiveTime = time() - $lastActivity;
$timeoutDuration = 300; // 5 minutes
$warningTime = 180; // 3 minutes (show warning at 2 minutes remaining)

if ($inactiveTime >= $timeoutDuration) {
    echo json_encode(['status' => 'expired']);
} elseif ($inactiveTime >= $warningTime) {
    $remainingTime = $timeoutDuration - $inactiveTime;
    echo json_encode(['status' => 'warning', 'remaining' => $remainingTime]);
} else {
    echo json_encode(['status' => 'active']);
}
?>
