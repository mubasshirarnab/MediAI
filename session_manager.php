<?php
/**
 * Session Management with Timeout
 * Handles session timeout and user activity tracking
 */

class SessionManager {
    private static $timeout_duration = 300; // 5 minutes in seconds
    private static $last_activity_key = 'last_activity';
    
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session has timed out
        if (self::isSessionExpired()) {
            self::destroySession();
            return false;
        }
        
        // Update last activity time
        $_SESSION[self::$last_activity_key] = time();
        return true;
    }
    
    public static function isSessionExpired() {
        if (!isset($_SESSION[self::$last_activity_key])) {
            return false; // New session
        }
        
        $inactive_time = time() - $_SESSION[self::$last_activity_key];
        return $inactive_time >= self::$timeout_duration;
    }
    
    public static function destroySession() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
    }
    
    public static function requireActiveSession() {
        if (!self::startSession()) {
            // Session expired - redirect with timeout message
            header("Location: login.php?timeout=1");
            exit();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }
    }
    
    public static function showTimeoutMessage() {
        if (isset($_GET['timeout']) && $_GET['timeout'] == '1') {
            echo '<div id="sessionTimeoutModal" class="session-timeout-modal show">
                <div class="session-timeout-content">
                    <div class="session-timeout-icon">⏰</div>
                    <h2 class="session-timeout-title">Session Timeout</h2>
                    <p class="session-timeout-message">
                        Your session has expired due to inactivity. For security reasons, you have been automatically logged out.
                    </p>
                    <div class="session-timeout-countdown">
                        <span class="session-timeout-countdown-text">Redirecting to login in</span>
                        <span class="session-timeout-countdown-timer" id="countdown">5</span>
                        <span class="session-timeout-countdown-text">seconds...</span>
                    </div>
                    <div class="session-timeout-buttons">
                        <a href="login.php" class="session-timeout-btn session-timeout-btn-extend">Login Now</a>
                        <a href="index.php" class="session-timeout-btn session-timeout-btn-logout">Go to Home</a>
                    </div>
                </div>
            </div>
            <script>
                let countdown = 5;
                const timerElement = document.getElementById("countdown");
                const interval = setInterval(() => {
                    countdown--;
                    timerElement.textContent = countdown;
                    if (countdown <= 0) {
                        clearInterval(interval);
                        window.location.href = "login.php";
                    }
                }, 1000);
            </script>';
        }
    }
    
    public static function updateActivity() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[self::$last_activity_key] = time();
        }
    }
}
?>
