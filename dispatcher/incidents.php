<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DISPATCHER') {
    header('Location: ../login.php');
    exit;
}

// Fetch incidents with history details
$query = "SELECT id, narrative, emergency_type, location, severity, status, reporter_name, contact_phone, 
                 submitted_at, verified_at, assigned_at, assigned_by, closed_at, ambulance_id 
          FROM incidents";
$result = mysqli_query($conn, $query);

// Handle close action
if (isset($_GET['close_id'])) {
    $close_id = (int)$_GET['close_id'];

    // Get the assigned ambulance
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
        }
        header('Location: incidents.php?message=Incident closed successfully');
        exit;
    } else {
        header('Location: incidents.php?error=Error closing incident: ' . mysqli_error($conn));
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
    <style>
        .content { margin-left: 220px; padding: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #0066cc; text-decoration: none; margin-right: 10px; }
        a:hover { text-decoration: underline; }
        .message { color: green; font-weight: bold; background-color: #e8f5e9; padding: 10px; margin: 10px 0; }
        .error { color: red; font-weight: bold; background-color: #ffebee; padding: 10px; margin: 10px 0; }
        .history { font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Manage Incidents</h1>
        <?php
        if (isset($_GET['message'])) {
            echo '<p class="message">' . htmlspecialchars($_GET['message']) . '</p>';
        }
        if (isset($_GET['error'])) {
            echo '<p class="error">' . htmlspecialchars($_GET['error']) . '</p>';
        }
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Emergency Type</th>
                <th>Narrative</th>
                <th>Location</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Reporter</th>
                <th>Contact</th>
                <th>Action</th>
                <th>History</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['emergency_type'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['narrative'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo $row['severity']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo htmlspecialchars($row['reporter_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_phone'] ?? 'N/A'); ?></td>
                    <td>
                        <?php if ($row['status'] == 'UNVERIFIED' || is_null($row['ambulance_id'])) { ?>
                            <a href="assign_ambulance.php?incident_id=<?php echo $row['id']; ?>">Assign Ambulance</a>
                        <?php } elseif ($row['status'] == 'ASSIGNED') { ?>
                            <a href="incidents.php?close_id=<?php echo $row['id']; ?>" onclick="return confirm('Close this incident?');">Close</a>
                        <?php } else { ?>
                            Closed
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
<?php mysqli_close($conn); ?>