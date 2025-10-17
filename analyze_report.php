<?php
session_start();
require_once 'dbConnect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to analyze reports']);
    exit;
}

try {
    $report_id = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;

    // Get report file path
    $stmt = $conn->prepare("SELECT report_file FROM lab_reports WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $report_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();

    if (!$report) {
        throw new Exception('Report not found');
    }

    // Get full file path and extension
    $file_path = __DIR__ . '/' . $report['report_file']; // Adjust path based on your upload directory
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    // Verify file exists
    if (!file_exists($file_path)) {
        throw new Exception('Report file not found on server');
    }

    // Create Python script path
    $python_script = __DIR__ . '/extract_text.py';

    // Execute Python script
    $command = sprintf('python "%s" "%s" "%s" 2>&1', $python_script, $file_path, $file_ext);
    $extracted_text = shell_exec($command);

    if ($extracted_text === null) {
        throw new Exception('Failed to extract text from file');
    }

    // Clean and format the extracted text
    $extracted_text = trim($extracted_text);
    if (empty($extracted_text)) {
        throw new Exception('No text could be extracted from the file');
    }

    echo json_encode([
        'success' => true,
        'text' => $extracted_text
    ]);
} catch (Exception $e) {
    error_log("Text extraction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
