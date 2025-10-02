<?php
session_start();
require_once 'dbConnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    try {
        $hospital_id = $_SESSION['user_id'];


        $conn->begin_transaction();


        $target_dir = "img/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $photo = '';
        if (isset($_FILES["doctor_photo"]) && $_FILES["doctor_photo"]["error"] == 0) {
            $file_extension = pathinfo($_FILES["doctor_photo"]["name"], PATHINFO_EXTENSION);
            $photo = uniqid() . "." . $file_extension;
            $target_file = $target_dir . $photo;

            if ($_FILES["doctor_photo"]["size"] > 5000000) {
                throw new Exception("Sorry, your file is too large.");
            }

            $allowed_types = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                throw new Exception("Sorry, only JPG, JPEG & PNG files are allowed.");
            }

            if (!move_uploaded_file($_FILES["doctor_photo"]["tmp_name"], $target_file)) {
                throw new Exception("Sorry, there was an error uploading your file.");
            }
        }

        $query = "INSERT INTO users (name, email, password, phone, role_id, status, otp) 
                 VALUES (?, ?, ?, ?, 2, 'authorized', 0)";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt->bind_param(
            "ssss",
            $_POST['name'],
            $_POST['generatedEmail'],
            $hashed_password,
            $_POST['phone']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting user: " . $stmt->error);
        }

        $doctor_id = $conn->insert_id;

        $query = "INSERT INTO doctor_hospital (doctor_id, hospital_id, created_at) 
                 VALUES (?, ?, NOW())";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $doctor_id, $hospital_id);

        if (!$stmt->execute()) {
            throw new Exception("Error linking doctor to hospital: " . $stmt->error);
        }


        $result = $conn->query("SELECT MAX(CAST(license_number AS SIGNED)) as max_license FROM doctors");
        $row = $result->fetch_assoc();
        $next_license = ($row['max_license'] ?? 0) + 1;


        $query = "INSERT INTO doctors (user_id, specialization, license_number, photo, available) 
                 VALUES (?, ?, ?, ?, 1)";

        $stmt = $conn->prepare($query);
        $stmt->bind_param(
            "isss",
            $doctor_id,
            $_POST['specialization'],
            $next_license,
            $photo
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting doctor details: " . $stmt->error);
        }


        $conn->commit();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Doctor added successfully']);
        exit;
    } catch (Exception $e) {
        $conn->rollback();

        if (isset($target_file) && file_exists($target_file)) {
            unlink($target_file);
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];


$query = "SELECT u.id, u.name, u.email, u.phone, d.photo, d.specialization 
          FROM users u 
          JOIN doctor_hospital dh ON u.id = dh.doctor_id 
          JOIN doctors d ON u.id = d.user_id
          WHERE dh.hospital_id = ? AND u.role_id = 2";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $hospital_id);
$stmt->execute();
$result = $stmt->get_result();
$doctors = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - MediAI</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #1a1c2e;
            color: #fff;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }

        .add-doctor-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-doctor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .doctor-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .doctor-info {
            margin-bottom: 15px;
        }

        .doctor-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 10px;
        }

        .doctor-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #667eea;
            color: white;
        }

        .edit-btn:hover {
            background: #5a6fe0;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #1a1c2e;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #fff;
        }

        .generate-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
            width: 100%;
            transition: all 0.3s ease;
        }

        .generate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .doctor-image {
            width: 100%;
            height: 400px;
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
        }

        .doctor-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .doctor-card:hover .doctor-image img {
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Manage Doctors</h1>
            <button class="add-doctor-btn" onclick="openModal()">
                <i class="fas fa-plus"></i> Add New Doctor
            </button>
        </div>

        <!-- Add Doctor Modal -->
        <div id="addDoctorModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()">&times;</span>
                <h2 style="margin-bottom: 20px;">Add New Doctor</h2>

                <form id="addDoctorForm" method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" id="doctorName" required>
                    </div>

                    <button type="button" class="generate-btn" onclick="generateCredentials()">
                        Generate Email & Password
                    </button>

                    <div class="form-group">
                        <label>Generated Email</label>
                        <input type="email" id="generatedEmail" readonly>
                    </div>

                    <div class="form-group">
                        <label>Generated Password</label>
                        <input type="text" id="generatedPassword" readonly>
                    </div>

                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" required>
                    </div>

                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" name="specialization" required>
                    </div>

                    <div class="form-group">
                        <label>License Number</label>
                        <input type="text" name="license_number" required>
                    </div>

                    <div class="form-group">
                        <label>Doctor's Photo</label>
                        <input type="file" name="doctor_photo" accept="image/*" required>
                    </div>

                    <button type="submit" class="add-doctor-btn" style="width: 100%;">
                        Add Doctor
                    </button>
                </form>
            </div>
        </div>

        <div class="doctors-grid">
            <?php if (empty($doctors)): ?>
                <div class="no-doctors">
                    <i class="fas fa-user-md" style="font-size: 3rem; color: #ccc; margin-bottom: 15px;"></i>
                    <h2>No Doctors Found</h2>
                    <p>Start by adding doctors to your hospital</p>
                </div>
            <?php else: ?>
                <?php foreach ($doctors as $doctor): ?>
                    <div class="doctor-card">
                        <?php if ($doctor['photo']): ?>
                            <div class="doctor-image">
                                <img src="img/<?php echo htmlspecialchars($doctor['photo']); ?>" alt="Doctor's photo">
                            </div>
                        <?php endif; ?>
                        <div class="doctor-info">
                            <div class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['name']); ?></div>
                            <div class="doctor-detail">
                                <i class="fas fa-envelope"></i>
                                <?php echo htmlspecialchars($doctor['email']); ?>
                            </div>
                            <div class="doctor-detail">
                                <i class="fas fa-phone"></i>
                                <?php echo htmlspecialchars($doctor['phone']); ?>
                            </div>
                        </div>
                        <div class="actions">
                            <button class="action-btn edit-btn" onclick="location.href='edit_doctor.php?id=<?php echo $doctor['id']; ?>'">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="confirmDelete(<?php echo $doctor['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('addDoctorModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('addDoctorModal').style.display = 'none';
        }


        function generateCredentials() {
            const fullName = document.getElementById('doctorName').value.trim();

            if (!fullName) {
                alert('Please enter the doctor\'s name first!');
                return;
            }


            const nameParts = fullName.toLowerCase().split(' ');

            if (nameParts.length < 2) {
                alert('Please enter both first and last name!');
                return;
            }

            const firstInitial = nameParts[0][0];
            const lastName = nameParts[nameParts.length - 1];

            const randomNum = Math.floor(1000 + Math.random() * 9000);


            const email = `${firstInitial}${lastName}${randomNum}@mediai.com`;


            const password = Math.random().toString(36).slice(-8);

            document.getElementById('generatedEmail').value = email;
            document.getElementById('generatedPassword').value = password;
        }

        window.onclick = function(event) {
            const modal = document.getElementById('addDoctorModal');
            if (event.target == modal) {
                closeModal();
            }
        }


        function confirmDelete(doctorId) {
            if (confirm('Are you sure you want to remove this doctor?')) {
                window.location.href = `delete_doctor.php?id=${doctorId}`;
            }
        }

        document.getElementById('addDoctorForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('generatedEmail', document.getElementById('generatedEmail').value);
            formData.append('password', document.getElementById('generatedPassword').value);

            fetch('manage_doctors.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert('Doctor added successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error: Something went wrong!');
                });
        });
    </script>
</body>

</html>