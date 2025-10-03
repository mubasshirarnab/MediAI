<?php
session_start();
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

function is_hospital_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'hospital';
}

function is_authenticated() {
    return isset($_SESSION['user_id']);
}

function get_json_body() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

// Ensure tables exist with required schema
function ensure_schema($conn) {
    // cabins
    $conn->query("CREATE TABLE IF NOT EXISTS cabins (
        cabin_id INT AUTO_INCREMENT PRIMARY KEY,
        cabin_number VARCHAR(50) NOT NULL UNIQUE,
        type ENUM('general','deluxe','ICU') NOT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        availability TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // cabin_bookings
    $conn->query("CREATE TABLE IF NOT EXISTS cabin_bookings (
        booking_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        cabin_id INT NOT NULL,
        booking_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        check_in DATE NOT NULL,
        check_out DATE NOT NULL,
        status ENUM('booked','completed','cancelled') NOT NULL DEFAULT 'booked',
        CONSTRAINT fk_cb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        CONSTRAINT fk_cb_cabin FOREIGN KEY (cabin_id) REFERENCES cabins(cabin_id) ON DELETE CASCADE,
        INDEX idx_cabin_dates (cabin_id, check_in, check_out),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

ensure_schema($conn);

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'cabins_list': // Admin list all cabins
            require_method('GET');
            if (!is_hospital_admin()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $res = $conn->query("SELECT cabin_id, cabin_number, type, price, availability, created_at, updated_at FROM cabins ORDER BY cabin_number ASC");
            $rows = [];
            if ($res) { while ($r = $res->fetch_assoc()) { $rows[] = $r; } }
            send_json(['success' => true, 'data' => $rows]);

        case 'cabins_add': // Admin create
            require_method('POST');
            if (!is_hospital_admin()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $body = get_json_body();
            $cabin_number = trim($body['cabin_number'] ?? '');
            $type = $body['type'] ?? '';
            $price = isset($body['price']) ? floatval($body['price']) : 0.0;
            $availability = isset($body['availability']) ? intval($body['availability']) : 1;
            if ($cabin_number === '' || !in_array($type, ['general','deluxe','ICU']) || $price < 0) {
                send_json(['success' => false, 'error' => 'Invalid input'], 400);
            }
            $stmt = $conn->prepare("INSERT INTO cabins (cabin_number, type, price, availability) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssdi', $cabin_number, $type, $price, $availability);
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true, 'data' => ['cabin_id' => $stmt->insert_id]]);

        case 'cabins_update': // Admin update
            require_method('POST');
            if (!is_hospital_admin()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $body = get_json_body();
            $cabin_id = intval($body['cabin_id'] ?? 0);
            $cabin_number = isset($body['cabin_number']) ? trim($body['cabin_number']) : null;
            $type = $body['type'] ?? null;
            $price = isset($body['price']) ? floatval($body['price']) : null;
            $availability = isset($body['availability']) ? intval($body['availability']) : null;
            if ($cabin_id <= 0) send_json(['success' => false, 'error' => 'Invalid cabin_id'], 400);

            // Build dynamic update
            $fields = [];
            $params = [];
            $types = '';
            if ($cabin_number !== null) { $fields[] = 'cabin_number = ?'; $params[] = $cabin_number; $types .= 's'; }
            if ($type !== null) {
                if (!in_array($type, ['general','deluxe','ICU'])) send_json(['success' => false, 'error' => 'Invalid type'], 400);
                $fields[] = 'type = ?'; $params[] = $type; $types .= 's';
            }
            if ($price !== null) { $fields[] = 'price = ?'; $params[] = $price; $types .= 'd'; }
            if ($availability !== null) { $fields[] = 'availability = ?'; $params[] = $availability; $types .= 'i'; }
            if (empty($fields)) send_json(['success' => false, 'error' => 'No fields to update'], 400);
            $sql = 'UPDATE cabins SET ' . implode(', ', $fields) . ' WHERE cabin_id = ?';
            $stmt = $conn->prepare($sql);
            $types .= 'i';
            $params[] = $cabin_id;
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'cabins_delete': // Admin delete
            require_method('POST');
            if (!is_hospital_admin()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $body = get_json_body();
            $cabin_id = intval($body['cabin_id'] ?? 0);
            if ($cabin_id <= 0) send_json(['success' => false, 'error' => 'Invalid cabin_id'], 400);
            // Disallow delete if there are active bookings
            $q = $conn->prepare("SELECT COUNT(*) c FROM cabin_bookings WHERE cabin_id = ? AND status IN ('booked')");
            $q->bind_param('i', $cabin_id);
            $q->execute();
            $c = $q->get_result()->fetch_assoc()['c'] ?? 0;
            if ($c > 0) send_json(['success' => false, 'error' => 'Cabin has active bookings'], 400);
            $stmt = $conn->prepare('DELETE FROM cabins WHERE cabin_id = ?');
            $stmt->bind_param('i', $cabin_id);
            if (!$stmt->execute()) send_json(['success' => false, 'error' => $stmt->error], 500);
            send_json(['success' => true]);

        case 'available_cabins': // Public: list available in a date range
            require_method('GET');
            $check_in = isset($_GET['check_in']) ? $_GET['check_in'] : null;
            $check_out = isset($_GET['check_out']) ? $_GET['check_out'] : null;
            if (!$check_in || !$check_out) {
                // Fallback: list all available (availability=1)
                $res = $conn->query("SELECT cabin_id, cabin_number, type, price, availability FROM cabins WHERE availability = 1 ORDER BY type, price ASC");
                $rows = [];
                if ($res) while ($r = $res->fetch_assoc()) { $rows[] = $r; }
                send_json(['success' => true, 'data' => $rows]);
            }
            // Validate dates
            $ci = date_create($check_in);
            $co = date_create($check_out);
            if (!$ci || !$co || $ci >= $co) send_json(['success' => false, 'error' => 'Invalid dates'], 400);

            // Find cabins without conflicting bookings
            $sql = "SELECT c.cabin_id, c.cabin_number, c.type, c.price, c.availability
                    FROM cabins c
                    WHERE c.availability = 1 AND NOT EXISTS (
                        SELECT 1 FROM cabin_bookings b
                        WHERE b.cabin_id = c.cabin_id
                          AND b.status IN ('booked','completed')
                          AND NOT (b.check_out <= ? OR b.check_in >= ?)
                    )
                    ORDER BY c.type, c.price ASC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $check_in, $check_out);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) { $rows[] = $row; }
            send_json(['success' => true, 'data' => $rows]);

        case 'book': // Patient book a cabin
            require_method('POST');
            if (!is_authenticated()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $body = get_json_body();
            $user_id = intval($_SESSION['user_id']);
            $cabin_id = intval($body['cabin_id'] ?? 0);
            $check_in = $body['check_in'] ?? null;
            $check_out = $body['check_out'] ?? null;
            if ($cabin_id <= 0) send_json(['success' => false, 'error' => 'Invalid cabin'], 400);
            $ci = date_create($check_in);
            $co = date_create($check_out);
            if (!$ci || !$co || $ci >= $co) send_json(['success' => false, 'error' => 'Invalid dates'], 400);

            // Check cabin exists and is available flag
            $s = $conn->prepare('SELECT availability FROM cabins WHERE cabin_id = ?');
            $s->bind_param('i', $cabin_id);
            $s->execute();
            $res = $s->get_result();
            if ($res->num_rows === 0) send_json(['success' => false, 'error' => 'Cabin not found'], 404);
            $availability = intval($res->fetch_assoc()['availability']);
            if ($availability !== 1) send_json(['success' => false, 'error' => 'Cabin not available'], 400);

            // Prevent overlapping bookings
            $q = $conn->prepare("SELECT COUNT(*) c FROM cabin_bookings 
                                  WHERE cabin_id = ? AND status IN ('booked','completed')
                                  AND NOT (check_out <= ? OR check_in >= ?)");
            $q->bind_param('iss', $cabin_id, $check_in, $check_out);
            $q->execute();
            $count = $q->get_result()->fetch_assoc()['c'] ?? 0;
            if ($count > 0) send_json(['success' => false, 'error' => 'Cabin is already booked for selected dates'], 400);

            // Create booking
            $ins = $conn->prepare('INSERT INTO cabin_bookings (user_id, cabin_id, check_in, check_out, status) VALUES (?, ?, ?, ?, \'booked\')');
            $ins->bind_param('iiss', $user_id, $cabin_id, $check_in, $check_out);
            if (!$ins->execute()) send_json(['success' => false, 'error' => $ins->error], 500);
            send_json(['success' => true, 'data' => ['booking_id' => $ins->insert_id]]);

        case 'my_bookings': // User bookings
            require_method('GET');
            if (!is_authenticated()) send_json(['success' => false, 'error' => 'Unauthorized'], 401);
            $user_id = intval($_SESSION['user_id']);
            $sql = "SELECT b.booking_id, b.check_in, b.check_out, b.status, b.booking_date,
                           c.cabin_number, c.type, c.price
                    FROM cabin_bookings b
                    JOIN cabins c ON c.cabin_id = b.cabin_id
                    WHERE b.user_id = ?
                    ORDER BY b.booking_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            while ($row = $result->fetch_assoc()) { $rows[] = $row; }
            send_json(['success' => true, 'data' => $rows]);

        default:
            send_json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}

