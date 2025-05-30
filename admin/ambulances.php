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

// Fetching all ambulances for display with their assignments
$query = "SELECT a.*, aa.paramedic_id, p.username AS paramedic_name, aa.driver_id, d.username AS driver_name 
          FROM ambulances a 
          LEFT JOIN ambulance_assignments aa ON a.id = aa.ambulance_id 
          LEFT JOIN users p ON aa.paramedic_id = p.id 
          LEFT JOIN users d ON aa.driver_id = d.id";
$result = mysqli_query($conn, $query);

// Fetching all drivers and paramedics for the assignment dropdowns
$drivers_query = "SELECT id, username FROM users WHERE role = 'DRIVER'";
$drivers_result = mysqli_query($conn, $drivers_query);
$paramedics_query = "SELECT id, username FROM users WHERE role = 'PARAMEDIC'";
$paramedics_result = mysqli_query($conn, $paramedics_query);

// Handling form submission to assign an ambulance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_ambulance'])) {
    $ambulance_id = (int)$_POST['ambulance_id'];
    $paramedic_id = !empty($_POST['paramedic_id']) ? (int)$_POST['paramedic_id'] : NULL;
    $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : NULL;

    // Check if an assignment already exists
    $check_query = "SELECT id FROM ambulance_assignments WHERE ambulance_id = $ambulance_id";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        // Update existing assignment
        $query = "UPDATE ambulance_assignments 
                  SET paramedic_id = " . ($paramedic_id ? $paramedic_id : 'NULL') . ", 
                      driver_id = " . ($driver_id ? $driver_id : 'NULL') . ", 
                      active = 1 
                  WHERE ambulance_id = $ambulance_id";
    } else {
        // Insert new assignment
        $query = "INSERT INTO ambulance_assignments (ambulance_id, paramedic_id, driver_id, active) 
                  VALUES ($ambulance_id, " . ($paramedic_id ? $paramedic_id : 'NULL') . ", " . ($driver_id ? $driver_id : 'NULL') . ", 1)";
    }

    if (mysqli_query($conn, $query)) {
        header('Location: ambulances.php?message=Ambulance assigned successfully');
        exit;
    } else {
        $error = "Error assigning ambulance: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ambulances</title>
    <!-- Internal CSS for styling -->
    <style>
        /* Removed body and sidebar styles as they are handled by sidebar.php */
        .content {
            margin-left: 220px;
            padding: 20px;
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
        .assign-form {
            display: none;
            margin: 10px 0;
        }
        .assign-form select {
            width: 150px;
            margin: 5px 0;
        }
        .assign-form input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .assign-form input[type="submit"]:hover {
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
    </style>
    <!-- JavaScript for toggling assign forms -->
    <script>
        function toggleAssignForm(rowId) {
            const form = document.getElementById('assign-form-' + rowId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <!-- Include the reusable sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main content area -->
    <div class="content">
        <h1>Manage Ambulances</h1>
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
        <p><a href="add_ambulance.php">Add New Ambulance</a></p>
        <table>
            <tr>
                <th>ID</th>
                <th>Vehicle Number</th>
                <th>Type</th>
                <th>Status</th>
                <th>Location</th>
                <th>Assigned Paramedic</th>
                <th>Assigned Driver</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['vehicle_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                    <td><?php echo $row['paramedic_name'] ? htmlspecialchars($row['paramedic_name']) : 'N/A'; ?></td>
                    <td><?php echo $row['driver_name'] ? htmlspecialchars($row['driver_name']) : 'N/A'; ?></td>
                    <td>
                        <a href="javascript:void(0);" onclick="toggleAssignForm(<?php echo $row['id']; ?>)">Assign</a> |
                        <a href="edit_ambulance.php?id=<?php echo $row['id']; ?>">Edit</a> |
                        <a href="delete_ambulance.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
                <tr>
                    <td colspan="8">
                        <div id="assign-form-<?php echo $row['id']; ?>" class="assign-form">
                            <form method="POST" action="">
                                <input type="hidden" name="ambulance_id" value="<?php echo $row['id']; ?>">
                                <label for="paramedic_id_<?php echo $row['id']; ?>">Paramedic:</label>
                                <select id="paramedic_id_<?php echo $row['id']; ?>" name="paramedic_id">
                                    <option value="">None</option>
                                    <?php 
                                    mysqli_data_seek($paramedics_result, 0);
                                    while ($paramedic = mysqli_fetch_assoc($paramedics_result)) { 
                                    ?>
                                        <option value="<?php echo $paramedic['id']; ?>" <?php if ($paramedic['id'] == $row['paramedic_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($paramedic['username']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                
                                <label for="driver_id_<?php echo $row['id']; ?>">Driver:</label>
                                <select id="driver_id_<?php echo $row['id']; ?>" name="driver_id">
                                    <option value="">None</option>
                                    <?php 
                                    mysqli_data_seek($drivers_result, 0);
                                    while ($driver = mysqli_fetch_assoc($drivers_result)) { 
                                    ?>
                                        <option value="<?php echo $driver['id']; ?>" <?php if ($driver['id'] == $row['driver_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($driver['username']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                
                                <input type="submit" name="assign_ambulance" value="Assign">
                            </form>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>