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

// Fetching the ambulance to edit
$ambulance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = "SELECT * FROM ambulances WHERE id = $ambulance_id";
$result = mysqli_query($conn, $query);
$ambulance = mysqli_fetch_assoc($result);

if (!$ambulance) {
    header('Location: ambulances.php?error=Ambulance not found');
    exit;
}

// Handling form submission to update the ambulance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $status = $_POST['status'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Basic validation
    if (empty($vehicle_number) || empty($type) || empty($status) || empty($location)) {
        $error = "All fields are required.";
    } else {
        // Updating the ambulance in the database
        $query = "UPDATE ambulances SET vehicle_number = '$vehicle_number', type = '$type', status = '$status', location = '$location' 
                  WHERE id = $ambulance_id";
        if (mysqli_query($conn, $query)) {
            header('Location: ambulances.php?message=Ambulance updated successfully');
            exit;
        } else {
            $error = "Error updating ambulance: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ambulance</title>
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
        h1 {
            color: #333;
        }
        form {
            max-width: 400px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input, select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
        }
    </style>
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
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Main content area -->
    <div class="content">
        <h1>Edit Ambulance</h1>
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="vehicle_number">Vehicle Number:</label>
            <input type="text" id="vehicle_number" name="vehicle_number" value="<?php echo htmlspecialchars($ambulance['vehicle_number']); ?>" required>
            
            <label for="type">Type:</label>
            <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($ambulance['type']); ?>" required>
            
            <label for="status">Status:</label>
            <select id="status" name="status" required>
                <option value="AVAILABLE" <?php if ($ambulance['status'] === 'AVAILABLE') echo 'selected'; ?>>Available</option>
                <option value="IN_USE" <?php if ($ambulance['status'] === 'IN_USE') echo 'selected'; ?>>In Use</option>
                <option value="MAINTENANCE" <?php if ($ambulance['status'] === 'MAINTENANCE') echo 'selected'; ?>>Maintenance</option>
            </select>
            
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($ambulance['location']); ?>" required>
            
            <input type="submit" value="Update Ambulance">
        </form>
    </div>
</body>
</html>