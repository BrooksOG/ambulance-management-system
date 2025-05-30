<?php
// Including the database connection file
include '../includes/db_connect.php';

// Starting the session to verify user authentication and role
session_start();

// Checking if the user is logged in and has the admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

// Initialize filter variables with defaults or POST values
$ambulance_filter = isset($_POST['ambulance_filter']) ? (int)$_POST['ambulance_filter'] : '';
$item_name_filter = isset($_POST['item_name_filter']) ? mysqli_real_escape_string($conn, trim($_POST['item_name_filter'])) : '';
$status_filter = isset($_POST['status_filter']) ? mysqli_real_escape_string($conn, trim($_POST['status_filter'])) : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-05-01';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-05-05';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'DESC';

// Construct the dynamic SQL query
$query = "SELECT ai.*, a.vehicle_number 
          FROM ambulance_inventory ai 
          LEFT JOIN ambulances a ON ai.ambulance_id = a.id 
          WHERE 1=1";
if ($ambulance_filter) {
    $query .= " AND ai.ambulance_id = $ambulance_filter";
}
if ($item_name_filter) {
    $query .= " AND ai.item_name LIKE '%$item_name_filter%'";
}
if ($status_filter) {
    $query .= " AND ai.status = '$status_filter'";
}
if ($start_date && $end_date) {
    $query .= " AND ai.last_updated BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}
$query .= " ORDER BY ai.last_updated $sort_order";
$result = mysqli_query($conn, $query);

// Check if the query failed
if ($result === FALSE) {
    $error = "Error fetching inventory: " . mysqli_error($conn);
}

// Fetching all ambulances for the dropdown
$ambulances_query = "SELECT id, vehicle_number FROM ambulances";
$ambulances_result = mysqli_query($conn, $ambulances_query);
if ($ambulances_result === FALSE) {
    $error = isset($error) ? $error . "<br>Error fetching ambulances: " . mysqli_error($conn) : "Error fetching ambulances: " . mysqli_error($conn);
}

// Handling form submission to add or update inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $ambulance_id = (int)$_POST['ambulance_id'];
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $inventory_id = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;

    // Determine status based on new criteria
    if ($quantity > 1) {
        $status = 'AVAILABLE';
    } elseif ($quantity >= 1 && $quantity <= 5) {
        $status = 'LOW';
    } else {
        $status = 'OUT_OF_STOCK';
    }

    if ($inventory_id) {
        // Update existing inventory item
        $query = "UPDATE ambulance_inventory 
                  SET ambulance_id = $ambulance_id, 
                      item_name = '$item_name', 
                      quantity = $quantity, 
                      status = '$status', 
                      last_updated = NOW() 
                  WHERE id = $inventory_id";
    } else {
        // Insert new inventory item
        $query = "INSERT INTO ambulance_inventory (ambulance_id, item_name, quantity, status, last_updated) 
                  VALUES ($ambulance_id, '$item_name', $quantity, '$status', NOW())";
    }

    if (mysqli_query($conn, $query)) {
        header('Location: manage_inventory.php?message=Inventory updated successfully');
        exit;
    } else {
        $error = "Error updating inventory: " . mysqli_error($conn);
    }
}

// Handling delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $query = "DELETE FROM ambulance_inventory WHERE id = $delete_id";
    if (mysqli_query($conn, $query)) {
        header('Location: manage_inventory.php?message=Inventory item deleted successfully');
        exit;
    } else {
        header('Location: manage_inventory.php?error=Error deleting inventory item: " . mysqli_error($conn)');
        exit;
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inventory</title>
    <!-- Internal CSS for styling -->
    <style>
        /* Removed body and sidebar styles as they are handled by sidebar.php */
        .content {
            margin-left: 220px;
            padding: 20px;
        }
        .filter-section {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-section label {
            margin-right: 5px;
        }
        .filter-section select, .filter-section input[type="date"], .filter-section input[type="text"] {
            padding: 5px;
            width: 150px;
        }
        .filter-section button {
            padding: 5px 10px;
            cursor: pointer;
        }
        .filter-section .generate-btn {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .filter-section .reset-btn {
            background-color: #6c757d;
            color: white;
            border: none;
        }
        .filter-section .print-btn {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .filter-section .generate-btn:hover, .filter-section .print-btn:hover {
            background-color: #0056b3;
        }
        .filter-section .reset-btn:hover {
            background-color: #5a6268;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .edit-form {
            display: none;
            margin: 10px 0;
        }
        .edit-form select, .edit-form input[type="text"], .edit-form input[type="number"] {
            width: 150px;
            margin: 5px 0;
        }
        .edit-form input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .edit-form input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error, .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .error {
            color: red;
            background-color: #ffebee;
        }
        .message {
            color: green;
            background-color: #e8f5e9;
        }
        /* Print-specific styles */
        @media print {
            .sidebar, .filter-section, .edit-form, .message, .error {
                display: none;
            }
            .content {
                margin-left: 0;
            }
            table {
                border: 2px solid #000;
            }
            th, td {
                border: 1px solid #000;
            }
        }
    </style>
    <!-- JavaScript for toggling edit forms and print functionality -->
    <script>
        function toggleEditForm(rowId) {
            const form = document.getElementById('edit-form-' + rowId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function printReport() {
            const originalContent = document.body.innerHTML;
            const printContent = `
                <div class="content">
                    <h1>Inventory Report</h1>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Ambulance</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                        </tr>
                        ${document.querySelector('table').innerHTML.replace(/<tr>.*?<td>.*?(Edit|Delete).*?<\/td>.*?<\/tr>/g, '')}
                    </table>
                </div>
            `;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        }

        function resetFilters() {
            document.getElementById('ambulance_filter').value = '';
            document.getElementById('item_name_filter').value = '';
            document.getElementById('status_filter').value = '';
            document.getElementById('start_date').value = '2025-05-01';
            document.getElementById('end_date').value = '2025-05-05';
            document.getElementById('sort_order').value = 'DESC';
            document.querySelector('form.filter-form').submit();
        }

        document.querySelector('.generate-btn').addEventListener('click', function() {
            document.querySelector('form.filter-form').submit();
        });
    </script>
</head>
<body>
    <!-- Include the reusable sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main content area -->
    <div class="content">
        <h1>Manage Inventory</h1>
        <?php 
        if (isset($_GET['message'])) {
            echo '<p class="message">' . htmlspecialchars($_GET['message']) . '</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        if (isset($error)) {
            echo '<p class="error">' . $error . '</p>';
        }
        ?>

        <!-- Add New Inventory Item Form -->
        <h2>Add New Inventory Item</h2>
        <form method="POST" action="">
            <label for="ambulance_id">Ambulance:</label>
            <select id="ambulance_id" name="ambulance_id" required>
                <option value="">Select Ambulance</option>
                <?php 
                if ($ambulances_result) {
                    mysqli_data_seek($ambulances_result, 0);
                    while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { 
                ?>
                    <option value="<?php echo $ambulance['id']; ?>">
                        <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                    </option>
                <?php 
                    }
                }
                ?>
            </select>
            <label for="item_name">Item Name:</label>
            <input type="text" id="item_name" name="item_name" required>
            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="0" required>
            <input type="submit" name="update_inventory" value="Add Item">
        </form>

        <!-- Filter Section -->
        <h2>Inventory List</h2>
        <form method="POST" action="" class="filter-form">
            <div class="filter-section">
                <label for="ambulance_filter">Ambulance:</label>
                <select id="ambulance_filter" name="ambulance_filter">
                    <option value="">All Ambulances</option>
                    <?php 
                    if ($ambulances_result) {
                        mysqli_data_seek($ambulances_result, 0);
                        while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { 
                    ?>
                        <option value="<?php echo $ambulance['id']; ?>" <?php if ($ambulance['id'] == $ambulance_filter) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                        </option>
                    <?php 
                        }
                    }
                    ?>
                </select>
                <label for="item_name_filter">Item Name:</label>
                <input type="text" id="item_name_filter" name="item_name_filter" value="<?php echo htmlspecialchars($item_name_filter); ?>">
                <label for="status_filter">Status:</label>
                <select id="status_filter" name="status_filter">
                    <option value="">All Statuses</option>
                    <option value="AVAILABLE" <?php if ($status_filter == 'AVAILABLE') echo 'selected'; ?>>AVAILABLE</option>
                    <option value="LOW" <?php if ($status_filter == 'LOW') echo 'selected'; ?>>LOW</option>
                    <option value="OUT_OF_STOCK" <?php if ($status_filter == 'OUT_OF_STOCK') echo 'selected'; ?>>OUT_OF_STOCK</option>
                </select>
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <label for="sort_order">Sort Order:</label>
                <select id="sort_order" name="sort_order">
                    <option value="DESC" <?php if ($sort_order == 'DESC') echo 'selected'; ?>>Descending</option>
                    <option value="ASC" <?php if ($sort_order == 'ASC') echo 'selected'; ?>>Ascending</option>
                </select>
                <button type="submit" class="generate-btn">Generate Report</button>
                <button type="button" class="reset-btn" onclick="resetFilters()">Reset Filters</button>
                <button type="button" class="print-btn" onclick="printReport()">Print Report</button>
            </div>
        </form>

        <!-- Inventory table -->
        <?php if (isset($error) || !$result) { ?>
            <p>No inventory items available or an error occurred.</p>
        <?php } else { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Ambulance</th>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Last Updated</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['vehicle_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo $row['last_updated']; ?></td>
                    <td>
                        <a href="javascript:void(0);" onclick="toggleEditForm(<?php echo $row['id']; ?>)">Edit</a> |
                        <a href="manage_inventory.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
                <tr>
                    <td colspan="7">
                        <div id="edit-form-<?php echo $row['id']; ?>" class="edit-form">
                            <form method="POST" action="">
                                <input type="hidden" name="inventory_id" value="<?php echo $row['id']; ?>">
                                <label for="ambulance_id_<?php echo $row['id']; ?>">Ambulance:</label>
                                <select id="ambulance_id_<?php echo $row['id']; ?>" name="ambulance_id" required>
                                    <option value="">Select Ambulance</option>
                                    <?php 
                                    if ($ambulances_result) {
                                        mysqli_data_seek($ambulances_result, 0);
                                        while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { 
                                    ?>
                                        <option value="<?php echo $ambulance['id']; ?>" <?php if ($ambulance['id'] == $row['ambulance_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                                        </option>
                                    <?php } 
                                    } ?>
                                </select>
                                <label for="item_name_<?php echo $row['id']; ?>">Item Name:</label>
                                <input type="text" id="item_name_<?php echo $row['id']; ?>" name="item_name" value="<?php echo htmlspecialchars($row['item_name']); ?>" required>
                                <label for="quantity_<?php echo $row['id']; ?>">Quantity:</label>
                                <input type="number" id="quantity_<?php echo $row['id']; ?>" name="quantity" value="<?php echo $row['quantity']; ?>" min="0" required>
                                <input type="submit" name="update_inventory" value="Update">
                            </form>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>
    </div>
</body>
</html>