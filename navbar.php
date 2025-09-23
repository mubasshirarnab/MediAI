<?php

require_once 'dbConnect.php';

$notification_count = 0;
$meeting_times = [];

if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
  // Get notification count and meeting times for the user
  $user_id = $_SESSION['user_id'];
  $query = "SELECT t.*, d.name as doctor_name 
              FROM time_for_meeting t 
              JOIN users d ON t.doctor_id = d.id 
              WHERE t.patient_id = ? 
              ORDER BY t.id DESC";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $meeting_times[] = $row;
  }
  $notification_count = count($meeting_times);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="css\navbar.css" />
  <title>Navbar</title>
  <style>
    .navbar {
      background: #000117;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
    }

    .navbar-logo {
      color: #fff;
      font-size: 1.5rem;
      font-weight: 700;
      text-decoration: none;
    }

    .navbar-icons {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .navbar-bell {
      color: #fff;
      font-size: 1.3rem;
      cursor: pointer;
      position: relative;
    }

    .notification-count {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #ff4444;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 0.7rem;
      font-weight: bold;
    }

    .notification-popup {
      position: absolute;
      top: 60px;
      right: 20px;
      width: 300px;
      background: #10142a;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      display: none;
      z-index: 1001;
      border: 1px solid #2a2a4a;
    }

    .notification-popup.show {
      display: block;
    }

    .notification-header {
      padding: 15px;
      border-bottom: 1px solid #2a2a4a;
      font-weight: 600;
      color: #fff;
    }

    .notification-list {
      max-height: 300px;
      overflow-y: auto;
    }

    .notification-item {
      padding: 12px 15px;
      border-bottom: 1px solid #2a2a4a;
      color: #fff;
    }

    .notification-item:last-child {
      border-bottom: none;
    }

    .notification-item .doctor-name {
      font-weight: 600;
      color: #7f5fff;
    }

    .notification-item .meeting-time {
      color: #b3b3b3;
      font-size: 0.9rem;
      margin-top: 4px;
    }

    .notification-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      z-index: 1000;
    }

    .notification-backdrop.show {
      display: block;
    }
  </style>
</head>

<body>
  <header class="navbar">
    <div class="navbar-logo">
      <a href="#">
        <img src="images\LOGO.png" alt="MEDIAi Logo" class="logo" /></a>
    </div>
    <nav class="navbar-links">
      <a href="index.php" class="navbar-link">Home</a>
      <a href="#" class="navbar-link">Contact</a>
      <a href="doctors.php" class="navbar-link">Appointment</a>
      <a href="feed.php" class="navbar-link">Community</a>
      <a href="mediReminder.php" class="navbar-link">Medication Reminder</a>
      <?php

      $profile_link = 'patient_dashboard.php';
      if (isset($_SESSION['role']) && $_SESSION['role'] == 'doctor') {
        $profile_link = 'doctor_dashboard.php';
      }
      ?>
      <a href="<?php echo $profile_link; ?>" class="navbar-link">Profile</a>
    </nav>
    <div class="navbar-icons">
      <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1): ?>
        <div style="position: relative;">
          <i class="fa-solid fa-bell navbar-bell" onclick="toggleNotifications()"></i>
          <?php if ($notification_count > 0): ?>
            <span class="notification-count"><?php echo $notification_count; ?></span>
          <?php endif; ?>

          <!-- Notification Popup -->
          <div id="notification-popup" class="notification-popup">
            <div class="notification-header">Meeting Notifications</div>
            <div class="notification-list">
              <?php if (empty($meeting_times)): ?>
                <div class="notification-item">No meeting notifications</div>
              <?php else: ?>
                <?php foreach ($meeting_times as $meeting): ?>
                  <div class="notification-item">
                    <div class="doctor-name">Dr. <?php echo htmlspecialchars($meeting['doctor_name']); ?></div>
                    <div class="meeting-time">Meeting Time: <?php echo htmlspecialchars($meeting['meeting_time']); ?></div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </header>
  <div id="notification-backdrop" class="notification-backdrop" onclick="toggleNotifications()"></div>

  <script>
    function toggleNotifications() {
      const popup = document.getElementById('notification-popup');
      const backdrop = document.getElementById('notification-backdrop');

      if (popup.classList.contains('show')) {
        popup.classList.remove('show');
        backdrop.classList.remove('show');
      } else {
        popup.classList.add('show');
        backdrop.classList.add('show');
      }
    }
  </script>
</body>

</html>