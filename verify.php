<?php
session_start();
require_once 'dbConnect.php';

// Check if user is coming from signup
if (!isset($_SESSION['verify_email'])) {
    header('Location: signup.php');
    exit();
}

$email = $_SESSION['verify_email'];
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    
    // Verify OTP
    $verify_query = "SELECT * FROM users WHERE email = ? AND otp = ?";
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update status to authorized
        $update_query = "UPDATE users SET status = 'authorized' WHERE email = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        // Clear session and redirect to login
        unset($_SESSION['verify_email']);
        header('Location: login.php?verified=1');
        exit();
    } else {
        $error = "Invalid verification code!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - MediAI</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .verify-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .verify-header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .verify-header p {
            color: #7f8c8d;
            font-size: 16px;
        }

        .otp-input {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .otp-input input {
            width: 100px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 2px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        .otp-input input:focus {
            border-color: #3498db;
            outline: none;
        }

        .verify-button {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .verify-button:hover {
            background: #2980b9;
        }

        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 20px;
        }

        .resend-link {
            text-align: center;
            margin-top: 20px;
        }

        .resend-link a {
            color: #3498db;
            text-decoration: none;
        }

        .resend-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <h1>Verify Your Email</h1>
            <p>We've sent a verification code to <?php echo htmlspecialchars($email); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="otp-input">
                <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" required 
                       placeholder="000000" autocomplete="off">
            </div>
            <button type="submit" class="verify-button">Verify Email</button>
        </form>

        <div class="resend-link">
            <a href="resend_verification.php">Didn't receive the code? Resend</a>
        </div>
    </div>

    <script>
        // Auto-focus the OTP input
        document.querySelector('input[name="otp"]').focus();

        // Auto-move to next input when a digit is entered
        document.querySelector('input[name="otp"]').addEventListener('input', function(e) {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html> 