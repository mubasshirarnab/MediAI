<?php
session_start();
// DEBUG: surface PHP errors during integration. Remove or disable in production.
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/dbConnect.php';

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

function is_hospital() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'hospital';
}

function is_patient() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'patient';
}

function is_authenticated() {
    return isset($_SESSION['user_id']);
}

// Ensure table and upload directory exist
function ensure_schema_and_dirs($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS lab_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        test_name VARCHAR(255) NOT NULL,
        report_file VARCHAR(255) NOT NULL,
        uploaded_by INT NOT NULL,
        uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        report_date DATE NOT NULL,
        INDEX idx_patient (patient_id),
        INDEX idx_uploaded_by (uploaded_by),
        -- Note: In this project, patients are identified by users.id via patients.user_id
        CONSTRAINT fk_lr_patient_user FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        -- Hospitals table uses user_id as its PK
        CONSTRAINT fk_lr_hospital_user FOREIGN KEY (uploaded_by) REFERENCES hospitals(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $uploadDir = __DIR__ . '/uploads/reports';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }
}

ensure_schema_and_dirs($conn);

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'ping':
            require_method('GET');
            send_json(['success' => true, 'time' => date('c')]);

        case 'patients_list':
            require_method('GET');
            if (!is_authenticated() || !is_hospital()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);

            $q = trim($_GET['q'] ?? '');
            $has_reports = isset($_GET['has_reports']) && $_GET['has_reports'] == '1';

            // Base query
            $sql = "SELECT p.user_id as patient_id, u.name, u.email
                    FROM patients p JOIN users u ON p.user_id = u.id";
            $conds = [];
            $types = '';
            $params = [];

            if ($has_reports) {
                $sql .= " INNER JOIN (SELECT DISTINCT patient_id FROM lab_reports) lr ON lr.patient_id = p.user_id";
            }

            if ($q !== '') {
                // If numeric, allow matching exact id as well
                if (ctype_digit($q)) {
                    $conds[] = "(u.id = ? OR u.name LIKE ? OR u.email LIKE ?)";
                    $types .= 'iss';
                    $params[] = intval($q);
                    $like = '%' . $q . '%';
                    $params[] = $like; $params[] = $like;
                } else {
                    $conds[] = "(u.name LIKE ? OR u.email LIKE ?)";
                    $types .= 'ss';
                    $like = '%' . $q . '%';
                    $params[] = $like; $params[] = $like;
                }
            }

            if ($conds) { $sql .= ' WHERE ' . implode(' AND ', $conds); }
            $sql .= ' ORDER BY u.name ASC LIMIT 200';

            if ($types) {
                $stmt = $conn->prepare($sql);
                if (!$stmt) send_json(['success' => false, 'error' => $conn->error], 500);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
            } else {
                $res = $conn->query($sql);
            }

            $rows = [];
            if ($res) while ($r = $res->fetch_assoc()) { $rows[] = $r; }
            send_json(['success' => true, 'data' => $rows]);

        case 'patient_reports':
            require_method('GET');
            if (!is_authenticated() || !is_hospital()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $patient_id = intval($_GET['patient_id'] ?? 0);
            if ($patient_id <= 0) send_json(['success' => false, 'error' => 'Invalid patient_id'], 400);
            $stmt = $conn->prepare("SELECT id, test_name, report_file, uploaded_by, uploaded_at, report_date FROM lab_reports WHERE patient_id = ? ORDER BY report_date DESC, uploaded_at DESC");
            $stmt->bind_param('i', $patient_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = [];
            while ($row = $res->fetch_assoc()) { $rows[] = $row; }
            send_json(['success' => true, 'data' => $rows]);

        case 'my_reports':
            require_method('GET');
            if (!is_authenticated() || !is_patient()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $user_id = intval($_SESSION['user_id']);
            $stmt = $conn->prepare("SELECT id, test_name, report_file, uploaded_at, report_date FROM lab_reports WHERE patient_id = ? ORDER BY report_date DESC, uploaded_at DESC");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $rs = $stmt->get_result();
            $rows = [];
            while ($row = $rs->fetch_assoc()) { $rows[] = $row; }
            send_json(['success' => true, 'data' => $rows]);

        case 'upload_report':
            require_method('POST');
            if (!is_authenticated() || !is_hospital()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);

            // Validate inputs
            $patient_id = intval($_POST['patient_id'] ?? 0);
            $test_name = trim($_POST['test_name'] ?? '');
            $report_date = trim($_POST['report_date'] ?? '');
            if ($patient_id <= 0 || $test_name === '' || $report_date === '') {
                send_json(['success' => false, 'error' => 'Missing required fields'], 400);
            }
            // Normalize report_date: accept YYYY-MM-DD or DD/MM/YYYY
            $normalized_date = $report_date;
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $report_date)) {
                // Convert DD/MM/YYYY -> YYYY-MM-DD
                [$dd,$mm,$yy] = explode('/', $report_date);
                $normalized_date = sprintf('%04d-%02d-%02d', intval($yy), intval($mm), intval($dd));
            }
            $d = date_create($normalized_date);
            if (!$d) send_json(['success' => false, 'error' => 'Invalid report_date format'], 400);

            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                send_json(['success' => false, 'error' => 'File upload failed'], 400);
            }

            $file = $_FILES['file'];
            $allowed_ext = ['pdf','jpg','jpeg','png'];
            $original = $file['name'];
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_ext)) {
                send_json(['success' => false, 'error' => 'Invalid file type'], 400);
            }

            // MIME detection with fallbacks
            $mime = null;
            if (class_exists('finfo')) {
                $f = new finfo(FILEINFO_MIME_TYPE);
                $mime = $f->file($file['tmp_name']);
            } elseif (function_exists('mime_content_type')) {
                $mime = @mime_content_type($file['tmp_name']);
            } elseif (in_array($ext, ['jpg','jpeg','png'])) {
                // As a last resort, try getimagesize for images
                $info = @getimagesize($file['tmp_name']);
                if ($info && isset($info['mime'])) { $mime = $info['mime']; }
            }
            $allowed_mime = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png'
            ];
            // Basic mime validation: allow pdf or images
            $valid_mime = $mime ? in_array($mime, ['application/pdf','image/jpeg','image/png']) : true; // if detection unavailable, rely on extension
            if (!$valid_mime) {
                send_json(['success' => false, 'error' => 'Invalid file content'], 400);
            }

            // Size limit ~10MB
            if ($file['size'] > 10 * 1024 * 1024) {
                send_json(['success' => false, 'error' => 'File too large'], 400);
            }

            $uploadDir = __DIR__ . '/uploads/reports';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($original, PATHINFO_FILENAME));
            $newName = sprintf('%s_pid%d_%s.%s', $safeBase, $patient_id, date('YmdHis'), $ext);
            $destPath = $uploadDir . '/' . $newName;
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                send_json(['success' => false, 'error' => 'Failed to save file'], 500);
            }

            $relPath = 'uploads/reports/' . $newName;
            // Resolve hospital user_id from session user_id
            $u = intval($_SESSION['user_id']);
            $hs = $conn->prepare('SELECT user_id FROM hospitals WHERE user_id = ?');
            $hs->bind_param('i', $u);
            $hs->execute();
            $hr = $hs->get_result();
            if ($hr->num_rows === 0) {
                @unlink($destPath);
                send_json(['success' => false, 'error' => 'Hospital not found for current user'], 400);
            }
            $uploaded_by = intval($hr->fetch_assoc()['user_id']);

            $stmt = $conn->prepare('INSERT INTO lab_reports (patient_id, test_name, report_file, uploaded_by, report_date) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issis', $patient_id, $test_name, $relPath, $uploaded_by, $normalized_date);
            if (!$stmt->execute()) {
                // Cleanup file if DB insert fails
                @unlink($destPath);
                send_json(['success' => false, 'error' => $stmt->error], 500);
            }

            send_json(['success' => true, 'data' => ['id' => $stmt->insert_id, 'file' => $relPath]]);

        default:
            send_json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}
