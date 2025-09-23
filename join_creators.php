<?php
require_once 'dbConnect.php';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all communities and their creators
$sql = "SELECT id, community_creator FROM community";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " communities<br>";
    
    while ($row = $result->fetch_assoc()) {
        $community_id = $row['id'];
        $creator_id = $row['community_creator'];
        
        // Check if creator is already a member
        $check_sql = "SELECT id FROM community_members WHERE user_id = ? AND community_id = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $creator_id, $community_id);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        
        if (!$exists) {
            // Insert creator as member
            $insert_sql = "INSERT INTO community_members (user_id, community_id) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ii", $creator_id, $community_id);
            
            if ($stmt->execute()) {
                echo "Added creator (ID: $creator_id) to community (ID: $community_id)<br>";
            } else {
                echo "Error adding creator to community: " . $stmt->error . "<br>";
            }
        } else {
            echo "Creator (ID: $creator_id) already a member of community (ID: $community_id)<br>";
        }
    }
} else {
    echo "No communities found in database<br>";
}

$conn->close();
?> 