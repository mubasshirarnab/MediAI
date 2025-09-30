<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/dbConnect.php';

function send_json($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_param($key, $default = null) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

// Determine action
$action = get_param('action', 'counts');

try {
    if ($action === 'counts') {
        // Total users
        $usersCount = 0;
        $stmt = $conn->query("SELECT COUNT(*) AS c FROM users");
        if ($stmt) {
            $row = $stmt->fetch_assoc();
            $usersCount = intval($row['c']);
        }

        // Total doctors (role_id -> roles.id where role_name='doctor')
        $doctorsCount = 0;
        $stmt = $conn->query("SELECT COUNT(*) AS c FROM users u JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'doctor'");
        if ($stmt) {
            $row = $stmt->fetch_assoc();
            $doctorsCount = intval($row['c']);
        }

        send_json([
            'success' => true,
            'data' => [
                'totalUsers' => $usersCount,
                'totalDoctors' => $doctorsCount
            ]
        ]);
    }

    if ($action === 'list_users') {
        $limit = intval(get_param('limit', 100));
        $offset = intval(get_param('offset', 0));

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at, r.role_name, u.status
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        send_json(['success' => true, 'data' => $rows]);
    }

    if ($action === 'list_doctors') {
        $limit = intval(get_param('limit', 100));
        $offset = intval(get_param('offset', 0));

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at, d.specialization, d.available
                FROM users u
                INNER JOIN roles r ON u.role_id = r.id AND r.role_name = 'doctor'
                LEFT JOIN doctors d ON d.user_id = u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        send_json(['success' => true, 'data' => $rows]);
    }

    send_json(['success' => false, 'error' => 'Invalid action'], 400);
} catch (Exception $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}

?>


