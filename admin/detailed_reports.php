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

// Initialize filter variables
$ambulance_status_filter = isset($_GET['ambulance_status']) ? $_GET['ambulance_status'] : '';
$driver_assignment_filter = isset($_GET['driver_assignment']) ? $_GET['driver_assignment'] : '';
$paramedic_assignment_filter = isset($_GET['paramedic_assignment']) ? $_GET['paramedic_assignment'] : '';
$incident_severity_filter = isset($_GET['incident_severity']) ? $_GET['incident_severity'] : '';

// Fetching ambulances with filter
$ambulance_query = "SELECT a.*, aa.paramedic_id, p.username AS paramedic_name, aa.driver_id, d.username AS driver_name 
                   FROM ambulances a 
                   LEFT JOIN ambulance_assignments aa ON a.id = aa.ambulance_id 
                   LEFT JOIN users p ON aa.paramedic_id = p.id 
                   LEFT JOIN users d ON aa.driver_id = d.id 
                   WHERE 1=1";
if ($ambulance_status_filter) {
    $ambulance_query .= " AND a.status = '" . mysqli_real_escape_string($conn, $ambulance_status_filter) . "'";
}
$ambulance_result = mysqli_query($conn, $ambulance_query);

// Fetching drivers with filter
$driver_query = "SELECT u.id, u.username, aa.ambulance_id, a.vehicle_number 
                 FROM users u 
                 LEFT JOIN ambulance_assignments aa ON u.id = aa.driver_id 
                 LEFT JOIN ambulances a ON aa.ambulance_id = a.id 
                 WHERE u.role = 'DRIVER'";
if ($driver_assignment_filter) {
    if ($driver_assignment_filter === 'assigned') {
        $driver_query .= " AND aa.ambulance_id IS NOT NULL";
    } elseif ($driver_assignment_filter === 'unassigned') {
        $driver_query .= " AND aa.ambulance_id IS NULL";
    }
}
$driver_result = mysqli_query($conn, $driver_query);

// Fetching paramedics with filter
$paramedic_query = "SELECT u.id, u.username, aa.ambulance_id, a.vehicle_number 
                    FROM users u 
                    LEFT JOIN ambulance_assignments aa ON u.id = aa.paramedic_id 
                    LEFT JOIN ambulances a ON aa.ambulance_id = a.id 
                    WHERE u.role = 'PARAMEDIC'";
if ($paramedic_assignment_filter) {
    if ($paramedic_assignment_filter === 'assigned') {
        $paramedic_query .= " AND aa.ambulance_id IS NOT NULL";
    } elseif ($paramedic_assignment_filter === 'unassigned') {
        $paramedic_query .= " AND aa.ambulance_id IS NULL";
    }
}
$paramedic_result = mysqli_query($conn, $paramedic_query);

// Fetching dispatchers
$dispatcher_query = "SELECT id, username FROM users WHERE role = 'DISPATCHER'";
$dispatcher_result = mysqli_query($conn, $dispatcher_query);

// Fetching incidents with filter
$incident_query = "SELECT * FROM incidents WHERE 1=1";
if ($incident_severity_filter) {
    $incident_query .= " AND severity = '" . mysqli_real_escape_string($conn, $incident_severity_filter) . "'";
}
$incident_result = mysqli_query($conn, $incident_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Reports</title>
    <!-- Internal CSS for styling -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .sidebar {
            width: 200px;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
        }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 18px;
            color: white;
            display: block;
        }
        .sidebar a:hover {
            background-color: #555;
        }
        .content {
            margin-left: 220px;
            padding: 20px;
        }
        h1, h2 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .filters {
            margin-bottom: 20px;
        }
        .filters label {
            margin-right: 10px;
        }
        .filters select {
            padding: 5px;
            margin-right: 10px;
        }
        .filters input[type="submit"] {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        .filters input[type="submit"]:hover {
            background-color: #45a049;
        }
        .print-button {
            margin-bottom: 20px;
        }
        .print-button button {
            padding: 10px 20px;
            background-color: #2196F3;
            color: white;
            border: none;
            cursor: pointer;
        }
        .print-button button:hover {
            background-color: #1976D2;
        }
    </style>
    <!-- JavaScript for print functionality -->
    <script>
        function printReport() {
            window.print();
        }
    </script>
</head>
<body>
    <!-- Sidebar navigation -->
    <div class="sidebar">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Manage Users</a>
        <a href="ambulances.php">Manage Ambulances</a>
        <a href="drivers.php">Manage Drivers</a>
        <a href="paramedics.php">Manage Paramedics</a>
        <a href="dispatches.php">Manage Dispatches</a>
        <a href="incidents.php">Manage Incidents</a>
        <a href="assignments.php">Manage Assignments</a>
        <a href="reports.php">Ambulance Reports</a>
        <a href="detailed_reports.php">Detailed Reports</a>
        <a href="manage_inventory.php">Manage Inventory</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Main content area -->
    <div class="content">
        <h1>Detailed Reports</h1>
        <div class="print-button">
            <button onclick="printReport()">Print Report</button>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="">
                <label for="ambulance_status">Ambulance Status:</label>
                <select id="ambulance_status" name="ambulance_status">
                    <option value="">All</option>
                    <option value="AVAILABLE" <?php if ($ambulance_status_filter === 'AVAILABLE') echo 'selected'; ?>>Available</option>
                    <option value="IN_USE" <?php if ($ambulance_status_filter === 'IN_USE') echo 'selected'; ?>>In Use</option>
                    <option value="MAINTENANCE" <?php if ($ambulance_status_filter === 'MAINTENANCE') echo 'selected'; ?>>Maintenance</option>
                </select>

                <label for="driver_assignment">Driver Assignment:</label>
                <select id="driver_assignment" name="driver_assignment">
                    <option value="">All</option>
                    <option value="assigned" <?php if ($driver_assignment_filter === 'assigned') echo 'selected'; ?>>Assigned</option>
                    <option value="unassigned" <?php if ($driver_assignment_filter === 'unassigned') echo 'selected'; ?>>Unassigned</option>
                </select>

                <label for="paramedic_assignment">Paramedic Assignment:</label>
                <select id="paramedic_assignment" name="paramedic_assignment">
                    <option value="">All</option>
                    <option value="assigned" <?php if ($paramedic_assignment_filter === 'assigned') echo 'selected'; ?>>Assigned</option>
                    <option value="unassigned" <?php if ($paramedic_assignment_filter === 'unassigned') echo 'selected'; ?>>Unassigned</option>
                </select>

                <label for="incident_severity">Incident Severity:</label>
                <select id="incident_severity" name="incident_severity">
                    <option value="">All</option>
                    <option value="LOW" <?php if ($incident_severity_filter === 'LOW') echo 'selected'; ?>>Low</option>
                    <option value="MEDIUM" <?php if ($incident_severity_filter === 'MEDIUM') echo 'selected'; ?>>Medium</option>
                    <option value="HIGH" <?php if ($incident_severity_filter === 'HIGH') echo 'selected'; ?>>High</option>
                </select>

                <input type="submit" value="Apply Filters">
            </form>
        </div>

        <!-- Ambulances Report -->
        <h2>Ambulances</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Vehicle Number</th>
                <th>Status</th>
                <th>Location</th>
                <th>Paramedic</th>
                <th>Driver</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($ambulance_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo $row['paramedic_name'] ? htmlspecialchars($row['paramedic_name']) : 'N/A'; ?></td>
                    <td><?php echo $row['driver_name'] ? htmlspecialchars($row['driver_name']) : 'N/A'; ?></td>
                </tr>
            <?php } ?>
        </table>

        <!-- Drivers Report -->
        <h2>Drivers</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Assigned Ambulance</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($driver_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo $row['vehicle_number'] ? htmlspecialchars($row['vehicle_number']) : 'N/A'; ?></td>
                </tr>
            <?php } ?>
        </table>

        <!-- Paramedics Report -->
        <h2>Paramedics</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Assigned Ambulance</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($paramedic_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo $row['vehicle_number'] ? htmlspecialchars($row['vehicle_number']) : 'N/A'; ?></td>
                </tr>
            <?php } ?>
        </table>

        <!-- Dispatchers Report -->
        <h2>Dispatchers</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($dispatcher_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                </tr>
            <?php } ?>
        </table>

        <!-- Incidents Report -->
        <h2>Incidents</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Narrative</th>
                <th>Location</th>
                <th>Severity</th>
                <th>Status</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($incident_result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['narrative']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo $row['severity']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>