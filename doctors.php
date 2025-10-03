<?php
session_start();
require_once 'dbConnect.php';

// Get all authorized doctors
$query = "SELECT u.id, u.name, d.specialization, d.photo, d.license_number
          FROM users u
          JOIN doctors d ON u.id = d.user_id
          WHERE u.role_id = (SELECT id FROM roles WHERE role_name = 'doctor')
          AND u.status = 'authorized'
          ORDER BY u.name";
$result = $conn->query($query);
$doctors = [];

if ($result) {
  while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
  }
}

// Get selected doctor's details (default to first doctor if none selected)
$selected_id = isset($_GET['id']) ? $_GET['id'] : ($doctors[0]['id'] ?? null);

if ($selected_id) {
  // Get detailed doctor info
  $query = "SELECT u.id, u.name, u.email, u.phone, d.specialization, d.license_number, d.photo
              FROM users u
              JOIN doctors d ON u.id = d.user_id
              WHERE u.id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $selected_id);
  $stmt->execute();
  $doctor = $stmt->get_result()->fetch_assoc();

  // Get expertise
  $exp_query = "SELECT expertise_name FROM expertise WHERE user_id = ?";
  $stmt = $conn->prepare($exp_query);
  $stmt->bind_param("i", $selected_id);
  $stmt->execute();
  $exp_result = $stmt->get_result();
  $expertise = [];
  while ($row = $exp_result->fetch_assoc()) {
    $expertise[] = $row['expertise_name'];
  }

  // Get qualifications
  $qual_query = "SELECT qualification, institute, year_obtained 
                   FROM qualifications 
                   WHERE user_id = ? 
                   ORDER BY year_obtained DESC";
  $stmt = $conn->prepare($qual_query);
  $stmt->bind_param("i", $selected_id);
  $stmt->execute();
  $qual_result = $stmt->get_result();
  $qualifications = [];
  while ($row = $qual_result->fetch_assoc()) {
    $qualifications[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doctors | MEDIAi</title>
  <!-- <link rel="stylesheet" href="css/doctors.css" /> -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet" />
  <style>
    /* doctors.css */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Inter", sans-serif;
    }

    body {
      min-height: 100vh;
      background: linear-gradient(135deg,
          #000117 0%,
          #000117 50%,
          #000117 100%);
      color: #fff;
    }

    .doctors-main-layout {
      display: flex;
      max-width: 1400px;
      margin: 40px auto;
      gap: 0;
    }

    /* Sidebar */
    .doctors-sidebar {
      width: 350px;
      background: #10142a;
      border-radius: 20px;
      box-shadow: 0 0 40px 0 #4221ff33;
      border: 2px solid #fff2;
      display: flex;
      flex-direction: column;

      position: relative;
      left: -100px;
      max-height: 80vh;
      /* z-index: 10; */
    }

    .doctors-title {
      font-size: 2.1rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      padding-left: 40px;
      padding-top: 30px;
    }

    .doctors-list {
      overflow-y: auto;
      padding-right: 1rem;
    }

    .doctors-list::-webkit-scrollbar {
      width: 6px;
    }

    .doctors-list::-webkit-scrollbar-thumb {
      background: #4221ff66;
      border-radius: 3px;
    }

    .doctor-list-item {
      background: #181d36;
      border-radius: 16px;
      margin-bottom: 1.1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border: 2px solid rgba(66, 33, 255, 0.3);
      box-shadow: 0 0 12px 0 #4221ff33;
      transition: border 0.2s, box-shadow 0.2s;
      margin-left: 30px;
      margin-right: 30px;
    }

    .doctor-list-item:hover {
      border-color: #6e54ff;
      box-shadow: 0 0 16px 0 #6e54ff33;
    }

    .doctor-item-text {
      max-width: 180px;
    }

    .doctor-item-name {
      font-weight: 600;
      font-size: 1.1rem;
      padding-left: 20px;
      padding-top: 10px;
    }

    .doctor-item-sub {
      font-size: 0.95rem;
      color: #bcbcbc;
      margin-top: 0.2rem;
      line-height: 1.2;
    }

    .view-btn {
      background: none;
      border: 2px solid #4221ff;
      color: #fff;
      font-size: 1.05rem;
      padding: 0.3rem 1rem;
      border-radius: 20px;
      box-shadow: 0 0 8px 0 #4221ff44;
      cursor: pointer;
      animation: borderGlow 3s linear infinite;
      transition: background 0.2s, color 0.2s;
    }

    .view-btn:hover {
      background: #4221ff;
    }

    @keyframes borderGlow {

      0%,
      100% {
        border-color: #4221ff;
        box-shadow: 0 0 5px #4221ff;
      }

      25% {
        border-color: #6e54ff;
        box-shadow: 0 0 10px #6e54ff;
      }

      75% {
        border-color: #2b15b3;
        box-shadow: 0 0 10px #2b15b3;
      }
    }

    /* Profile Card */
    .doctor-profile-main {
      flex: 1;
      display: flex;
      justify-content: center;
      padding-left: 2rem;
    }

    .doctor-profile-card {
      background: #10142a;
      border-radius: 24px;
      padding: 2.5rem;
      box-shadow: 0 0 40px 0 #4221ff33;
      border: 2px solid #fff2;
      max-width: 900px;
      position: relative;
      top: -90px;
      width: 100%;
    }

    .doctor-profile-flex {
      display: flex;
      gap: 2.5rem;
    }

    .doctor-profile-img-wrap {
      flex: 1 1 320px;
      display: flex;
      justify-content: center;
    }

    .doctor-profile-img {
      width: 320px;
      height: 420px;
      object-fit: cover;
      border-radius: 18px;
      box-shadow: 0 0 30px 0 #4221ff33;
      border: 2px solid #fff2;
      background: #222;
    }

    .doctor-profile-info {
      flex: 2 1 400px;
      display: flex;
      flex-direction: column;
    }

    .doctor-profile-name {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .doctor-profile-special {
      font-size: 1.2rem;
      color: #b3b3b3;
      margin-bottom: 0.7rem;
    }

    .doctor-profile-rating {
      font-size: 1.1rem;
      margin-bottom: 1.2rem;
    }

    .stars {
      color: #ffd700;
      font-size: 1.2rem;
      letter-spacing: 1px;
    }

    .rating-score {
      color: #b3b3b3;
      font-size: 1rem;
    }

    .doctor-profile-section {
      margin-bottom: 1.2rem;
    }

    .doctor-profile-section-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 0.3rem;
    }

    .doctor-profile-list {
      margin-left: 1.2rem;
      color: #b3b3b3;
      font-size: 1rem;
    }

    .doctor-profile-list li {
      margin-bottom: 0.2rem;
    }

    .book-appointment-btn {
      margin-top: 1.5rem;
      padding: 0.8rem 2.2rem;
      font-size: 1.1rem;
      font-weight: 600;
      color: #fff;
      background: linear-gradient(90deg, #4221ff 0%, #8f5cff 100%);
      border: none;
      border-radius: 30px;
      box-shadow: 0 0 18px 0 #4221ff77;
      cursor: pointer;
      transition: background 0.2s, box-shadow 0.2s;
    }

    .book-appointment-btn:hover {
      background: linear-gradient(90deg, #8f5cff 0%, #4221ff 100%);
      box-shadow: 0 0 28px 0 #8f5cffcc;
    }

    /* Responsive */
    @media (max-width: 1100px) {
      .doctors-main-layout {
        flex-direction: column;
      }

      .doctors-sidebar {
        width: 100%;
        border-radius: 18px 18px 0 0;
        max-height: 300px;
      }

      .doctor-profile-flex {
        flex-direction: column;
        align-items: center;
      }

      .doctor-profile-img {
        width: 220px;
        height: 300px;
      }

      .doctor-profile-info {
        align-items: center;
        text-align: center;
        padding-top: 1rem;
      }
    }

    .doctors-container {
      display: flex;
      min-height: calc(100vh - 80px);
      background: #0a0c1b;
      color: #fff;
    }

    .doctors-list {
      width: 300px;
      background: #000117;
      padding: 20px;
      border-right: 4px solid transparent;
      border-image: linear-gradient(to bottom, transparent 0%, whitesmoke 20%, whitesmoke 80%, transparent 100%);
      border-image-slice: 1;
    }

    .doctor-name {
      padding: 15px;
      margin: 5px 0;
      cursor: pointer;
      border-radius: 8px;
      transition: background 0.3s;
    }

    .doctor-name:hover {
      background: #1a1d3a;
    }

    .doctor-name.active {
      background: #1a1d3a;
      border-left: 4px solid #7f5fff;
    }

    .doctor-details {
      flex: 1;
      padding: 40px;
      background: #000117;
    }

    .doctor-header {
      display: flex;
      gap: 30px;
      margin-bottom: 40px;
    }

    .doctor-image {
      width: 200px;
      height: 200px;
      border-radius: 50%;
      object-fit: cover;
    }

    .doctor-info h2 {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    .doctor-info p {
      color: #b0b0b0;
      margin: 5px 0;
    }

    .section {
      margin: 30px 0;
    }

    .section h3 {
      color: #7f5fff;
      margin-bottom: 15px;
    }

    .expertise-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .expertise-tag {
      background: #1a1d3a;
      padding: 8px 15px;
      border-radius: 20px;
      font-size: 0.9rem;
    }

    .qualification-item {
      background: #1a1d3a;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 10px;
    }

    .qualification-item h4 {
      color: #7f5fff;
      margin-bottom: 5px;
    }

    .custom-sidebar {
      background: #07081a;
      min-height: 76vh;
      padding-left: 50px;
      border-radius: 0 20px 20px 0;
      box-shadow: none;
      border: none;
      width: 370px;
      display: flex;
      flex-direction: column;
    }

    .custom-title {
      font-size: 2.3rem;
      font-weight: 800;
      color: #fff;
      margin-bottom: 0.5rem;
      margin-top: 0.3rem;
      margin-left: 32px;
      font-family: 'Inter', sans-serif;
    }

    .custom-list {
      flex: 1;
      overflow-y: auto;
      padding-right: 0.5rem;
      padding-left: 0.5rem;
      padding-bottom: 2rem;
      max-height: calc(100vh - 120px);
    }

    .custom-doctor-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: transparent;
      border-radius: 50px;
      border: 2px solid rgba(44, 11, 233, 0.47);


      box-shadow: 0 0 18px 0rgba(44, 11, 233, 0.47);
      margin-bottom: 1.5rem;
      padding: 0.7rem 2.2rem 0.7rem 1.2rem;

    }

    .custom-doctor-item:hover {
      box-shadow: 0 0 28px 0 #8f5cffcc;
      cursor: pointer;

    }

    .custom-doctor-name {
      font-size: 1.25rem;
      font-weight: 700;
      color: #fff;
      font-family: 'Inter', sans-serif;
    }

    .custom-view-btn {
      background: none;
      border: none;
      color: #fff;
      font-size: 1.05rem;
      font-weight: 500;
      font-family: 'Inter', sans-serif;
      cursor: pointer;
      outline: none;
      transition: color 0.2s;
    }

    .custom-view-btn:hover {
      color: #8f5cff;
    }

    @media (max-width: 900px) {
      .custom-sidebar {
        width: 100%;
        min-height: unset;
        border-radius: 0;
      }
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
    }

    .modal-content {
      background: #10142a;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 20px;
      width: 90%;
      max-width: 600px;
      border: 2px solid #4221ff;
      box-shadow: 0 0 30px 0 #4221ff33;
      max-height: 80vh;
      overflow-y: auto;
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s;
    }

    .close:hover {
      color: #4221ff;
    }

    .modal-content h2 {
      color: #fff;
      margin-bottom: 1.5rem;
      text-align: center;
      font-size: 1.8rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #fff;
      font-weight: 500;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.8rem;
      border: 2px solid #4221ff33;
      border-radius: 10px;
      background: #181d36;
      color: #fff;
      font-size: 1rem;
      transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #4221ff;
      box-shadow: 0 0 10px 0 #4221ff33;
    }

    .form-group small {
      color: #b3b3b3;
      font-size: 0.85rem;
      margin-top: 0.3rem;
      display: block;
    }

    .submit-btn {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(90deg, #4221ff 0%, #8f5cff 100%);
      color: #fff;
      border: none;
      border-radius: 15px;
      font-size: 1.1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 1rem;
    }

    .submit-btn:hover {
      background: linear-gradient(90deg, #8f5cff 0%, #4221ff 100%);
      box-shadow: 0 0 20px 0 #8f5cff66;
    }

    .submit-btn:disabled {
      background: #666;
      cursor: not-allowed;
    }

    /* Loading spinner */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #ffffff33;
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
      margin-right: 10px;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <iframe src="navbar.php" frameborder="0" style="width: 100%; height: 70px;"></iframe>
  <?php

  include_once 'navbar.php';
  ?>
  <div class="doctors-main-layout">
    <!-- Left Sidebar: Doctors List -->
    <aside class="doctors-sidebar custom-sidebar">
      <div class="doctors-title custom-title">Doctors</div>
      <div class="doctors-list custom-list" id="doctors-list">
        <?php foreach ($doctors as $doc): ?>
          <div class="custom-doctor-item">
            <span class="custom-doctor-name"><?php echo htmlspecialchars($doc['name']); ?></span>
            <button class="custom-view-btn" onclick="window.location.href='?id=<?php echo $doc['id']; ?>'">View</button>
          </div>
        <?php endforeach; ?>
      </div>
    </aside>

    <!-- Main Profile Card -->
    <main class="doctor-profile-main">
      <?php if (isset($doctor)): ?>
        <div class="doctor-profile-card" style="margin-top: 80px">
          <div class="doctor-profile-flex">
            <div class="doctor-profile-img-wrap">
              <img src="img/<?php echo htmlspecialchars($doctor['photo']); ?>"
                alt="<?php echo htmlspecialchars($doctor['name']); ?>"
                class="doctor-profile-img" />
            </div>
            <div class="doctor-profile-info">
              <div class="doctor-profile-name"><?php echo htmlspecialchars($doctor['name']); ?></div>
              <div class="doctor-profile-special">
                Specialist in <?php echo htmlspecialchars($doctor['specialization']); ?>
              </div>
              <div class="doctor-profile-rating">
                Patient Rating:
                <span class="stars">★★★★★</span>
                <span class="rating-score">(4.8/5)</span>
              </div>

              <div class="doctor-profile-section">
                <div class="doctor-profile-section-title">Expertise</div>
                <ul class="doctor-profile-list">
                  <?php foreach ($expertise as $exp): ?>
                    <li><?php echo htmlspecialchars($exp); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <div class="doctor-profile-section">
                <div class="doctor-profile-section-title">Qualification</div>
                <ul class="doctor-profile-list">
                  <?php foreach ($qualifications as $qual): ?>
                    <li><?php echo htmlspecialchars($qual['qualification']); ?>: <?php echo htmlspecialchars($qual['institute']); ?> (<?php echo htmlspecialchars($qual['year_obtained']); ?>)</li>
                  <?php endforeach; ?>
                </ul>
              </div>

              <button type="button" class="book-appointment-btn" onclick="openAppointmentModal(<?php echo htmlspecialchars($doctor['id']); ?>, '<?php echo htmlspecialchars($doctor['name']); ?>')">Book Appointment</button>

            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <!-- Appointment Booking Modal -->
  <div id="appointmentModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeAppointmentModal()">&times;</span>
      <h2>Book Appointment</h2>
      <form id="appointmentForm" enctype="multipart/form-data">
        <input type="hidden" id="doctor_id" name="doctor_id">

        <div class="form-group">
          <label for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" required>
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" required>
        </div>

        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
          <label for="appointment_day">Preferred Day</label>
          <select id="appointment_day" name="appointment_day" required>
            <option value="">Select Day</option>
          </select>
        </div>

        <div class="form-group">
          <label for="appointment_time">Preferred Time Slot</label>
          <select id="appointment_time" name="appointment_time" required>
            <option value="">Select Time</option>
          </select>
        </div>

        <div class="form-group">
          <label for="reason">Reason for Visit</label>
          <textarea id="reason" name="reason" rows="4" required></textarea>
        </div>

        <div class="form-group">
          <label for="medical_report">Upload Medical Reports (Optional)</label>
          <input type="file" id="medical_report" name="medical_report" accept=".pdf,.jpg,.jpeg,.png">
          <small>Accepted formats: PDF, JPG, PNG</small>
        </div>

        <button type="submit" class="submit-btn">Confirm Appointment</button>
      </form>
    </div>
  </div>

  <script>
    // Get current user information from session
    let currentUser = null;
    <?php if (isset($_SESSION['user_id'])): ?>
      <?php
      // Get phone number from database
      $phone_number = '';
      if (isset($_SESSION['user_id'])) {
        $phone_query = "SELECT phone FROM users WHERE id = ?";
        $phone_stmt = $conn->prepare($phone_query);
        $phone_stmt->bind_param("i", $_SESSION['user_id']);
        $phone_stmt->execute();
        $phone_result = $phone_stmt->get_result();
        if ($phone_row = $phone_result->fetch_assoc()) {
          $phone_number = $phone_row['phone'];
        }
      }
      ?>
      currentUser = {
        id: <?php echo $_SESSION['user_id']; ?>,
        name: '<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>',
        email: '<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>',
        phone: '<?php echo htmlspecialchars($phone_number); ?>'
      };
    <?php endif; ?>

    function openAppointmentModal(doctorId, doctorName) {
      document.getElementById('doctor_id').value = doctorId;
      document.getElementById('appointmentModal').style.display = 'block';

      // Pre-fill user information if available
      if (currentUser) {
        document.getElementById('full_name').value = currentUser.name;
        document.getElementById('email').value = currentUser.email;
        document.getElementById('phone').value = currentUser.phone;
      }

      // Load available dates and times
      loadAvailableDates(doctorId);
    }

    function closeAppointmentModal() {
      document.getElementById('appointmentModal').style.display = 'none';
      document.getElementById('appointmentForm').reset();
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('appointmentModal');
      if (event.target === modal) {
        closeAppointmentModal();
      }
    }

    function loadAvailableDates(doctorId) {
      fetch(`get_available_slots.php?doctor_id=${doctorId}`)
        .then(response => response.json())
        .then(data => {
          const daySelect = document.getElementById('appointment_day');
          const timeSelect = document.getElementById('appointment_time');

          // Clear existing options
          daySelect.innerHTML = '<option value="">Select Day</option>';
          timeSelect.innerHTML = '<option value="">Select Time</option>';

          if (data.success && data.available_days) {
            // Populate days
            data.available_days.forEach(day => {
              const option = document.createElement('option');
              option.value = day.day_number;
              option.textContent = day.day_name;
              daySelect.appendChild(option);
            });
          }
        })
        .catch(error => {
          console.error('Error loading available days:', error);
        });
    }

    // Load time slots when day is selected
    document.getElementById('appointment_day').addEventListener('change', function() {
      const doctorId = document.getElementById('doctor_id').value;
      const selectedDay = this.value;
      const timeSelect = document.getElementById('appointment_time');

      if (selectedDay) {
        fetch(`get_available_slots.php?doctor_id=${doctorId}&selected_day=${selectedDay}`)
          .then(response => response.json())
          .then(data => {
            timeSelect.innerHTML = '<option value="">Select Time</option>';

            if (data.success && data.time_slots) {
              data.time_slots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.time;
                option.textContent = slot.time_formatted;
                timeSelect.appendChild(option);
              });
            }
          })
          .catch(error => {
            console.error('Error loading time slots:', error);
          });
      }
    });

    // Handle form submission
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
      e.preventDefault();

      const submitBtn = this.querySelector('.submit-btn');
      const originalText = submitBtn.textContent;

      // Show loading state
      submitBtn.innerHTML = '<span class="loading"></span>Booking Appointment...';
      submitBtn.disabled = true;

      const formData = new FormData(this);

      fetch('book_appointment.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Appointment booked successfully!');
            closeAppointmentModal();
          } else {
            alert('Error: ' + (data.message || 'Failed to book appointment'));
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error: Failed to book appointment');
        })
        .finally(() => {
          // Reset button
          submitBtn.innerHTML = originalText;
          submitBtn.disabled = false;
        });
    });
  </script>
</body>

</html>