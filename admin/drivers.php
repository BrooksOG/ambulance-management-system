<?php
include '../includes/db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}

// Handle Add New Driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_driver'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $license_number = mysqli_real_escape_string($conn, trim($_POST['license_number']));
    $password = password_hash('defaultpassword', PASSWORD_DEFAULT);

    $user_query = "INSERT INTO users (username, password, role, email, created_at) VALUES ('$username', '$password', 'DRIVER', '$email', NOW())";
    if (mysqli_query($conn, $user_query)) {
        $user_id = mysqli_insert_id($conn);
        $driver_query = "INSERT INTO driver_details (user_id, license_number, license_type, status, created_at) VALUES ('$user_id', '$license_number', 'ambulance', 'ACTIVE', NOW())";
        if (!mysqli_query($conn, $driver_query)) {
            $error = "Error adding driver details: " . mysqli_error($conn);
        }
    } else {
        $error = "Error adding user: " . mysqli_error($conn);
    }
}

// Handle Edit Driver
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_driver'])) {
    $id = mysqli_real_escape_string($conn, trim($_POST['id']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $license_number = mysqli_real_escape_string($conn, trim($_POST['license_number']));

    $update_query = "UPDATE users u 
                     JOIN driver_details dd ON u.id = dd.user_id 
                     SET u.username = '$username', u.email = '$email', dd.license_number = '$license_number' 
                     WHERE u.id = '$id'";
    if (!mysqli_query($conn, $update_query)) {
        $error = "Error updating driver: " . mysqli_error($conn);
    }
}

// Handle Assign Ambulance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ambulance'])) {
    $driver_id = mysqli_real_escape_string($conn, trim($_POST['driver_id']));
    $ambulance_id = mysqli_real_escape_string($conn, trim($_POST['ambulance_id']));

    // Check if the ambulance is linked to an open incident via dispatches
    $incident_query = "SELECT d.incident_id, i.status 
                      FROM dispatches d 
                      JOIN incidents i ON d.incident_id = i.id 
                      WHERE d.ambulance_id = '$ambulance_id' AND i.status != 'CLOSED'";
    $incident_result = mysqli_query($conn, $incident_query);
    $incident = mysqli_fetch_assoc($incident_result);
    $incident_id = $incident ? $incident['incident_id'] : null;

    // Check if assignment exists and update, or insert new
    $check_query = "SELECT id, ambulance_id FROM ambulance_assignments WHERE driver_id = '$driver_id' AND active = 1";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $row = mysqli_fetch_assoc($check_result);
        $old_ambulance_id = $row['ambulance_id'];

        // Check if the old ambulance was linked to an open incident
        $old_incident_query = "SELECT d.incident_id, i.status 
                               FROM dispatches d 
                               JOIN incidents i ON d.incident_id = i.id 
                               WHERE d.ambulance_id = '$old_ambulance_id' AND i.status != 'CLOSED'";
        $old_incident_result = mysqli_query($conn, $old_incident_query);
        $old_incident = mysqli_fetch_assoc($old_incident_result);
        $old_incident_id = $old_incident ? $old_incident['incident_id'] : null;

        // Log unassignment of the old incident (if any)
        if ($old_incident_id) {
            $driver_query = "SELECT history FROM driver_details WHERE user_id = '$driver_id'";
            $driver_result = mysqli_query($conn, $driver_query);
            $driver = mysqli_fetch_assoc($driver_result);
            $current_history = $driver['history'] ? $driver['history'] . "\n" : '';
            $new_history = $current_history . date('Y-m-d H:i:s') . " - UNASSIGNED from Incident #" . $old_incident_id;
            $update_history_query = "UPDATE driver_details SET history = '$new_history' WHERE user_id = '$driver_id'";
            mysqli_query($conn, $update_history_query);
        }

        // Update assignment
        $update_query = "UPDATE ambulance_assignments SET ambulance_id = '$ambulance_id', created_at = NOW() WHERE driver_id = '$driver_id' AND active = 1";
    } else {
        $update_query = "INSERT INTO ambulance_assignments (ambulance_id, driver_id, active, created_at) VALUES ('$ambulance_id', '$driver_id', 1, NOW())";
    }
    if (mysqli_query($conn, $update_query)) {
        // Log new incident assignment (if any)
        if ($incident_id) {
            $driver_query = "SELECT history FROM driver_details WHERE user_id = '$driver_id'";
            $driver_result = mysqli_query($conn, $driver_query);
            $driver = mysqli_fetch_assoc($driver_result);
            $current_history = $driver['history'] ? $driver['history'] . "\n" : '';
            $new_history = $current_history . date('Y-m-d H:i:s') . " - ASSIGNED to Incident #" . $incident_id;
            $update_history_query = "UPDATE driver_details SET history = '$new_history' WHERE user_id = '$driver_id'";
            if (!mysqli_query($conn, $update_history_query)) {
                $error = "Error updating assignment history: " . mysqli_error($conn);
            }
        }
    } else {
        $error = "Error assigning ambulance: " . mysqli_error($conn);
    }
}

// Fetch drivers with ambulance assignments and history
$query = "SELECT u.id, u.username, u.email, dd.license_number, dd.status, 
                 aa.ambulance_id, a.vehicle_number, dd.history, 
                 (SELECT i.id FROM dispatches d 
                  JOIN incidents i ON d.incident_id = i.id 
                  WHERE d.ambulance_id = aa.ambulance_id AND i.status != 'CLOSED' LIMIT 1) AS current_incident_id
          FROM users u 
          LEFT JOIN driver_details dd ON u.id = dd.user_id 
          LEFT JOIN ambulance_assignments aa ON u.id = aa.driver_id AND aa.active = 1 
          LEFT JOIN ambulances a ON aa.ambulance_id = a.id 
          WHERE u.role = 'DRIVER' 
          ORDER BY u.username";
$result = mysqli_query($conn, $query);
if ($result === FALSE) {
    $error = "Error fetching drivers: " . mysqli_error($conn);
}

// Fetch available ambulances (not assigned to an open incident)
$ambulance_query = "SELECT a.id, a.vehicle_number 
                   FROM ambulances a 
                   LEFT JOIN ambulance_assignments aa ON a.id = aa.ambulance_id AND aa.active = 1 
                   LEFT JOIN dispatches d ON a.id = d.ambulance_id 
                   LEFT JOIN incidents i ON d.incident_id = i.id 
                   WHERE (i.status IS NULL OR i.status = 'CLOSED') 
                      OR (aa.ambulance_id IS NULL)";
$ambulance_result = mysqli_query($conn, $ambulance_query);
$ambulances = [];
while ($row = mysqli_fetch_assoc($ambulance_result)) {
    $ambulances[$row['id']] = $row['vehicle_number'];
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Drivers</title>
    <style>
        .content { margin-left: 220px; padding: 20px; }
        .add-driver { margin-bottom: 20px; }
        .add-driver a, .edit-form a { color: #007bff; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .actions a { color: #007bff; margin-right: 10px; text-decoration: none; }
        .error { color: red; margin: 10px 0; }
        .edit-form, .assign-form, .history-form { display: none; margin-top: 10px; }
        .edit-form form, .assign-form form { display: flex; flex-direction: column; gap: 10px; }
        .history-form { padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; }
        .current-incident { font-style: italic; color: #555; }
    </style>
    <script>
        function showEditForm(id, username, email, license_number) {
            document.getElementById('editForm_' + id).style.display = 'block';
            document.getElementById('edit_id_' + id).value = id;
            document.getElementById('edit_username_' + id).value = username;
            document.getElementById('edit_email_' + id).value = email;
            document.getElementById('edit_license_' + id).value = license_number;
        }

        function showAssignForm(id) {
            document.getElementById('assignForm_' + id).style.display = 'block';
            document.getElementById('assign_driver_id_' + id).value = id;
        }

        function showHistory(id) {
            document.getElementById('historyForm_' + id).style.display = 'block';
        }
    </script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Manage Drivers</h1>
        <div class="add-driver">
            <a href="#" onclick="document.getElementById('addDriverForm').style.display='block'">Add New Driver</a>
        </div>
        
        <!-- Add Driver Form -->
        <div id="addDriverForm" style="display:none; margin-bottom: 20px;">
            <form method="POST" action="">
                <label>Username: <input type="text" name="username" required></label><br>
                <label>Email: <input type="email" name="email" required></label><br>
                <label>License Number: <input type="text" name="license_number" required></label><br>
                <button type="submit" name="add_driver">Add Driver</button>
                <button type="button" onclick="document.getElementById('addDriverForm').style.display='none'">Cancel</button>
            </form>
        </div>

        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>License Number</th>
                <th>Status</th>
                <th>Assigned Ambulance</th>
                <th>Current Incident</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['license_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo $row['vehicle_number'] ? htmlspecialchars($row['vehicle_number']) : 'unassigned'; ?></td>
                    <td class="current-incident"><?php echo $row['current_incident_id'] ? "Incident #" . htmlspecialchars($row['current_incident_id']) : 'None'; ?></td>
                    <td class="actions">
                        <a href="#" onclick="showEditForm(<?php echo $row['id']; ?>, '<?php echo addslashes($row['username']); ?>', '<?php echo addslashes($row['email']); ?>', '<?php echo addslashes($row['license_number']); ?>')">Edit</a> | 
                        <a href="#">Delete</a> | 
                        <a href="#" onclick="showAssignForm(<?php echo $row['id']; ?>)">Assign</a> | 
                        <a href="#" onclick="showHistory(<?php echo $row['id']; ?>)">History</a>
                    </td>
                </tr>
                <!-- Edit Form -->
                <tr id="editForm_<?php echo $row['id']; ?>" class="edit-form">
                    <td colspan="8">
                        <form method="POST" action="">
                            <input type="hidden" name="id" id="edit_id_<?php echo $row['id']; ?>" value="">
                            <label>Username: <input type="text" name="username" id="edit_username_<?php echo $row['id']; ?>" required></label><br>
                            <label>Email: <input type="email" name="email" id="edit_email_<?php echo $row['id']; ?>" required></label><br>
                            <label>License Number: <input type="text" name="license_number" id="edit_license_<?php echo $row['id']; ?>" required></label><br>
                            <button type="submit" name="edit_driver">Update</button>
                            <button type="button" onclick="document.getElementById('editForm_<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                        </form>
                    </td>
                </tr>
                <!-- Assign Form -->
                <tr id="assignForm_<?php echo $row['id']; ?>" class="assign-form">
                    <td colspan="8">
                        <form method="POST" action="">
                            <input type="hidden" name="driver_id" id="assign_driver_id_<?php echo $row['id']; ?>" value="">
                            <label>Assign Ambulance: 
                                <select name="ambulance_id" required>
                                    <option value="">Select Ambulance</option>
                                    <?php foreach ($ambulances as $id => $vehicle_number) { ?>
                                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($vehicle_number); ?></option>
                                    <?php } ?>
                                </select>
                            </label><br>
                            <button type="submit" name="assign_ambulance">Assign</button>
                            <button type="button" onclick="document.getElementById('assignForm_<?php echo $row['id']; ?>').style.display='none'">Cancel</button>
                        </form>
                    </td>
                </tr>
                <!-- History Form -->
                <tr id="historyForm_<?php echo $row['id']; ?>" class="history-form">
                    <td colspan="8">
                        <h4>Incident History for <?php echo htmlspecialchars($row['username']); ?></h4>
                        <div class="history">
                            <?php
                            $history = $row['history'] ? nl2br(htmlspecialchars($row['history'])) : 'No incident history available.';
                            echo $history;
                            ?>
                        </div>
                        <button type="button" onclick="document.getElementById('historyForm_<?php echo $row['id']; ?>').style.display='none'">Close</button>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>