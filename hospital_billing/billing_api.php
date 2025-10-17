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
            
            // Enhanced query to get comprehensive ledger data
            $sql = "SELECT 
                        pl.*,
                        u.name as created_by_name,
                        CASE 
                            WHEN pl.reference_type = 'bill' THEN b.bill_type
                            WHEN pl.reference_type = 'payment' THEN p.payment_method
                            ELSE NULL
                        END as reference_details,
                        CASE 
                            WHEN pl.reference_type = 'bill' THEN b.total_amount
                            WHEN pl.reference_type = 'payment' THEN p.payment_amount
                            ELSE NULL
                        END as reference_amount,
                        CASE 
                            WHEN pl.reference_type = 'bill' THEN b.status
                            WHEN pl.reference_type = 'payment' THEN p.payment_status
                            ELSE NULL
                        END as reference_status
                    FROM patient_ledger pl 
                    LEFT JOIN users u ON pl.created_by = u.id 
                    LEFT JOIN bills b ON pl.reference_type = 'bill' AND pl.reference_id = b.id
                    LEFT JOIN payments p ON pl.reference_type = 'payment' AND pl.reference_id = p.id
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
            
            // Get patient financial summary
            $summary_sql = "SELECT 
                                COUNT(CASE WHEN transaction_type = 'charge' THEN 1 END) as total_charges,
                                COUNT(CASE WHEN transaction_type = 'payment' THEN 1 END) as total_payments,
                                SUM(CASE WHEN transaction_type = 'charge' THEN amount ELSE 0 END) as total_charged,
                                SUM(CASE WHEN transaction_type = 'payment' THEN amount ELSE 0 END) as total_paid,
                                SUM(CASE WHEN transaction_type = 'refund' THEN amount ELSE 0 END) as total_refunded,
                                SUM(CASE WHEN transaction_type = 'discount' THEN amount ELSE 0 END) as total_discounted
                            FROM patient_ledger 
                            WHERE patient_id = ?";
            
            $summary_stmt = $conn->prepare($summary_sql);
            $summary_stmt->bind_param('i', $patient_id);
            $summary_stmt->execute();
            $summary = $summary_stmt->get_result()->fetch_assoc();
            
            // Calculate current balance
            $current_balance = ($summary['total_charged'] ?? 0) - ($summary['total_paid'] ?? 0) - ($summary['total_refunded'] ?? 0) - ($summary['total_discounted'] ?? 0);
            
            send_json([
                'success' => true,
                'data' => $ledger,
                'summary' => [
                    'total_charges' => intval($summary['total_charges'] ?? 0),
                    'total_payments' => intval($summary['total_payments'] ?? 0),
                    'total_charged' => floatval($summary['total_charged'] ?? 0),
                    'total_paid' => floatval($summary['total_paid'] ?? 0),
                    'total_refunded' => floatval($summary['total_refunded'] ?? 0),
                    'total_discounted' => floatval($summary['total_discounted'] ?? 0),
                    'current_balance' => floatval($current_balance)
                ],
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
            $issued_date = $data['issued_date'] ?? date('Y-m-d'); // Use provided date or current date
            $items = $data['items'] ?? [];
            $discount_id = intval($data['discount_id'] ?? 0);
            $insurance_id = intval($data['insurance_id'] ?? 0);
            $corporate_id = intval($data['corporate_id'] ?? 0);
            
            if ($patient_id <= 0 || empty($items)) {
                send_json(['success' => false, 'error' => 'Invalid data'], 400);
            }
            
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issued_date)) {
                $issued_date = date('Y-m-d'); // Fallback to current date
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
                
                // Create bill with provided or current date
                $bill_sql = "INSERT INTO bills (patient_id, amount, status, bill_type, issued_date, discount_amount, total_amount, balance_amount, insurance_claim_id, corporate_client_id, created_by) 
                            VALUES (?, ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $bill_stmt = $conn->prepare($bill_sql);
                $bill_stmt->bind_param('iissddddii', $patient_id, $subtotal, $bill_type, $issued_date, $discount_amount, $total_amount, $total_amount, $insurance_id, $corporate_id, $_SESSION['user_id']);
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

        case 'test_database_tables':
            require_method('GET');
            
            try {
                // Test if tables exist
                $tables_to_check = ['users', 'bills', 'bill_items'];
                $table_status = [];
                
                foreach ($tables_to_check as $table) {
                    $check_sql = "SHOW TABLES LIKE ?";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param('s', $table);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    $table_status[$table] = $result->num_rows > 0;
                }
                
                // Test if we can query users table
                $test_sql = "SELECT COUNT(*) as count FROM users WHERE role_id = 1";
                $test_stmt = $conn->prepare($test_sql);
                $test_stmt->execute();
                $patient_count = $test_stmt->get_result()->fetch_assoc()['count'];
                
                // Test if we can query bills table
                $bills_sql = "SELECT COUNT(*) as count FROM bills";
                $bills_stmt = $conn->prepare($bills_sql);
                $bills_stmt->execute();
                $bills_count = $bills_stmt->get_result()->fetch_assoc()['count'];
                
                send_json([
                    'success' => true,
                    'database_connection' => true,
                    'tables_exist' => $table_status,
                    'patient_count' => $patient_count,
                    'bills_count' => $bills_count,
                    'message' => 'Database tables are accessible'
                ]);
                
            } catch (Exception $e) {
                error_log("Database test error: " . $e->getMessage());
                send_json(['success' => false, 'error' => 'Database test failed: ' . $e->getMessage()], 500);
            }

        case 'patient_discharge_summary':
            require_method('GET');
            $patient_id = intval($_GET['patient_id'] ?? 0);
            
            if ($patient_id <= 0) {
                send_json(['success' => false, 'error' => 'Invalid patient ID'], 400);
            }

            try {
                // Get patient information
                $patient_sql = "SELECT id, name, email, phone FROM users WHERE id = ? AND role_id = 1";
                $patient_stmt = $conn->prepare($patient_sql);
                if (!$patient_stmt) {
                    send_json(['success' => false, 'error' => 'Database error: Failed to prepare patient query'], 500);
                }
                
                $patient_stmt->bind_param('i', $patient_id);
                $patient_stmt->execute();
                $patient_result = $patient_stmt->get_result();
                $patient_info = $patient_result->fetch_assoc();

                if (!$patient_info) {
                    send_json(['success' => false, 'error' => 'Patient not found'], 404);
                }

                // Get active cabin booking for this patient
                $cabin_booking_sql = "SELECT 
                    cb.booking_id,
                    cb.user_id,
                    cb.cabin_id,
                    cb.booking_date,
                    cb.check_in,
                    cb.check_out,
                    cb.status as booking_status,
                    c.cabin_number,
                    c.type as cabin_type,
                    c.price as daily_rate,
                    DATEDIFF(CURDATE(), cb.check_in) as days_stayed,
                    (DATEDIFF(CURDATE(), cb.check_in) * c.price) as cabin_total_cost
                FROM cabin_bookings cb
                JOIN cabins c ON cb.cabin_id = c.cabin_id
                WHERE cb.user_id = ? AND cb.status = 'booked'";

                $cabin_stmt = $conn->prepare($cabin_booking_sql);
                if (!$cabin_stmt) {
                    send_json(['success' => false, 'error' => 'Database error: Failed to prepare cabin query'], 500);
                }
                
                $cabin_stmt->bind_param('i', $patient_id);
                $cabin_stmt->execute();
                $cabin_result = $cabin_stmt->get_result();
                $cabin_booking = $cabin_result->fetch_assoc();

                // Get all medical charges for this patient
                $charges_sql = "SELECT 
                    bi.*,
                    b.bill_type,
                    b.issued_date,
                    b.status,
                    b.amount as bill_amount,
                    b.total_amount as bill_total_amount
                FROM bill_items bi
                JOIN bills b ON bi.bill_id = b.id
                WHERE b.patient_id = ?
                ORDER BY b.issued_date DESC, bi.service_date DESC";

                $charges_stmt = $conn->prepare($charges_sql);
                if (!$charges_stmt) {
                    send_json(['success' => false, 'error' => 'Database error: Failed to prepare charges query'], 500);
                }
                
                $charges_stmt->bind_param('i', $patient_id);
                $charges_stmt->execute();
                $charges_result = $charges_stmt->get_result();

                $charges = [];
                while ($row = $charges_result->fetch_assoc()) {
                    $charges[] = $row;
                }

                // Get total amount from bills table for this patient
                $bills_total_sql = "SELECT 
                    SUM(COALESCE(total_amount, amount, 0)) as total_bills_amount,
                    SUM(CASE WHEN status = 'paid' THEN COALESCE(total_amount, amount, 0) ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN status != 'paid' OR status IS NULL THEN COALESCE(total_amount, amount, 0) ELSE 0 END) as unpaid_amount,
                    COUNT(*) as total_bills
                FROM bills 
                WHERE patient_id = ?";

                $bills_total_stmt = $conn->prepare($bills_total_sql);
                if (!$bills_total_stmt) {
                    send_json(['success' => false, 'error' => 'Database error: Failed to prepare bills total query'], 500);
                }
                
                $bills_total_stmt->bind_param('i', $patient_id);
                $bills_total_stmt->execute();
                $bills_total_result = $bills_total_stmt->get_result();
                $bills_summary = $bills_total_result->fetch_assoc();

                // Add cabin charges if patient has active booking
                if ($cabin_booking) {
                    $cabin_charges = [
                        'id' => 'cabin_' . $cabin_booking['booking_id'],
                        'bill_id' => null,
                        'item_type' => 'room',
                        'item_name' => 'Cabin Accommodation',
                        'item_description' => "Cabin #{$cabin_booking['cabin_number']} ({$cabin_booking['cabin_type']}) - {$cabin_booking['days_stayed']} days",
                        'quantity' => $cabin_booking['days_stayed'],
                        'unit_price' => $cabin_booking['daily_rate'],
                        'total_price' => $cabin_booking['cabin_total_cost'],
                        'service_date' => $cabin_booking['check_in'],
                        'bill_type' => 'cabin_booking',
                        'issued_date' => $cabin_booking['booking_date'],
                        'status' => 'pending'
                    ];
                    array_unshift($charges, $cabin_charges); // Add cabin charges at the beginning
                }

                // Calculate grand total safely
                $grand_total = 0;
                if (!empty($charges)) {
                    $grand_total = array_sum(array_column($charges, 'total_price'));
                }

                send_json([
                    'success' => true,
                    'patient_info' => $patient_info,
                    'cabin_booking' => $cabin_booking,
                    'charges' => $charges,
                    'total_charges' => count($charges),
                    'grand_total' => $grand_total,
                    'bills_summary' => $bills_summary,
                    'message' => count($charges) > 0 ? 'Charges loaded successfully' : 'No charges found for this patient'
                ]);
                
            } catch (Exception $e) {
                error_log("Patient discharge summary error: " . $e->getMessage());
                send_json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
            }

        case 'complete_patient_discharge':
            require_method('POST');
            $data = get_json_body();
            $patient_id = intval($data['patient_id'] ?? 0);
            $discharge_date = $data['discharge_date'] ?? date('Y-m-d');
            $discharge_notes = $data['discharge_notes'] ?? '';
            $unpaid_amount = floatval($data['unpaid_amount'] ?? 0);
            
            if ($patient_id <= 0) {
                send_json(['success' => false, 'error' => 'Invalid patient ID'], 400);
            }

            // Check for unpaid bills
            if ($unpaid_amount > 0) {
                send_json([
                    'success' => false, 
                    'error' => "Patient has unpaid bills totaling ৳" . number_format($unpaid_amount, 2) . ". Please collect payment before discharge.",
                    'unpaid_amount' => $unpaid_amount,
                    'requires_payment' => true
                ], 400);
            }

            $conn->begin_transaction();
            try {
                // Get active cabin booking
                $cabin_booking_sql = "SELECT cb.*, c.cabin_number, c.price as daily_rate 
                                     FROM cabin_bookings cb 
                                     JOIN cabins c ON cb.cabin_id = c.cabin_id 
                                     WHERE cb.user_id = ? AND cb.status = 'booked'";
                $cabin_stmt = $conn->prepare($cabin_booking_sql);
                $cabin_stmt->bind_param('i', $patient_id);
                $cabin_stmt->execute();
                $cabin_booking = $cabin_stmt->get_result()->fetch_assoc();

                // Calculate final cabin charges
                $final_cabin_cost = 0;
                if ($cabin_booking) {
                    $days_stayed = max(1, DATEDIFF($discharge_date, $cabin_booking['check_in']));
                    $final_cabin_cost = $days_stayed * $cabin_booking['daily_rate'];
                }

                // Get all pending medical charges
                $pending_charges_sql = "SELECT SUM(bi.total_price) as total_medical 
                                      FROM bill_items bi 
                                      JOIN bills b ON bi.bill_id = b.id 
                                      WHERE b.patient_id = ? AND b.status != 'paid'";
                $charges_stmt = $conn->prepare($pending_charges_sql);
                $charges_stmt->bind_param('i', $patient_id);
                $charges_stmt->execute();
                $medical_total = $charges_stmt->get_result()->fetch_assoc()['total_medical'] ?? 0;

                $total_discharge_amount = $final_cabin_cost + $medical_total;

                // Create final discharge bill
                $bill_sql = "INSERT INTO bills (patient_id, amount, status, bill_type, issued_date, total_amount, balance_amount, created_by) 
                           VALUES (?, ?, 'pending', 'final', ?, ?, ?, ?)";
                $bill_stmt = $conn->prepare($bill_sql);
                $created_by = $_SESSION['user_id'] ?? null;
                $bill_stmt->bind_param('idssddi', $patient_id, $total_discharge_amount, $discharge_date, $total_discharge_amount, $total_discharge_amount, $created_by);
                $bill_stmt->execute();
                $final_bill_id = $conn->insert_id;

                // Add cabin charges to bill items if applicable
                if ($cabin_booking && $final_cabin_cost > 0) {
                    $cabin_item_sql = "INSERT INTO bill_items (bill_id, item_type, item_name, item_description, quantity, unit_price, total_price, service_date) 
                                      VALUES (?, 'room', 'Cabin Accommodation', ?, ?, ?, ?, ?)";
                    $cabin_item_stmt = $conn->prepare($cabin_item_sql);
                    $days_stayed = max(1, DATEDIFF($discharge_date, $cabin_booking['check_in']));
                    $description = "Cabin #{$cabin_booking['cabin_number']} - {$days_stayed} days";
                    $cabin_item_stmt->bind_param('issddss', $final_bill_id, $description, $days_stayed, $cabin_booking['daily_rate'], $final_cabin_cost, $cabin_booking['check_in']);
                    $cabin_item_stmt->execute();
                }

                // Update cabin booking status
                if ($cabin_booking) {
                    $update_cabin_sql = "UPDATE cabin_bookings SET check_out = ?, status = 'completed' WHERE booking_id = ?";
                    $update_cabin_stmt = $conn->prepare($update_cabin_sql);
                    $update_cabin_stmt->bind_param('si', $discharge_date, $cabin_booking['booking_id']);
                    $update_cabin_stmt->execute();

                    // Update cabin availability
                    $update_cabin_availability = $conn->prepare("UPDATE cabins SET availability = 1 WHERE cabin_id = ?");
                    $update_cabin_availability->bind_param('i', $cabin_booking['cabin_id']);
                    $update_cabin_availability->execute();
                }

                // Update patient ledger
                $ledger_sql = "INSERT INTO patient_ledger (patient_id, transaction_type, amount, description, reference_id, reference_type, created_by) 
                              VALUES (?, 'charge', ?, ?, ?, 'bill', ?)";
                $ledger_stmt = $conn->prepare($ledger_sql);
                $description = "Final discharge bill - Cabin: " . ($final_cabin_cost > 0 ? "৳" . number_format($final_cabin_cost, 2) : "N/A") . 
                              ", Medical: ৳" . number_format($medical_total, 2);
                $ledger_stmt->bind_param('idssi', $patient_id, $total_discharge_amount, $description, $final_bill_id, $created_by);
                $ledger_stmt->execute();

                $conn->commit();

                send_json([
                    'success' => true,
                    'message' => 'Patient discharge completed successfully',
                    'final_bill_id' => $final_bill_id,
                    'total_amount' => $total_discharge_amount,
                    'cabin_cost' => $final_cabin_cost,
                    'medical_cost' => $medical_total,
                    'discharge_date' => $discharge_date
                ]);

            } catch (Exception $e) {
                $conn->rollback();
                error_log("Complete discharge error: " . $e->getMessage());
                send_json(['success' => false, 'error' => 'Failed to complete discharge: ' . $e->getMessage()], 500);
            }

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
            $patient_id = intval($_GET['patient_id'] ?? 0);
            
            $where_conditions = ["role_id = 1"]; // Only patients
            $params = [];
            $types = '';
            
            if ($patient_id > 0) {
                $where_conditions[] = "id = ?";
                $params[] = $patient_id;
                $types .= 'i';
            } elseif ($search) {
                $where_conditions[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ? OR id LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
                $types .= 'ssss';
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

        case 'revenue_trend':
            require_method('GET');
            $period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly, yearly
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            
            $date_format = match($period) {
                'daily' => '%Y-%m-%d',
                'weekly' => '%Y-%u',
                'monthly' => '%Y-%m',
                'yearly' => '%Y',
                default => '%Y-%m'
            };
            
            $sql = "SELECT 
                        DATE_FORMAT(payment_date, ?) as period,
                        SUM(payment_amount) as total_revenue,
                        COUNT(*) as transaction_count
                    FROM payments 
                    WHERE payment_status = 'completed' 
                    AND DATE(payment_date) BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(payment_date, ?)
                    ORDER BY period";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssss', $date_format, $start_date, $end_date, $date_format);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $trend_data = [];
            while ($row = $result->fetch_assoc()) {
                $trend_data[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => $trend_data,
                'period' => $period,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

        case 'service_revenue':
            require_method('GET');
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                        bi.item_type,
                        bi.item_name,
                        COUNT(*) as service_count,
                        SUM(bi.total_price) as total_revenue,
                        AVG(bi.total_price) as avg_price
                    FROM bill_items bi
                    JOIN bills b ON bi.bill_id = b.id
                    WHERE DATE(b.issued_date) BETWEEN ? AND ?
                    GROUP BY bi.item_type, bi.item_name
                    ORDER BY total_revenue DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $service_data = [];
            while ($row = $result->fetch_assoc()) {
                $service_data[] = $row;
            }
            
            send_json([
                'success' => true,
                'data' => $service_data,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

        case 'payment_method_analysis':
            require_method('GET');
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            
            $sql = "SELECT 
                        payment_method,
                        COUNT(*) as transaction_count,
                        SUM(payment_amount) as total_amount,
                        AVG(payment_amount) as avg_amount
                    FROM payments 
                    WHERE payment_status = 'completed' 
                    AND DATE(payment_date) BETWEEN ? AND ?
                    GROUP BY payment_method
                    ORDER BY total_amount DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $payment_data = [];
            $total_amount = 0;
            while ($row = $result->fetch_assoc()) {
                $payment_data[] = $row;
                $total_amount += $row['total_amount'];
            }
            
            // Add percentage calculation
            foreach ($payment_data as &$item) {
                $item['percentage'] = $total_amount > 0 ? round(($item['total_amount'] / $total_amount) * 100, 2) : 0;
            }
            
            send_json([
                'success' => true,
                'data' => $payment_data,
                'total_amount' => $total_amount,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

        case 'financial_summary':
            require_method('GET');
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-d');
            
            // Total Revenue
            $revenue_sql = "SELECT SUM(payment_amount) as total_revenue FROM payments WHERE payment_status = 'completed' AND DATE(payment_date) BETWEEN ? AND ?";
            $revenue_stmt = $conn->prepare($revenue_sql);
            $revenue_stmt->bind_param('ss', $start_date, $end_date);
            $revenue_stmt->execute();
            $total_revenue = $revenue_stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
            
            // Total Bills
            $bills_sql = "SELECT COUNT(*) as total_bills, SUM(total_amount) as total_billed FROM bills WHERE DATE(issued_date) BETWEEN ? AND ?";
            $bills_stmt = $conn->prepare($bills_sql);
            $bills_stmt->bind_param('ss', $start_date, $end_date);
            $bills_stmt->execute();
            $bills_result = $bills_stmt->get_result()->fetch_assoc();
            
            // Outstanding Amount
            $outstanding_sql = "SELECT SUM(balance_amount) as total_outstanding FROM bills WHERE balance_amount > 0";
            $outstanding_stmt = $conn->query($outstanding_sql);
            $total_outstanding = $outstanding_stmt->fetch_assoc()['total_outstanding'] ?? 0;
            
            // Average Bill Amount
            $avg_bill_sql = "SELECT AVG(total_amount) as avg_bill FROM bills WHERE DATE(issued_date) BETWEEN ? AND ?";
            $avg_bill_stmt = $conn->prepare($avg_bill_sql);
            $avg_bill_stmt->bind_param('ss', $start_date, $end_date);
            $avg_bill_stmt->execute();
            $avg_bill = $avg_bill_stmt->get_result()->fetch_assoc()['avg_bill'] ?? 0;
            
            // Collection Rate
            $collection_rate = $bills_result['total_billed'] > 0 ? round(($total_revenue / $bills_result['total_billed']) * 100, 2) : 0;
            
            send_json([
                'success' => true,
                'data' => [
                    'total_revenue' => floatval($total_revenue),
                    'total_bills' => intval($bills_result['total_bills'] ?? 0),
                    'total_billed' => floatval($bills_result['total_billed'] ?? 0),
                    'total_outstanding' => floatval($total_outstanding),
                    'avg_bill_amount' => floatval($avg_bill),
                    'collection_rate' => floatval($collection_rate)
                ],
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);

        default:
            send_json(['success' => false, 'error' => 'Invalid action'], 400);
    }
} catch (Throwable $e) {
    send_json(['success' => false, 'error' => $e->getMessage()], 500);
}
?>
