<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DISPATCHER') {
    header('Location: ../login.php');
    exit;
}

$incident_id = isset($_GET['incident_id']) ? (int)$_GET['incident_id'] : 0;
if ($incident_id == 0) {
    header('Location: incidents.php?error=Invalid incident ID');
    exit;
}

// Fetch incident details
$incident_query = "SELECT location, narrative FROM incidents WHERE id = $incident_id";
$incident_result = mysqli_query($conn, $incident_query);
$incident = mysqli_fetch_assoc($incident_result);

// Fetch available ambulances
$ambulance_query = "SELECT id, vehicle_number FROM ambulances WHERE status = 'AVAILABLE'";
$ambulances_result = mysqli_query($conn, $ambulance_query);

// Fetch available drivers and paramedics
$drivers_query = "SELECT id, username FROM users WHERE role = 'DRIVER'";
$drivers_result = mysqli_query($conn, $drivers_query);
$paramedics_query = "SELECT id, username FROM users WHERE role = 'PARAMEDIC'";
$paramedics_result = mysqli_query($conn, $paramedics_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_ambulance'])) {
    $ambulance_id = (int)$_POST['ambulance_id'];
    $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : 'NULL';
    $paramedic_id = !empty($_POST['paramedic_id']) ? (int)$_POST['paramedic_id'] : 'NULL';

    mysqli_begin_transaction($conn);
    try {
        // Insert into dispatches
        $dispatch_query = "INSERT INTO dispatches (incident_id, ambulance_id, driver_id, paramedic_id, dispatch_time, status) 
                           VALUES ($incident_id, $ambulance_id, $driver_id, $paramedic_id, NOW(), 'PENDING')";
        mysqli_query($conn, $dispatch_query);

        // Update incident, including verified_at
        $update_incident = "UPDATE incidents 
                            SET status = 'ASSIGNED', 
                                ambulance_id = $ambulance_id, 
                                assigned_at = NOW(), 
                                verified_at = NOW(), 
                                assigned_by = '{$_SESSION['username']}' 
                            WHERE id = $incident_id";
        mysqli_query($conn, $update_incident);

        // Update ambulance status
        $update_ambulance = "UPDATE ambulances SET status = 'DISPATCHED' WHERE id = $ambulance_id";
        mysqli_query($conn, $update_ambulance);

        mysqli_commit($conn);
        header('Location: incidents.php?message=Assignment successful');
        exit;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Error assigning ambulance: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Ambulance</title>
    <style>
        .content { margin-left: 220px; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        select { width: 200px; padding: 5px; }
        input[type="submit"] { padding: 5px 10px; background-color: #4CAF50; color: white; border: none; cursor: pointer; }
        .error { color: red; font-weight: bold; }
        .incident-info { margin-bottom: 20px; padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Assign Ambulance to Incident #<?php echo $incident_id; ?></h1>
        <?php if (isset($error)) { echo '<p class="error">' . $error . '</p>'; } ?>
        <?php if ($incident) { ?>
            <div class="incident-info">
                <p><strong>Location:</strong> <?php echo htmlspecialchars($incident['location'] ?? 'N/A'); ?></p>
                <p><strong>Narrative:</strong> <?php echo htmlspecialchars($incident['narrative'] ?? 'N/A'); ?></p>
            </div>
        <?php } ?>
        <?php if (mysqli_num_rows($ambulances_result) > 0) { ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="ambulance_id">Select Ambulance:</label>
                    <select id="ambulance_id" name="ambulance_id" required>
                        <option value="">-- Select Ambulance --</option>
                        <?php while ($ambulance = mysqli_fetch_assoc($ambulances_result)) { ?>
                            <option value="<?php echo $ambulance['id']; ?>">
                                <?php echo htmlspecialchars($ambulance['vehicle_number']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="driver_id">Select Driver:</label>
                    <select id="driver_id" name="driver_id">
                        <option value="">-- Select Driver (Optional) --</option>
                        <?php while ($driver = mysqli_fetch_assoc($drivers_result)) { ?>
                            <option value="<?php echo $driver['id']; ?>">
                                <?php echo htmlspecialchars($driver['username']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="paramedic_id">Select Paramedic:</label>
                    <select id="paramedic_id" name="paramedic_id">
                        <option value="">-- Select Paramedic (Optional) --</option>
                        <?php while ($paramedic = mysqli_fetch_assoc($paramedics_result)) { ?>
                            <option value="<?php echo $paramedic['id']; ?>">
                                <?php echo htmlspecialchars($paramedic['username']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <input type="submit" name="assign_ambulance" value="Assign">
            </form>
        <?php } else { ?>
            <p>No available ambulances.</p>
        <?php } ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>