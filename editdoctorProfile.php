<?php
session_start();
require_once 'dbConnect.php';

// Turn on MySQLi exceptions for clearer error reporting during saves
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'doctor') {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Fetch doctor info
$query = "SELECT u.name, d.specialization, d.license_number, d.photo FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $response = ['success' => false, 'message' => ''];
  $user_id = $_SESSION['user_id'];

  // Update users and doctors
  $name = isset($_POST['name']) ? trim($_POST['name']) : null;
  $specialization = isset($_POST['specialization']) ? trim($_POST['specialization']) : null;
  $license_number = isset($_POST['license_number']) ? trim($_POST['license_number']) : null;
  $photo = null;
  if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $photo = uniqid() . '_' . basename($_FILES['photo']['name']);
    move_uploaded_file($_FILES['photo']['tmp_name'], 'img/' . $photo);
  }

  $conn->begin_transaction();
  try {
    $insertedQualifications = 0;
    $insertedExpertise = 0;
    $insertedPricing = 0;

    if ($name !== null && $name !== '') {
      $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
      $stmt->bind_param("si", $name, $user_id);
      $stmt->execute();
    }

    if (($specialization !== null && $specialization !== '') || ($license_number !== null && $license_number !== '') || $photo) {
      if ($photo) {
        $stmt = $conn->prepare("UPDATE doctors SET specialization=COALESCE(NULLIF(?, ''), specialization), license_number=COALESCE(NULLIF(?, ''), license_number), photo=? WHERE user_id=?");
        $stmt->bind_param("sssi", $specialization, $license_number, $photo, $user_id);
      } else {
        $stmt = $conn->prepare("UPDATE doctors SET specialization=COALESCE(NULLIF(?, ''), specialization), license_number=COALESCE(NULLIF(?, ''), license_number) WHERE user_id=?");
        $stmt->bind_param("ssi", $specialization, $license_number, $user_id);
      }
      $stmt->execute();
    }

    // Degrees
    if (isset($_POST['degrees'])) {
      $degrees = json_decode($_POST['degrees'], true);
      if (is_array($degrees)) {
        $stmt = $conn->prepare("DELETE FROM qualifications WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        foreach ($degrees as $deg) {
          $qualification = isset($deg['degree']) ? $deg['degree'] : null;
          $institute = isset($deg['institute']) ? $deg['institute'] : null;
          $year = isset($deg['year']) ? $deg['year'] : null;
          if ($qualification && $institute && $year) {
            $stmt = $conn->prepare("INSERT INTO qualifications (user_id, qualification, institute, year_obtained) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $qualification, $institute, $year);
            $stmt->execute();
            $insertedQualifications++;
          }
        }
      }
    }

    // Expertise
    if (isset($_POST['expertises'])) {
      $expertises = json_decode($_POST['expertises'], true);
      if (is_array($expertises)) {
        $stmt = $conn->prepare("DELETE FROM expertise WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        foreach ($expertises as $exp) {
          $expName = is_string($exp) ? trim($exp) : null;
          if ($expName) {
            $stmt = $conn->prepare("INSERT INTO expertise (user_id, expertise_name) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $expName);
            $stmt->execute();
            $insertedExpertise++;
          }
        }
      }
    }

    // Pricing
    $stmt = $conn->prepare("DELETE FROM pricing WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $pricing = [
      ['type' => 'Standard', 'value' => $_POST['price_standard'] ?? null],
      ['type' => 'Second Visit', 'value' => $_POST['price_second'] ?? null],
      ['type' => 'Report Checkup', 'value' => $_POST['price_report'] ?? null],
    ];
    foreach ($pricing as $p) {
      if ($p['value'] !== null && $p['value'] !== '') {
        $priceValue = is_numeric($p['value']) ? (float)$p['value'] : null;
        if ($priceValue !== null) {
          $stmt = $conn->prepare("INSERT INTO pricing (user_id, service_type, price) VALUES (?, ?, ?)");
          $stmt->bind_param("isd", $user_id, $p['type'], $priceValue);
          $stmt->execute();
          $insertedPricing++;
        }
      }
    }

    // Handle Availability Schedule
    if (isset($_POST['schedule'])) {
      $schedule = json_decode($_POST['schedule'], true);
      if (is_array($schedule)) {
        // Get hospital_id from the schedule
        $hospital_id = $schedule[0]['hospital_id'] ?? null;

        if ($hospital_id) {
          // Delete existing schedule for this doctor and hospital
          $stmt = $conn->prepare("DELETE FROM available_hours WHERE user_id = ? AND hospital_id = ?");
          $stmt->bind_param("ii", $user_id, $hospital_id);
          $stmt->execute();

          // Insert new schedule
          $stmt = $conn->prepare("INSERT INTO available_hours (user_id, hospital_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?)");

          foreach ($schedule as $slot) {
            if (!empty($slot['start_time']) && !empty($slot['end_time'])) {
              $stmt->bind_param(
                "iiiss",
                $user_id,
                $hospital_id,
                $slot['day_of_week'],
                $slot['start_time'],
                $slot['end_time']
              );
              $stmt->execute();
            }
          }
        }
      }
    }

    $conn->commit();
    $response['success'] = true;
    $response['inserted'] = [
      'qualifications' => $insertedQualifications,
      'expertise' => $insertedExpertise,
      'pricing' => $insertedPricing
    ];
  } catch (Throwable $e) {
    $conn->rollback();
    $response['message'] = $e->getMessage();
  }
  header('Content-Type: application/json');
  echo json_encode($response);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Doctor Profile</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap"
    rel="stylesheet" />
  <link rel="stylesheet" href="css/doctorProfile.css" />
  <style>
    .upload-btn {
      display: inline-block;
      background: #471cc8;
      color: #fff;
      border-radius: 8px;
      padding: 0.5rem 1.2rem;
      font-size: 1rem;
      cursor: pointer;
      margin: 10px 0;
      transition: background 0.3s;
    }

    .upload-btn:hover {
      background: #5a2de0;
    }

    .upload-btn input[type='file'] {
      display: none;
    }

    .hospital-schedule {
      background: rgba(255, 255, 255, 0.05);
      border-radius: 15px;
      padding: 25px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .hospital-select {
      margin-bottom: 25px;
    }

    .hospital-select label {
      display: block;
      color: #667eea;
      font-size: 0.9rem;
      margin-bottom: 8px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .hospital-select select {
      width: 100%;
      padding: 12px 15px;
      background: rgba(255, 255, 255, 0.07);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      color: #fff;
      font-size: 1rem;
      transition: all 0.3s ease;
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 15px center;
      background-size: 16px;
    }

    .hospital-select select:hover,
    .hospital-select select:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
    }

    .hospital-select select option {
      background: #1a1c2e;
      color: #fff;
      padding: 10px;
    }

    .days-selection {
      display: grid;
      gap: 12px;
      margin-top: 20px;
    }

    .days-selection h4 {
      color: #667eea;
      margin-bottom: 15px;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .day-slot {
      background: rgba(255, 255, 255, 0.03);
      padding: 15px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.3s ease;
    }

    .day-slot:hover {
      background: rgba(255, 255, 255, 0.05);
      border-color: rgba(255, 255, 255, 0.1);
    }

    .day-slot label {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #fff;
      font-size: 0.95rem;
      cursor: pointer;
    }

    .day-slot input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #667eea;
      cursor: pointer;
    }

    .time-inputs {
      margin-top: 12px;
      display: flex;
      align-items: center;
      gap: 15px;
      padding-left: 28px;
    }

    .time-inputs input[type="time"] {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 6px;
      color: #fff;
      padding: 8px 12px;
      font-size: 0.9rem;
      width: 130px;
      transition: all 0.3s ease;
    }

    .time-inputs input[type="time"]:hover,
    .time-inputs input[type="time"]:focus {
      border-color: #667eea;
      outline: none;
    }

    .time-inputs span {
      color: rgba(255, 255, 255, 0.6);
      font-size: 0.9rem;
    }

    .save-schedule-btn {
      background: linear-gradient(45deg, #667eea, #764ba2);
      color: white;
      padding: 12px 25px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 25px;
      width: 100%;
      font-size: 1rem;
      font-weight: 500;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
    }

    .save-schedule-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
    }

    .save-schedule-btn:active {
      transform: translateY(0);
    }
  </style>
</head>

<body style="background-color: #000117">
  <iframe
    src="navbar.php"
    frameborder="0"
    style="width: 100%; height: 70px"></iframe>
  <?php

  include_once 'navbar.php';
  ?>
  <main>
    <form id="profile-form" enctype="multipart/form-data" method="POST">
      <div class="profile-header">
        <img id="profile-img" src="img/<?php echo htmlspecialchars($doctor['photo'] ?? 'default.png'); ?>" alt="Doctor" />
        <label class="upload-btn">
          <input type="file" id="img-upload" name="photo" accept="image/*" style="display:none;" />
          <span>Upload Image</span>
        </label>
        <div>
          <p><strong>Name:</strong> <?php echo htmlspecialchars($doctor['name']); ?></p>
          <p><strong>Specialization:</strong> <?php echo htmlspecialchars($doctor['specialization']); ?></p>
          <p><strong>License Number:</strong> <?php echo htmlspecialchars($doctor['license_number']); ?></p>
        </div>
      </div>

      <div class="form-section">
        <div class="column">
          <h3>Education</h3>
          <div id="degree-list"></div>
          <div class="degree-inputs">
            <input required type="text" id="degree" placeholder="Degree" />
            <input required type="text" id="institute" placeholder="Institute" />
            <input required type="text" id="discipline" placeholder="Discipline" />
            <input required type="text" id="year" placeholder="Year" class="small-input" />
            <button type="button" id="add-degree">+ Add Degree</button>
          </div>

          <h3>Expertise</h3>
          <div id="expertise-list"></div>
          <div class="expertise-inputs">
            <input type="text" id="expertise" placeholder="Expertise" />
            <button type="button" id="add-expertise">+ Add Expertise</button>
          </div>

          <h3 style="margin-top: 40px">Pricing</h3>
          <div class="pricing">
            <div>
              <label>Standard</label><input type="text" name="price_standard" class="small-input" />
            </div>
            <div>
              <label>Second Visit</label><input type="text" name="price_second" class="small-input" />
            </div>
            <div>
              <label>Report Checkup</label><input type="text" name="price_report" class="small-input" />
            </div>
          </div>

        </div>

        <div class="column">
          <h3>Availability</h3>
          <div class="hospital-schedule">
            <div class="hospital-select">
              <label>Select Hospital</label>
              <select id="hospitalSelect">
                <option value="">Choose Hospital</option>
              </select>
            </div>

            <div id="scheduleContainer" style="display: none;">
              <div class="days-selection">
                <h4>Select Days & Time</h4>
                <?php
                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                foreach ($days as $index => $day):
                ?>
                  <div class="day-slot">
                    <label>
                      <input type="checkbox" class="day-checkbox" value="<?php echo $index; ?>">
                      <?php echo $day; ?>
                    </label>
                    <div class="time-inputs" style="display: none;">
                      <input type="time" class="start-time">
                      <span>to</span>
                      <input type="time" class="end-time">
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <button type="button" class="save-schedule-btn">Save Schedule</button>
            </div>
          </div>
        </div>
      </div>

      <div class="submit-btn">
        <button type="submit">Save</button>
      </div>
    </form>
  </main>

  <script>
    let degrees = [];
    let expertises = [];

    // Image preview
    document.getElementById('img-upload').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
          document.getElementById('profile-img').src = evt.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    // Degrees
    function renderDegrees() {
      const list = document.getElementById('degree-list');
      list.innerHTML = degrees.map((d, i) =>
        `<div>${d.degree} - ${d.institute} - ${d.discipline} - ${d.year} <button type="button" onclick="removeDegree(${i})">x</button></div>`
      ).join('');
    }
    window.removeDegree = function(i) {
      degrees.splice(i, 1);
      renderDegrees();
    };
    document.getElementById('add-degree').onclick = function() {
      const degree = document.getElementById('degree').value.trim();
      const institute = document.getElementById('institute').value.trim();
      const discipline = document.getElementById('discipline').value.trim();
      const year = document.getElementById('year').value.trim();
      if (degree && institute && year) {
        degrees.push({
          degree,
          institute,
          discipline,
          year
        });
        document.getElementById('degree').value = '';
        document.getElementById('institute').value = '';
        document.getElementById('discipline').value = '';
        document.getElementById('year').value = '';
        renderDegrees();
      }
    };

    // Expertise
    function renderExpertises() {
      const list = document.getElementById('expertise-list');
      list.innerHTML = expertises.map((e, i) =>
        `<div>${e} <button type="button" onclick="removeExpertise(${i})">x</button></div>`
      ).join('');
    }
    window.removeExpertise = function(i) {
      expertises.splice(i, 1);
      renderExpertises();
    };
    document.getElementById('add-expertise').onclick = function() {
      const exp = document.getElementById('expertise').value.trim();
      if (exp) {
        expertises.push(exp);
        document.getElementById('expertise').value = '';
        renderExpertises();
      }
    };

    document.addEventListener('DOMContentLoaded', function() {
      // Fetch associated hospitals
      fetch('get_doctor_hospitals.php')
        .then(res => res.json())
        .then(hospitals => {
          const select = document.getElementById('hospitalSelect');
          hospitals.forEach(hospital => {
            const option = document.createElement('option');
            option.value = hospital.id;
            option.textContent = hospital.name;
            select.appendChild(option);
          });
        });

      // Hospital select change handler
      document.getElementById('hospitalSelect').addEventListener('change', function() {
        const hospitalId = this.value;
        document.getElementById('scheduleContainer').style.display = hospitalId ? 'block' : 'none';

        if (hospitalId) {
          // Fetch existing schedule for this hospital
          fetch(`get_doctor_schedule.php?hospital_id=${hospitalId}`)
            .then(res => res.json())
            .then(schedule => {
              // Reset all checkboxes and times
              document.querySelectorAll('.day-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.day-slot').querySelector('.time-inputs').style.display = 'none';
              });

              // Set saved schedules
              schedule.forEach(slot => {
                const daySlot = document.querySelector(`.day-checkbox[value="${slot.day_of_week}"]`).closest('.day-slot');
                daySlot.querySelector('.day-checkbox').checked = true;
                daySlot.querySelector('.time-inputs').style.display = 'flex';
                daySlot.querySelector('.start-time').value = slot.start_time;
                daySlot.querySelector('.end-time').value = slot.end_time;
              });
            });
        }
      });

      // Day checkbox change handler
      document.querySelectorAll('.day-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
          const timeInputs = this.closest('.day-slot').querySelector('.time-inputs');
          timeInputs.style.display = this.checked ? 'flex' : 'none';
        });
      });

      // Save schedule button handler
      document.querySelector('.save-schedule-btn').addEventListener('click', function() {
        const hospitalId = document.getElementById('hospitalSelect').value;
        if (!hospitalId) {
          alert('Please select a hospital');
          return;
        }

        const schedule = [];
        document.querySelectorAll('.day-checkbox:checked').forEach(checkbox => {
          const daySlot = checkbox.closest('.day-slot');
          const startTime = daySlot.querySelector('.start-time').value;
          const endTime = daySlot.querySelector('.end-time').value;

          if (!startTime || !endTime) {
            alert('Please set both start and end time for selected days');
            return;
          }

          schedule.push({
            day_of_week: parseInt(checkbox.value),
            start_time: startTime,
            end_time: endTime,
            hospital_id: parseInt(hospitalId)
          });
        });

        if (schedule.length === 0) {
          alert('Please select at least one day and set time');
          return;
        }

        // Add schedule to the main form data
        const formData = new FormData(document.getElementById('profile-form'));
        formData.append('schedule', JSON.stringify(schedule));

        // Send to server
        fetch('editdoctorProfile.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              alert('Schedule updated successfully!');
            } else {
              alert('Error updating schedule: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Error updating schedule');
          });
      });
    });

    // On Save: collect all data and send via AJAX
    document.getElementById('profile-form').onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(this);
      formData.append('degrees', JSON.stringify(degrees));
      formData.append('expertises', JSON.stringify(expertises));
      fetch('editdoctorProfile.php', {
          method: 'POST',
          body: formData
        }).then(res => res.json())
        .then(data => {
          alert(data.success ? 'Profile updated!' : 'Error: ' + data.message);
          if (data.success) window.location.href = 'doctor_dashboard.php';
        });
    };
  </script>
</body>

</html>