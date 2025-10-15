<?php
  session_start();
  require_once 'dbConnect.php';
  
  // Set timezone to Bangladesh time
  date_default_timezone_set('Asia/Dhaka');

  if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
  }

  $user_id = $_SESSION['user_id'];

  // Verify database connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Fetch user's name
  $user_name = "User";
  $user_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
  $user_stmt->bind_param("i", $user_id);
  $user_stmt->execute();
  $user_result = $user_stmt->get_result();
  if ($user_row = $user_result->fetch_assoc()) {
    $user_name = $user_row['name'];
  }
  $user_stmt->close();

  // Handle form submission
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_medicine'])) {
    $medicine_name = trim($_POST['medicine_name']);
    $meal_time = $_POST['meal_time'];
    $begin_date = $_POST['begin_date'];
    $end_date = $_POST['end_date'];
    $dose_times = isset($_POST['dose_times']) ? $_POST['dose_times'] : [];

    // Debug: Check what we received
    error_log("Medicine Name: " . $medicine_name);
    error_log("Meal Time: " . $meal_time);
    error_log("Begin Date: " . $begin_date);
    error_log("End Date: " . $end_date);
    error_log("Dose Times: " . print_r($dose_times, true));

    if (!empty($medicine_name) && !empty($dose_times)) {
      // Start transaction
      $conn->begin_transaction();
      
      try {
        // Insert into medication table
        $stmt = $conn->prepare("INSERT INTO medication (user_id, medicine_name, meal_time, begin_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $medicine_name, $meal_time, $begin_date, $end_date);
        
        if ($stmt->execute()) {
          $medication_id = $conn->insert_id;
          error_log("Medication ID: " . $medication_id);
          
          // Insert dose times
          $time_stmt = $conn->prepare("INSERT INTO medication_times (medication_id, dose_time) VALUES (?, ?)");
          
          foreach ($dose_times as $time) {
            if (!empty($time)) {
              error_log("Inserting time: " . $time);
              $time_stmt->bind_param("is", $medication_id, $time);
              if (!$time_stmt->execute()) {
                throw new Exception("Error inserting time: " . $time_stmt->error);
              }
            }
          }
          
          $time_stmt->close();
          $stmt->close();
          
          // Commit transaction
          $conn->commit();
          $success_message = "Medicine added successfully!";
          error_log("Medicine added successfully with ID: " . $medication_id);
        } else {
          throw new Exception("Error adding medicine: " . $stmt->error);
        }
      } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
        error_log("Error in medication insertion: " . $e->getMessage());
      }
    } else {
      $error_message = "Please fill in all required fields including at least one time.";
      error_log("Form validation failed - missing required fields");
    }
  }

  // Fetch user's medications with their times
  $medications = [];
  $stmt = $conn->prepare("
    SELECT m.id, m.medicine_name, m.meal_time, m.begin_date, m.end_date, 
           GROUP_CONCAT(mt.dose_time ORDER BY mt.dose_time SEPARATOR ',') as dose_times
    FROM medication m 
    LEFT JOIN medication_times mt ON m.id = mt.medication_id 
    WHERE m.user_id = ? 
    GROUP BY m.id 
    ORDER BY m.created_at DESC
  ");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  while ($row = $result->fetch_assoc()) {
    $medications[] = $row;
    error_log("Fetched medication: " . $row['medicine_name'] . " with times: " . $row['dose_times']);
  }
  $stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Medicine Planner</title>
  <link rel="stylesheet" href="css/mediRem.css">
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Extend the original input styling to include date and time inputs */
    .medicine-form input[type="text"],
    .medicine-form input[type="date"],
    .medicine-form input[type="time"] {
      background: rgba(255, 255, 255, 0.05);
      border: 2px solid rgba(255, 255, 255, 0.2);
      border-radius: 16px;
      padding: 16px 20px;
      color: #fff;
      font-size: 1rem;
      outline: none;
      width: 100%;
      box-sizing: border-box;
      transition: all 0.3s ease;
      backdrop-filter: blur(10px);
      font-family: 'Montserrat', sans-serif;
    }
    
    .medicine-form input[type="text"]:focus,
    .medicine-form input[type="date"]:focus,
    .medicine-form input[type="time"]:focus {
      border-color: #a084ee;
      background: rgba(160, 132, 238, 0.1);
      box-shadow: 0 0 20px rgba(160, 132, 238, 0.3);
      transform: translateY(-2px);
    }
    
    .medicine-form input[type="text"]::placeholder,
    .medicine-form input[type="date"]::placeholder,
    .medicine-form input[type="time"]::placeholder {
      color: rgba(255, 255, 255, 0.6);
      font-weight: 400;
    }
    
    /* Style for date and time input placeholders */
    .medicine-form input[type="date"]::-webkit-calendar-picker-indicator,
    .medicine-form input[type="time"]::-webkit-calendar-picker-indicator {
      filter: invert(1) brightness(0.8);
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .medicine-form input[type="date"]::-webkit-calendar-picker-indicator:hover,
    .medicine-form input[type="time"]::-webkit-calendar-picker-indicator:hover {
      filter: invert(1) brightness(1);
      transform: scale(1.1);
    }
    
    .time-input-group {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-bottom: 12px;
      padding: 8px;
      background: rgba(255, 255, 255, 0.02);
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      transition: all 0.3s ease;
    }
    
    .time-input-group:hover {
      background: rgba(160, 132, 238, 0.05);
      border-color: rgba(160, 132, 238, 0.2);
    }
    
    .time-input-group input[type="time"] {
      flex: 1;
      margin: 0;
    }
    
    .remove-time-btn {
      background: linear-gradient(135deg, #ff4757, #ff3742);
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      cursor: pointer;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
    }
    
    .remove-time-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 16px rgba(255, 71, 87, 0.4);
    }
    
    .add-time-btn {
      background: linear-gradient(135deg, #a084ee, #7a5de4);
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      cursor: pointer;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(160, 132, 238, 0.3);
    }
    
    .add-time-btn:hover {
      transform: scale(1.1);
      box-shadow: 0 6px 16px rgba(160, 132, 238, 0.4);
    }
    
    .save-btn {
      background: linear-gradient(135deg, #2ecc40, #27ae60);
      color: #fff;
      border: none;
      border-radius: 16px;
      padding: 16px 0;
      width: 100%;
      font-size: 1.1rem;
      font-weight: 600;
      margin-top: 20px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-family: 'Montserrat', sans-serif;
      letter-spacing: 0.5px;
      box-shadow: 0 6px 20px rgba(46, 204, 64, 0.3);
      position: relative;
      overflow: hidden;
    }
    
    .save-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(46, 204, 64, 0.4);
    }
    
    .save-btn:active {
      transform: translateY(0);
    }
    
    .save-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }
    
    .save-btn:hover::before {
      left: 100%;
    }
    
    .message {
      padding: 16px;
      border-radius: 12px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 500;
      backdrop-filter: blur(10px);
      border: 1px solid;
      animation: slideInDown 0.4s ease;
    }
    
    @keyframes slideInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .success {
      background: rgba(46, 204, 64, 0.15);
      border-color: rgba(46, 204, 64, 0.3);
      color: #2ecc40;
    }
    
    .error {
      background: rgba(255, 71, 87, 0.15);
      border-color: rgba(255, 71, 87, 0.3);
      color: #ff4757;
    }
    
    /* Form container enhancement */
    .medicine-form {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 16px;
      padding: 20px;
      background: rgba(255, 255, 255, 0.02);
      border-radius: 20px;
      border: 1px solid rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
    }
    
    .dose-icon {
      width: 50px;
      height: 50px;
      background: #a084ee;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
    }
    
    .dose-icon i {
      color: #fff;
      font-size: 20px;
    }
    
    /* Prevent day buttons from changing on hover/focus/click */
    .day:hover,
    .day:focus,
    .day:active {
      background: transparent !important;
      border: 2px solid #a084ee !important;
      color: #a084ee !important;
    }
    
    .day.active:hover,
    .day.active:focus,
    .day.active:active {
      background: #a084ee !important;
      border: 2px solid #a084ee !important;
      color: #fff !important;
    }
    
    /* Empty state styling */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      margin: 40px auto;
      max-width: 400px;
      background: rgba(160, 132, 238, 0.05);
      border: 2px dashed rgba(160, 132, 238, 0.3);
      border-radius: 24px;
      position: relative;
      animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .empty-icon {
      width: 80px;
      height: 80px;
      background: linear-gradient(135deg, #a084ee, #7a5de4);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      box-shadow: 0 8px 24px rgba(160, 132, 238, 0.3);
    }
    
    .empty-icon i {
      font-size: 32px;
      color: #fff;
    }
    
    .empty-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: #fff;
      margin: 0 0 12px 0;
      letter-spacing: 0.5px;
    }
    
    .empty-description {
      font-size: 1rem;
      color: #bdbdbd;
      line-height: 1.6;
      margin: 0 0 32px 0;
      max-width: 280px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .empty-arrow {
      position: absolute;
      left: -60px;
      top: 50%;
      transform: translateY(-50%);
      width: 40px;
      height: 40px;
      background: rgba(160, 132, 238, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: pulse 2s infinite;
    }
    
    .empty-arrow i {
      color: #a084ee;
      font-size: 16px;
    }
    
    @keyframes pulse {
      0% {
        transform: translateY(-50%) scale(1);
        opacity: 1;
      }
      50% {
        transform: translateY(-50%) scale(1.1);
        opacity: 0.7;
      }
      100% {
        transform: translateY(-50%) scale(1);
        opacity: 1;
      }
    }
    
    /* Enhanced radio button styling */
    .radio-group {
      display: flex;
      gap: 16px;
      color: #fff;
      font-size: 0.95rem;
      margin-bottom: 16px;
      padding: 16px;
      background: rgba(255, 255, 255, 0.03);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .custom-radio {
      display: flex;
      align-items: center;
      position: relative;
      padding-left: 32px;
      cursor: pointer;
      font-size: 1rem;
      user-select: none;
      transition: all 0.3s ease;
      flex: 1;
      padding: 12px 16px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.02);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .custom-radio:hover {
      background: rgba(160, 132, 238, 0.1);
      border-color: rgba(160, 132, 238, 0.3);
      transform: translateY(-1px);
    }
    
    .custom-radio input[type="radio"] {
      opacity: 0;
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 20px;
      height: 20px;
      margin: 0;
      z-index: 2;
      cursor: pointer;
    }
    
    .radio-btn {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      height: 20px;
      width: 20px;
      background: rgba(255, 255, 255, 0.1);
      border: 2px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      transition: all 0.3s ease;
      box-sizing: border-box;
    }
    
    .custom-radio input[type="radio"]:checked ~ .radio-btn {
      background: linear-gradient(135deg, #a084ee, #7a5de4);
      border: 2px solid #a084ee;
      box-shadow: 0 0 15px rgba(160, 132, 238, 0.5);
    }
    
    .custom-radio input[type="radio"]:checked ~ .radio-btn:after {
      content: "";
      display: block;
      position: absolute;
      left: 5px;
      top: 5px;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #fff;
      animation: radioPulse 0.3s ease;
    }
    
    @keyframes radioPulse {
      0% { transform: scale(0); }
      50% { transform: scale(1.2); }
      100% { transform: scale(1); }
    }
    
    .sidebar {
      width: 350px;
      background: #000117;
      padding: 32px 20px;
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      flex-shrink: 0;
    }
    
    .main-content {
      flex: 1;
      padding: 0 0 0 40px;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
  </style>
</head>
<body>
  <iframe
        src="Navbar\navbar.html"
        frameborder="0"
        style="width: 100%; height: 80px"></iframe>
  <?php require_once 'navbar.php'; ?>

  <div class="container">
    <aside class="sidebar">
      <form class="medicine-form" method="POST">
        <?php if (isset($success_message)): ?>
          <div class="message success" id="success_message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <input type="text" id ="medicine" name="medicine_name" placeholder="Medicine Name" required>
        
        <div class="radio-group">
          <label class="custom-radio">
            <input type="radio" id="before_meal" name="meal_time" value="Before Meal" checked>
            <span class="radio-btn"></span>
            Before Meal
          </label>
          <label class="custom-radio">
            <input type="radio" id="after_meal" name="meal_time" value="After Meal">
            <span class="radio-btn"></span>
            After Meal
          </label>
        </div>
        
        <input type="date" id = "start_date" name="begin_date" placeholder="Begin Date" required>
        <input type="date" id = "end_date" name="end_date" placeholder="End Date" required>
        
        <div id="time-inputs">
          <div class="time-input-group">
            <input type="time" id ="time" name="dose_times[]" required>
            <button type="button" class="add-time-btn" onclick="addTimeInput()">+</button>
          </div>
        </div>
        
        <button type="submit" id="add" name="save_medicine" class="save-btn">Save Medicine</button>
      </form>
    </aside>
    
    <div class="divider"></div>
    
    <main class="main-content">
      <section class="planner">
        <h2><span class="user-name"><?php echo strtoupper(htmlspecialchars($user_name)); ?></span>'s<br><span class="subtitle">Medicine Planner</span></h2>
        <div class="days">
          <?php
            // Get current day of week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
            $current_day = date('w');
            $days = ['SUNDAY', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            
            foreach ($days as $index => $day) {
              $is_active = ($index == $current_day) ? 'active' : '';
              echo "<button type=\"button\" class=\"day $is_active\" disabled>$day</button>";
            }
          ?>
        </div>
        <div class="upcoming">
          <h3>Upcoming Doses</h3>
          <div class="dose-list">
            <?php if (empty($medications)): ?>
              <div class="empty-state">
                <div class="empty-icon">
                  <i class="fas fa-pills"></i>
                </div>
                <h3 class="empty-title">No Medications Yet</h3>
                <p class="empty-description">Start managing your health by adding your first medicine from the sidebar.</p>
                <div class="empty-arrow">
                  <i class="fas fa-arrow-left"></i>
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($medications as $med): ?>
                <div class="dose-card">
                  <div class="dose-icon">
                    <i class="fas fa-pills"></i>
                  </div>
                  <div class="dose-info">
                    <div class="dose-title"><?php echo htmlspecialchars($med['medicine_name']); ?></div>
                    <div class="dose-desc">1 Pill <?php echo $med['meal_time']; ?></div>
                    <div class="dose-time">
                      <span class="clock"></span> 
                      <?php 
                        if (!empty($med['dose_times'])) {
                          $times = explode(',', $med['dose_times']);
                          $formatted_times = array();
                          foreach ($times as $time) {
                            if ($time !== null && $time !== '') {
                              $formatted_times[] = date('g:i A', strtotime($time));
                            }
                          }
                          if (!empty($formatted_times)) {
                            echo implode(' & ', $formatted_times);
                          }
                        }
                      ?>
                    </div>
                  </div>
                  <div class="dose-check">
                    <svg width="40" height="40" viewBox="0 0 40 40">
                      <circle cx="20" cy="20" r="18" fill="#2ecc40"/>
                      <polyline points="13,22 19,28 28,14" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </section>
    </main>
  </div>

  <script>
    function addTimeInput() {
      const timeInputs = document.getElementById('time-inputs');
      const newTimeGroup = document.createElement('div');
      newTimeGroup.className = 'time-input-group';
      newTimeGroup.innerHTML = `
        <input type="time" name="dose_times[]" required>
        <button type="button" class="remove-time-btn" onclick="removeTimeInput(this)">×</button>
      `;
      timeInputs.appendChild(newTimeGroup);
    }

    function removeTimeInput(button) {
      button.parentElement.remove();
    }

    // Auto medication reminder system
    function checkMedicationReminders() {
      fetch('auto_medication_reminder.php')
        .then(response => response.text())
        .catch(error => {
          // Silent error handling - system continues to work
        });
    }

    // Check for reminders every minute when page is active
    let reminderInterval;
    
    function startReminderChecks() {
      // Check immediately when page loads
      checkMedicationReminders();
      
      // Then check every minute
      reminderInterval = setInterval(checkMedicationReminders, 60000); // 60 seconds
    }

    function stopReminderChecks() {
      if (reminderInterval) {
        clearInterval(reminderInterval);
      }
    }

    // Start checking when page becomes visible
    document.addEventListener('visibilitychange', function() {
      if (document.visibilityState === 'visible') {
        startReminderChecks();
      } else {
        stopReminderChecks();
      }
    });

    // Start checking when page loads
    document.addEventListener('DOMContentLoaded', function() {
      startReminderChecks();
    });

    // Also check when user interacts with the page
    document.addEventListener('click', function() {
      if (!reminderInterval) {
        startReminderChecks();
      }
    });

    // Check every 30 seconds if user is actively using the page
    setInterval(function() {
      if (document.visibilityState === 'visible' && !reminderInterval) {
        startReminderChecks();
      }
    }, 30000);
  </script>
</body>
</html>