<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PARAMEDIC') {
    header('Location: ../login.php');
    exit;
}

$dispatch_id = isset($_GET['dispatch_id']) ? (int)$_GET['dispatch_id'] : 0;
if ($dispatch_id == 0) {
    header('Location: dashboard.php?error=Invalid dispatch ID');
    exit;
}

// Fetch incident, ambulance, and driver details
$query = "SELECT i.id AS incident_id, i.narrative, i.location, i.severity, i.emergency_type, i.reporter_name, i.contact_phone,
                 a.vehicle_number, a.type AS ambulance_type, u.username AS driver_name
          FROM dispatches d
          JOIN incidents i ON d.incident_id = i.id
          JOIN ambulances a ON d.ambulance_id = a.id
          LEFT JOIN users u ON d.driver_id = u.id
          WHERE d.id = $dispatch_id AND d.paramedic_id = {$_SESSION['user_id']}";
$result = mysqli_query($conn, $query);
$details = mysqli_fetch_assoc($result);

if (!$details) {
    header('Location: dashboard.php?error=Incident not found or not assigned to you');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Details</title>
    <style>
        .content { margin-left: 220px; padding: 20px; }
        .details { background-color: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; }
        .details h2 { margin-top: 0; }
        .details p { margin: 5px 0; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Incident Details</h1>
        <div class="details">
            <h2>Incident #<?php echo $details['incident_id']; ?></h2>
            <p><strong>Narrative:</strong> <?php echo htmlspecialchars($details['narrative']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($details['location']); ?></p>
            <p><strong>Severity:</strong> <?php echo $details['severity']; ?></p>
            <p><strong>Emergency Type:</strong> <?php echo htmlspecialchars($details['emergency_type'] ?? 'N/A'); ?></p>
            <p><strong>Reporter:</strong> <?php echo htmlspecialchars($details['reporter_name'] ?? 'N/A'); ?></p>
            <p><strong>Contact:</strong> <?php echo htmlspecialchars($details['contact_phone'] ?? 'N/A'); ?></p>
            <h3>Assigned Ambulance</h3>
            <p><strong>Vehicle Number:</strong> <?php echo htmlspecialchars($details['vehicle_number']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($details['ambulance_type']); ?></p>
            <h3>Assigned Driver</h3>
            <p><strong>Driver:</strong> <?php echo htmlspecialchars($details['driver_name'] ?? 'Not Assigned'); ?></p>
        </div>
        <p><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>