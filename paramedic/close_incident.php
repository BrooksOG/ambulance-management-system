<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PARAMEDIC') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_incident'])) {
    $incident_id = (int)$_POST['incident_id'];
    $patient_name = mysqli_real_escape_string($conn, trim($_POST['patient_name']));
    $outcome = mysqli_real_escape_string($conn, trim($_POST['outcome']));

    // Start transaction
    mysqli_begin_transaction($conn);
    try {
        // Update incident status to CLOSED and set closed_at
        $update_incident_query = "UPDATE incidents 
                                 SET status = 'CLOSED', 
                                     closed_at = NOW() 
                                 WHERE id = $incident_id AND ambulance_id IN (
                                     SELECT ambulance_id 
                                     FROM dispatches 
                                     WHERE paramedic_id = {$_SESSION['user_id']} AND status = 'PENDING'
                                 )";
        if (mysqli_query($conn, $update_incident_query)) {
            // Get the assigned ambulance ID
            $ambulance_query = "SELECT ambulance_id FROM incidents WHERE id = $incident_id";
            $ambulance_result = mysqli_query($conn, $ambulance_query);
            $ambulance = mysqli_fetch_assoc($ambulance_result);
            $ambulance_id = $ambulance['ambulance_id'];

            if ($ambulance_id) {
                // Set ambulance status back to AVAILABLE
                $update_ambulance_query = "UPDATE ambulances SET status = 'AVAILABLE' WHERE id = $ambulance_id";
                mysqli_query($conn, $update_ambulance_query);

                // Deactivate the dispatch
                $deactivate_query = "UPDATE dispatches SET status = 'COMPLETED' WHERE incident_id = $incident_id AND paramedic_id = {$_SESSION['user_id']}";
                mysqli_query($conn, $deactivate_query);
            }

            // Insert patient outcome into the new table
            $outcome_query = "INSERT INTO patient_outcomes (incident_id, patient_name, outcome) 
                             VALUES ($incident_id, '$patient_name', '$outcome')";
            if (!mysqli_query($conn, $outcome_query)) {
                throw new Exception("Error recording patient outcome: " . mysqli_error($conn));
            }

            mysqli_commit($conn);
            header('Location: dashboard.php?message=Incident closed successfully');
            exit;
        } else {
            throw new Exception("Error closing incident: " . mysqli_error($conn));
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}

// Fetch pending incidents for the paramedic to close
$user_id = $_SESSION['user_id'];
$query = "SELECT i.id, i.narrative, i.location 
          FROM incidents i
          JOIN dispatches d ON i.id = d.incident_id
          WHERE d.paramedic_id = $user_id AND d.status = 'PENDING'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Close Incident</title>
    <style>
        .content { margin-left: 220px; padding: 20px; }
        .card { background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #333; }
        .incident-list { list-style: none; padding: 0; }
        .incident-list li { margin-bottom: 10px; }
        form { margin-top: 10px; }
        label { display: block; margin: 5px 0; }
        input[type="text"], textarea { width: 300px; padding: 5px; }
        input[type="submit"] { background-color: #4CAF50; color: white; border: none; padding: 5px 10px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #45a049; }
        .error { color: red; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Close Incident</h1>
        <?php 
        if (isset($_GET['message'])) {
            echo '<p class="message">' . htmlspecialchars($_GET['message']) . '</p>';
        }
        if (isset($error)) {
            echo '<p class="error">' . $error . '</p>';
        }
        ?>

        <?php if (mysqli_num_rows($result) > 0) { ?>
            <div class="card">
                <h3>Select Incident to Close</h3>
                <ul class="incident-list">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <li>
                            Incident #<?php echo $row['id']; ?> - 
                            <?php echo htmlspecialchars($row['narrative']); ?> 
                            (<?php echo htmlspecialchars($row['location']); ?>)
                            <form method="POST" action="">
                                <input type="hidden" name="incident_id" value="<?php echo $row['id']; ?>">
                                <label for="patient_name_<?php echo $row['id']; ?>">Patient Name:</label>
                                <input type="text" id="patient_name_<?php echo $row['id']; ?>" name="patient_name" required>
                                <label for="outcome_<?php echo $row['id']; ?>">Outcome (e.g., Successful or Patient Died):</label>
                                <input type="text" id="outcome_<?php echo $row['id']; ?>" name="outcome" required>
                                <input type="submit" name="close_incident" value="Close Incident">
                            </form>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } else { ?>
            <p>No pending incidents to close.</p>
        <?php } ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>