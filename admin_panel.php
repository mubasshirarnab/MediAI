<?php
session_start();
require_once 'dbConnect.php';

// Check if user is authenticated as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Verify admin role
$query = "SELECT role_id FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['role_id'] != 4) {
    header("Location: login.php");
    exit();
}

// Fetch database statistics
$tables = ['users', 'patients', 'doctors', 'hospitals', 'admins', 'appointments', 'posts', 'medications', 'feedback'];
$stats = [];
foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    $stats[$table] = $result->fetch_assoc()['count'];
}

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle data fetch
if (isset($_GET['fetch']) && isset($_GET['table'])) {
    $table = $_GET['table'];
    if (in_array($table, $tables)) {
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM $table ORDER BY id DESC LIMIT 100";
        if ($table == 'medications') {
            $query = "SELECT * FROM medications ORDER BY id DESC LIMIT 100";
        }
        
        $result = $conn->query($query);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode($data);
        exit();
    }
}

// Handle filtered data fetch (for role-specific user filtering)
if (isset($_GET['fetch_filtered']) && isset($_GET['table']) && isset($_GET['role_id'])) {
    $table = $_GET['table'];
    $role_id = intval($_GET['role_id']);
    
    // Add CORS headers
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: http://localhost:3001');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    if ($table === 'users') {
        // Filter users by role_id
        $query = "SELECT * FROM users WHERE role_id = ? ORDER BY id DESC LIMIT 100";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $role_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Query failed: ' . $conn->error]);
            exit();
        }
        
        $data = $result->fetch_all(MYSQLI_ASSOC);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediAI Admin Panel - PHP Version</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Inter", sans-serif;
        }
        
        body {
            min-height: 100vh;
            background: #000117;
            color: #fff;
            padding: 20px;
        }
        
        .admin-container {
            background: #13153a;
            border-radius: 18px;
            box-shadow: 0 2px 12px 0 #0002;
            border: 1px solid rgb(163, 184, 239);
            padding: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #a259ff;
        }
        
        .admin-header h1 {
            color: #fff;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .welcome-text {
            color: #b3b3b3;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .logout-btn {
            padding: 10px 20px;
            background: #181d36;
            color: #fff;
            border: 1px solid rgb(163, 184, 239);
            border-radius: 30px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background: #a259ff;
            color: #fff;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #13153a;
            color: #fff;
            border: 1px solid rgb(163, 184, 239);
            padding: 20px;
            border-radius: 18px;
            text-align: center;
            box-shadow: 0 2px 12px 0 #0002;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            background: #181d36;
        }
        
        .stat-title {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .admin-content {
            display: flex;
            gap: 20px;
        }
        
        .sidebar {
            width: 250px;
            background: #181d36;
            border-radius: 10px;
            padding: 20px;
            border: 1px solid rgb(163, 184, 239);
        }
        
        .sidebar h3 {
            margin-bottom: 20px;
            color: #fff;
            font-weight: 600;
        }
        
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .nav-button {
            padding: 15px;
            background: #13153a;
            border: 1px solid rgb(163, 184, 239);
            border-radius: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
            font-weight: 500;
        }
        
        .nav-button:hover {
            background: #181d36;
            color: #a259ff;
        }
        
        .nav-button.active {
            background: #181d36;
            border-left: 4px solid #a259ff;
            color: #a259ff;
        }
        
        .main-content {
            flex: 1;
            background: #181d36;
            border: 1px solid rgb(163, 184, 239);
            border-radius: 10px;
            padding: 25px;
            min-height: 600px;
        }
        
        .table-container {
            background: #13153a;
            border: 1px solid rgb(163, 184, 239);
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 2px 12px 0 #0002;
        }
        
        .table-container h3 {
            margin-bottom: 20px;
            color: #fff;
            border-bottom: 2px solid #a259ff;
            padding-bottom: 10px;
            font-weight: 600;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .data-table th {
            background: #181d36;
            color: #fff;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid rgb(163, 184, 239);
        }
        
        .data-table td {
            padding: 12px;
            border-bottom: 1px solid rgb(163, 184, 239);
            color: #b3b3b3;
        }
        
        .data-table tr:hover {
            background: rgba(162, 89, 255, 0.1);
        }
        
        /* Action Buttons Styles */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            min-width: 80px;
            text-align: center;
        }
        
        .action-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .block-btn {
            background: #ff6b6b;
            color: white;
        }
        
        .block-btn:hover:not(:disabled) {
            background: #ff5252;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(255, 107, 107, 0.3);
        }
        
        .block-btn.unblock {
            background: #4caf50;
        }
        
        .block-btn.unblock:hover:not(:disabled) {
            background: #45a049;
            box-shadow: 0 3px 8px rgba(76, 175, 80, 0.3);
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
        }
        
        .delete-btn:hover:not(:disabled) {
            background: #d32f2f;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(244, 67, 54, 0.3);
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #b3b3b3;
        }
        
        .loading h3 {
            color: #fff;
        }
        
        .error {
            color: #ff6b6b;
            text-align: center;
            padding: 40px;
        }
        
        .no-data {
            text-align: center;
            color: #b3b3b3;
            padding: 40px;
        }
        
        .no-data h3 {
            color: #fff;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 0.7rem 1.2rem;
            border: 1px solid rgb(163, 184, 239);
            border-radius: 30px;
            background: #181d36;
            color: #fff;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            background: #13153a;
            border-color: #a259ff;
            box-shadow: 0 0 10px rgba(162, 89, 255, 0.3);
        }
        
        .search-input::placeholder {
            color: #b3b3b3;
        }
        
        @media (max-width: 768px) {
            .admin-content {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .admin-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🔐 MediAI Admin Panel</h1>
            <div class="user-info">
                <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="logout" class="logout-btn">🚪 Logout</button>
                </form>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">👥 Total Users</div>
                <div class="stat-number"><?php echo $stats['users']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">🏥 Patients</div>
                <div class="stat-number"><?php echo $stats['patients']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">👨‍⚕️ Doctors</div>
                <div class="stat-number"><?php echo $stats['doctors']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">🏨 Hospitals</div>
                <div class="stat-number"><?php echo $stats['hospitals']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">👨‍💼 Admins</div>
                <div class="stat-number"><?php echo $stats['admins']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-title">📅 Appointments</div>
                <div class="stat-number"><?php echo $stats['appointments']; ?></div>
            </div>
        </div>

        <div class="admin-content">
            <div class="sidebar">
                <h3>📊 Database Sections</h3>
                <div class="nav-menu">
                    <button class="nav-button" onclick="loadTable('users')">👥 All Users (<?php echo $stats['users']; ?>)</button>
                    <button class="nav-button" onclick="loadTableFiltered('users', 1)">🏥 Patients (<?php echo $stats['patients']; ?>)</button>
                    <button class="nav-button" onclick="loadTableFiltered('users', 2)">👨‍⚕️ Doctors (<?php echo $stats['doctors']; ?>)</button>
                    <button class="nav-button" onclick="loadTableFiltered('users', 3)">🏨 Hospitals (<?php echo $stats['hospitals']; ?>)</button>
                    <button class="nav-button" onclick="loadTableFiltered('users', 4)">👨‍💼 Admins (<?php echo $stats['admins']; ?>)</button>
                    <button class="nav-button" onclick="loadTable('appointments')">📅 Appointments (<?php echo $stats['appointments']; ?>)</button>
                    <button class="nav-button" onclick="loadTable('posts')">📝 Posts (<?php echo $stats['posts']; ?>)</button>
                    <button class="nav-button" onclick="loadTable('medications')">💊 Medications (<?php echo $stats['medications']; ?>)</button>
                    <button class="nav-button" onclick="loadTable('feedback')">💬 Feedback (<?php echo $stats['feedback']; ?>)</button>
                </div>
            </div>

            <div class="main-content">
                <div class="table-container">
                    <div class="loading">
                        <h3>Select a table to view data</h3>
                        <p>Click on any section from the sidebar to load the data</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadTable(tableName) {
            // Update active button
            document.querySelectorAll('.nav-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show loading
            const container = document.querySelector('.table-container');
            container.innerHTML = '<div class="loading"><h3>Loading ' + tableName + ' data...</h3></div>';
            
            // Fetch data
            fetch(`?fetch=1&table=${tableName}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        container.innerHTML = '<div class="no-data"><h3>' + tableName + ' Table</h3><p>No data available</p></div>';
                        return;
                    }
                    
                    // Create table
                    let html = `<h3>${tableName.toUpperCase()} Table (${data.length} records)</h3>`;
                    html += '<table class="data-table">';
                    
                    // Headers
                    html += '<thead><tr>';
                    Object.keys(data[0]).forEach(key => {
                        // Hide password and OTP columns for users table
                        if (tableName === 'users' && (key === 'password' || key === 'otp')) {
                            return;
                        }
                        html += `<th>${key.toUpperCase()}</th>`;
                    });
                    html += '<th>ACTIONS</th>';
                    html += '</tr></thead>';
                    
                    // Data rows
                    html += '<tbody>';
                    data.forEach(row => {
                        html += '<tr>';
                        Object.keys(row).forEach(key => {
                            // Hide password and OTP columns for users table
                            if (tableName === 'users' && (key === 'password' || key === 'otp')) {
                                return;
                            }
                            const value = row[key];
                            const displayValue = value === null ? '' : String(value);
                            html += `<td>${displayValue}</td>`;
                        });
                        // Add action buttons
                        const recordId = row.id || row.user_id;
                        const recordName = row.name || `Record ${recordId}`;
                        const currentStatus = row.status || 'authorized';
                        html += `<td>
                            <div class="action-buttons">
                                <button class="action-btn block-btn ${currentStatus === 'authorized' ? 'block' : 'unblock'}" 
                                        onclick="handleBlock(${recordId}, '${tableName}', '${recordName}', '${currentStatus}')">
                                    ${currentStatus === 'authorized' ? '🚫 Block' : '✅ Unblock'}
                                </button>
                                <button class="action-btn delete-btn" 
                                        onclick="handleDelete(${recordId}, '${tableName}', '${recordName}')">
                                    🗑️ Delete
                                </button>
                            </div>
                        </td>`;
                        html += '</tr>';
                    });
                    html += '</tbody>';
                    html += '</table>';
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    container.innerHTML = '<div class="error"><h3>Error</h3><p>Failed to load data: ' + error.message + '</p></div>';
                });
        }

        function loadTableFiltered(tableName, roleId) {
            // Update active button
            document.querySelectorAll('.nav-button').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Show loading
            const container = document.querySelector('.table-container');
            container.innerHTML = '<div class="loading"><h3>Loading filtered ' + tableName + ' data...</h3></div>';
            
            // Fetch data
            fetch(`?fetch_filtered=1&table=${tableName}&role_id=${roleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) {
                        container.innerHTML = '<div class="no-data"><h3>' + tableName + ' Table</h3><p>No data available</p></div>';
                        return;
                    }
                    
                    // Create table
                    let html = `<h3>${tableName.toUpperCase()} Table - Role ID ${roleId} (${data.length} records)</h3>`;
                    html += '<table class="data-table">';
                    
                    // Headers
                    html += '<thead><tr>';
                    Object.keys(data[0]).forEach(key => {
                        // Hide password and OTP columns for users table
                        if (tableName === 'users' && (key === 'password' || key === 'otp')) {
                            return;
                        }
                        html += `<th>${key.toUpperCase()}</th>`;
                    });
                    html += '<th>ACTIONS</th>';
                    html += '</tr></thead>';
                    
                    // Data rows
                    html += '<tbody>';
                    data.forEach(row => {
                        html += '<tr>';
                        Object.keys(row).forEach(key => {
                            // Hide password and OTP columns for users table
                            if (tableName === 'users' && (key === 'password' || key === 'otp')) {
                                return;
                            }
                            const value = row[key];
                            const displayValue = value === null ? '' : String(value);
                            html += `<td>${displayValue}</td>`;
                        });
                        // Add action buttons
                        const recordId = row.id || row.user_id;
                        const recordName = row.name || `Record ${recordId}`;
                        const currentStatus = row.status || 'authorized';
                        html += `<td>
                            <div class="action-buttons">
                                <button class="action-btn block-btn ${currentStatus === 'authorized' ? 'block' : 'unblock'}" 
                                        onclick="handleBlock(${recordId}, '${tableName}', '${recordName}', '${currentStatus}')">
                                    ${currentStatus === 'authorized' ? '🚫 Block' : '✅ Unblock'}
                                </button>
                                <button class="action-btn delete-btn" 
                                        onclick="handleDelete(${recordId}, '${tableName}', '${recordName}')">
                                    🗑️ Delete
                                </button>
                            </div>
                        </td>`;
                        html += '</tr>';
                    });
                    html += '</tbody>';
                    html += '</table>';
                    
                    container.innerHTML = html;
                })
                .catch(error => {
                    container.innerHTML = '<div class="error"><h3>Error</h3><p>Failed to load data: ' + error.message + '</p></div>';
                });
        }

        // Handle delete action
        function handleDelete(id, table, recordName) {
            if (!confirm(`Are you sure you want to PERMANENTLY DELETE this ${recordName}? This action cannot be undone!`)) {
                return;
            }

            fetch('admin_delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    table: table
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ ${recordName} permanently deleted successfully!`);
                    // Reload the current table
                    const activeButton = document.querySelector('.nav-button.active');
                    if (activeButton) {
                        activeButton.click();
                    }
                } else {
                    alert(`❌ Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert(`❌ Failed to delete ${recordName}: ${error.message}`);
            });
        }

        // Handle block/unblock action
        function handleBlock(id, table, recordName, currentStatus) {
            const action = currentStatus === 'authorized' ? 'block' : 'unblock';
            const actionText = action === 'block' ? 'BLOCK' : 'UNBLOCK';
            
            if (!confirm(`Are you sure you want to ${actionText} this ${recordName}?`)) {
                return;
            }

            fetch('admin_block.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    table: table,
                    action: action
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ ${recordName} ${action}ed successfully!`);
                    // Reload the current table
                    const activeButton = document.querySelector('.nav-button.active');
                    if (activeButton) {
                        activeButton.click();
                    }
                } else {
                    alert(`❌ Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Block error:', error);
                alert(`❌ Failed to ${action} ${recordName}: ${error.message}`);
            });
        }
    </script>
</body>
</html>
