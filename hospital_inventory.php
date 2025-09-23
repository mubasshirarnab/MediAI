<?php
session_start();
require_once 'dbConnect.php';
include 'hospitalnav.php';

// Only allow hospitals
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'hospital') {
    header("Location: login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];

// Handle Add/Edit/Delete/Stock/Transaction actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Item
    if (isset($_POST['action']) && $_POST['action'] === 'add_item') {
        $name = trim($_POST['item_name']);
        $desc = trim($_POST['item_description']);
        $cat = trim($_POST['category']);
        $unit = trim($_POST['unit']);
        $stmt = $conn->prepare("INSERT INTO inventory_items (hospital_id, item_name, item_description, category, unit) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $hospital_id, $name, $desc, $cat, $unit);
        $stmt->execute();
        $item_id = $stmt->insert_id;
        $conn->query("INSERT INTO inventory_stock (item_id, quantity) VALUES ($item_id, 0)");
        header("Location: hospital_inventory.php?msg=added");
        exit();
    }
    // Edit Item
    if (isset($_POST['action']) && $_POST['action'] === 'edit_item') {
        $id = intval($_POST['item_id']);
        $name = trim($_POST['item_name']);
        $desc = trim($_POST['item_description']);
        $cat = trim($_POST['category']);
        $unit = trim($_POST['unit']);
        $stmt = $conn->prepare("UPDATE inventory_items SET item_name=?, item_description=?, category=?, unit=? WHERE id=? AND hospital_id=?");
        $stmt->bind_param("ssssii", $name, $desc, $cat, $unit, $id, $hospital_id);
        $stmt->execute();
        header("Location: hospital_inventory.php?msg=edited");
        exit();
    }
    // Delete Item
    if (isset($_POST['action']) && $_POST['action'] === 'delete_item') {
        $id = intval($_POST['item_id']);
        $conn->query("DELETE FROM inventory_items WHERE id=$id AND hospital_id=$hospital_id");
        header("Location: hospital_inventory.php?msg=deleted");
        exit();
    }
    // Stock In/Out
    if (isset($_POST['action']) && $_POST['action'] === 'stock_transaction') {
        $item_id = intval($_POST['item_id']);
        $type = $_POST['transaction_type'];
        $qty = intval($_POST['quantity']);
        $remarks = trim($_POST['remarks']);
        // Update stock
        if ($type === 'in') {
            $conn->query("UPDATE inventory_stock SET quantity = quantity + $qty WHERE item_id=$item_id");
        } else {
            $conn->query("UPDATE inventory_stock SET quantity = GREATEST(quantity - $qty, 0) WHERE item_id=$item_id");
        }
        // Log transaction
        $stmt = $conn->prepare("INSERT INTO inventory_transactions (item_id, hospital_id, transaction_type, quantity, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $item_id, $hospital_id, $type, $qty, $remarks);
        $stmt->execute();
        header("Location: hospital_inventory.php?msg=stocked");
        exit();
    }
}

// Fetch inventory items and stock
$items = [];
$sql = "SELECT i.*, s.quantity FROM inventory_items i 
        LEFT JOIN inventory_stock s ON i.id = s.item_id 
        WHERE i.hospital_id = $hospital_id ORDER BY i.created_at DESC";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) $items[] = $row;

// Fetch transactions (for modal)
$transactions = [];
if (isset($_GET['view_transactions'])) {
    $item_id = intval($_GET['view_transactions']);
    $tr = $conn->query("SELECT * FROM inventory_transactions WHERE item_id=$item_id AND hospital_id=$hospital_id ORDER BY transaction_date DESC");
    while ($row = $tr->fetch_assoc()) $transactions[] = $row;
}

// Fetch all categories for filter
$categories = [];
$cat_res = $conn->query("SELECT DISTINCT category FROM inventory_items WHERE hospital_id = $hospital_id AND category IS NOT NULL AND category != ''");
while ($row = $cat_res->fetch_assoc()) $categories[] = $row['category'];

// Fetch recent activity (last 5 transactions)
$recent_activity = [];
$ra_res = $conn->query("SELECT t.*, i.item_name FROM inventory_transactions t JOIN inventory_items i ON t.item_id = i.id WHERE t.hospital_id = $hospital_id ORDER BY t.transaction_date DESC LIMIT 5");
while ($row = $ra_res->fetch_assoc()) $recent_activity[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Hospital Inventory Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #000117;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
        }
        .inventory-container {
            max-width: 1100px;
            margin: 40px auto;
            background: rgba(24,28,43,0.98);
            border-radius: 18px;
            box-shadow: 0 2px 24px 0 #0004;
            padding: 2.5rem 2rem 2rem 2rem;
            animation: fadeIn 1s;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(40px);} to { opacity: 1; transform: none; } }
        h1 {
            text-align: center;
            font-size: 2.5rem;
            letter-spacing: 2px;
            margin-bottom: 2rem;
            background: linear-gradient(90deg, #38bdf8, #a855f7, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradientMove 3s infinite alternate;
        }
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }
        .inventory-controls {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        .recent-activity-btn {
            background: linear-gradient(90deg, #a855f7, #38bdf8);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 0.7rem 2rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px #38bdf844;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            transition: background 0.2s, transform 0.2s;
            outline: none;
        }
        .recent-activity-btn:hover {
            background: linear-gradient(90deg, #38bdf8, #a855f7);
            transform: scale(1.05);
        }
        .search-bar {
            flex: 1 1 220px;
            padding: 0.7rem 1.2rem;
            border-radius: 30px;
            border: 1.5px solid #23244a;
            background: #23244a;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 2px 8px #0002;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .search-bar:focus {
            border: 1.5px solid #38bdf8;
            box-shadow: 0 0 0 2px #38bdf855;
        }
        .category-dropdown {
            padding: 0.7rem 1.2rem;
            border-radius: 30px;
            border: 1.5px solid #23244a;
            background: #23244a;
            color: #fff;
            font-size: 1rem;
            box-shadow: 0 2px 8px #0002;
            transition: border 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .category-dropdown:focus {
            border: 1.5px solid #a855f7;
            box-shadow: 0 0 0 2px #a855f755;
        }
        .add-btn {
            background: linear-gradient(90deg, #38bdf8, #a855f7);
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 0.7rem 1.2rem;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 8px #38bdf844;
            transition: background 0.2s, transform 0.2s;
            /* margin-bottom: 1.5rem; */
        }
        .add-btn:hover {
            background: linear-gradient(90deg, #a855f7, #38bdf8);
            transform: scale(1.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background: rgba(0,0,0,0.2);
            border-radius: 12px;
            overflow: hidden;
            animation: fadeIn 1.2s;
        }
        th, td {
            padding: 1rem;
            text-align: left;
        }
        th {
            background: #181c2b;
            color: #38bdf8;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        tr {
            transition: background 0.2s;
        }
        tr:hover {
            background: rgba(56,189,248,0.08);
        }
        td {
            border-bottom: 1px solid #23244a;
        }
        .actions button, .actions a {
            background: none;
            border: none;
            color: #38bdf8;
            font-size: 1.2rem;
            margin-right: 10px;
            cursor: pointer;
            transition: color 0.2s, transform 0.2s;
        }
        .actions button:hover, .actions a:hover {
            color: #a855f7;
            transform: scale(1.2);
        }
        .stock {
            font-weight: bold;
            color: #2fff8d;
            font-size: 1.1rem;
        }
        /* Modal styles */
        .modal-bg {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            animation: fadeIn 0.3s;
        }
        .modal {
            background: #181c2b;
            border-radius: 18px;
            box-shadow: 0 0 40px #000a, 0 0 20px #38bdf833;
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 420px;
            margin: 80px auto 0 auto;
            position: relative;
            animation: fadeIn 0.4s;
        }
        .modal h2 {
            margin-top: 0;
            color: #38bdf8;
            font-size: 1.5rem;
            margin-bottom: 1.2rem;
        }
        .modal label {
            display: block;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .modal input, .modal textarea, .modal select {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 1.2rem;
            border-radius: 8px;
            border: 1px solid #23244a;
            background: #23244a;
            color: #fff;
            font-size: 1rem;
        }
        .modal textarea { min-height: 60px; }
        .modal .modal-actions {
            text-align: right;
        }
        .modal .modal-actions button {
            background: linear-gradient(90deg, #38bdf8, #a855f7);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.6rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-left: 10px;
            transition: background 0.2s, transform 0.2s;
        }
        .modal .modal-actions button:hover {
            background: linear-gradient(90deg, #a855f7, #38bdf8);
            transform: scale(1.08);
        }
        .close-modal {
            position: absolute;
            top: 18px;
            right: 28px;
            background: none;
            border: none;
            color: #fff;
            font-size: 2.2rem;
            cursor: pointer;
            z-index: 1003;
            transition: color 0.2s;
        }
        .close-modal:hover {
            color: #a855f7;
        }
        /* Transaction log modal */
        .transaction-log {
            max-height: 400px;
            overflow-y: auto;
        }
        .transaction-log table th, .transaction-log table td {
            font-size: 0.98rem;
        }
        .transaction-in { color: #2fff8d; }
        .transaction-out { color: #ff4444; }
        @media (max-width: 700px) {
            .inventory-container { padding: 1rem; }
            .modal { padding: 1rem; }
        }
        @media (max-width: 900px) {
            .inventory-controls {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            .add-btn, .recent-activity-btn, .search-bar, .category-dropdown {
                width: 100%;
                min-width: 0;
            }
        }
        .low-stock {
            background: rgba(255,68,68,0.08) !important;
            animation: flashRed 1s;
        }
        .low-stock-icon {
            color: #ff4444;
            margin-left: 8px;
            font-size: 1.1em;
            animation: flashRed 1s infinite alternate;
        }
        @keyframes flashRed {
            0% { box-shadow: 0 0 0px #ff4444; }
            100% { box-shadow: 0 0 12px #ff4444; }
        }
    </style>
</head>
<body>
    <div class="inventory-container">
        <h1>Inventory Management</h1>
        <div class="inventory-controls">
            <button class="recent-activity-btn" onclick="openRecentActivity()"><i class="fa fa-bolt"></i> Recent Activity</button>
            <input type="text" id="searchBox" class="search-bar" placeholder="Search item name...">
            <select id="categoryFilter" class="category-dropdown">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="add-btn" onclick="openModal('add')"><i class="fa fa-plus"></i> Add Item</button>
        </div>
        <table id="inventoryTable">
            <tr>
                <th>Item Name</th>
                <th>Category</th>
                <th>Unit</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr class="inv-row<?php echo ($item['quantity'] <= 10 ? ' low-stock' : ''); ?>">
                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                <td><?php echo htmlspecialchars($item['category']); ?></td>
                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                <td class="stock">
                    <?php echo (int)$item['quantity']; ?>
                    <?php if ($item['quantity'] <= 10): ?>
                        <span title="Low Stock" class="low-stock-icon"><i class="fa fa-exclamation-triangle"></i></span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <button title="Edit" onclick="openModal('edit', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>', '<?php echo htmlspecialchars(addslashes($item['item_description'])); ?>', '<?php echo htmlspecialchars(addslashes($item['category'])); ?>', '<?php echo htmlspecialchars(addslashes($item['unit'])); ?>')"><i class="fa fa-edit"></i></button>
                    <button title="Delete" onclick="openModal('delete', <?php echo $item['id']; ?>)"><i class="fa fa-trash"></i></button>
                    <button title="Stock In/Out" onclick="openModal('stock', <?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>')"><i class="fa fa-exchange-alt"></i></button>
                    <a title="View Transactions" href="hospital_inventory.php?view_transactions=<?php echo $item['id']; ?>"><i class="fa fa-history"></i></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Modals -->
    <div class="modal-bg" id="modal-bg">
        <div class="modal" id="modal-add" style="display:none;">
            <button class="close-modal" onclick="closeModal()">&times;</button>
            <h2>Add Item</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_item">
                <label>Item Name</label>
                <input type="text" name="item_name" required>
                <label>Description</label>
                <textarea name="item_description"></textarea>
                <label>Category</label>
                <input type="text" name="category">
                <label>Unit</label>
                <input type="text" name="unit">
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()">Cancel</button>
                    <button type="submit">Add</button>
                </div>
            </form>
        </div>
        <div class="modal" id="modal-edit" style="display:none;">
            <button class="close-modal" onclick="closeModal()">&times;</button>
            <h2>Edit Item</h2>
            <form method="POST">
                <input type="hidden" name="action" value="edit_item">
                <input type="hidden" name="item_id" id="edit_item_id">
                <label>Item Name</label>
                <input type="text" name="item_name" id="edit_item_name" required>
                <label>Description</label>
                <textarea name="item_description" id="edit_item_description"></textarea>
                <label>Category</label>
                <input type="text" name="category" id="edit_item_category">
                <label>Unit</label>
                <input type="text" name="unit" id="edit_item_unit">
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()">Cancel</button>
                    <button type="submit">Save</button>
                </div>
            </form>
        </div>
        <div class="modal" id="modal-delete" style="display:none;">
            <button class="close-modal" onclick="closeModal()">&times;</button>
            <h2>Delete Item</h2>
            <form method="POST">
                <input type="hidden" name="action" value="delete_item">
                <input type="hidden" name="item_id" id="delete_item_id">
                <p>Are you sure you want to delete this item?</p>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()">Cancel</button>
                    <button type="submit" style="background: #ff4444;">Delete</button>
                </div>
            </form>
        </div>
        <div class="modal" id="modal-stock" style="display:none;">
            <button class="close-modal" onclick="closeModal()">&times;</button>
            <h2>Stock In/Out</h2>
            <form method="POST">
                <input type="hidden" name="action" value="stock_transaction">
                <input type="hidden" name="item_id" id="stock_item_id">
                <label>Transaction Type</label>
                <select name="transaction_type" id="stock_transaction_type">
                    <option value="in">Stock In</option>
                    <option value="out">Stock Out</option>
                </select>
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" required>
                <label>Remarks</label>
                <textarea name="remarks"></textarea>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()">Cancel</button>
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Transaction Log Modal -->
    <?php if (isset($_GET['view_transactions'])): ?>
    <div class="modal-bg" style="display:block;" onclick="window.location.href='hospital_inventory.php'">
        <div class="modal transaction-log" onclick="event.stopPropagation()">
            <button class="close-modal" onclick="window.location.href='hospital_inventory.php'">&times;</button>
            <h2>Transaction Log</h2>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Remarks</th>
                </tr>
                <?php foreach ($transactions as $tr): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tr['transaction_date']); ?></td>
                    <td class="transaction-<?php echo $tr['transaction_type']; ?>"><?php echo strtoupper($tr['transaction_type']); ?></td>
                    <td><?php echo (int)$tr['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($tr['remarks']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Activity Modal -->
    <div class="modal-bg" id="recent-activity-bg" style="display:none;">
        <div class="modal transaction-log" style="max-width:600px;" onclick="event.stopPropagation()">
            <button class="close-modal" onclick="closeRecentActivity()">&times;</button>
            <h2>Recent Activity</h2>
            <table>
                <tr><th>Date</th><th>Item</th><th>Type</th><th>Qty</th><th>Remarks</th></tr>
                <?php foreach ($recent_activity as $ra): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ra['transaction_date']); ?></td>
                    <td><?php echo htmlspecialchars($ra['item_name']); ?></td>
                    <td class="transaction-<?php echo $ra['transaction_type']; ?>"><?php echo strtoupper($ra['transaction_type']); ?></td>
                    <td><?php echo (int)$ra['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($ra['remarks']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <script>
        function openModal(type, id = '', name = '', desc = '', cat = '', unit = '') {
            document.getElementById('modal-bg').style.display = 'block';
            document.getElementById('modal-add').style.display = (type === 'add') ? 'block' : 'none';
            document.getElementById('modal-edit').style.display = (type === 'edit') ? 'block' : 'none';
            document.getElementById('modal-delete').style.display = (type === 'delete') ? 'block' : 'none';
            document.getElementById('modal-stock').style.display = (type === 'stock') ? 'block' : 'none';
            if (type === 'edit') {
                document.getElementById('edit_item_id').value = id;
                document.getElementById('edit_item_name').value = name;
                document.getElementById('edit_item_description').value = desc;
                document.getElementById('edit_item_category').value = cat;
                document.getElementById('edit_item_unit').value = unit;
            }
            if (type === 'delete') {
                document.getElementById('delete_item_id').value = id;
            }
            if (type === 'stock') {
                document.getElementById('stock_item_id').value = id;
                document.querySelector('#modal-stock h2').innerText = 'Stock In/Out: ' + name;
            }
        }
        function closeModal() {
            document.getElementById('modal-bg').style.display = 'none';
            document.getElementById('modal-add').style.display = 'none';
            document.getElementById('modal-edit').style.display = 'none';
            document.getElementById('modal-delete').style.display = 'none';
            document.getElementById('modal-stock').style.display = 'none';
        }

        // Filter and search functionality
        const searchBox = document.getElementById('searchBox');
        const categoryFilter = document.getElementById('categoryFilter');
        const table = document.getElementById('inventoryTable');
        searchBox.addEventListener('input', filterTable);
        categoryFilter.addEventListener('change', filterTable);
        function filterTable() {
            const search = searchBox.value.toLowerCase();
            const cat = categoryFilter.value;
            for (let row of table.rows) {
                if (row.rowIndex === 0) continue;
                const name = row.cells[0].innerText.toLowerCase();
                const category = row.cells[1].innerText;
                let show = true;
                if (search && !name.includes(search)) show = false;
                if (cat && category !== cat) show = false;
                row.style.display = show ? '' : 'none';
            }
        }

        // Recent activity modal
        function openRecentActivity() {
            document.getElementById('recent-activity-bg').style.display = 'block';
        }
        function closeRecentActivity() {
            document.getElementById('recent-activity-bg').style.display = 'none';
        }
    </script>
</body>
</html> 