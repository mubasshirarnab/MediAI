<?php
// Include session timeout styles and JavaScript
function includeSessionTimeoutUI() {
    echo '<link rel="stylesheet" href="session_timeout_styles.css">';
    
    // Add warning modal for approaching timeout
    echo '<script>
    let warningShown = false;
    let timeoutWarning;
    
    function checkSessionTimeout() {
        fetch("check_session_status.php")
            .then(response => response.json())
            .then(data => {
                if (data.status === "warning" && !warningShown) {
                    showSessionWarning();
                    warningShown = true;
                } else if (data.status === "expired") {
                    showSessionTimeout();
                }
            })
            .catch(error => console.log("Session check error:", error));
    }
    
    function showSessionWarning() {
        const modal = document.createElement("div");
        modal.className = "session-timeout-modal show";
        modal.innerHTML = `
            <div class="session-timeout-content">
                <div class="session-timeout-icon">⚠️</div>
                <h2 class="session-timeout-title">Session Expiring Soon</h2>
                <p class="session-timeout-message">
                    Your session will expire in 2 minutes due to inactivity. Would you like to extend your session?
                </p>
                <div class="session-timeout-countdown">
                    <span class="session-timeout-countdown-text">Time remaining:</span>
                    <span class="session-timeout-countdown-timer" id="warningCountdown">120</span>
                    <span class="session-timeout-countdown-text">seconds</span>
                </div>
                <div class="session-timeout-buttons">
                    <button class="session-timeout-btn session-timeout-btn-extend" onclick="extendSession()">Extend Session</button>
                    <button class="session-timeout-btn session-timeout-btn-logout" onclick="logoutNow()">Logout Now</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        let countdown = 120;
        const timerElement = document.getElementById("warningCountdown");
        timeoutWarning = setInterval(() => {
            countdown--;
            timerElement.textContent = countdown;
            if (countdown <= 0) {
                clearInterval(timeoutWarning);
                showSessionTimeout();
            }
        }, 1000);
    }
    
    function extendSession() {
        fetch("extend_session.php")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove warning modal
                    const modal = document.querySelector(".session-timeout-modal");
                    if (modal) modal.remove();
                    warningShown = false;
                    clearInterval(timeoutWarning);
                    
                    // Show success notification
                    showNotification("Session extended successfully!", "success");
                }
            });
    }
    
    function logoutNow() {
        window.location.href = "logout.php";
    }
    
    function showSessionTimeout() {
        window.location.href = "login.php?timeout=1";
    }
    
    function showNotification(message, type) {
        const notification = document.createElement("div");
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === "success" ? "#6bcf7f" : "#ff6b6b"};
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = "slideOutRight 0.3s ease-out";
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Check session status every 30 seconds
    setInterval(checkSessionTimeout, 30000);
    
    // Add CSS animations
    const style = document.createElement("style");
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    </script>';
}
?>
