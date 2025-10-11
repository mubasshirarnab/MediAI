<?php
session_start();
require_once 'dbConnect.php';

// Check if user is logged in and is a hospital
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 'hospital') {
    header("Location: login.php");
    exit();
}

// Get hospital information
$user_id = $_SESSION['user_id'];
$query = "SELECT h.*, u.name, u.email, u.phone 
          FROM hospitals h 
          JOIN users u ON h.user_id = u.id 
          WHERE h.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$hospital = $result->fetch_assoc();

// Get today's appointments
$today = date('Y-m-d');
$appointments_query = "SELECT COUNT(*) as total_appointments 
                  FROM appointments a 
                  JOIN doctors d ON a.doctor_id = d.user_id 
                  JOIN users u ON d.user_id = u.id
                  WHERE u.role_id = 2 AND DATE(a.timeslot) = ?";
$stmt = $conn->prepare($appointments_query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $today);
$stmt->execute();
$appointments_count = $stmt->get_result()->fetch_assoc()['total_appointments'];

// Get total doctors
$doctors_query = "SELECT COUNT(*) as total_doctors FROM doctors";
$total_doctors = $conn->query($doctors_query)->fetch_assoc()['total_doctors'];

// Get total patients
$patients_query = "SELECT COUNT(*) as total_patients FROM patients";
$total_patients = $conn->query($patients_query)->fetch_assoc()['total_patients'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - MEDIAi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #000117;
            color: #fff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated background particles */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.15) 0%, transparent 50%);
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-10px, -10px) rotate(1deg); }
            50% { transform: translate(10px, -5px) rotate(-1deg); }
            75% { transform: translate(-5px, 10px) rotate(0.5deg); }
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
            position: relative;
        }

        /* Header Styles */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px 0;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(102, 126, 234, 0.5);
        }

        .header-actions {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .header-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .header-btn.primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .header-btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        }

        .header-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .header-btn:hover::before {
            left: 100%;
        }

        /* Profile Section */
        .profile-section {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .profile-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(102, 126, 234, 0.3);
        }

        .profile-section h2 {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .profile-section h2::before {
            content: '\f0f0';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .profile-item {
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .profile-item:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateX(5px);
        }

        .profile-item strong {
            color: #667eea;
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
            cursor: pointer;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.3);
            border-color: rgba(102, 126, 234, 0.5);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card h3 {
            margin-bottom: 15px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
            opacity: 0.9;
        }

        .stat-card .number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            display: block;
        }

        .stat-card .icon {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 15px;
            opacity: 0.7;
        }

        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.6s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 25px 50px rgba(102, 126, 234, 0.3);
            border-color: rgba(102, 126, 234, 0.5);
        }

        .feature-card:hover::before {
            width: 300px;
            height: 300px;
        }

        .feature-card h3 {
            margin-bottom: 15px;
            color: #fff;
            font-size: 1.3rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }

        .feature-card p {
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
            position: relative;
            z-index: 1;
        }

        .feature-card a {
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            padding: 12px 30px;
            border: 2px solid #667eea;
            border-radius: 25px;
            transition: all 0.3s ease;
            display: inline-block;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        .feature-card a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        .feature-card a:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .feature-card a:hover::before {
            left: 0;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 20px 15px;
            }

            header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }

            header h1 {
                font-size: 2rem;
            }

            .header-actions {
                flex-direction: column;
                gap: 10px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeInUp 0.6s ease forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(45deg, #764ba2, #667eea);
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <header>
            <div style="display: flex; align-items: center; gap: 20px;">
                <a href="javascript:history.back()" class="header-btn secondary" style="padding: 10px 15px; font-size: 0.9rem;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-hospital"></i> Welcome, <?php echo htmlspecialchars($hospital['hospital_name']); ?></h1>
            </div>
            <div class="header-actions">
                <a href="hospitalProfile.html" class="header-btn primary">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="logout.php" class="header-btn secondary">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>

        <div class="profile-section loading">
            <h2>Hospital Profile</h2>
            <div class="profile-grid">
                <div class="profile-item">
                    <strong>Hospital Name</strong>
                    <?php echo htmlspecialchars($hospital['hospital_name']); ?>
                </div>
                <div class="profile-item">
                    <strong>Email Address</strong>
                    <?php echo htmlspecialchars($hospital['email']); ?>
                </div>
                <div class="profile-item">
                    <strong>Phone Number</strong>
                    <?php echo htmlspecialchars($hospital['phone']); ?>
                </div>
                <div class="profile-item">
                    <strong>Registration Number</strong>
                    <?php echo htmlspecialchars($hospital['registration_number']); ?>
                </div>
                <div class="profile-item">
                    <strong>Location</strong>
                    <?php echo htmlspecialchars($hospital['location']); ?>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card loading">
                <i class="fas fa-calendar-check icon"></i>
                <h3>Today's Appointments</h3>
                <div class="number"><?php echo $appointments_count; ?></div>
            </div>
            <div class="stat-card loading">
                <i class="fas fa-user-md icon"></i>
                <h3>Total Doctors</h3>
                <div class="number"><?php echo $total_doctors; ?></div>
            </div>
            <div class="stat-card loading">
                <i class="fas fa-users icon"></i>
                <h3>Total Patients</h3>
                <div class="number"><?php echo $total_patients; ?></div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card loading">
                <i class="fas fa-user-md" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                <h3>Manage Doctors</h3>
                <p>Add, edit, and manage hospital doctors with comprehensive profiles and specializations</p>
                <a href="manage_doctors.php">Manage Doctors</a>
            </div>

            <div class="feature-card loading">
                <i class="fas fa-calendar-alt" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                <h3>Appointments</h3>
                <p>View and manage all appointments with real-time scheduling and patient information</p>
                <a href="book_appointment.php">View Appointments</a>
            </div>

            <div class="feature-card loading">
                <i class="fas fa-boxes" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                <h3>Inventory</h3>
                <p>Manage hospital inventory, track supplies, and monitor stock levels efficiently</p>
                <a href="hospital_inventory.php">Manage Inventory</a>
            </div>

            <div class="feature-card loading">
                <i class="fas fa-chart-line" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                <h3>Reports</h3>
                <p>Access comprehensive hospital reports, analytics, and performance metrics</p>
                <a href="hospital_reports.php">View Reports</a>
            </div>

            <div class="feature-card loading">
                <i class="fas fa-credit-card" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                <h3>Billing</h3>
                <p>Manage hospital billing, payments, and financial transactions with detailed records</p>
                <a href="hospital_billing/billing.php">Manage Billing</a>
            </div>

            <div class="feature-card loading">
                <i class="fas fa-cog" style="font-size: 2.5rem; color: #667eea; margin-bottom: 15px;"></i>
                <h3>Settings</h3>
                <p>Configure hospital settings, preferences, and system parameters</p>
                <a href="hospital_settings.php">Settings</a>
            </div>
        </div>
    </div>

    <script>
        // Add staggered loading animation
        document.addEventListener('DOMContentLoaded', function() {
            const loadingElements = document.querySelectorAll('.loading');
            loadingElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });

            // Add click effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });

            // Add ripple effect to feature cards
            const featureCards = document.querySelectorAll('.feature-card');
            featureCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    const ripple = document.createElement('div');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.3)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.left = e.clientX - this.offsetLeft + 'px';
                    ripple.style.top = e.clientY - this.offsetTop + 'px';
                    ripple.style.width = ripple.style.height = '20px';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>