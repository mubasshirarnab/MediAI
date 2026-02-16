<?php
// Test session timeout functionality
require_once 'session_manager.php';

echo "=== Testing Session Timeout ===\n";

// Start session
SessionManager::startSession();
echo "Session started at: " . date('Y-m-d H:i:s') . "\n";
echo "Last activity: " . (isset($_SESSION['last_activity']) ? date('Y-m-d H:i:s', $_SESSION['last_activity']) : 'Not set') . "\n";

// Test if session is expired
echo "Is session expired: " . (SessionManager::isSessionExpired() ? 'Yes' : 'No') . "\n";

// Simulate expired session by setting old activity time
$_SESSION['last_activity'] = time() - 400; // 400 seconds ago (more than 5 minutes)
echo "Simulated old activity time: " . date('Y-m-d H:i:s', $_SESSION['last_activity']) . "\n";
echo "Is session expired now: " . (SessionManager::isSessionExpired() ? 'Yes' : 'No') . "\n";

// Test requireActiveSession
echo "Testing requireActiveSession...\n";
SessionManager::requireActiveSession();
echo "Session is still active\n";

echo "\n=== Test Complete ===\n";
?>
