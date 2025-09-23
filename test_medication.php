<?php
require_once 'dbConnect.php';

echo "<h2>Testing Medication Tables</h2>";

// Test 1: Check if tables exist
echo "<h3>1. Checking if tables exist:</h3>";
$tables = ['medication', 'medication_times'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists<br>";
    } else {
        echo "✗ Table '$table' does not exist<br>";
    }
}

// Test 2: Check table structure
echo "<h3>2. Checking table structure:</h3>";
foreach ($tables as $table) {
    echo "<h4>Table: $table</h4>";
    $result = $conn->query("DESCRIBE $table");
    while ($row = $result->fetch_assoc()) {
        echo "Column: {$row['Field']} - Type: {$row['Type']} - Null: {$row['Null']} - Key: {$row['Key']}<br>";
    }
}

// Test 3: Check if there are any existing records
echo "<h3>3. Checking existing records:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM medication");
$med_count = $result->fetch_assoc()['count'];
echo "Medication records: $med_count<br>";

$result = $conn->query("SELECT COUNT(*) as count FROM medication_times");
$time_count = $result->fetch_assoc()['count'];
echo "Medication times records: $time_count<br>";

// Test 4: Show sample data
if ($med_count > 0) {
    echo "<h3>4. Sample medication data:</h3>";
    $result = $conn->query("
        SELECT m.id, m.medicine_name, m.meal_time, 
               GROUP_CONCAT(mt.dose_time ORDER BY mt.dose_time SEPARATOR ', ') as times
        FROM medication m 
        LEFT JOIN medication_times mt ON m.id = mt.medication_id 
        GROUP BY m.id 
        LIMIT 5
    ");
    
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} - Medicine: {$row['medicine_name']} - Meal: {$row['meal_time']} - Times: {$row['times']}<br>";
    }
}

// Test 5: Manual insertion test
echo "<h3>5. Testing manual insertion:</h3>";
try {
    $conn->begin_transaction();
    
    // Insert test medication
    $stmt = $conn->prepare("INSERT INTO medication (user_id, medicine_name, meal_time, begin_date, end_date) VALUES (?, ?, ?, ?, ?)");
    $user_id = 1; // Assuming user ID 1 exists
    $medicine_name = "Test Medicine";
    $meal_time = "After Meal";
    $begin_date = "2024-01-01";
    $end_date = "2024-12-31";
    
    $stmt->bind_param("issss", $user_id, $medicine_name, $meal_time, $begin_date, $end_date);
    
    if ($stmt->execute()) {
        $medication_id = $conn->insert_id;
        echo "✓ Test medication inserted with ID: $medication_id<br>";
        
        // Insert test times
        $time_stmt = $conn->prepare("INSERT INTO medication_times (medication_id, dose_time) VALUES (?, ?)");
        $test_times = ['09:00:00', '14:00:00', '20:00:00'];
        
        foreach ($test_times as $time) {
            $time_stmt->bind_param("is", $medication_id, $time);
            if ($time_stmt->execute()) {
                echo "✓ Time inserted: $time<br>";
            } else {
                echo "✗ Failed to insert time: $time - Error: " . $time_stmt->error . "<br>";
            }
        }
        
        $time_stmt->close();
        $conn->commit();
        echo "✓ Test completed successfully!<br>";
        
        // Clean up test data
        $conn->query("DELETE FROM medication WHERE id = $medication_id");
        echo "✓ Test data cleaned up<br>";
        
    } else {
        echo "✗ Failed to insert test medication: " . $stmt->error . "<br>";
        $conn->rollback();
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    $conn->rollback();
    echo "✗ Test failed: " . $e->getMessage() . "<br>";
}

echo "<h3>6. Check your error log for debugging information:</h3>";
echo "Look in your PHP error log for messages starting with 'Medicine Name:', 'Dose Times:', etc.<br>";
echo "This will help identify if the form data is being received correctly.<br>";

$conn->close();
?> 