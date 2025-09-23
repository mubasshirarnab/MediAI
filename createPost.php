<?php
session_start();
require_once 'dbConnect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all incoming data
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
error_log("SESSION data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    error_log("No user_id in session");
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

$user_id = $_SESSION['user_id'];
$community_id = $_POST['community_id'] ?? null;
$caption = $_POST['caption'] ?? '';

// Debug information
error_log("Creating post - User ID: $user_id, Community ID: $community_id, Caption: $caption");

// Validate user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    error_log("Invalid user ID: $user_id");
    die(json_encode(['success' => false, 'message' => 'Invalid user ID']));
}

if (!$community_id) {
    error_log("No community_id provided");
    die(json_encode(['success' => false, 'message' => 'Community ID is required']));
}

// Validate community exists
$stmt = $conn->prepare("SELECT id FROM community WHERE id = ?");
$stmt->bind_param("i", $community_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    error_log("Invalid community ID: $community_id");
    die(json_encode(['success' => false, 'message' => 'Invalid community ID']));
}

if (empty($caption)) {
    error_log("Empty caption");
    die(json_encode(['success' => false, 'message' => 'Caption is required']));
}

// Check if user is a member of the community
$stmt = $conn->prepare("SELECT id FROM community_members WHERE user_id = ? AND community_id = ?");
$stmt->bind_param("ii", $user_id, $community_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("User $user_id is not a member of community $community_id");
    die(json_encode(['success' => false, 'message' => 'You are not a member of this community']));
}

// Start transaction
$conn->begin_transaction();

try {
    // Handle file upload if photo is provided
    $photo = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'postImages/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory: " . error_get_last()['message']);
            }
        }
        
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
        
        error_log("Attempting to upload file to: $target_path");
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $photo = $new_filename;
            error_log("File uploaded successfully: $new_filename");
        } else {
            $error = error_get_last();
            error_log("File upload failed: " . print_r($error, true));
            throw new Exception("Failed to upload photo: " . $error['message']);
        }
    }

    // Insert post with or without photo
    $stmt = $conn->prepare("INSERT INTO posts (post_creator, community_id, caption, photo) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param("iiss", $user_id, $community_id, $caption, $photo);
    
    // Execute the statement
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Get the new post ID
    $post_id = $conn->insert_id;
    error_log("Post created successfully with ID: $post_id");
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true, 
        'post_id' => $post_id,
        'message' => 'Post created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error creating post: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to create post: ' . $e->getMessage()
    ]);
}
?> 