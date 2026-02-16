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

    $stmt = $conn->prepare("SELECT report_file FROM lab_reports WHERE id = ? AND patient_id = ?");
    $stmt->bind_param("ii", $report_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();

    if (!$report) {
        throw new Exception('Report not found');
    }

    $file_path = __DIR__ . '/' . $report['report_file']; // Adjust path based on your upload directory
    $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

    if (!file_exists($file_path)) {
        throw new Exception('Report file not found on server');
    }

    $python_script = __DIR__ . '/extract_text.py';

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

    // Prepare prompt for summarization
    $prompt = "You are a helpful medical assistant. Read the lab report text below and provide a concise, helpfull, detailed, plain-language summary suitable for a patient. Include: 1) a short overall summary (4-5 sentences), 2) any key abnormal findings (list values and what they might mean), and 3) suggested next steps (In details) (e.g., discuss with doctor, repeat tests, urgent care if applicable). Keep the language non-technical when possible.\n\nLab report text:\n" . $extracted_text;

    // OPENROUTER / MODEL CALL (server-side)
    // NOTE: The API key is stored here. Replace the value with your OpenRouter key if different.
    $api_key = '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';
    $url = 'https://openrouter.ai/api/v1/chat/completions';

    $payload = [
        'model' => 'microsoft/phi-3-mini-128k-instruct',
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 800,
        'temperature' => 0.0
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'X-Title: MediAI_LabReport'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $api_response = curl_exec($ch);
    $curl_err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($api_response === false) {
        throw new Exception('Model API request failed: ' . $curl_err);
    }

    $api_data = json_decode($api_response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON from model API');
    }

    if ($http_code < 200 || $http_code >= 300) {
        $err_msg = isset($api_data['error']) ? json_encode($api_data['error']) : $api_response;
        throw new Exception('Model API returned HTTP ' . $http_code . ': ' . $err_msg);
    }

    // Extract summary from response
    $summary = null;
    if (isset($api_data['choices'][0]['message']['content'])) {
        $summary = $api_data['choices'][0]['message']['content'];
    } elseif (isset($api_data['choices'][0]['text'])) {
        $summary = $api_data['choices'][0]['text'];
    }

    if (!$summary) {
        // Fallback: return the raw extracted text if summarization failed
        $summary = "(Could not generate a summary.)\n\n" . $extracted_text;
    }

    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'text' => $extracted_text
    ]);
} catch (Exception $e) {
    error_log("Text extraction error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
