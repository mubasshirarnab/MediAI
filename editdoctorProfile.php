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
  </style>
</head>

<body style="background-color: #000117">
  <?php
  session_start();
  require_once 'dbConnect.php';

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
    $name = $_POST['name'] ?? '';
    $specialization = $_POST['specialization'] ?? '';
    $license_number = $_POST['license_number'] ?? '';
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
      $photo = uniqid() . '_' . basename($_FILES['photo']['name']);
      move_uploaded_file($_FILES['photo']['tmp_name'], 'img/' . $photo);
    }

    $conn->begin_transaction();
    try {
      $stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
      $stmt->bind_param("si", $name, $user_id);
      $stmt->execute();

      if ($photo) {
        $stmt = $conn->prepare("UPDATE doctors SET specialization=?, license_number=?, photo=? WHERE user_id=?");
        $stmt->bind_param("sssi", $specialization, $license_number, $photo, $user_id);
      } else {
        $stmt = $conn->prepare("UPDATE doctors SET specialization=?, license_number=? WHERE user_id=?");
        $stmt->bind_param("ssi", $specialization, $license_number, $user_id);
      }
      $stmt->execute();

      // Degrees
      $conn->query("DELETE FROM qualifications WHERE user_id=$user_id");
      $degrees = json_decode($_POST['degrees'], true);
      foreach ($degrees as $deg) {
        $stmt = $conn->prepare("INSERT INTO qualifications (user_id, qualification, institute, year_obtained) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $deg['degree'], $deg['institute'], $deg['year']);
        $stmt->execute();
      }

      // Expertise
      $conn->query("DELETE FROM expertise WHERE user_id=$user_id");
      $expertises = json_decode($_POST['expertises'], true);
      foreach ($expertises as $exp) {
        $stmt = $conn->prepare("INSERT INTO expertise (user_id, expertise_name) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $exp);
        $stmt->execute();
      }

      // Availability
      $conn->query("DELETE FROM available_hours WHERE user_id=$user_id");
      $days = isset($_POST['days']) ? explode(',', $_POST['days']) : [];
      $start_time = $_POST['start_time'] ?? '';
      $end_time = $_POST['end_time'] ?? '';
      $duration = $_POST['duration'] ?? '';
      foreach ($days as $day) {
        $day = trim($day);
        if ($day !== '' && $start_time && $end_time) {
          $stmt = $conn->prepare("INSERT INTO available_hours (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
          $stmt->bind_param("iiss", $user_id, $day, $start_time, $end_time);
          $stmt->execute();
          // Optionally, you can store duration in a separate table or as a comment/extra field if needed
        }
      }

      // Pricing
      $conn->query("DELETE FROM pricing WHERE user_id=$user_id");
      $pricing = [
        ['type' => 'Standard', 'value' => $_POST['price_standard'] ?? null],
        ['type' => 'Second Visit', 'value' => $_POST['price_second'] ?? null],
        ['type' => 'Report Checkup', 'value' => $_POST['price_report'] ?? null],
      ];
      foreach ($pricing as $p) {
        if ($p['value'] !== null && $p['value'] !== '') {
          $stmt = $conn->prepare("INSERT INTO pricing (user_id, service_type, price) VALUES (?, ?, ?)");
          $stmt->bind_param("isd", $user_id, $p['type'], $p['value']);
          $stmt->execute();
        }
      }

      $conn->commit();
      $response['success'] = true;
    } catch (Exception $e) {
      $conn->rollback();
      $response['message'] = $e->getMessage();
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
  }
  ?>
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


        </div>

        <div class="column">
          <h3>Availability</h3>
          <label>Weekly days (e.g. 1,2,3 for Mon,Tue,Wed)</label>
          <input type="text" name="days" placeholder="0=Sun, 1=Mon, ..." />
          <label>Start Time</label>
          <input type="time" name="start_time" />
          <label>End Time</label>
          <input type="time" name="end_time" />
          <label>Duration per Patient (minutes)</label>
          <input type="number" name="duration" />

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