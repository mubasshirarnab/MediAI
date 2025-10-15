<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../dbConnect.php';

// Helper functions
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

function is_authenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function get_json_body() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

function ensure_billing_schema($conn) {
    // This function ensures all billing tables exist
    // The SQL file should be imported first
    return true;
}

ensure_billing_schema($conn);

$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'patient_ledger':
            require_method('GET');
            $patient_id = intval($_GET['patient_id'] ?? 0);
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            if ($patient_id <= 0) {
                send_json(['success' => false, 'error' => 'Invalid patient ID'], 400);
            }
            
            $sql = "SELECT pl.*, u.name as created_by_name 
                    FROM patient_ledger pl 
                    LEFT JOIN users u ON pl.created_by = u.id 
                    WHERE pl.patient_id = ? 
                    ORDER BY pl.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $patient_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $ledger = [];
            while ($row = $result->fetch_assoc()) {
                $ledger[] = $row;
            }
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM patient_ledger WHERE patient_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param('i', $patient_id);
            $count_stmt->execute();
            $total = $count_stmt->get_result()->fetch_assoc()['total'];
            
            send_json([
                'success' => true,
                'data' => $ledger,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        case 'dashboard_stats':
            require_method('GET');
            // Total bills
            $total_bills = $conn->query("SELECT COUNT(*) AS c FROM bills")->fetch_assoc()['c'] ?? 0;
            // Total revenue = sum of paid_amount across all bills
            $total_revenue = $conn->query("SELECT COALESCE(SUM(paid_amount),0) AS s FROM bills")->fetch_assoc()['s'] ?? 0;
            // Outstanding amount = sum of balance_amount across unpaid/partial bills
            $outstanding_amount = $conn->query("SELECT COALESCE(SUM(balance_amount),0) AS s FROM bills WHERE balance_amount > 0")->fetch_assoc()['s'] ?? 0;
            // Today's collection from payments (completed)
            $stmt = $conn->prepare("SELECT COALESCE(SUM(payment_amount),0) AS s FROM payments WHERE payment_status = 'completed' AND DATE(payment_date) = CURDATE()");
            $stmt->execute();
            $today_collection = $stmt->get_result()->fetch_assoc()['s'] ?? 0;
            send_json([
                'success' => true,
                'data' => [
                    'total_bills' => intval($total_bills),
                    'total_revenue' => floatval($total_revenue),
                    'outstanding_amount' => floatval($outstanding_amount),
                    'today_collection' => floatval($today_collection)
                ]
            ]);

        case 'create_bill':
            require_method('POST');
            $data = get_json_body();
            
            $patient_id = intval($data['patient_id'] ?? 0);
            $bill_type = $data['bill_type'] ?? 'final';
            $items = $data['items'] ?? [];
            $discount_id = intval($data['discount_id'] ?? 0);
            $insurance_id = intval($data['insurance_id'] ?? 0);
            $corporate_id = intval($data['corporate_id'] ?? 0);
            
            if ($patient_id <= 0 || empty($items)) {
                send_json(['success' => false, 'error' => 'Invalid data'], 400);
            }
            
            $conn->begin_transaction();
            
            try {
                // Calculate total amount
                $subtotal = 0;
                foreach ($items as $item) {
                    $subtotal += floatval($item['total_price']);
                }
                
                // Apply discount
                $discount_amount = 0;
                if ($discount_id > 0) {
                    $discount_sql = "SELECT discount_type, discount_value FROM discounts WHERE id = ? AND is_active = 1";
                    $discount_stmt = $conn->prepare($discount_sql);
                    $discount_stmt->bind_param('i', $discount_id);
                    $discount_stmt->execute();
                    $discount = $discount_stmt->get_result()->fetch_assoc();
                    
                    if ($discount) {
                        if ($discount['discount_type'] === 'percentage') {
                            $discount_amount = ($subtotal * $discount['discount_value']) / 100;
                        } else {
                            $discount_amount = $discount['discount_value'];
                        }
                    }
                }
                
                $total_amount = $subtotal - $discount_amount;
                
                // Create bill
                $bill_sql = "INSERT INTO bills (patient_id, amount, status, bill_type, discount_amount, total_amount, balance_amount, insurance_claim_id, corporate_client_id, created_by) 
                            VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?)";
                
                $bill_stmt = $conn->prepare($bill_sql);
                $bill_stmt->bind_param('iisddddii', $patient_id, $subtotal, $bill_type, $discount_amount, $total_amount, $total_amount, $insurance_id, $corporate_id, $_SESSION['user_id']);
                $bill_stmt->execute();
                
                $bill_id = $conn->insert_id;
                
                // Add bill items
                foreach ($items as $item) {
                    $item_sql = "INSERT INTO bill_items (bill_id, item_type, item_name, item_description, quantity, unit_price, total_price, final_price) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $item_stmt = $conn->prepare($item_sql);
                    $item_stmt->bind_param('isssiddd', $bill_id, $item['item_type'], $item['item_name'], $item['item_description'], $item['quantity'], $item['unit_price'], $item['total_price'], $item['total_price']);
                    $item_stmt->execute();
                }
                
                // Add to patient ledger
                $ledger_sql = "INSERT INTO patient_ledger (patient_id, transaction_type, amount, description, reference_id, reference_type, created_by) 
                              VALUES (?, 'charge', ?, 'Bill created', ?, 'bill', ?)";
                
                $ledger_stmt = $conn->prepare($ledger_sql);
                $ledger_stmt->bind_param('idii', $patient_id, $total_amount, $bill_id, $_SESSION['user_id']);
                $ledger_stmt->execute();
                
                $conn->commit();
                
                send_json([
                    'success' => true,
                    'message' => 'Bill created successfully',
                    'bill_id' => $bill_id
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        case 'make_payment':
            require_method('POST');
            $data = get_json_body();
            
            $bill_id = intval($data['bill_id'] ?? 0);
            $payment_method = $data['payment_method'] ?? '';
            $payment_amount = floatval($data['payment_amount'] ?? 0);
            $payment_reference = $data['payment_reference'] ?? '';
            $transaction_id = $data['transaction_id'] ?? '';
            $bank_name = $data['bank_name'] ?? '';
            $mobile_banking_provider = $data['mobile_banking_provider'] ?? '';
            $mobile_number = $data['mobile_number'] ?? '';
            
            if ($bill_id <= 0 || $payment_amount <= 0) {
                send_json(['success' => false, 'error' => 'Invalid payment data'], 400);
            }
            
            $conn->begin_transaction();
            
            try {
                // Get bill details
                $bill_sql = "SELECT * FROM bills WHERE id = ?";
                $bill_stmt = $conn->prepare($bill_sql);
                $bill_stmt->bind_param('i', $bill_id);
                $bill_stmt->execute();
                $bill = $bill_stmt->get_result()->fetch_assoc();
                
                if (!$bill) {
                    throw new Exception('Bill not found');
                }
                
                // Create payment record
                $payment_sql = "INSERT INTO payments (bill_id, patient_id, payment_method, payment_amount, payment_reference, transaction_id, bank_name, mobile_banking_provider, mobile_number, created_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $payment_stmt = $conn->prepare($payment_sql);
                $payment_stmt->bind_param('iisssssssi', $bill_id, $bill['patient_id'], $payment_method, $payment_amount, $payment_reference, $transaction_id, $bank_name, $mobile_banking_provider, $mobile_number, $_SESSION['user_id']);
                $payment_stmt->execute();
                
                // Update bill
                $new_paid_amount = $bill['paid_amount'] + $payment_amount;
                $new_balance = $bill['total_amount'] - $new_paid_amount;
                $new_status = $new_balance <= 0 ? 'paid' : 'partial';
                
                $update_bill_sql = "UPDATE bills SET paid_amount = ?, balance_amount = ?, status = ?, updated_by = ? WHERE id = ?";
                $update_bill_stmt = $conn->prepare($update_bill_sql);
                $update_bill_stmt->bind_param('ddsii', $new_paid_amount, $new_balance, $new_status, $_SESSION['user_id'], $bill_id);
                $update_bill_stmt->execute();
                
                // Add to patient ledger
                $ledger_sql = "INSERT INTO patient_ledger (patient_id, transaction_type, amount, description, reference_id, reference_type, created_by) 
                              VALUES (?, 'payment', ?, 'Payment received', ?, 'payment', ?)";
                
                $ledger_stmt = $conn->prepare($ledger_sql);
                $ledger_stmt->bind_param('idii', $bill['patient_id'], $payment_amount, $bill_id, $_SESSION['user_id']);
                $ledger_stmt->execute();
                
                $conn->commit();
                
                send_json([
                    'success' => true,
                    'message' => 'Payment recorded successfully',
                    'payment_id' => $conn->insert_id
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

        case 'bills_list':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? '';
            $patient_id = intval($_GET['patient_id'] ?? 0);
            
            $where_conditions = [];
            $params = [];
            $types = '';
            
            if ($status) {
                $where_conditions[] = "b.status = ?";
                $params[] = $status;
                $types .= 's';
            }
            
            if ($patient_id > 0) {
                $where_conditions[] = "b.patient_id = ?";
                $params[] = $patient_id;
                $types .= 'i';
            }
            
            $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
            
            $sql = "SELECT b.*, u.name as patient_name, u.phone as patient_phone 
                    FROM bills b 
                    JOIN users u ON b.patient_id = u.id 
                    $where_clause
                    ORDER BY b.issued_date DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $bills = [];
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM bills b $where_clause";
            $count_stmt = $conn->prepare($count_sql);
            if (!empty($params) && count($params) > 2) {
                $count_params = array_slice($params, 0, -2);
                $count_types = substr($types, 0, -2);
                $count_stmt->bind_param($count_types, ...$count_params);
            }
            $count_stmt->execute();
            $total = $count_stmt->get_result()->fetch_assoc()['total'];
            
            send_json([
                'success' => true,
                'data' => $bills,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);

        case 'bill_details':
            require_method('GET');
            $bill_id = intval($_GET['bill_id'] ?? 0);
            
            if ($bill_id <= 0) {
                send_json(['success' => false, 'error' => 'Invalid bill ID'], 400);
            }
            
            // Get bill details
            $bill_sql = "SELECT b.*, u.name as patient_name, u.phone as patient_phone, u.email as patient_email 
                        FROM bills b 
                        JOIN users u ON b.patient_id = u.id 
                        WHERE b.id = ?";
            
            $bill_stmt = $conn->prepare($bill_sql);
            $bill_stmt->bind_param('i', $bill_id);
            $bill_stmt->execute();
            $bill = $bill_stmt->get_result()->fetch_assoc();
            
            if (!$bill) {
                send_json(['success' => false, 'error' => 'Bill not found'], 404);
            }
            
            // Get bill items
            $items_sql = "SELECT * FROM bill_items WHERE bill_id = ? ORDER BY id";
            $items_stmt = $conn->prepare($items_sql);
            $items_stmt->bind_param('i', $bill_id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            
            $items = [];
            while ($row = $items_result->fetch_assoc()) {
                $items[] = $row;
            }
            
            // Get payments
            $payments_sql = "SELECT * FROM payments WHERE bill_id = ? ORDER BY payment_date";
            $payments_stmt = $conn->prepare($payments_sql);
            $payments_stmt->bind_param('i', $bill_id);
            $payments_stmt->execute();
            $payments_result = $payments_stmt->get_result();
            
            $payments = [];
            while ($row = $payments_result->fetch_assoc()) {
                $payments[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => [
                    'bill' => $bill,
                    'items' => $items,
                    'payments' => $payments
                ]
            ]);

        case 'service_charges':
            require_method('GET');
            $service_type = $_GET['service_type'] ?? '';
            $department = $_GET['department'] ?? '';
            $with_tariffs = isset($_GET['with_tariffs']) ? intval($_GET['with_tariffs']) : 0;
            
            $where_conditions = ["is_active = 1"];
            $params = [];
            $types = '';
            
            if ($service_type) {
                $where_conditions[] = "service_type = ?";
                $params[] = $service_type;
                $types .= 's';
            }
            
            if ($department) {
                $where_conditions[] = "department = ?";
                $params[] = $department;
                $types .= 's';
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            
            if ($with_tariffs) {
                $sql = "SELECT sc.*, st_general.price AS price_general, st_vip.price AS price_vip, st_corp.price AS price_corporate
                        FROM service_charges sc
                        LEFT JOIN service_tariffs st_general ON st_general.service_id = sc.id AND st_general.price_group = 'general'
                        LEFT JOIN service_tariffs st_vip ON st_vip.service_id = sc.id AND st_vip.price_group = 'vip'
                        LEFT JOIN service_tariffs st_corp ON st_corp.service_id = sc.id AND st_corp.price_group = 'corporate'
                        $where_clause ORDER BY sc.service_name";
            } else {
                $sql = "SELECT * FROM service_charges $where_clause ORDER BY service_name";
            }
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $services = [];
            while ($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => $services
            ]);

        case 'service_tariffs':
            require_method('GET');
            $service_id = intval($_GET['service_id'] ?? 0);
            if ($service_id <= 0) send_json(['success' => false, 'error' => 'Invalid service id'], 400);
            $stmt = $conn->prepare("SELECT * FROM service_tariffs WHERE service_id = ?");
            $stmt->bind_param('i', $service_id);
            $stmt->execute();
            $tariffs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            send_json(['success' => true, 'data' => $tariffs]);

        case 'service_tariff_upsert':
            require_method('POST');
            $data = get_json_body();
            $service_id = intval($data['service_id'] ?? 0);
            $price_group = $data['price_group'] ?? '';
            $price = floatval($data['price'] ?? 0);
            if ($service_id <= 0 || empty($price_group) || $price <= 0) send_json(['success' => false, 'error' => 'Invalid tariff data'], 400);
            $stmt = $conn->prepare("INSERT INTO service_tariffs (service_id, price_group, price) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE price = VALUES(price)");
            $stmt->bind_param('isd', $service_id, $price_group, $price);
            $stmt->execute();
            send_json(['success' => true]);

        case 'service_create':
            require_method('POST');
            $data = get_json_body();
            $service_name = trim($data['service_name'] ?? '');
            $service_type = $data['service_type'] ?? '';
            $department = $data['department'] ?? null;
            $base_price = floatval($data['base_price'] ?? 0);
            if (!$service_name || !$service_type || $base_price <= 0) send_json(['success' => false, 'error' => 'Invalid service data'], 400);
            $stmt = $conn->prepare("INSERT INTO service_charges (service_name, service_type, department, base_price, created_by) VALUES (?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'] ?? null;
            $stmt->bind_param('sssdi', $service_name, $service_type, $department, $base_price, $created_by);
            $stmt->execute();
            $service_id = $conn->insert_id;
            // Initialize tariffs
            $init = $conn->prepare("INSERT INTO service_tariffs (service_id, price_group, price) VALUES 
              (?, 'general', ?), (?, 'vip', ?), (?, 'corporate', ?)");
            $vip = round($base_price * 1.15, 2); $corp = round($base_price * 0.9, 2);
            $init->bind_param('ididid', $service_id, $base_price, $service_id, $vip, $service_id, $corp);
            $init->execute();
            send_json(['success' => true, 'service_id' => $service_id]);

        case 'service_update':
            require_method('POST');
            $data = get_json_body();
            $service_id = intval($data['id'] ?? 0);
            $service_name = trim($data['service_name'] ?? '');
            $department = $data['department'] ?? null;
            $base_price = floatval($data['base_price'] ?? 0);
            if ($service_id <= 0 || !$service_name || $base_price <= 0) send_json(['success' => false, 'error' => 'Invalid service data'], 400);
            $stmt = $conn->prepare("UPDATE service_charges SET service_name = ?, department = ?, base_price = ? WHERE id = ?");
            $stmt->bind_param('ssdi', $service_name, $department, $base_price, $service_id);
            $stmt->execute();
            send_json(['success' => true]);

        case 'service_delete':
            require_method('POST');
            $data = get_json_body();
            $service_id = intval($data['id'] ?? 0);
            if ($service_id <= 0) send_json(['success' => false, 'error' => 'Invalid service id'], 400);
            $stmt = $conn->prepare("DELETE FROM service_charges WHERE id = ?");
            $stmt->bind_param('i', $service_id);
            $stmt->execute();
            send_json(['success' => true]);

        case 'discounts':
            require_method('GET');
            $applicable_to = $_GET['applicable_to'] ?? '';
            
            $where_conditions = ["is_active = 1"];
            $params = [];
            $types = '';
            
            if ($applicable_to) {
                $where_conditions[] = "applicable_to = ?";
                $params[] = $applicable_to;
                $types .= 's';
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            
            $sql = "SELECT * FROM discounts $where_clause ORDER BY discount_name";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $discounts = [];
            while ($row = $result->fetch_assoc()) {
                $discounts[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => $discounts
            ]);

        case 'packages':
            require_method('GET');
            $package_type = $_GET['package_type'] ?? '';
            
            $where_conditions = ["is_active = 1"];
            $params = [];
            $types = '';
            
            if ($package_type) {
                $where_conditions[] = "package_type = ?";
                $params[] = $package_type;
                $types .= 's';
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            
            $sql = "SELECT * FROM packages $where_clause ORDER BY package_name";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $packages = [];
            while ($row = $result->fetch_assoc()) {
                $packages[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => $packages
            ]);

        case 'package_items':
            require_method('GET');
            $package_id = intval($_GET['package_id'] ?? 0);
            if ($package_id <= 0) send_json(['success' => false, 'error' => 'Invalid package id'], 400);
            $stmt = $conn->prepare("SELECT * FROM package_items WHERE package_id = ? ORDER BY id");
            $stmt->bind_param('i', $package_id);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            send_json(['success' => true, 'data' => $items]);

        case 'package_create':
            require_method('POST');
            $data = get_json_body();
            $name = trim($data['package_name'] ?? '');
            $type = $data['package_type'] ?? 'general';
            $price = floatval($data['total_price'] ?? 0);
            $desc = $data['package_description'] ?? null;
            if (!$name || $price <= 0) send_json(['success' => false, 'error' => 'Invalid package'], 400);
            $stmt = $conn->prepare("INSERT INTO packages (package_name, package_description, package_type, total_price, created_by) VALUES (?, ?, ?, ?, ?)");
            $created_by = $_SESSION['user_id'] ?? null;
            $stmt->bind_param('sssdi', $name, $desc, $type, $price, $created_by);
            $stmt->execute();
            send_json(['success' => true, 'package_id' => $conn->insert_id]);

        case 'package_add_item':
            require_method('POST');
            $data = get_json_body();
            $package_id = intval($data['package_id'] ?? 0);
            $item_name = trim($data['item_name'] ?? '');
            $item_type = $data['item_type'] ?? 'service';
            $quantity = intval($data['quantity'] ?? 1);
            $unit_price = floatval($data['unit_price'] ?? 0);
            if ($package_id <= 0 || !$item_name || $quantity <= 0 || $unit_price < 0) send_json(['success' => false, 'error' => 'Invalid item'], 400);
            $total_price = $unit_price * $quantity;
            $stmt = $conn->prepare("INSERT INTO package_items (package_id, item_type, item_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issidd', $package_id, $item_type, $item_name, $quantity, $unit_price, $total_price);
            $stmt->execute();
            send_json(['success' => true, 'item_id' => $conn->insert_id]);

        case 'daily_collection':
            require_method('GET');
            $date = $_GET['date'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                        payment_method,
                        COUNT(*) as transaction_count,
                        SUM(payment_amount) as total_amount
                    FROM payments 
                    WHERE DATE(payment_date) = ? AND payment_status = 'completed'
                    GROUP BY payment_method
                    ORDER BY total_amount DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $collection = [];
            $total_amount = 0;
            
            while ($row = $result->fetch_assoc()) {
                $collection[] = $row;
                $total_amount += $row['total_amount'];
            }
            
            send_json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'collection' => $collection,
                    'total_amount' => $total_amount
                ]
            ]);

        case 'outstanding_reports':
            require_method('GET');
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT 
                        b.id as bill_id,
                        b.patient_id,
                        u.name as patient_name,
                        u.phone as patient_phone,
                        b.total_amount,
                        b.paid_amount,
                        b.balance_amount,
                        b.issued_date,
                        b.due_date
                    FROM bills b 
                    JOIN users u ON b.patient_id = u.id 
                    WHERE b.balance_amount > 0 
                    ORDER BY b.balance_amount DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $outstanding = [];
            while ($row = $result->fetch_assoc()) {
                $outstanding[] = $row;
            }
            
            // Get total outstanding amount
            $total_sql = "SELECT SUM(balance_amount) as total_outstanding FROM bills WHERE balance_amount > 0";
            $total_stmt = $conn->query($total_sql);
            $total_outstanding = $total_stmt->fetch_assoc()['total_outstanding'] ?? 0;
            
            send_json([
                'success' => true,
                'data' => $outstanding,
                'total_outstanding' => $total_outstanding,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);

        case 'patient_discharge_summary':
            require_method('GET');
            $patient_id = intval($_GET['patient_id'] ?? 0);
            
            if ($patient_id <= 0) {
                send_json(['success' => false, 'error' => 'Invalid patient ID'], 400);
            }

            // Get patient information
            $patient_sql = "SELECT id, name, email, phone FROM users WHERE id = ? AND role_id = 1";
            $patient_stmt = $conn->prepare($patient_sql);
            $patient_stmt->bind_param('i', $patient_id);
            $patient_stmt->execute();
            $patient_result = $patient_stmt->get_result();
            $patient_info = $patient_result->fetch_assoc();

            if (!$patient_info) {
                send_json(['success' => false, 'error' => 'Patient not found'], 404);
            }

            // Get all charges for this patient
            $charges_sql = "SELECT 
                bi.*,
                b.bill_type,
                b.issued_date,
                b.status
            FROM bill_items bi
            JOIN bills b ON bi.bill_id = b.id
            WHERE b.patient_id = ?
            ORDER BY b.issued_date DESC, bi.item_date DESC";

            $charges_stmt = $conn->prepare($charges_sql);
            $charges_stmt->bind_param('i', $patient_id);
            $charges_stmt->execute();
            $charges_result = $charges_stmt->get_result();

            $charges = [];
            while ($row = $charges_result->fetch_assoc()) {
                $charges[] = $row;
            }

            send_json([
                'success' => true,
                'patient_info' => $patient_info,
                'charges' => $charges,
                'total_charges' => count($charges),
                'grand_total' => array_sum(array_column($charges, 'total_price'))
            ]);

        case 'generate_discharge_bill':
            require_method('POST');
            $data = get_json_body();
            $patient_id = intval($data['patient_id'] ?? 0);
            $bill_type = $data['bill_type'] ?? 'final';

            if ($patient_id <= 0) {
                send_json(['success' => false, 'error' => 'Invalid patient ID'], 400);
            }

            // Check if patient exists
            $patient_check = $conn->prepare("SELECT id FROM users WHERE id = ? AND role_id = 1");
            $patient_check->bind_param('i', $patient_id);
            $patient_check->execute();
            if (!$patient_check->get_result()->fetch_assoc()) {
                send_json(['success' => false, 'error' => 'Patient not found'], 404);
            }

            // Get all existing charges for this patient
            $existing_charges_sql = "SELECT 
                SUM(bi.total_price) as total_amount
            FROM bill_items bi
            JOIN bills b ON bi.bill_id = b.id
            WHERE b.patient_id = ? AND b.bill_type != 'final'";

            $existing_stmt = $conn->prepare($existing_charges_sql);
            $existing_stmt->bind_param('i', $patient_id);
            $existing_stmt->execute();
            $total_amount = $existing_stmt->get_result()->fetch_assoc()['total_amount'] ?? 0;

            if ($total_amount <= 0) {
                send_json(['success' => false, 'error' => 'No charges found to generate discharge bill'], 400);
            }

            $conn->begin_transaction();
            try {
                // Create final discharge bill
                $bill_sql = "INSERT INTO bills (patient_id, amount, status, bill_type) VALUES (?, ?, 'pending', ?)";
                $bill_stmt = $conn->prepare($bill_sql);
                $bill_stmt->bind_param('ids', $patient_id, $total_amount, $bill_type);
                $bill_stmt->execute();
                $bill_id = $conn->insert_id;

                // Copy all existing bill items to the discharge bill
                $copy_items_sql = "INSERT INTO bill_items (
                    bill_id, item_type, item_name, item_description, 
                    quantity, unit_price, total_price, item_date
                )
                SELECT 
                    ?, bi.item_type, bi.item_name, bi.item_description,
                    bi.quantity, bi.unit_price, bi.total_price, bi.item_date
                FROM bill_items bi
                JOIN bills b ON bi.bill_id = b.id
                WHERE b.patient_id = ? AND b.bill_type != 'final'";

                $copy_stmt = $conn->prepare($copy_items_sql);
                $copy_stmt->bind_param('ii', $bill_id, $patient_id);
                $copy_stmt->execute();

                // Update patient ledger
                $ledger_sql = "INSERT INTO patient_ledgers (patient_id, total_billed, balance_due) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE 
                              total_billed = total_billed + ?, 
                              balance_due = balance_due + ?";
                $ledger_stmt = $conn->prepare($ledger_sql);
                $ledger_stmt->bind_param('idddd', $patient_id, $total_amount, $total_amount, $total_amount, $total_amount);
                $ledger_stmt->execute();

                $conn->commit();

                send_json([
                    'success' => true,
                    'message' => 'Discharge bill generated successfully',
                    'bill_id' => $bill_id,
                    'total_amount' => $total_amount
                ]);

            } catch (mysqli_sql_exception $e) {
                $conn->rollback();
                send_json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }

        case 'get_patients':
            require_method('GET');
            $search = $_GET['search'] ?? '';
            
            $where_conditions = ["role_id = 1"]; // Only patients
            $params = [];
            $types = '';
            
            if ($search) {
                $where_conditions[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, [$search_param, $search_param, $search_param]);
                $types .= 'sss';
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            
            $sql = "SELECT id, name, email, phone FROM users $where_clause ORDER BY name LIMIT 100";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => $patients
            ]);

        default:
            send_json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
