<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/../dbConnect.php';

function send_json($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function require_method($method) {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        send_json(['success' => false, 'error' => 'Method not allowed'], 405);
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_json_body() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

// Ensure admin has access
if (!is_admin()) {
    send_json(['success' => false, 'error' => 'Unauthorized'], 401);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'users_list':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            // Get filter parameters
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            $blocked = $_GET['blocked'] ?? '';
            
            // Build WHERE clause
            $where_conditions = [];
            $params = [];
            $param_types = '';
            
            if ($search) {
                $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ?)";
                $search_param = "%$search%";
                $params[] = $search_param;
                $params[] = $search_param;
                $param_types .= 'ss';
            }
            
            if ($role) {
                $where_conditions[] = "r.role_name = ?";
                $params[] = $role;
                $param_types .= 's';
            }
            
            if ($status) {
                $where_conditions[] = "u.status = ?";
                $params[] = $status;
                $param_types .= 's';
            }
            
            if ($blocked !== '') {
                $where_conditions[] = "u.is_blocked = ?";
                $params[] = intval($blocked);
                $param_types .= 'i';
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $sql = "SELECT u.id, u.name as username, u.email, r.role_name as role, u.created_at, u.status as is_verified, u.is_blocked 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id
                    $where_clause
                    ORDER BY u.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $param_types .= 'ii';
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($param_types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            
            // Get total count with same filters
            $count_sql = "SELECT COUNT(*) as total FROM users u JOIN roles r ON u.role_id = r.id $where_clause";
            $count_stmt = $conn->prepare($count_sql);
            if (!empty($where_conditions)) {
                $count_params = array_slice($params, 0, -2); // Remove limit and offset
                $count_param_types = substr($param_types, 0, -2); // Remove 'ii'
                $count_stmt->bind_param($count_param_types, ...$count_params);
            }
            $count_stmt->execute();
            $total = $count_stmt->get_result()->fetch_assoc()['total'];
            
            send_json([
                'success' => true,
                'data' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        case 'user_update':
            require_method('POST');
            $body = get_json_body();
            $user_id = intval($body['user_id'] ?? 0);
            $role = $body['role'] ?? null;
            $is_verified = isset($body['is_verified']) ? intval($body['is_verified']) : null;
            
            if ($user_id <= 0) send_json(['success' => false, 'error' => 'Invalid user ID'], 400);
            
            $fields = [];
            $params = [];
            $types = '';
            
            if ($role !== null && in_array($role, ['patient', 'doctor', 'hospital', 'admin'])) {
                $fields[] = 'role = ?';
                $params[] = $role;
                $types .= 's';
            }
            
            if ($is_verified !== null) {
                $fields[] = 'is_verified = ?';
                $params[] = $is_verified;
                $types .= 'i';
            }
            
            if (empty($fields)) send_json(['success' => false, 'error' => 'No fields to update'], 400);
            
            $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
            $stmt = $conn->prepare($sql);
            $types .= 'i';
            $params[] = $user_id;
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'user_delete':
            require_method('POST');
            $body = get_json_body();
            $user_id = intval($body['user_id'] ?? 0);
            
            if ($user_id <= 0) send_json(['success' => false, 'error' => 'Invalid user ID'], 400);
            
            // Prevent admin from deleting themselves
            if ($user_id == $_SESSION['user_id']) {
                send_json(['success' => false, 'error' => 'Cannot delete your own account'], 400);
            }
            
            // Start transaction to handle cascading deletes
            $conn->begin_transaction();
            try {
                // Get user role to determine which related tables to clean
                $role_stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
                $role_stmt->bind_param('i', $user_id);
                $role_stmt->execute();
                $role_result = $role_stmt->get_result();
                
                if ($role_result->num_rows === 0) {
                    throw new Exception('User not found');
                }
                
                $user_role = $role_result->fetch_assoc()['role_id'];
                
                // Delete from ALL role-specific tables (user might have records in multiple tables)
                $conn->query("DELETE FROM patients WHERE user_id = $user_id");
                $conn->query("DELETE FROM medication WHERE user_id = $user_id");
                $conn->query("DELETE FROM medications WHERE user_id = $user_id");
                $conn->query("DELETE FROM doctors WHERE user_id = $user_id");
                $conn->query("DELETE FROM available_hours WHERE user_id = $user_id");
                $conn->query("DELETE FROM doctor_hospital WHERE doctor_id = $user_id");
                $conn->query("DELETE FROM expertise WHERE user_id = $user_id");
                $conn->query("DELETE FROM qualifications WHERE user_id = $user_id");
                $conn->query("DELETE FROM pricing WHERE user_id = $user_id");
                $conn->query("DELETE FROM hospitals WHERE user_id = $user_id");
                $conn->query("DELETE FROM inventory_items WHERE hospital_id = $user_id");
                $conn->query("DELETE FROM inventory_stock WHERE item_id IN (SELECT id FROM inventory_items WHERE hospital_id = $user_id)");
                $conn->query("DELETE FROM inventory_transactions WHERE hospital_id = $user_id");
                $conn->query("DELETE FROM admins WHERE user_id = $user_id");
                
                // Delete from general user-related tables
                $conn->query("DELETE FROM ai_conversations WHERE user_id = $user_id");
                $conn->query("DELETE FROM chatbot_queries WHERE user_id = $user_id");
                $conn->query("DELETE FROM cabin_bookings WHERE user_id = $user_id");
                $conn->query("DELETE FROM cabin_bookings_legacy WHERE patient_id = $user_id");
                $conn->query("DELETE FROM appointments WHERE patient_id = $user_id OR doctor_id = $user_id");
                $conn->query("DELETE FROM bills WHERE patient_id = $user_id");
                $conn->query("DELETE FROM feedback WHERE patient_id = $user_id OR doctor_id = $user_id");
                $conn->query("DELETE FROM disease_predictions WHERE patient_id = $user_id");
                $conn->query("DELETE FROM risk_predictions WHERE patient_id = $user_id");
                $conn->query("DELETE FROM posts WHERE post_creator = $user_id");
                $conn->query("DELETE FROM post_likes WHERE user_id = $user_id");
                $conn->query("DELETE FROM comments WHERE commentor = $user_id");
                $conn->query("DELETE FROM community WHERE community_creator = $user_id");
                $conn->query("DELETE FROM community_members WHERE user_id = $user_id");
                $conn->query("DELETE FROM medication_times WHERE medication_id IN (SELECT id FROM medication WHERE user_id = $user_id)");
                $conn->query("DELETE FROM medication_reminders_sent WHERE medication_id IN (SELECT id FROM medication WHERE user_id = $user_id)");
                $conn->query("DELETE FROM time_for_meeting WHERE patient_id = $user_id OR doctor_id = $user_id");
                $conn->query("DELETE FROM meeting_code WHERE patient_id = $user_id OR doctor_id = $user_id");
                $conn->query("DELETE FROM video_meeting WHERE patient_id = $user_id OR doctor_id = $user_id");
                
                // Finally delete the user
                $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
                $stmt->bind_param('i', $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to delete user: ' . $stmt->error);
                }
                
                $conn->commit();
                send_json(['success' => true]);
                
            } catch (Exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => $e->getMessage()], 500);
            }

        case 'hospitals_list':
            require_method('GET');
            $sql = "SELECT h.*, u.name as username, u.email, u.status as is_verified 
                    FROM hospitals h 
                    JOIN users u ON h.user_id = u.id 
                    ORDER BY u.created_at DESC";
            $result = $conn->query($sql);
            
            $hospitals = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $hospitals[] = $row;
                }
            }
            
            send_json(['success' => true, 'data' => $hospitals]);

        case 'doctors_list':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            try {
                // Simplified query first to test
                $sql = "SELECT d.*, u.name as doctor_name, u.email, u.phone, u.is_blocked, u.status as is_verified,
                               'Multiple Hospitals' as hospitals
                        FROM doctors d 
                        JOIN users u ON d.user_id = u.id
                        ORDER BY u.created_at DESC 
                        LIMIT ? OFFSET ?";
                
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception('Prepare failed: ' . $conn->error);
                }
                
                $stmt->bind_param('ii', $limit, $offset);
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed: ' . $stmt->error);
                }
                
                $result = $stmt->get_result();
                $doctors = [];
                while ($row = $result->fetch_assoc()) {
                    $doctors[] = $row;
                }
                
                // Get total count
                $count_stmt = $conn->query("SELECT COUNT(*) as total FROM doctors d JOIN users u ON d.user_id = u.id");
                if (!$count_stmt) {
                    throw new Exception('Count query failed: ' . $conn->error);
                }
                $total = $count_stmt->fetch_assoc()['total'];
                
                send_json([
                    'success' => true,
                    'data' => $doctors,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            } catch (Exception $e) {
                send_json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }

        case 'doctor_add':
            require_method('POST');
            $body = get_json_body();
            
            $name = trim($body['name'] ?? '');
            $email = trim($body['email'] ?? '');
            $password = trim($body['password'] ?? '');
            $phone = trim($body['phone'] ?? '');
            $specialization = trim($body['specialization'] ?? '');
            $license_number = trim($body['license_number'] ?? '');
            $hospital_ids = $body['hospital_ids'] ?? [];
            
            if (empty($name) || empty($email) || empty($password) || empty($specialization)) {
                send_json(['success' => false, 'error' => 'All required fields must be filled'], 400);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                send_json(['success' => false, 'error' => 'Invalid email format'], 400);
            }
            
            // Check if email already exists
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->bind_param('s', $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                send_json(['success' => false, 'error' => 'Email already exists'], 400);
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $conn->begin_transaction();
            try {
                // Create user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role_id, status) VALUES (?, ?, ?, ?, 2, 'authorized')");
                $stmt->bind_param('ssss', $name, $email, $hashed_password, $phone);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $user_id = $conn->insert_id;
                
                // Create doctor profile
                $stmt = $conn->prepare("INSERT INTO doctors (user_id, specialization, license_number, photo, available) VALUES (?, ?, ?, 'default.jpg', 1)");
                $stmt->bind_param('iss', $user_id, $specialization, $license_number);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                // Assign to hospitals
                if (!empty($hospital_ids)) {
                    foreach ($hospital_ids as $hospital_id) {
                        $stmt = $conn->prepare("INSERT INTO doctor_hospital (doctor_id, hospital_id) VALUES (?, ?)");
                        $stmt->bind_param('ii', $user_id, $hospital_id);
                        if (!$stmt->execute()) throw new Exception($stmt->error);
                    }
                }
                
                $conn->commit();
                send_json(['success' => true, 'data' => ['user_id' => $user_id]]);
            } catch (Exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => $e->getMessage()], 500);
            }

        case 'doctor_update':
            require_method('POST');
            $body = get_json_body();
            $doctor_id = intval($body['doctor_id'] ?? 0);
            
            if ($doctor_id <= 0) send_json(['success' => false, 'error' => 'Invalid doctor ID'], 400);
            
            $name = trim($body['name'] ?? '');
            $email = trim($body['email'] ?? '');
            $phone = trim($body['phone'] ?? '');
            $specialization = trim($body['specialization'] ?? '');
            $license_number = trim($body['license_number'] ?? '');
            $hospital_ids = $body['hospital_ids'] ?? [];
            
            if (empty($name) || empty($email) || empty($specialization)) {
                send_json(['success' => false, 'error' => 'All required fields must be filled'], 400);
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                send_json(['success' => false, 'error' => 'Invalid email format'], 400);
            }
            
            $conn->begin_transaction();
            try {
                // Update user info
                $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->bind_param('sssi', $name, $email, $phone, $doctor_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                // Update doctor profile
                $stmt = $conn->prepare("UPDATE doctors SET specialization = ?, license_number = ? WHERE user_id = ?");
                $stmt->bind_param('ssi', $specialization, $license_number, $doctor_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                // Update hospital assignments
                $stmt = $conn->prepare("DELETE FROM doctor_hospital WHERE doctor_id = ?");
                $stmt->bind_param('i', $doctor_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                if (!empty($hospital_ids)) {
                    foreach ($hospital_ids as $hospital_id) {
                        $stmt = $conn->prepare("INSERT INTO doctor_hospital (doctor_id, hospital_id) VALUES (?, ?)");
                        $stmt->bind_param('ii', $doctor_id, $hospital_id);
                        if (!$stmt->execute()) throw new Exception($stmt->error);
                    }
                }
                
                $conn->commit();
                send_json(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => $e->getMessage()], 500);
            }

        case 'doctor_delete':
            require_method('POST');
            $body = get_json_body();
            $doctor_id = intval($body['doctor_id'] ?? 0);
            
            if ($doctor_id <= 0) send_json(['success' => false, 'error' => 'Invalid doctor ID'], 400);
            
            $conn->begin_transaction();
            try {
                // Delete doctor-hospital relationships
                $stmt = $conn->prepare("DELETE FROM doctor_hospital WHERE doctor_id = ?");
                $stmt->bind_param('i', $doctor_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                // Delete doctor profile
                $stmt = $conn->prepare("DELETE FROM doctors WHERE user_id = ?");
                $stmt->bind_param('i', $doctor_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param('i', $doctor_id);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                $conn->commit();
                send_json(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => $e->getMessage()], 500);
            }

        case 'doctor_block':
            require_method('POST');
            $body = get_json_body();
            $doctor_id = intval($body['doctor_id'] ?? 0);
            $action = $body['action'] ?? ''; // 'block' or 'unblock'
            
            if ($doctor_id <= 0) send_json(['success' => false, 'error' => 'Invalid doctor ID'], 400);
            if (!in_array($action, ['block', 'unblock'])) send_json(['success' => false, 'error' => 'Invalid action'], 400);
            
            // Prevent admin from blocking themselves
            if ($doctor_id == $_SESSION['user_id']) {
                send_json(['success' => false, 'error' => 'Cannot block your own account'], 400);
            }
            
            $is_blocked = ($action === 'block') ? 1 : 0;
            $stmt = $conn->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
            $stmt->bind_param('ii', $is_blocked, $doctor_id);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'hospitals_for_doctors':
            require_method('GET');
            $sql = "SELECT h.user_id as id, h.name, h.address FROM hospitals h JOIN users u ON h.user_id = u.id WHERE u.is_blocked = 0 ORDER BY h.name";
            $result = $conn->query($sql);
            
            $hospitals = [];
            while ($row = $result->fetch_assoc()) {
                $hospitals[] = $row;
            }
            
            send_json(['success' => true, 'data' => $hospitals]);

        case 'doctor_specializations':
            require_method('GET');
            $sql = "SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization";
            $result = $conn->query($sql);
            
            $specializations = [];
            while ($row = $result->fetch_assoc()) {
                $specializations[] = $row['specialization'];
            }
            
            send_json(['success' => true, 'data' => $specializations]);

        case 'doctor_schedule_add':
            require_method('POST');
            $body = get_json_body();
            $doctor_id = intval($body['doctor_id'] ?? 0);
            $day_of_week = intval($body['day_of_week'] ?? 0);
            $start_time = trim($body['start_time'] ?? '');
            $end_time = trim($body['end_time'] ?? '');
            
            if ($doctor_id <= 0 || $day_of_week < 1 || $day_of_week > 7) {
                send_json(['success' => false, 'error' => 'Invalid doctor ID or day of week'], 400);
            }
            
            if (empty($start_time) || empty($end_time)) {
                send_json(['success' => false, 'error' => 'Start and end times are required'], 400);
            }
            
            $stmt = $conn->prepare("INSERT INTO available_hours (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiss', $doctor_id, $day_of_week, $start_time, $end_time);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'doctor_schedule_list':
            require_method('GET');
            $doctor_id = intval($_GET['doctor_id'] ?? 0);
            
            if ($doctor_id <= 0) send_json(['success' => false, 'error' => 'Invalid doctor ID'], 400);
            
            $sql = "SELECT * FROM available_hours WHERE user_id = ? ORDER BY day_of_week, start_time";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $doctor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $schedules = [];
            while ($row = $result->fetch_assoc()) {
                $schedules[] = $row;
            }
            
            send_json(['success' => true, 'data' => $schedules]);

        case 'doctor_schedule_delete':
            require_method('POST');
            $body = get_json_body();
            $schedule_id = intval($body['schedule_id'] ?? 0);
            
            if ($schedule_id <= 0) send_json(['success' => false, 'error' => 'Invalid schedule ID'], 400);
            
            $stmt = $conn->prepare("DELETE FROM available_hours WHERE id = ?");
            $stmt->bind_param('i', $schedule_id);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'system_stats':
            require_method('GET');
            
            // Get comprehensive statistics
            $stats = [];
            
            // User statistics
            $result = $conn->query("SELECT COUNT(*) as count FROM users");
            $stats['total_users'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'patient'");
            $stats['total_patients'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'doctor'");
            $stats['total_doctors'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'hospital'");
            $stats['total_hospitals'] = $result->fetch_assoc()['count'];
            
            // Appointment statistics
            $result = $conn->query("SELECT COUNT(*) as count FROM appointments");
            $stats['total_appointments'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE timeslot > NOW()");
            $stats['pending_appointments'] = $result->fetch_assoc()['count'];
            
            // Cabin statistics
            $result = $conn->query("SELECT COUNT(*) as count FROM cabins");
            $stats['total_cabins'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM cabin_bookings WHERE status = 'booked' AND check_in <= CURDATE() AND check_out >= CURDATE()");
            $stats['occupied_cabins'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM cabin_bookings WHERE status = 'booked'");
            $stats['active_bookings'] = $result->fetch_assoc()['count'];
            
            // Community statistics
            $result = $conn->query("SELECT COUNT(*) as count FROM posts");
            $stats['total_posts'] = $result->fetch_assoc()['count'];
            
            $result = $conn->query("SELECT COUNT(*) as count FROM comments");
            $stats['total_comments'] = $result->fetch_assoc()['count'];
            
            // Blocked users
            $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'unauthorized'");
            $stats['blocked_users'] = $result->fetch_assoc()['count'];
            
            send_json(['success' => true, 'data' => $stats]);

        case 'recent_activity':
            require_method('GET');
            
            $activities = [];
            
            // Recent user registrations
            $sql = "SELECT 'user_registration' as type, username, created_at 
                    FROM users 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY created_at DESC 
                    LIMIT 10";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            }
            
            // Recent bookings
            $sql = "SELECT 'booking' as type, 
                           CONCAT('Booking #', booking_id) as username, 
                           booking_date as created_at 
                    FROM cabin_bookings 
                    WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY booking_date DESC 
                    LIMIT 10";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $activities[] = $row;
                }
            }
            
            // Sort by date
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            send_json(['success' => true, 'data' => array_slice($activities, 0, 20)]);

        case 'system_settings':
            require_method('GET');
            // For now, return default settings
            $settings = [
                'site_name' => 'MediAI',
                'maintenance_mode' => false,
                'registration_enabled' => true,
                'email_verification_required' => true
            ];
            send_json(['success' => true, 'data' => $settings]);

        case 'update_settings':
            require_method('POST');
            $body = get_json_body();
            // For now, just return success - implement actual settings storage later
            send_json(['success' => true]);

        case 'user_block':
            require_method('POST');
            $body = get_json_body();
            $user_id = intval($body['user_id'] ?? 0);
            $action = $body['action'] ?? ''; // 'block' or 'unblock'
            
            if ($user_id <= 0) send_json(['success' => false, 'error' => 'Invalid user ID'], 400);
            if (!in_array($action, ['block', 'unblock'])) send_json(['success' => false, 'error' => 'Invalid action'], 400);
            
            // Prevent admin from blocking themselves
            if ($user_id == $_SESSION['user_id']) {
                send_json(['success' => false, 'error' => 'Cannot block your own account'], 400);
            }
            
            $is_blocked = ($action === 'block') ? 1 : 0;
            $stmt = $conn->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
            $stmt->bind_param('ii', $is_blocked, $user_id);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'appointments_list':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT a.*, 
                           p.name as patient_name, p.email as patient_email,
                           d.name as doctor_name, d.email as doctor_email,
                           h.hospital_name
                    FROM appointments a
                    LEFT JOIN users p ON a.patient_id = p.id
                    LEFT JOIN users d ON a.doctor_id = d.id
                    LEFT JOIN hospitals h ON d.id = h.user_id
                    ORDER BY a.id DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $appointments = [];
            while ($row = $result->fetch_assoc()) {
                $appointments[] = $row;
            }
            
            // Get total count
            $count_stmt = $conn->query("SELECT COUNT(*) as total FROM appointments");
            $total = $count_stmt->fetch_assoc()['total'];
            
            send_json([
                'success' => true, 
                'data' => $appointments,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        case 'cabin_bookings_list':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT cb.*, 
                           u.name as user_name, u.email as user_email,
                           c.cabin_number, c.type, c.price
                    FROM cabin_bookings cb
                    JOIN users u ON cb.user_id = u.id
                    JOIN cabins c ON cb.cabin_id = c.cabin_id
                    ORDER BY cb.booking_date DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $bookings = [];
            while ($row = $result->fetch_assoc()) {
                $bookings[] = $row;
            }
            
            // Get total count
            $count_stmt = $conn->query("SELECT COUNT(*) as total FROM cabin_bookings");
            $total = $count_stmt->fetch_assoc()['total'];
            
            send_json([
                'success' => true, 
                'data' => $bookings,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        case 'update_booking_status':
            require_method('POST');
            $body = get_json_body();
            $booking_id = intval($body['booking_id'] ?? 0);
            $status = $body['status'] ?? '';
            
            if ($booking_id <= 0) send_json(['success' => false, 'error' => 'Invalid booking ID'], 400);
            if (!in_array($status, ['booked', 'completed', 'cancelled'])) send_json(['success' => false, 'error' => 'Invalid status'], 400);
            
            $stmt = $conn->prepare('UPDATE cabin_bookings SET status = ? WHERE booking_id = ?');
            $stmt->bind_param('si', $status, $booking_id);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'community_posts':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 10);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT p.*, 
                           u.name as creator_name, u.email as creator_email,
                           c.name as community_name
                    FROM posts p
                    JOIN users u ON p.post_creator = u.id
                    JOIN community c ON p.community_id = c.id
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $posts = [];
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
            
            // Get total count
            $count_stmt = $conn->query("SELECT COUNT(*) as total FROM posts");
            $total = $count_stmt->fetch_assoc()['total'];
            
            send_json([
                'success' => true, 
                'data' => $posts,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        case 'delete_post':
            require_method('POST');
            $body = get_json_body();
            $post_id = intval($body['post_id'] ?? 0);
            
            if ($post_id <= 0) send_json(['success' => false, 'error' => 'Invalid post ID'], 400);
            
            $stmt = $conn->prepare('DELETE FROM posts WHERE id = ?');
            $stmt->bind_param('i', $post_id);
            
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'doctor_add':
            require_method('POST');
            $body = get_json_body();
            $name = trim($body['name'] ?? '');
            $email = trim($body['email'] ?? '');
            $password = $body['password'] ?? '';
            $phone = trim($body['phone'] ?? '');
            $specialization = trim($body['specialization'] ?? '');
            $license_number = trim($body['license_number'] ?? '');
            $hospital_id = intval($body['hospital_id'] ?? 0);
            
            if (empty($name) || empty($email) || empty($password)) {
                send_json(['success' => false, 'error' => 'Name, email, and password are required'], 400);
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $conn->begin_transaction();
            try {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role_id, status) VALUES (?, ?, ?, ?, 2, 'authorized')");
                $stmt->bind_param('ssss', $name, $email, $hashed_password, $phone);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                $user_id = $conn->insert_id;
                
                // Insert doctor
                $stmt = $conn->prepare("INSERT INTO doctors (user_id, specialization, license_number, photo, available) VALUES (?, ?, ?, 'default.jpg', 1)");
                $stmt->bind_param('iss', $user_id, $specialization, $license_number);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                // Assign to hospital if specified
                if ($hospital_id > 0) {
                    $stmt = $conn->prepare("INSERT INTO doctor_hospital (doctor_id, hospital_id) VALUES (?, ?)");
                    $stmt->bind_param('ii', $user_id, $hospital_id);
                    if (!$stmt->execute()) throw new Exception($stmt->error);
                }
                
                $conn->commit();
                send_json(['success' => true, 'data' => ['user_id' => $user_id]]);
            } catch (Exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => $e->getMessage()], 500);
            }

        case 'hospital_add':
            require_method('POST');
            $body = get_json_body();
            $name = trim($body['name'] ?? '');
            $email = trim($body['email'] ?? '');
            $password = $body['password'] ?? '';
            $phone = trim($body['phone'] ?? '');
            $registration_number = trim($body['registration_number'] ?? '');
            $location = trim($body['location'] ?? '');
            
            if (empty($name) || empty($email) || empty($password)) {
                send_json(['success' => false, 'error' => 'Name, email, and password are required'], 400);
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $conn->begin_transaction();
            try {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role_id, status) VALUES (?, ?, ?, ?, 3, 'authorized')");
                $stmt->bind_param('ssss', $name, $email, $hashed_password, $phone);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                $user_id = $conn->insert_id;
                
                // Insert hospital
                $stmt = $conn->prepare("INSERT INTO hospitals (user_id, hospital_name, registration_number, location) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isss', $user_id, $name, $registration_number, $location);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                
                $conn->commit();
                send_json(['success' => true, 'data' => ['user_id' => $user_id]]);
            } catch (Exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => $e->getMessage()], 500);
            }

        default:
            send_json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}
