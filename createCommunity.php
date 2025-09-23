<?php
session_start();
require_once 'dbConnect.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not logged in']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $creator_id = $_SESSION['user_id'];

    if (empty($name)) {
        die(json_encode(['success' => false, 'message' => 'Community name is required']));
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert community
        $stmt = $conn->prepare("INSERT INTO community (name, description, community_creator) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $description, $creator_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating community: " . $stmt->error);
        }
        
        $community_id = $conn->insert_id;

        // Automatically join creator to the community
        $join_stmt = $conn->prepare("INSERT INTO community_members (user_id, community_id) VALUES (?, ?)");
        $join_stmt->bind_param("ii", $creator_id, $community_id);
        
        if (!$join_stmt->execute()) {
            throw new Exception("Error joining community: " . $join_stmt->error);
        }

        // Handle photo upload if exists
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'groupsImages/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $new_filename = $community_id . '.' . $file_extension;
            $target_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_stmt = $conn->prepare("UPDATE community SET photo = ? WHERE id = ?");
                $photo_stmt->bind_param("si", $new_filename, $community_id);
                if (!$photo_stmt->execute()) {
                    throw new Exception("Error updating photo: " . $photo_stmt->error);
                }
            }
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Community created successfully']);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 