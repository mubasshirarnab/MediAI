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
            background: #f0f2f5;
            color: #333;
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
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .add-doctor-btn {
            background: #4CAF50;
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
            background: #45a049;
            transform: translateY(-2px);
        }

        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .doctor-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .doctor-info {
            margin-bottom: 15px;
        }

        .doctor-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .doctor-detail {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            margin-bottom: 5px;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
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
            background: #2196F3;
            color: white;
        }

        .edit-btn:hover {
            background: #1976D2;
        }

        .delete-btn {
            background: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background: #d32f2f;
        }

        .no-doctors {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Manage Doctors</h1>
            <button class="add-doctor-btn" onclick="location.href='add_doctor.php'">
                <i class="fas fa-plus"></i> Add New Doctor
            </button>
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
        function confirmDelete(doctorId) {
            if (confirm('Are you sure you want to remove this doctor?')) {
                window.location.href = `delete_doctor.php?id=${doctorId}`;
            }
        }
    </script>
</body>

</html>