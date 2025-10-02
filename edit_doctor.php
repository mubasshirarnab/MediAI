<?php

session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

$doctor_id = isset($_GET['id']) ? $_GET['id'] : 0;
$hospital_id = $_SESSION['user_id'];

// Check if the doctor belongs to this hospital
$query = "SELECT u.*, d.specialization, d.license_number, d.photo 
          FROM users u 
          JOIN doctors d ON u.id = d.user_id
          JOIN doctor_hospital dh ON u.id = dh.doctor_id 
          WHERE u.id = ? AND dh.hospital_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $doctor_id, $hospital_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    header("Location: manage_doctors.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Update users table
        $update_user = "UPDATE users SET name = ?, phone = ? WHERE id = ?";
        $stmt = $conn->prepare($update_user);
        $stmt->bind_param("ssi", $_POST['name'], $_POST['phone'], $doctor_id);
        $stmt->execute();

        // Handle photo upload if new photo is provided
        $photo = $doctor['photo'];
        if (isset($_FILES["doctor_photo"]) && $_FILES["doctor_photo"]["error"] == 0) {
            $target_dir = "uploads/doctors/";
            $file_extension = pathinfo($_FILES["doctor_photo"]["name"], PATHINFO_EXTENSION);
            $photo = uniqid() . "." . $file_extension;
            $target_file = $target_dir . $photo;

            if ($_FILES["doctor_photo"]["size"] > 5000000) {
                throw new Exception("File is too large");
            }

            $allowed_types = ['jpg', 'jpeg', 'png'];
            if (!in_array(strtolower($file_extension), $allowed_types)) {
                throw new Exception("Invalid file type");
            }

            move_uploaded_file($_FILES["doctor_photo"]["tmp_name"], $target_file);

            // Delete old photo
            if ($doctor['photo'] && file_exists("uploads/doctors/" . $doctor['photo'])) {
                unlink("uploads/doctors/" . $doctor['photo']);
            }
        }

        // Update doctors table
        $update_doctor = "UPDATE doctors SET specialization = ?, license_number = ?, photo = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_doctor);
        $stmt->bind_param("sssi", $_POST['specialization'], $_POST['license_number'], $photo, $doctor_id);
        $stmt->execute();

        $conn->commit();
        header("Location: manage_doctors.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor - MediAI</title>
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
            min-height: 100vh;
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

        .header h1 {
            color: #fff;
            font-size: 1.8rem;
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
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .add-doctor-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .edit-form {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .form-group input[disabled] {
            background: rgba(255, 255, 255, 0.05);
            cursor: not-allowed;
        }

        .form-group input[type="file"] {
            padding: 8px;
            background: transparent;
            border: 1px dashed rgba(255, 255, 255, 0.2);
            cursor: pointer;
        }

        .form-group p {
            margin-top: 8px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        button[type="submit"] {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .edit-form {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Edit Doctor</h1>
            <a href="manage_doctors.php" class="add-doctor-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="edit-form">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($doctor['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Email (Cannot be changed)</label>
                    <input type="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" readonly disabled>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Specialization</label>
                    <input type="text" name="specialization" value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                </div>

                <div class="form-group">
                    <label>License Number</label>
                    <input type="text" name="license_number" value="<?php echo htmlspecialchars($doctor['license_number']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Doctor's Photo</label>
                    <input type="file" name="doctor_photo" accept="image/*">
                    <?php if ($doctor['photo']): ?>
                        <p>Current photo: <?php echo htmlspecialchars($doctor['photo']); ?></p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="add-doctor-btn" style="width: 100%;">
                    Update Doctor
                </button>
            </form>
        </div>
    </div>
</body>

</html>