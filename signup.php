<?php
session_start();
require_once 'dbConnect.php';

// Include PHPMailer files
require 'mail/PHPMailer.php';
require 'mail/SMTP.php';
require 'mail/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  try {
    // Get form data
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact_no = $_POST['contact_no'];
    $account_type = $_POST['accountType'];

    // Validate password match
    if ($password !== $confirm_password) {
      throw new Exception("Passwords do not match!");
    }

    // Map role values to role_id
    $role_map = array(
      'patient' => 1,
      'doctor' => 2,
      'hospital' => 3,
      'admin' => 4
    );

    if (!isset($role_map[$account_type])) {
      throw new Exception("Invalid account type selected");
    }

    $role_id = $role_map[$account_type];

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate OTP
    $otp = rand(100000, 999999);

    // Start transaction
    $conn->begin_transaction();

    try {
      // Insert into users table with unauthorized status
      $insert_user = "INSERT INTO users (name, email, password, phone, role_id, status, otp) VALUES (?, ?, ?, ?, ?, 'unauthorized', ?)";
      $stmt = $conn->prepare($insert_user);

      if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
      }

      $stmt->bind_param("ssssii", $full_name, $email, $hashed_password, $contact_no, $role_id, $otp);

      if (!$stmt->execute()) {
        throw new Exception("User insertion failed: " . $stmt->error);
      }

      $user_id = $conn->insert_id;

      // Insert into specific role table
      switch ($account_type) {
        case 'patient':
          $dob = $_POST['dob'];
          $gender = $_POST['gender'];
          $address = $_POST['address'];

          if (empty($dob) || empty($gender) || empty($address)) {
            throw new Exception("All patient information is required");
          }

          $insert_patient = "INSERT INTO patients (user_id, gender, date_of_birth, address) VALUES (?, ?, ?, ?)";
          $stmt = $conn->prepare($insert_patient);
          if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
          $stmt->bind_param("isss", $user_id, $gender, $dob, $address);
          break;

        case 'hospital':
          $reg_no = $_POST['reg_no'];
          $hospital_address = $_POST['hospital_address'];

          if (empty($reg_no) || empty($hospital_address)) {
            throw new Exception("All hospital information is required");
          }

          $insert_hospital = "INSERT INTO hospitals (user_id, hospital_name, registration_number, location) VALUES (?, ?, ?, ?)";
          $stmt = $conn->prepare($insert_hospital);
          if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
          $stmt->bind_param("isss", $user_id, $full_name, $reg_no, $hospital_address);
          break;

        case 'admin':
          $admin_role = $_POST['admin_role'];
          $admin_department = $_POST['admin_department'];

          if (empty($admin_role) || empty($admin_department)) {
            throw new Exception("All admin information is required");
          }

          $insert_admin = "INSERT INTO admins (user_id, admin_role, department) VALUES (?, ?, ?)";
          $stmt = $conn->prepare($insert_admin);
          if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
          $stmt->bind_param("iss", $user_id, $admin_role, $admin_department);
          break;
      }

      if (!$stmt->execute()) {
        // If role-specific insertion fails, delete the user and throw exception
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        throw new Exception("Role data insertion failed: " . $stmt->error);
      }

      // Send verification email using PHPMailer
      $mail = new PHPMailer(true);

      try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'mubasshirahmed263@gmail.com';
        // IMPORTANT: Use a Gmail App Password here, not your regular Gmail password.
        // See: https://myaccount.google.com/apppasswords
        $mail->Password = 'xeen nwrp mclu mqcf'; // <-- Replace with your App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('mubasshirahmed263@gmail.com', 'MediAI');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'MediAI - Email Verification';
        $mail->Body = "
                    <h2>Welcome to MediAI!</h2>
                    <p>Your verification code is: <strong>$otp</strong></p>
                    <p>Please enter this code on the verification page to complete your registration.</p>
                    <p>If you didn't create an account, please ignore this email.</p>
                ";

        $mail->send();
      } catch (Exception $e) {
        // If email fails, rollback the transaction
        $conn->rollback();
        throw new Exception("Failed to send verification email: " . $e->getMessage());
      }

      // Store email in session for verification
      $_SESSION['verify_email'] = $email;

      // Commit transaction
      $conn->commit();

      // Redirect to verification page
      header("Location: verify.php");
      exit();
    } catch (Exception $e) {
      // Rollback transaction on error
      $conn->rollback();
      throw $e;
    }
  } catch (Exception $e) {
    $error = $e->getMessage();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MEDIAi Sign-Up</title>
  <link rel="stylesheet" href="css/signup.css" />
  <style>
    /* reset & background */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      height: 100vh;
      background: url("images/loginBG.jpg") no-repeat center/cover;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: sans-serif;
    }

    /* card container */
    .signup-card {
      width: 90%;
      max-width: 800px;
      background: radial-gradient(circle at top,
          rgba(32, 34, 54, 0.8),
          rgba(18, 20, 36, 0.9));
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      color: #fff;
    }

    /* logo */
    .logo {
      width: 150px;
      height: 50px;
      object-fit: contain;
    }

    /* section titles */
    .section-title {
      margin-top: 30px;
      margin-bottom: 15px;
      font-size: 1rem;
      font-weight: 500;
      opacity: 0.8;
    }

    /* form rows for 2- or 3-column layouts */
    .form-row {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 15px;
    }

    .form-row input {
      flex: 1;
      min-width: 200px;
      padding: 12px 16px;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.7);
      border-radius: 50px;
      color: #fff;
      font-size: 0.95rem;
    }

    .form-row input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }

    /* full width override */
    .full-width {
      flex-basis: 100%;
    }

    /* radio button group */
    .radio-group {
      display: flex;
      gap: 30px;
      margin-bottom: 20px;
    }

    .radio-group label {
      font-size: 0.95rem;
      cursor: pointer;
    }

    .radio-group input {
      margin-right: 8px;
    }

    /* submit button */
    .btn {
      display: block;
      width: 200px;
      margin-top: 20px;
      padding: 12px 0;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      background: rgba(255, 255, 255, 0.1);
      border: none;
      border-radius: 50px;
      color: #fff;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.3s;
    }

    .btn:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    /* small login text */
    .small-text {
      margin-top: 20px;

      padding: 12px 0;
      text-align: center;
      font-size: 0.85rem;
      color: rgba(255, 255, 255, 0.7);
    }

    .small-text a {
      color: #ff4f81;
      text-decoration: none;
    }

    .small-text a:hover {
      text-decoration: underline;
    }

    .hidden {
      display: none !important;
    }
  </style>
</head>

<body>
  <div class="signup-card">
    <div
      style="
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-items: center;
        ">
      <img class="logo" src="images/LOGO.png" alt="" />
    </div>
    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <!-- Basic Information -->
      <h2 class="section-title">Basic Information</h2>
      <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>
      <div class="form-row">
        <input type="text" name="full_name" placeholder="Full name" required />
        <input type="email" name="email" placeholder="Email address" required />
      </div>
      <div class="form-row">
        <input type="password" name="password" placeholder="Password" required />
        <input type="tel" name="contact_no" placeholder="Contact no." required />
      </div>
      <div class="form-row">
        <input
          type="password"
          name="confirm_password"
          placeholder="Confirm password"
          required
          class="full-width" />
      </div>

      <!-- Sign-up as -->
      <h2 class="section-title">Sign up as</h2>
      <div class="radio-group">
        <label>
          <input type="radio" name="accountType" value="patient" checked />
          Patient
        </label>
        <label>
          <input type="radio" name="accountType" value="hospital" />
          Hospital
        </label>
        <label>
          <input type="radio" name="accountType" value="admin" />
          Admin
        </label>
      </div>

      <!-- Patient's Information -->
      <div id="patientFields">
        <h2 class="section-title">Patient's Information</h2>
        <div class="form-row">
          <input type="date" name="dob" placeholder="Date of Birth" />
          <input type="text" name="gender" placeholder="Gender" />
          <input type="text" name="address" placeholder="Address" />
        </div>
      </div>

      <!-- Hospital's Information -->
      <div id="hospitalFields" class="hidden">
        <h2 class="section-title">Hospital's Information</h2>
        <div class="form-row">
          <input type="text" name="reg_no" placeholder="Registration no." />
          <input type="text" name="hospital_address" placeholder="Address" />
        </div>
      </div>

      <!-- Admin's Information -->
      <div id="adminFields" class="hidden">
        <h2 class="section-title">Admin's Information</h2>
        <div class="form-row">
          <input type="text" name="admin_role" placeholder="Admin Role" />
          <input type="text" name="admin_department" placeholder="Department" />
        </div>
      </div>

      <!-- Submit -->
      <div class="sub" style="display: flex; justify-content: space-between">
        <button type="submit" class="btn">Sign Up</button>
        <p class="small-text">
          Already have account? <a href="login.php">Login</a>
        </p>
      </div>
    </form>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const sections = {
        patient: document.getElementById("patientFields"),
        hospital: document.getElementById("hospitalFields"),
        admin: document.getElementById("adminFields")
      };

      document
        .querySelectorAll('input[name="accountType"]')
        .forEach((radio) => {
          radio.addEventListener("change", function() {
            // hide all
            Object.values(sections).forEach((sec) =>
              sec.classList.add("hidden")
            );
            // show selected
            sections[this.value].classList.remove("hidden");
          });
        });
    });
  </script>
</body>

</html>