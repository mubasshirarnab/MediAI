<?php
require_once 'session_manager.php';
require_once 'dbConnect.php';

// Logout user using session manager
SessionManager::destroySession();

// Redirect to login with logout message
header("Location: login.php?logout=1");
exit();
?>