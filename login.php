<?php
session_start();
require_once 'dbConnect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  // Prepare and execute query
  $query = "SELECT u.*, r.role_name FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.email = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {
      // Set session variables
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['role'] = $user['role_name'];
      $_SESSION['full_name'] = $user['name'];

      // Redirect based on role_id
      switch ($user['role_id']) {
        case 1: // patient
          header("Location: index.php");
          break;
        case 2: // doctor
          header("Location: index.php");
          break;
        case 3: // hospital
          header("Location: index.php");
          break;
        case 4: // admin
          header("Location: http://localhost:3001");
          break;
        default:
          header("Location: index.php");
      }
      exit();
    } else {
      $error = "Invalid password!";
    }
  } else {
    $error = "Email not found!";
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css\login.css" />
  <title>MEDIAi Login</title>
  <style>
    body {
      background-image: url(images/loginBG.jpg);
      background-size: cover;
      margin: 0;
      padding: 0;
      min-height: 100vh;
    }

    /* Login Page Redesign */
    .login-bg {
      min-height: 100vh;
      width: 100vw;
      background: radial-gradient(ellipse at center, #2a1a3a 0%, #090926 100%);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-card {
      background: linear-gradient(180deg, #23234a 0%, #2d234a 60%, #3a2e5a 100%);
      border-radius: 24px;
      box-shadow: 0 8px 40px 0 #1a093a99, 0 1.5px 8px 0 #00000033;
      padding: 44px 48px 36px 48px;
      min-width: 350px;
      max-width: 370px;
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
    }

    .login-logo-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 18px;
    }

    .login-logo-img {
      height: 38px;
      width: auto;
      filter: brightness(0) invert(1);
    }



    .login-form {
      width: 100%;
      margin-top: 18px;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .input-group {
      display: flex;
      align-items: center;
      background: transparent;
      border-bottom: 1.5px solid #bcbcbc55;
      padding: 0 0 2px 0;
      margin-bottom: 8px;
    }

    .input-icon {
      color: #fff;
      font-size: 1.1rem;
      margin-right: 10px;
      opacity: 0.85;
    }

    .input-group input {
      background: transparent;
      border: none;
      outline: none;
      color: #fff;
      font-size: 1.08rem;
      font-family: "Montserrat", Arial, sans-serif;
      padding: 10px 0;
      flex: 1 1 auto;
    }

    .input-group input::placeholder {
      color: #bcbcbc;
      opacity: 0.8;
    }

    .login-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin: 0 0 8px 0;
      width: 100%;
    }

    .remember-me {
      color: #bcbcbc;
      font-size: 0.98rem;
      font-family: "Montserrat", Arial, sans-serif;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .forgot-link {
      color: #bcbcbc;
      font-size: 0.98rem;
      font-style: italic;
      text-decoration: none;
      opacity: 0.7;
      transition: color 0.2s;
    }

    .forgot-link:hover {
      color: #fff;
      opacity: 1;
    }

    .login-btn {
      width: 100%;
      background: linear-gradient(90deg, #6a6aff 0%, #a18fff 100%);
      color: #fff;
      font-size: 1.18rem;
      font-family: "Montserrat", Arial, sans-serif;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      padding: 12px 0;
      margin-top: 18px;
      letter-spacing: 0.12em;
      box-shadow: 0 2px 12px 0 #6a6aff33;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
    }

    .login-btn:hover {
      background: linear-gradient(90deg, #4a4aff 0%, #6a6aff 100%);
      box-shadow: 0 4px 18px 0 #6a6aff55;
    }

    .signup-link {
      color: #fff;
      font-size: 1.01rem;
      text-align: center;
      margin-top: 18px;
    }

    .signup-link a {
      color: #e16fff;
      text-decoration: none;
      font-weight: 500;
      margin-left: 2px;
      transition: color 0.2s;
    }

    .signup-link a:hover {
      color: #fff;
    }

    .success-message,
    .error-message {
      width: 100%;
      text-align: center;
      margin-bottom: 10px;
      font-size: 1rem;
      border-radius: 6px;
      padding: 8px 0;
    }

    .success-message {
      background: #2ecc40;
      color: #fff;
    }

    .error-message {
      background: #e74c3c;
      color: #fff;
    }
  </style>
</head>

<body>
  <div class="login-bg">
    <div class="login-card">
      <div class="login-logo-row">
        <img class="login-logo-img" src="images/LOGO.png" alt="MEDIAi logo" />

      </div>
      <?php if (isset($_GET['signup']) && $_GET['signup'] == 'success'): ?>
        <div class="success-message">Signup successful! Please login.</div>
      <?php endif; ?>
      <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="login-form">
        <div class="input-group">
          <span class="input-icon"><i class="fas fa-envelope"></i></span>
          <input type="email" name="email" placeholder="Email Id" required />
        </div>
        <div class="input-group">
          <span class="input-icon"><i class="fas fa-lock"></i></span>
          <input type="password" name="password" placeholder="Password" required />
        </div>
        <div class="login-options-row">
          <label class="remember-me"><input type="checkbox" name="remember" /> Remember me</label>
          <a href="#" class="forgot-link">forgot password?</a>
        </div>
        <button type="submit" class="login-btn">LOGIN</button>
      </form>
      <div class="signup-link">
        <span>Don't have account? <a href="signup.php">Sign up</a></span>
      </div>
    </div>
  </div>
</body>

</html>