<?php
session_start();
require_once 'dbConnect.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];


$query = "SELECT u.id, u.name, u.email, u.phone 
          FROM users u 
          JOIN doctor_hospital dh ON u.id = dh.doctor_id 
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
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
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

                <button class="generate-btn" onclick="generateCredentials()">
                    Generate Email & Password
                </button>

                <form id="addDoctorForm">
                    <div class="form-group">
                        <label>Generated Email</label>
                        <input type="email" id="generatedEmail" readonly>
                    </div>

                    <div class="form-group">
                        <label>Generated Password</label>
                        <input type="text" id="generatedPassword" readonly>
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
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
            const timestamp = Date.now().toString().slice(-4);
            const randomNum = Math.floor(Math.random() * 1000);
            const email = `doctor${timestamp}${randomNum}@mediai.com`;
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
    </script>
</body>

</html>