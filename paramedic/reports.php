<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PARAMEDIC') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialize filter variables for incidents report
$status_filter = isset($_POST['status_filter']) ? mysqli_real_escape_string($conn, trim($_POST['status_filter'])) : '';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-05-01';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-05-06'; // Updated to today
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'DESC';

// Fetch incidents handled by the paramedic
$incident_query = "SELECT i.id, i.narrative, i.location, i.severity, i.emergency_type, d.status, d.dispatch_time
                  FROM dispatches d
                  JOIN incidents i ON d.incident_id = i.id
                  WHERE d.paramedic_id = $user_id";
if ($status_filter) {
    $incident_query .= " AND d.status = '$status_filter'";
}
if ($start_date && $end_date) {
    $incident_query .= " AND d.dispatch_time BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
}
$incident_query .= " ORDER BY d.dispatch_time $sort_order";
$incident_result = mysqli_query($conn, $incident_query);
if ($incident_result === FALSE) {
    $error = "Error fetching incidents: " . mysqli_error($conn);
}

// Debug: Check if Irine has any assigned incidents
$debug_query = "SELECT COUNT(*) as count FROM dispatches WHERE paramedic_id = $user_id";
$debug_result = mysqli_query($conn, $debug_query);
$debug_row = mysqli_fetch_assoc($debug_result);
$incident_count = $debug_row['count'];

// Fetch ambulances assigned to the paramedic for inventory management
$ambulance_query = "SELECT DISTINCT a.id, a.vehicle_number 
                   FROM ambulances a 
                   JOIN dispatches d ON a.id = d.ambulance_id 
                   WHERE d.paramedic_id = $user_id AND d.status = 'PENDING'";
$ambulances_result = mysqli_query($conn, $ambulance_query);

// Fetch current inventory for ambulances assigned to the paramedic
$inventory_query = "SELECT ai.*, a.vehicle_number 
                    FROM ambulance_inventory ai 
                    JOIN dispatches d ON ai.ambulance_id = d.ambulance_id 
                    JOIN ambulances a ON ai.ambulance_id = a.id 
                    WHERE d.paramedic_id = $user_id AND d.status = 'PENDING'";
$inventory_result = mysqli_query($conn, $inventory_query);

// Handle inventory update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $ambulance_id = (int)$_POST['ambulance_id'];
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $quantity = (int)$_POST['quantity'];
    $inventory_id = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;

    // Determine status based on quantity
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
        header('Location: reports.php?message=Inventory updated successfully');
        exit;
    } else {
        $error = "Error updating inventory: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports and Inventory</title>
    <style>
        .content { margin-left: 220px; padding: 20px; }
        .filter-section { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-section label { margin-right: 5px; }
        .filter-section select, .filter-section input[type="date"] { padding: 5px; width: 150px; }
        .filter-section button { padding: 5px 10px; cursor: pointer; }
        .filter-section .generate-btn { background-color: #007bff; color: white; border: none; }
        .filter-section .reset-btn { background-color: #6c757d; color: white; border: none; }
        .filter-section .print-btn { background-color: #007bff; color: white; border: none; }
        .filter-section .generate-btn:hover, .filter-section .print-btn:hover { background-color: #0056b3; }
        .filter-section .reset-btn:hover { background-color: #5a6268; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .edit-form { display: none; margin: 10px 0; }
        .edit-form select, .edit-form input[type="text"], .edit-form input[type="number"] { width: 150px; margin: 5px 0; }
        .edit-form input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 5px 10px; cursor: pointer; }
        .edit-form input[type="submit"]:hover { background-color: #45a049; }
        .error, .message { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .error { color: red; background-color: #ffebee; }
        .message { color: green; background-color: #e8f5e9; }
        @media print {
            .sidebar, .filter-section, .edit-form, .message, .error { display: none; }
            .content { margin-left: 0; }
            table { border: 2px solid #000; }
            th, td { border: 1px solid #000; }
        }
    </style>
    <script>
        function toggleEditForm(rowId) {
            const form = document.getElementById('edit-form-' + rowId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function printReport() {
            const originalContent = document.body.innerHTML;
            const printContent = `
                <div class="content">
                    <h1>Incidents Report</h1>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Emergency Type</th>
                            <th>Narrative</th>
                            <th>Location</th>
                            <th>Severity</th>
                            <th>Status</th>
                            <th>Dispatch Time</th>
                        </tr>
                        ${document.querySelector('#incidents-table').innerHTML}
                    </table>
                </div>
            `;
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        }

        function resetFilters() {
            document.getElementById('status_filter').value = '';
            document.getElementById('start_date').value = '2025-05-01';
            document.getElementById('end_date').value = '2025-05-06';
            document.getElementById('sort_order').value = 'DESC';
            document.querySelector('form.filter-form').submit();
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Reports and Inventory Management</h1>
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
        if ($incident_count == 0) {
            echo '<p class="error">No incidents found for your user ID in the dispatches table.</p>';
        }
        ?>

        <!-- Incidents Report Section -->
        <h2>Incidents Handled</h2>
        <form method="POST" action="" class="filter-form">
            <div class="filter-section">
                <label for="status_filter">Status:</label>
                <select id="status_filter" name="status_filter">
                    <option value="">All Statuses</option>
                    <option value="PENDING" <?php if ($status_filter == 'PENDING') echo 'selected'; ?>>PENDING</option>
                    <option value="COMPLETED" <?php if ($status_filter == 'COMPLETED') echo 'selected'; ?>>COMPLETED</option>
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

        <?php if (isset($error) || !$incident_result) { ?>
            <p>No incidents available or an error occurred.</p>
        <?php } else { ?>
        <table id="incidents-table">
            <tr>
                <th>ID</th>
                <th>Emergency Type</th>
                <th>Narrative</th>
                <th>Location</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Dispatch Time</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($incident_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['emergency_type'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['narrative']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo $row['severity']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['dispatch_time']; ?></td>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>

        <!-- Inventory Management Section -->
        <h2>Manage Inventory</h2>
        <h3>Add/Update Inventory Item</h3>
        <?php if (mysqli_num_rows($ambulances_result) > 0) { ?>
            <form method="POST" action="">
                <label for="ambulance_id">Ambulance:</label>
                <select id="ambulance_id" name="ambulance_id" required>
                    <option value="">Select Ambulance</option>
                    <?php 
                    mysqli_data_seek($ambulances_result, 0);
                    while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { 
                    ?>
                        <option value="<?php echo $ambulance['id']; ?>">
                            <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                        </option>
                    <?php } ?>
                </select>
                <label for="item_name">Item Name:</label>
                <input type="text" id="item_name" name="item_name" required>
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" min="0" required>
                <input type="submit" name="update_inventory" value="Add Item">
            </form>
        <?php } else { ?>
            <p>No ambulances assigned to you for inventory management.</p>
        <?php } ?>

        <h3>Current Inventory</h3>
        <?php if (mysqli_num_rows($inventory_result) > 0) { ?>
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
            <?php while ($row = mysqli_fetch_assoc($inventory_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['last_updated']; ?></td>
                    <td>
                        <a href="javascript:void(0);" onclick="toggleEditForm(<?php echo $row['id']; ?>)">Edit</a>
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
                                    mysqli_data_seek($ambulances_result, 0);
                                    while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { 
                                    ?>
                                        <option value="<?php echo $ambulance['id']; ?>" <?php if ($ambulance['id'] == $row['ambulance_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                                        </option>
                                    <?php } ?>
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
        <?php } else { ?>
            <p>No inventory items for your assigned ambulances.</p>
        <?php } ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>