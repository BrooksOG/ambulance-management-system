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

// Fetch current user's username
$assigned_by = isset($_SESSION['username']) ? $_SESSION['username'] : 'Unknown';

// Fetching all incidents with their assigned ambulance and personnel
$query = "SELECT i.*, a.vehicle_number, aa.paramedic_id, p.username AS paramedic_name, aa.driver_id, d.username AS driver_name 
          FROM incidents i 
          LEFT JOIN ambulance_assignments aa ON i.ambulance_id = aa.ambulance_id 
          LEFT JOIN ambulances a ON i.ambulance_id = a.id 
          LEFT JOIN users p ON aa.paramedic_id = p.id 
          LEFT JOIN users d ON aa.driver_id = d.id";
$result = mysqli_query($conn, $query);

// Fetching the 5 most recent unassigned incidents
$unassigned_query = "SELECT id, narrative, location, severity, submitted_at 
                    FROM incidents 
                    WHERE status = 'UNVERIFIED' 
                    ORDER BY submitted_at DESC 
                    LIMIT 5";
$unassigned_result = mysqli_query($conn, $unassigned_query);

// Fetching all available ambulances for the assignment dropdown
$ambulances_query = "SELECT id, vehicle_number FROM ambulances WHERE status = 'AVAILABLE'";
$ambulances_result = mysqli_query($conn, $ambulances_query);

// Handling form submission to assign an ambulance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ambulance'])) {
    $incident_id = (int)$_POST['incident_id'];
    $ambulance_id = (int)$_POST['ambulance_id'];

    // Check if ambulance is available
    $check_query = "SELECT status FROM ambulances WHERE id = $ambulance_id";
    $check_result = mysqli_query($conn, $check_query);
    $ambulance_status = mysqli_fetch_assoc($check_result)['status'];

    if ($ambulance_status === 'AVAILABLE') {
        // Update incident status to 'ASSIGNED', set ambulance, and record timestamps and assigned_by
        $query = "UPDATE incidents 
                  SET ambulance_id = $ambulance_id, 
                      status = 'ASSIGNED', 
                      verified_at = NOW(), 
                      assigned_at = NOW(), 
                      assigned_by = '$assigned_by' 
                  WHERE id = $incident_id";
        if (mysqli_query($conn, $query)) {
            // Update ambulance status to 'IN_USE'
            $update_ambulance_query = "UPDATE ambulances SET status = 'IN_USE' WHERE id = $ambulance_id";
            mysqli_query($conn, $update_ambulance_query);

            // Log the assignment in the driver's history
            $driver_query = "SELECT driver_id FROM ambulance_assignments WHERE ambulance_id = $ambulance_id AND active = 1";
            $driver_result = mysqli_query($conn, $driver_query);
            if ($driver_row = mysqli_fetch_assoc($driver_result)) {
                $driver_id = $driver_row['driver_id'];
                $history_query = "SELECT history FROM driver_details WHERE user_id = '$driver_id'";
                $history_result = mysqli_query($conn, $history_query);
                $history_row = mysqli_fetch_assoc($history_result);
                $current_history = $history_row['history'] ? $history_row['history'] . "\n" : '';
                $new_history = $current_history . date('Y-m-d H:i:s') . " - ASSIGNED to Incident #" . $incident_id;
                $update_history_query = "UPDATE driver_details SET history = '$new_history' WHERE user_id = '$driver_id'";
                mysqli_query($conn, $update_history_query);
            }

            header('Location: incidents.php?message=Ambulance assigned successfully');
            exit;
        } else {
            $error = "Error assigning ambulance: " . mysqli_error($conn);
        }
    } else {
        $error = "Ambulance is already in use.";
    }
}

// Handling delete action
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Fetch the incident to get the assigned ambulance
    $incident_query = "SELECT ambulance_id FROM incidents WHERE id = $delete_id";
    $incident_result = mysqli_query($conn, $incident_query);
    $incident = mysqli_fetch_assoc($incident_result);
    $ambulance_id = $incident['ambulance_id'];

    // Delete the incident
    $query = "DELETE FROM incidents WHERE id = $delete_id";
    if (mysqli_query($conn, $query)) {
        // If an ambulance was assigned, set its status back to 'AVAILABLE'
        if ($ambulance_id) {
            $update_ambulance_query = "UPDATE ambulances SET status = 'AVAILABLE' WHERE id = $ambulance_id";
            mysqli_query($conn, $update_ambulance_query);
        }
        header('Location: incidents.php?message=Incident deleted successfully');
        exit;
    } else {
        header('Location: incidents.php?error=Error deleting incident: " . mysqli_error($conn)');
        exit;
    }
}

// Handling close action
if (isset($_GET['close_id'])) {
    $close_id = (int)$_GET['close_id'];

    // Get the assigned ambulance and driver
    $incident_query = "SELECT ambulance_id FROM incidents WHERE id = $close_id";
    $incident_result = mysqli_query($conn, $incident_query);
    $incident = mysqli_fetch_assoc($incident_result);
    $ambulance_id = $incident['ambulance_id'];

    // Update incident status to 'CLOSED'
    $query = "UPDATE incidents SET status = 'CLOSED', closed_at = NOW() WHERE id = $close_id";
    if (mysqli_query($conn, $query)) {
        // Set ambulance status back to 'AVAILABLE'
        if ($ambulance_id) {
            $update_ambulance_query = "UPDATE ambulances SET status = 'AVAILABLE' WHERE id = $ambulance_id";
            mysqli_query($conn, $update_ambulance_query);

            // Update driver's history with closure
            $driver_query = "SELECT driver_id FROM ambulance_assignments WHERE ambulance_id = $ambulance_id AND active = 1";
            $driver_result = mysqli_query($conn, $driver_query);
            if ($driver_row = mysqli_fetch_assoc($driver_result)) {
                $driver_id = $driver_row['driver_id'];
                $history_query = "SELECT history FROM driver_details WHERE user_id = '$driver_id'";
                $history_result = mysqli_query($conn, $history_query);
                $history_row = mysqli_fetch_assoc($history_result);
                $current_history = $history_row['history'] ? $history_row['history'] . "\n" : '';
                $new_history = $current_history . date('Y-m-d H:i:s') . " - Incident #" . $close_id . " CLOSED";
                $update_history_query = "UPDATE driver_details SET history = '$new_history' WHERE user_id = '$driver_id'";
                mysqli_query($conn, $update_history_query);

                // Deactivate the assignment
                $deactivate_query = "UPDATE ambulance_assignments SET active = 0 WHERE driver_id = '$driver_id' AND ambulance_id = '$ambulance_id'";
                mysqli_query($conn, $deactivate_query);
            }
        }
        header('Location: incidents.php?message=Incident closed successfully');
        exit;
    } else {
        header('Location: incidents.php?error=Error closing incident: " . mysqli_error($conn)');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Incidents</title>
    <!-- Internal CSS for styling -->
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            max-width: 600px;
        }
        .card h3 {
            margin-top: 0;
            color: #333;
        }
        .incident-list {
            list-style: none;
            padding: 0;
        }
        .incident-list li {
            margin-bottom: 10px;
        }
        .assign-form {
            display: inline;
            margin-left: 10px;
        }
        .assign-form select {
            padding: 5px;
            font-size: 12px;
        }
        .assign-form input[type="submit"] {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }
        .assign-form input[type="submit"]:hover {
            background-color: #45a049;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        a {
            color: #0066cc;
            text-decoration: none;
            margin-right: 10px;
        }
        a:hover {
            text-decoration: underline;
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
        .history {
            margin-top: 5px;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main content area -->
    <div class="content">
        <h1>Manage Incidents</h1>
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

        <!-- Card for Unassigned Incidents -->
        <div class="card">
            <h3>Unassigned Incidents (Most Recent 5)</h3>
            <ul class="incident-list">
                <?php while ($unassigned = mysqli_fetch_assoc($unassigned_result)) { ?>
                    <li>
                        ID: <?php echo $unassigned['id']; ?> - 
                        <?php echo htmlspecialchars($unassigned['narrative']); ?> (<?php echo htmlspecialchars($unassigned['location']); ?>, 
                        Severity: <?php echo htmlspecialchars($unassigned['severity']); ?>) - 
                        Submitted: <?php echo $unassigned['submitted_at']; ?>
                        <form method="POST" action="" class="assign-form">
                            <input type="hidden" name="incident_id" value="<?php echo $unassigned['id']; ?>">
                            <select name="ambulance_id">
                                <option value="">Select Ambulance</option>
                                <?php 
                                mysqli_data_seek($ambulances_result, 0);
                                while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { ?>
                                    <option value="<?php echo $ambulance['id']; ?>">
                                        <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <input type="submit" name="assign_ambulance" value="Assign">
                        </form>
                    </li>
                <?php } ?>
            </ul>
        </div>

        <!-- Incidents Table with History -->
        <table>
            <tr>
                <th>ID</th>
                <th>Emergency Type</th>
                <th>Narrative</th>
                <th>Location</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Reporter Name</th>
                <th>Contact Phone</th>
                <th>Assigned Ambulance</th>
                <th>Paramedic</th>
                <th>Driver</th>
                <th>Actions</th>
                <th>History</th>
            </tr>
            <?php 
            mysqli_data_seek($result, 0);
            while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['emergency_type'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['narrative']); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo htmlspecialchars($row['severity'] ?? 'UNKNOWN'); ?></td>
                    <td><?php echo htmlspecialchars($row['status'] ?? 'UNVERIFIED'); ?></td>
                    <td><?php echo htmlspecialchars($row['reporter_name'] ?? 'Anonymous'); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_phone'] ?? 'N/A'); ?></td>
                    <td><?php echo $row['vehicle_number'] ? htmlspecialchars($row['vehicle_number']) : 'Not Assigned'; ?></td>
                    <td><?php echo $row['paramedic_name'] ? htmlspecialchars($row['paramedic_name']) : 'N/A'; ?></td>
                    <td><?php echo $row['driver_name'] ? htmlspecialchars($row['driver_name']) : 'N/A'; ?></td>
                    <td>
                        <a href="incidents.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this incident?');">Delete</a>
                        <?php if ($row['status'] === 'ASSIGNED') { ?>
                            <a href="incidents.php?close_id=<?php echo $row['id']; ?>" onclick="return confirm('Close this incident?');"> | Close</a>
                        <?php } elseif ($row['status'] === 'UNVERIFIED') { ?>
                            <form method="POST" action="" class="actions-form">
                                <input type="hidden" name="incident_id" value="<?php echo $row['id']; ?>">
                                <select name="ambulance_id">
                                    <option value="">Select Ambulance</option>
                                    <?php 
                                    mysqli_data_seek($ambulances_result, 0);
                                    while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { 
                                        if ($ambulance['id'] != $row['ambulance_id']) { ?>
                                            <option value="<?php echo $ambulance['id']; ?>">
                                                <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                                            </option>
                                    <?php } } ?>
                                </select>
                                <input type="submit" name="assign_ambulance" value="Assign">
                            </form>
                        <?php } ?>
                    </td>
                    <td class="history">
                        Submitted: <?php echo $row['submitted_at'] ?? 'N/A'; ?><br>
                        Verified: <?php echo $row['verified_at'] ?? 'N/A'; ?><br>
                        Assigned: <?php echo $row['assigned_at'] ?? 'N/A'; ?><br>
                        Assigned by: <?php echo htmlspecialchars($row['assigned_by'] ?? 'N/A'); ?><br>
                        Closed: <?php echo $row['closed_at'] ?? 'N/A'; ?>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>

