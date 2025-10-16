<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'doctor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get doctor and user info
$query = "SELECT u.name, u.email, u.phone, d.specialization, d.license_number, d.photo
          FROM users u
          JOIN doctors d ON u.id = d.user_id
          WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Get expertise
$expertise = [];
$exp_query = "SELECT expertise_name FROM expertise WHERE user_id = ?";
$stmt = $conn->prepare($exp_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $expertise[] = $row['expertise_name'];
}

// Get qualifications
$qualifications = [];
$qual_query = "SELECT qualification, institute, year_obtained FROM qualifications WHERE user_id = ?";
$stmt = $conn->prepare($qual_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $qualifications[] = $row;
}

// Get available hours
$available_hours = [];
$hours_query = "SELECT day_of_week, start_time, end_time FROM available_hours WHERE user_id = ? ORDER BY day_of_week";
$stmt = $conn->prepare($hours_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
while ($row = $result->fetch_assoc()) {
    $row['day_name'] = $days[$row['day_of_week']];
    $available_hours[] = $row;
}

// Get today's appointments
$today = date('Y-m-d');
$appointments_query = "SELECT a.*, p.name as patient_name, p.phone as patient_phone, 
                              pat.gender, pat.date_of_birth, pat.address
                      FROM appointments a 
                      JOIN users p ON a.patient_id = p.id 
                      JOIN patients pat ON p.id = pat.user_id
                      WHERE a.doctor_id = ? 
                      ORDER BY a.timeslot DESC";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | MEDIAi</title>
    <link rel="stylesheet" href="css/communityHome.css" />
    <style>
        body {
            background: #000117;
            font-family: 'Inter', sans-serif;
            margin: 0;
            color: #fff;
        }

        .dashboard-container {
            display: flex;
            max-width: 1400px;
            margin: 40px auto 0 auto;
            gap: 40px;
        }

        .profile-card {
            background: rgba(24, 28, 43, 0.95);
            border-radius: 18px;
            box-shadow: 0 2px 24px 0 #0004;
            padding: 2.5rem 2rem 2rem 2rem;
            width: 340px;
            display: flex;
            flex-direction: column;
            align-items: center;
            border: 1.5px solid #23244a;
        }

        .profile-card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #fff;
            margin-bottom: 1.2rem;
        }

        .profile-card h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .profile-card .doc-role {
            color: #b3b3b3;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .profile-card .doc-license,
        .profile-card .doc-rating {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .profile-card .doc-rating {
            color: #ffd700;
            font-weight: 600;
        }

        .profile-card .section-title {
            margin-top: 1.2rem;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .profile-card ul {
            margin: 0 0 0 1.2rem;
            padding: 0;
            color: #b3b3b3;
            font-size: 0.98rem;
        }

        .profile-card .edit-btn {
            margin-top: 2rem;
            background: linear-gradient(90deg, #a259ff 0%, #471cc8 100%);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 0.9rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 2px 8px #0003;
            transition: background 0.2s;
        }

        .profile-card .edit-btn:hover {
            background: linear-gradient(90deg, #471cc8 0%, #a259ff 100%);
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #fff;
        }

        .appointment-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .appointment-card {
            background: rgba(36, 38, 58, 0.95);
            border-radius: 16px;
            border: 1.5px solid #a3a3a3;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 16px 0 #0002;
            position: relative;
            transition: box-shadow 0.2s, border 0.2s;
        }


        .appointment-info {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .appointment-patient {
            font-size: 1.15rem;
            font-weight: 600;
            color: #fff;
        }

        .appointment-meta {
            color: #b3b3b3;
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .appointment-status {
            font-weight: 600;
            color: #2fff8d;
            font-size: 1.05rem;
        }

        .connect-btn,
        .send-time-btn,
        .view-file-btn {
            background: #2fff8d;
            color: #181c2b;
            border: none;
            border-radius: 12px;
            padding: 0.7rem 2.2rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px #2fff8d44;
            transition: background 0.2s;
        }

        .view-file-btn {
            background: #ff8d2f;
            box-shadow: 0 2px 8px #ff8d2f44;
        }

        .connect-btn:hover {
            background: rgb(6, 54, 25);
        }

        .send-time-btn:hover {
            background: rgb(6, 54, 25);
        }

        .view-file-btn:hover {
            background: rgb(54, 25, 6);
        }

        /* Popup styles copied from patient_dashboard */
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

        /* Send Time Popup Styles */
        .send-time-popup {
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

        .send-time-popup.show {
            display: flex;
        }

        .send-time-popup .popup-title {
            color: #bdbdbd;
            font-size: 1rem;
            margin-bottom: 18px;
            margin-left: 2px;
        }

        .send-time-popup .popup-form {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .send-time-popup .popup-form label {
            color: #fff;
            font-size: 1.5rem;
            text-align: center;
            margin-bottom: 6px;
        }

        .send-time-popup .popup-form input {
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

        .send-time-popup .popup-form input:focus {
            border: 2px solid #a47fff;
        }

        .send-time-popup .popup-join-btn {
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

        .send-time-popup .popup-join-btn:hover {
            background: linear-gradient(90deg, #7f5fff, #a47fff);
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
    <div class="dashboard-container">
        <!-- Profile Card -->
        <div class="profile-card">
            <?php if (!empty($doctor['photo'])): ?>
                <img src="img/<?php echo htmlspecialchars($doctor['photo']); ?>" alt="Doctor Photo" />
            <?php endif; ?>
            <?php if (!empty($doctor['name'])): ?>
                <h2><?php echo htmlspecialchars($doctor['name']); ?></h2>
            <?php endif; ?>
            <?php if (!empty($doctor['specialization'])): ?>
                <div class="doc-role"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
            <?php endif; ?>
            <?php if (!empty($doctor['license_number'])): ?>
                <div class="doc-license">License no: <?php echo htmlspecialchars($doctor['license_number']); ?></div>
            <?php endif; ?>

            <!-- Expertise -->
            <div class="section-title">Expertise:</div>
            <?php if (!empty($expertise)): ?>
                <ul>
                    <?php foreach ($expertise as $exp): ?>
                        <li><?php echo htmlspecialchars($exp); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <!-- Qualifications -->
            <div class="section-title">Qualification:</div>
            <?php if (!empty($qualifications)): ?>
                <ul>
                    <?php foreach ($qualifications as $qual): ?>
                        <li>
                            <?php echo htmlspecialchars($qual['qualification']); ?>
                            <?php if (!empty($qual['institute'])): ?>
                                : <?php echo htmlspecialchars($qual['institute']); ?>
                            <?php endif; ?>
                            <?php if (!empty($qual['year_obtained'])): ?>
                                (<?php echo htmlspecialchars($qual['year_obtained']); ?>)
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <!-- Available Hours -->
            <div class="section-title">Available Hours (Online):</div>
            <?php if (!empty($available_hours)): ?>
                <ul>
                    <?php foreach ($available_hours as $hour): ?>
                        <li>
                            <?php echo htmlspecialchars($hour['day_name']); ?>:
                            <?php echo date('g:i A', strtotime($hour['start_time'])); ?> - <?php echo date('g:i A', strtotime($hour['end_time'])); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <button class="edit-btn" onclick="window.location.href='editdoctorProfile.php'">Edit Profile</button>
            <button class="edit-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
        <!-- Main Content -->
        <div class="main-content">
            <div>
                <div class="section-title">Available Appointments</div>
                <div class="appointment-list">
                    <?php if ($appointments->num_rows === 0): ?>
                        <div class="appointment-card">
                            <div class="appointment-info">
                                <div class="appointment-patient">No Appointments</div>
                                <div class="appointment-meta">You don't have any appointments scheduled</div>
                                <div class="appointment-status">Check back later for new appointments</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php while ($appointment = $appointments->fetch_assoc()): ?>
                            <?php
                            // Calculate age from date of birth
                            $age = '';
                            if (!empty($appointment['date_of_birth'])) {
                                $birth_date = new DateTime($appointment['date_of_birth']);
                                $today = new DateTime();
                                $age = $today->diff($birth_date)->y;
                            }
                            ?>
                            <div class="appointment-card">
                                <div class="appointment-info">
                                    <div class="appointment-patient"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                    <div class="appointment-meta">
                                        <?php if ($age): ?>Age <?php echo $age; ?> | <?php endif; ?>
                                    <?php echo htmlspecialchars($appointment['gender'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="appointment-status">
                                        <strong>Appointment Time:</strong> <?php echo date('l, F j, Y g:i A', strtotime($appointment['timeslot'])); ?>
                                        <?php if (!empty($appointment['notes'])): ?>
                                            <br><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 10px;flex-direction: column;">
                                    <button class="connect-btn" onclick="goToVideo(<?php echo $appointment['id']; ?>, <?php echo $appointment['patient_id']; ?>)">Connect</button>
                                    <button class="send-time-btn" onclick="sendMeetingTime(<?php echo $appointment['patient_id']; ?>)">Send Time</button>
                                    <button class="view-file-btn" onclick="viewFile(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['report_file'] ?? ''); ?>')">View File</button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- Popup Modal for Join Meeting (copied from patient_dashboard) -->
    <div id="meeting-popup" class="meeting-popup">
        <button class="close-popup" onclick="closePopup()">&times;</button>
        <div class="popup-title">doctor room</div>
        <form class="popup-form">
            <label for="popup-name">Enter Your Email</label>
            <input type="text" id="popup-name" placeholder="" autocomplete="off" />
            <label for="popup-meeting">Enter Meeting Id</label>
            <input type="text" id="popup-meeting" placeholder="" autocomplete="off" />
            <button type="submit" class="popup-join-btn">Join Meeting</button>
        </form>
    </div>
    <div id="popup-backdrop" class="popup-backdrop"></div>

    <!-- Send Time Popup Modal -->
    <div id="send-time-popup" class="send-time-popup">
        <button class="close-popup" onclick="closeSendTimePopup()">&times;</button>
        <div class="popup-title">Send Meeting Time</div>
        <form class="popup-form" action="save_meeting_time.php" method="POST">
            <label for="meeting-time">Enter Meeting Time</label>
            <input type="text" id="meeting-time" name="meeting_time" placeholder="" autocomplete="off" required />
            <input type="hidden" name="patient_id" id="patient_id_input" value="">
            <button type="submit" class="popup-join-btn">Send Time</button>
        </form>
    </div>

    <!-- File View Modal -->
    <div id="file-modal" class="meeting-popup" style="width: 80%; max-width: 800px; height: 80%; max-height: 600px;">
        <button class="close-popup" onclick="closeFileModal()">&times;</button>
        <div class="popup-title">Medical Report</div>
        <div id="file-content" style="height: calc(100% - 100px); overflow: auto; padding: 20px;">
            <!-- File content will be loaded here -->
        </div>
    </div>
</body>
<script>
    // Show popup on connect button click (copied from patient_dashboard)
    // document.querySelectorAll('.connect-btn').forEach(btn => {
    //     btn.addEventListener('click', function(e) {
    //         e.preventDefault();
    //         document.getElementById('meeting-popup').classList.add('show');
    //         document.getElementById('popup-backdrop').classList.add('show');
    //     });
    // });

    // Show popup on send time button click
    document.querySelectorAll('.send-time-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('send-time-popup').classList.add('show');
            document.getElementById('popup-backdrop').classList.add('show');
        });
    });

    function closePopup() {
        document.getElementById('meeting-popup').classList.remove('show');
        document.getElementById('popup-backdrop').classList.remove('show');
    }

    function closeSendTimePopup() {
        document.getElementById('send-time-popup').classList.remove('show');
        document.getElementById('popup-backdrop').classList.remove('show');
    }

    function closeFileModal() {
        document.getElementById('file-modal').classList.remove('show');
        document.getElementById('popup-backdrop').classList.remove('show');
    }

    // Optional: close popup on backdrop click
    document.getElementById('popup-backdrop').onclick = function() {
        closePopup();
        closeSendTimePopup();
        closeFileModal();
    };

    function goToVideo(appointmentId, patientId) {
        window.location.href = 'video_counselling.php?appointment_id=' + appointmentId + '&patient_id=' + patientId;
    }

    function sendMeetingTime(patientId) {
        document.getElementById('patient_id_input').value = patientId;
        document.getElementById('send-time-popup').classList.add('show');
        document.getElementById('popup-backdrop').classList.add('show');
    }

    function viewFile(appointmentId, fileName) {
        if (!fileName || fileName.trim() === '') {
            // No file present
            document.getElementById('file-content').innerHTML = `
                <div style="text-align: center; padding: 50px; color: #fff;">
                    <h3>No File Present</h3>
                    <p>No medical report has been uploaded for this appointment.</p>
                </div>
            `;
        } else {
            // File exists - show it
            const fileExtension = fileName.split('.').pop().toLowerCase();
            const filePath = 'uploads/reports/' + fileName;

            if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExtension)) {
                // Image file
                document.getElementById('file-content').innerHTML = `
                    <div style="text-align: center;">
                        <img src="${filePath}" style="max-width: 100%; max-height: 100%; object-fit: contain;" alt="Medical Report">
                    </div>
                `;
            } else if (fileExtension === 'pdf') {
                // PDF file
                document.getElementById('file-content').innerHTML = `
                    <iframe src="${filePath}" style="width: 100%; height: 100%; border: none;"></iframe>
                `;
            } else {
                // Other file types
                document.getElementById('file-content').innerHTML = `
                    <div style="text-align: center; padding: 50px; color: #fff;">
                        <h3>File Available</h3>
                        <p>File: ${fileName}</p>
                        <a href="${filePath}" target="_blank" style="color: #2fff8d; text-decoration: none;">
                            <button style="background: #2fff8d; color: #181c2b; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                                Download File
                            </button>
                        </a>
                    </div>
                `;
            }
        }

        document.getElementById('file-modal').classList.add('show');
        document.getElementById('popup-backdrop').classList.add('show');
    }

    // Handle form submission
    document.querySelector('.send-time-popup .popup-form').onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('save_meeting_time.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Meeting time saved successfully!');
                    closeSendTimePopup();
                } else {
                    alert('Error saving meeting time: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving meeting time');
            });
    };
</script>

</html>