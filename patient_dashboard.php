<?php
session_start();
require_once 'dbConnect.php';
// Add navbar at the top

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'patient') {
    header("Location: login.php");
    exit();
}

// Get patient information
$user_id = $_SESSION['user_id'];
$query = "SELECT p.*, u.name, u.email, u.phone 
          FROM patients p 
          JOIN users u ON p.user_id = u.id 
          WHERE p.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

// Get patient's appointments
$appointments = [];
$appointments_query = "SELECT a.*, u.name as doctor_name, d.specialization 
                       FROM appointments a 
                       JOIN users u ON a.doctor_id = u.id 
                       JOIN doctors d ON u.id = d.user_id 
                       WHERE a.patient_id = ? 
                       ORDER BY a.timeslot ASC";
$appt_stmt = $conn->prepare($appointments_query);
$appt_stmt->bind_param("i", $user_id);
$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();
while ($row = $appt_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Ensure lab_reports table exists and load patient's lab reports
$reports = [];
try {
    $conn->query("CREATE TABLE IF NOT EXISTS lab_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        test_name VARCHAR(255) NOT NULL,
        report_file VARCHAR(255) NOT NULL,
        uploaded_by INT NOT NULL,
        uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        report_date DATE NOT NULL,
        INDEX idx_patient (patient_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // In current schema, lab_reports.patient_id = users.id of the patient
    $rep_stmt = $conn->prepare('SELECT id, test_name, report_file, uploaded_at, report_date FROM lab_reports WHERE patient_id = ? ORDER BY report_date DESC, uploaded_at DESC');
    $rep_stmt->bind_param('i', $user_id);
    $rep_stmt->execute();
    $rep_res = $rep_stmt->get_result();
    while ($r = $rep_res->fetch_assoc()) {
        $reports[] = $r;
    }
} catch (Throwable $e) {
    // Silently ignore to avoid breaking the dashboard
}

// Add this at the top of the file where other PHP code is
$notification_count = 0;
$meeting_notifications = []; // Renamed for clarity

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    // New query to fetch meeting_time and the corresponding meeting_code
    $query = "SELECT 
                tmf.meeting_time,
                mc.meeting_code,
                d.name as doctor_name
              FROM time_for_meeting tmf
              LEFT JOIN meeting_code mc ON tmf.patient_id = mc.patient_id AND tmf.doctor_id = mc.doctor_id
              LEFT JOIN users d ON tmf.doctor_id = d.id
              WHERE tmf.patient_id = ? 
              ORDER BY tmf.id DESC";
    $stmt = $conn->prepare($query);
    if ($stmt) { // Check if prepare was successful
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $meeting_notifications[] = $row;
        }
        $stmt->close(); // Good practice
    } else {
        // You might want to log an error if prepare fails
        // error_log("Failed to prepare statement for meeting notifications: " . $conn->error);
    }
    $notification_count = count($meeting_notifications);
}

// var_dump($meeting_times);
// exit();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - MEDIAi</title>
    <link rel="stylesheet" href="styles.css">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css\navbar.css" />
    <style>
        body {
            background: #0a0c1b;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-flex {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 350px;
            background: #000117;
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            box-shadow: 2px 0 20px 0 #0002;
            border-right: 4px solid transparent;
            border-image: linear-gradient(to bottom, transparent 0%, whitesmoke 20%, whitesmoke 80%, transparent 100%);
            border-image-slice: 1;
        }

        .sidebar h2 {
            margin-bottom: 20px;
            font-size: 2rem;
            font-weight: 700;
        }

        .sidebar .info {
            margin-bottom: 30px;
        }

        .sidebar .info p {
            margin: 6px 0;
            font-size: 1rem;
            color: #c7c7c7;
        }

        .edit-btn {
            background: linear-gradient(90deg, #7f5fff, #5e3be1);
            color: #fff;
            border: none;
            border-radius: 24px;
            padding: 10px 28px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
            box-shadow: 0 2px 10px #7f5fff44;
            transition: background 0.2s;
        }

        .edit-btn:hover {
            background: linear-gradient(90deg, #5e3be1, #7f5fff);
        }

        .content {
            flex: 1;
            padding: 60px 40px;
            background: #000117;
            display: flex;
            flex-direction: column;
        }

        .appointments-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 30px;
        }

        .appointments-list {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .appointment-card {
            background: linear-gradient(90deg, #191b2e 60%, #1a1d3a 100%);
            border-radius: 20px;
            box-shadow: 0 0 24px 0 #00ffb033, 0 2px 8px #0002;
            padding: 32px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 2px solid transparent;
            transition: box-shadow 0.2s, border 0.2s;
        }

        .appointment-card:hover {
            border: 2px solid #00ffb0;
            box-shadow: 0 0 32px 0 #00ffb055, 0 2px 8px #0002;
        }

        .appointment-info {
            flex: 1;
        }

        .appointment-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .appointment-specialist {
            font-size: 1rem;
            color: #b0b0b0;
            margin-bottom: 10px;
        }

        .appointment-details {
            font-size: 0.98rem;
            color: #d0d0d0;
        }

        .badge {
            display: inline-block;
            background: #e53935;
            color: #fff;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 2px 10px;
            margin-right: 6px;
        }

        .badge.gray {
            background: #444;
        }

        .meeting-popup {
            position: fixed;
            top: 100px;
            right: 80px;
            width: 320px;
            background: radial-gradient(ellipse at 60% 40%, #181a2b 70%, #2a1a4d 100%);
            border: 2px solid #fff;
            border-radius: 38px;
            box-shadow: 0 0 60px 0 #000a, 0 0 40px 0 #7f5fff33;
            padding: 38px 32px 32px 32px;
            z-index: 1002;
            display: none;
            flex-direction: column;
            align-items: stretch;
            animation: popup-fade-in 0.3s;
        }

        .meeting-popup.show {
            display: flex;
        }

        .popup-title {
            color: #bdbdbd;
            font-size: 1rem;
            margin-bottom: 18px;
            margin-left: 2px;
        }

        .popup-form {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .popup-form label {
            color: #fff;
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 6px;
        }

        .popup-form input {
            background: transparent;
            height: 10px;
            border: 2px solid #bdaaff;
            border-radius: 24px;
            padding: 18px 18px;
            color: #fff;
            font-size: 1.1rem;
            outline: none;
            margin-bottom: 8px;
            transition: border 0.2s;
        }

        .popup-form input:focus {
            border: 2px solid #a47fff;
        }

        .popup-join-btn {
            background: linear-gradient(90deg, #a47fff, #7f5fff);
            color: #fff;
            border: none;
            height: 50px;
            border-radius: 24px;
            padding: 18px 0;
            font-size: 1.3rem;
            font-weight: 500;
            margin-top: 18px;
            cursor: pointer;
            box-shadow: 0 2px 20px #7f5fff44;
            transition: background 0.2s;
        }

        .popup-join-btn:hover {
            background: linear-gradient(90deg, #7f5fff, #a47fff);
        }

        .close-popup {
            position: absolute;
            top: 18px;
            right: 28px;
            background: none;
            border: none;
            color: #fff;
            font-size: 2.2rem;
            cursor: pointer;
            z-index: 1003;
            transition: color 0.2s;
        }

        .close-popup:hover {
            color: #a47fff;
        }

        .popup-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.35);
            z-index: 1001;
            display: none;
        }

        .popup-backdrop.show {
            display: block;
        }

        @keyframes popup-fade-in {
            from {
                opacity: 0;
                transform: translateY(40px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <?php

    include_once 'navbar.php';
    ?>
    <div class="main-flex">
        <div class="sidebar">
            <h2><?php echo htmlspecialchars($patient['name']); ?></h2>
            <div class="info">
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($patient['date_of_birth']); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($patient['address']); ?></p>
                <p><strong>Contact no.:</strong> <?php echo htmlspecialchars($patient['phone']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($patient['email']); ?></p>
            </div>
            <button class="edit-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>

        <div class="content" ">
            <div class=" appointments-title">Available Appointments</div>
        <div style="position: relative;">
            <i style="position: absolute;top:-50px; right: 100px;" class="fa-solid fa-bell navbar-bell" onclick="toggleNotifications()"></i>
            <?php if ($notification_count > 0): ?>
                <span style="position: absolute; top: 95px; right: 95px; background: #ff4444; color: white; border-radius: 50%; padding: 2px 6px; font-size: 0.7rem; font-weight: bold;">
                    <?php echo $notification_count; ?>
                </span>
            <?php endif; ?>

            <!-- Notification Popup -->
            <div id="notification-popup" style="position: fixed; top: 120px; right: 100px; width: 300px; background: #10142a; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); display: none; z-index: 1001; border: 1px solid #2a2a4a;">
                <div style="padding: 15px; border-bottom: 1px solid #2a2a4a; font-weight: 600; color: #fff;">Meeting Notifications</div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($meeting_notifications) === 0): ?>
                        <div style="padding: 12px 15px; color: #fff;">No meeting notifications</div>
                    <?php else: ?>
                        <?php foreach ($meeting_notifications as $notification): ?>
                            <div style="padding: 12px 15px; border-bottom: 1px solid #2a2a4a; color: #fff;">
                                <div style="color: #b3b3b3; font-size: 0.9rem; margin-top: 4px;">
                                    A meeting has been scheduled by <strong><?php echo htmlspecialchars($notification['doctor_name']); ?></strong> at <strong><?php echo htmlspecialchars($notification['meeting_time']); ?></strong>.
                                    <?php if (!empty($notification['meeting_code'])): ?>
                                        <br>Meeting Code: <strong><?php echo htmlspecialchars($notification['meeting_code']); ?></strong>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="appointments-list">
            <?php if (empty($appointments)): ?>
                <div class="appointment-card">
                    <div class="appointment-info">
                        <div class="appointment-title">No Appointments</div>
                        <div class="appointment-specialist">You don't have any upcoming appointments</div>
                        <div class="appointment-details">
                            <strong>Book an appointment:</strong> Visit the doctors page to schedule your next visit
                        </div>
                    </div>
                    <button class="edit-btn connect-btn" onclick="window.location.href='doctors.php'">Book Appointment</button>
                </div>
            <?php else: ?>
                <?php foreach ($appointments as $appointment): ?>
                    <div class="appointment-card">
                        <div class="appointment-info">
                            <div class="appointment-title"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                            <div class="appointment-specialist">Specialist in <?php echo htmlspecialchars($appointment['specialization']); ?></div>
                            <div class="appointment-details">
                                <strong>Appointment Time:</strong> <?php echo date('l, F j, Y g:i A', strtotime($appointment['timeslot'])); ?>
                                <?php
                                // Calculate serial number
                                $serial_query = "SELECT COUNT(*) as serial_no 
                                                   FROM appointments 
                                                   WHERE doctor_id = ? 
                                                   AND hospital_id = ? 
                                                   AND DATE(timeslot) = DATE(?) 
                                                   AND TIME(timeslot) <= TIME(?)";
                                $stmt = $conn->prepare($serial_query);
                                $stmt->bind_param(
                                    "iiss",
                                    $appointment['doctor_id'],
                                    $appointment['hospital_id'],
                                    $appointment['timeslot'],
                                    $appointment['timeslot']
                                );
                                $stmt->execute();
                                $serial_result = $stmt->get_result();
                                $serial_no = $serial_result->fetch_assoc()['serial_no'];

                                // Calculate waiting time
                                $people_ahead = $serial_no - 1;
                                $total_minutes = $people_ahead * 20;
                                $hours = floor($total_minutes / 60);
                                $minutes = $total_minutes % 60;
                                ?>
                                <div class="serial-info" style="margin-top: 10px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                                    <strong style="color: #7f5fff;">Your Serial Number:</strong> <?php echo $serial_no; ?>
                                    <?php if ($people_ahead > 0): ?>
                                        <br>
                                        <strong style="color: #7f5fff;">Estimated Waiting Time:</strong>
                                        <?php
                                        if ($hours > 0) {
                                            echo $hours . ' hour' . ($hours > 1 ? 's' : '');
                                            if ($minutes > 0) {
                                                echo ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                            }
                                        } else {
                                            echo $minutes . ' minute' . ($minutes > 1 ? 's' : '');
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($appointment['notes'])): ?>
                                    <br><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button class="edit-btn connect-btn" onclick="goToVideo(<?php echo $appointment['id']; ?>)">Connect</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- My Reports Section -->
        <div style="margin-top: 40px;" class="appointments-title">My Reports</div>
        <div class="appointment-card">
            <div class="appointment-info" style="width:100%">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #23244a; color:#b3b3b3; background:#0f1230;">Test Name</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #23244a; color:#b3b3b3; background:#0f1230;">Report Date</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #23244a; color:#b3b3b3; background:#0f1230;">Uploaded At</th>
                            <th style="text-align:left; padding:10px; border-bottom:1px solid #23244a; color:#b3b3b3; background:#0f1230;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="4" style="padding:14px; color:#d0d0d0;">No reports available yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $rep): ?>
                                <tr>
                                    <td style="padding:10px; border-bottom:1px solid #23244a; "><?php echo htmlspecialchars($rep['test_name']); ?></td>
                                    <td style="padding:10px; border-bottom:1px solid #23244a; "><?php echo htmlspecialchars($rep['report_date']); ?></td>
                                    <td style="padding:10px; border-bottom:1px solid #23244a; "><?php echo htmlspecialchars($rep['uploaded_at']); ?></td>
                                    <td style="padding:10px; border-bottom:1px solid #23244a; "><a style="color:#7f5fff; text-decoration:underline;" href="<?php echo htmlspecialchars($rep['report_file']); ?>" target="_blank" rel="noopener noreferrer">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Popup Modal for Join Meeting -->
        <div id="meeting-popup" class="meeting-popup">
            <button class="close-popup" onclick="closePopup()">&times;</button>
            <div class="popup-title">patient room</div>
            <form class="popup-form">
                <label for="popup-name">Enter Your Email</label>
                <input type="text" id="popup-name" placeholder="" autocomplete="off" />
                <label for="popup-meeting">Enter Meeting Id</label>
                <input type="text" id="popup-meeting" placeholder="" autocomplete="off" />
                <button type="submit" class="popup-join-btn">Join Meeting</button>
            </form>
        </div>
        <!-- <div id="popup-backdrop" class="popup-backdrop"></div>
        <script>
            // Show popup on connect button click
            document.querySelectorAll('.connect-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('meeting-popup').classList.add('show');
                    document.getElementById('popup-backdrop').classList.add('show');
                });
            });

            function closePopup() {
                document.getElementById('meeting-popup').classList.remove('show');
                document.getElementById('popup-backdrop').classList.remove('show');
            }
            // Optional: close popup on backdrop click
            document.getElementById('popup-backdrop').onclick = closePopup;
            // Prevent form submit (demo only)
            document.querySelector('.popup-form').onsubmit = function(e) {
                e.preventDefault();
            };
        </script> -->
    </div>
    </div>

    <!-- Add this before </body> -->
    <div id="notification-backdrop" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; z-index: 1000;" onclick="toggleNotifications()"></div>

    <script>
        function toggleNotifications() {
            const popup = document.getElementById('notification-popup');
            const backdrop = document.getElementById('notification-backdrop');

            if (popup.style.display === 'none' || popup.style.display === '') {
                popup.style.display = 'block';
                backdrop.style.display = 'block';
            } else {
                popup.style.display = 'none';
                backdrop.style.display = 'none';
            }
        }

        function goToVideo(appointmentId) {
            // You can customize this function to handle video connection
            // For now, it will redirect to join_meeting.php with appointment ID
            window.location.href = 'join_meeting.php?appointment_id=' + appointmentId;
        }
    </script>
</body>

</html>