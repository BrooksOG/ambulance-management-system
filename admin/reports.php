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
$report_type = isset($_POST['report_type']) ? mysqli_real_escape_string($conn, trim($_POST['report_type'])) : 'Ambulance Usage';
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-05-01';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-05-05';
$location_filter = isset($_POST['location_filter']) ? mysqli_real_escape_string($conn, trim($_POST['location_filter'])) : '';
$severity_filter = isset($_POST['severity_filter']) ? mysqli_real_escape_string($conn, trim($_POST['severity_filter'])) : '';
$status_filter = isset($_POST['status_filter']) ? mysqli_real_escape_string($conn, trim($_POST['status_filter'])) : '';
$sort_order = isset($_POST['sort_order']) ? $_POST['sort_order'] : 'DESC';

// Construct the dynamic SQL query based on report type
switch ($report_type) {
    case 'Ambulance Usage':
        $query = "SELECT a.*, ai.quantity, ai.status AS inventory_status, ai.last_updated 
                  FROM ambulances a 
                  LEFT JOIN ambulance_inventory ai ON a.id = ai.ambulance_id 
                  WHERE 1=1";
        if ($location_filter) {
            $query .= " AND a.location LIKE '%$location_filter%'";
        }
        if ($status_filter && $status_filter != '') {
            $query .= " AND (a.status = '$status_filter' OR a.status IS NULL)";
        }
        if ($start_date && $end_date) {
            $query .= " AND a.last_updated BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
        }
        $query .= " ORDER BY a.last_updated $sort_order";
        break;
    case 'Incidents Report':
        $query = "SELECT i.id, i.narrative, i.severity, i.emergency_type, i.location, i.created_at, a.vehicle_number, 
                         COALESCE(a.status, 'UNKNOWN') AS ambulance_status, d.status AS dispatch_status 
                  FROM incidents i 
                  LEFT JOIN dispatches d ON i.id = d.incident_id 
                  LEFT JOIN ambulances a ON i.ambulance_id = a.id 
                  WHERE 1=1";
        if ($location_filter) {
            $query .= " AND i.location LIKE '%$location_filter%'";
        }
        if ($severity_filter) {
            $query .= " AND i.severity = '$severity_filter'";
        }
        if ($start_date && $end_date) {
            $query .= " AND i.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
        }
        $query .= " ORDER BY i.created_at $sort_order";
        break;
    case 'Paramedics Performance':
        $query = "SELECT u.username AS paramedic, aa.created_at, COUNT(DISTINCT d.incident_id) AS incidents_handled, i.severity, i.narrative 
                  FROM ambulance_assignments aa 
                  LEFT JOIN users u ON aa.paramedic_id = u.id 
                  LEFT JOIN dispatches d ON aa.ambulance_id = d.ambulance_id 
                  LEFT JOIN incidents i ON d.incident_id = i.id 
                  WHERE aa.paramedic_id IS NOT NULL AND 1=1";
        if ($start_date && $end_date) {
            $query .= " AND aa.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
        }
        $query .= " GROUP BY u.username, aa.created_at, i.severity, i.narrative 
                   ORDER BY aa.created_at $sort_order";
        break;
    case 'Drivers Performance':
        $query = "SELECT u.username AS driver, aa.created_at, COUNT(DISTINCT d.incident_id) AS incidents_handled, i.severity, i.narrative 
                  FROM ambulance_assignments aa 
                  LEFT JOIN users u ON aa.driver_id = u.id 
                  LEFT JOIN dispatches d ON aa.ambulance_id = d.ambulance_id 
                  LEFT JOIN incidents i ON d.incident_id = i.id 
                  WHERE aa.driver_id IS NOT NULL AND 1=1";
        if ($start_date && $end_date) {
            $query .= " AND aa.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
        }
        $query .= " GROUP BY u.username, aa.created_at, i.severity, i.narrative 
                   ORDER BY aa.created_at $sort_order";
        break;
    case 'Dispatchers Performance':
        $query = "SELECT u.username AS dispatcher, i.created_at, COUNT(DISTINCT i.id) AS incidents_handled, i.severity, i.narrative 
                  FROM incidents i 
                  LEFT JOIN users u ON i.dispatcher_id = u.id 
                  WHERE u.role = 'DISPATCHER' AND 1=1";
        if ($start_date && $end_date) {
            $query .= " AND i.created_at BETWEEN '$start_date 00:00:00' AND '$end_date 23:59:59'";
        }
        $query .= " GROUP BY u.username, i.created_at, i.severity, i.narrative 
                   ORDER BY i.created_at $sort_order";
        break;
}

$result = mysqli_query($conn, $query);

// Check if the query failed
if ($result === FALSE) {
    $error = "Error fetching report: " . mysqli_error($conn);
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Reports</title>
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
            .sidebar, .filter-section, .message, .error {
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
    <!-- JavaScript for print functionality and reset filters -->
    <script>
        function printReport() {
            const originalContent = document.body.innerHTML;
            let printContent = `<div class="content"><h1>${document.querySelector('h1').textContent}</h1><table>`;
            printContent += document.querySelector('table').innerHTML;
            printContent += '</table></div>';
            document.body.innerHTML = printContent;
            window.print();
            document.body.innerHTML = originalContent;
        }

        function resetFilters() {
            document.getElementById('report_type').value = 'Ambulance Usage';
            document.getElementById('start_date').value = '2025-05-01';
            document.getElementById('end_date').value = '2025-05-05';
            document.getElementById('location_filter').value = '';
            document.getElementById('severity_filter').value = '';
            document.getElementById('status_filter').value = '';
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
        <h1>Generate Reports</h1>
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

        <!-- Filter Section -->
        <form method="POST" action="" class="filter-form">
            <div class="filter-section">
                <label for="report_type">Report Type:</label>
                <select id="report_type" name="report_type">
                    <option value="Incidents Report" <?php if ($report_type == 'Incidents Report') echo 'selected'; ?>>Incidents Report</option>
                    <option value="Paramedics Performance" <?php if ($report_type == 'Paramedics Performance') echo 'selected'; ?>>Paramedics Performance</option>
                    <option value="Drivers Performance" <?php if ($report_type == 'Drivers Performance') echo 'selected'; ?>>Drivers Performance</option>
                    <option value="Dispatchers Performance" <?php if ($report_type == 'Dispatchers Performance') echo 'selected'; ?>>Dispatchers Performance</option>
                    <option value="Ambulance Usage" <?php if ($report_type == 'Ambulance Usage') echo 'selected'; ?>>Ambulance Usage</option>
                </select>
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                <label for="location_filter">Location:</label>
                <input type="text" id="location_filter" name="location_filter" value="<?php echo htmlspecialchars($location_filter); ?>">
                <label for="severity_filter">Severity:</label>
                <select id="severity_filter" name="severity_filter">
                    <option value="">All Severities</option>
                    <option value="LOW" <?php if ($severity_filter == 'LOW') echo 'selected'; ?>>LOW</option>
                    <option value="MEDIUM" <?php if ($severity_filter == 'MEDIUM') echo 'selected'; ?>>MEDIUM</option>
                    <option value="HIGH" <?php if ($severity_filter == 'HIGH') echo 'selected'; ?>>HIGH</option>
                    <option value="CRITICAL" <?php if ($severity_filter == 'CRITICAL') echo 'selected'; ?>>CRITICAL</option>
                </select>
                <label for="status_filter">Status:</label>
                <select id="status_filter" name="status_filter">
                    <option value="">All Statuses</option>
                    <option value="AVAILABLE" <?php if ($status_filter == 'AVAILABLE') echo 'selected'; ?>>AVAILABLE</option>
                    <option value="DISPATCHED" <?php if ($status_filter == 'DISPATCHED') echo 'selected'; ?>>DISPATCHED</option>
                    <option value="MAINTENANCE" <?php if ($status_filter == 'MAINTENANCE') echo 'selected'; ?>>MAINTENANCE</option>
                </select>
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

        <!-- Report table -->
        <?php if (isset($error) || !$result) { ?>
            <p>No data available or an error occurred.</p>
        <?php } else { ?>
        <table>
            <tr>
                <?php
                if ($report_type == 'Ambulance Usage') {
                    echo '<th>ID</th><th>Vehicle Number</th><th>Type</th><th>Location</th><th>Quantity</th><th>Inventory Status</th><th>Ambulance Status</th><th>Last Updated</th>';
                } elseif ($report_type == 'Incidents Report') {
                    echo '<th>ID</th><th>Narrative</th><th>Severity</th><th>Emergency Type</th><th>Location</th><th>Vehicle Number</th><th>Ambulance Status</th><th>Dispatch Status</th><th>Created At</th>';
                } elseif ($report_type == 'Paramedics Performance') {
                    echo '<th>Paramedic</th><th>Assignment Date</th><th>Incidents Handled</th><th>Severity</th><th>Narrative</th>';
                } elseif ($report_type == 'Drivers Performance') {
                    echo '<th>Driver</th><th>Assignment Date</th><th>Incidents Handled</th><th>Severity</th><th>Narrative</th>';
                } elseif ($report_type == 'Dispatchers Performance') {
                    echo '<th>Dispatcher</th><th>Incident Date</th><th>Incidents Handled</th><th>Severity</th><th>Narrative</th>';
                }
                ?>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <?php
                    if ($report_type == 'Ambulance Usage') {
                        echo '<td>' . $row['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($row['vehicle_number'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['type'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['location'] ?? 'N/A') . '</td>';
                        echo '<td>' . ($row['quantity'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['inventory_status'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['status'] ?? 'UNKNOWN') . '</td>';
                        echo '<td>' . $row['last_updated'] . '</td>';
                    } elseif ($report_type == 'Incidents Report') {
                        echo '<td>' . $row['id'] . '</td>';
                        echo '<td>' . htmlspecialchars($row['narrative'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['severity'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['emergency_type'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['location'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['vehicle_number'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['ambulance_status'] ?? 'UNKNOWN') . '</td>';
                        echo '<td>' . htmlspecialchars($row['dispatch_status'] ?? 'N/A') . '</td>';
                        echo '<td>' . $row['created_at'] . '</td>';
                    } elseif ($report_type == 'Paramedics Performance') {
                        echo '<td>' . htmlspecialchars($row['paramedic'] ?? 'N/A') . '</td>';
                        echo '<td>' . $row['created_at'] . '</td>';
                        echo '<td>' . ($row['incidents_handled'] ?? 0) . '</td>';
                        echo '<td>' . htmlspecialchars($row['severity'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['narrative'] ?? 'N/A') . '</td>';
                    } elseif ($report_type == 'Drivers Performance') {
                        echo '<td>' . htmlspecialchars($row['driver'] ?? 'N/A') . '</td>';
                        echo '<td>' . $row['created_at'] . '</td>';
                        echo '<td>' . ($row['incidents_handled'] ?? 0) . '</td>';
                        echo '<td>' . htmlspecialchars($row['severity'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['narrative'] ?? 'N/A') . '</td>';
                    } elseif ($report_type == 'Dispatchers Performance') {
                        echo '<td>' . htmlspecialchars($row['dispatcher'] ?? 'N/A') . '</td>';
                        echo '<td>' . $row['created_at'] . '</td>';
                        echo '<td>' . ($row['incidents_handled'] ?? 0) . '</td>';
                        echo '<td>' . htmlspecialchars($row['severity'] ?? 'N/A') . '</td>';
                        echo '<td>' . htmlspecialchars($row['narrative'] ?? 'N/A') . '</td>';
                    }
                    ?>
                </tr>
            <?php } ?>
        </table>
        <?php } ?>
    </div>
</body>
</html>