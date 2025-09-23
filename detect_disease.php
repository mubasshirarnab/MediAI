<?php
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Check if image file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No image uploaded or upload error']);
    exit;
}

// Check if detection type is specified
if (!isset($_POST['type'])) {
    echo json_encode(['success' => false, 'error' => 'Detection type not specified']);
    exit;
}

$detection_type = $_POST['type'];
$uploaded_file = $_FILES['image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($uploaded_file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed']);
    exit;
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
$unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
$upload_path = $upload_dir . $unique_filename;

// Move uploaded file to uploads directory
if (!move_uploaded_file($uploaded_file['tmp_name'], $upload_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file']);
    exit;
}

// Get absolute path for the uploaded image
$absolute_path = realpath($upload_path);

// Determine the API endpoint based on detection type
$api_endpoint = '';
$detection_name = '';

switch ($detection_type) {
    case 'breast':
        $api_endpoint = 'http://127.0.0.1:5000/detect-breast';
        $detection_name = 'Breast Cancer Detection';
        break;
    case 'tumor':
        $api_endpoint = 'http://127.0.0.1:5000/detect-tumor';
        $detection_name = 'Brain Tumor Detection';
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid detection type']);
        exit;
}

// Prepare the data for API call
$data = array("image_path" => $absolute_path);
$json_data = json_encode($data);

// Initialize cURL session
$ch = curl_init($api_endpoint);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_data)
));
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Execute the request
$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    $error_message = curl_error($ch);
    curl_close($ch);
    
    // Clean up uploaded file
    if (file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    echo json_encode(['success' => false, 'error' => 'Connection error: ' . $error_message]);
    exit;
}

// Get HTTP status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Clean up uploaded file
if (file_exists($upload_path)) {
    unlink($upload_path);
}

// Check if the request was successful (accept 200 and 202)
if ($http_code !== 200 && $http_code !== 202) {
    echo json_encode(['success' => false, 'error' => 'Detection service unavailable. Please try again later.']);
    exit;
}

// Decode the response
$result = json_decode($response, true);

if ($result === null) {
    echo json_encode(['success' => false, 'error' => 'Invalid response from detection service']);
    exit;
}

// Format the response for the frontend
$formatted_response = [
    'success' => true,
    'detection_type' => $detection_name,
    'result' => isset($result['result']) ? $result['result'] : 'Analysis completed',
    'confidence' => isset($result['confidence']) ? $result['confidence'] : null,
    'details' => isset($result['details']) ? $result['details'] : 'Analysis completed successfully'
];

echo json_encode($formatted_response);
?> 