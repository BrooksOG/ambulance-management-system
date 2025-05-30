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

// Handling form submission to add a new ambulance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_number = mysqli_real_escape_string($conn, $_POST['vehicle_number']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $status = $_POST['status'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Basic validation
    if (empty($vehicle_number) || empty($type) || empty($status) || empty($location)) {
        $error = "All fields are required.";
    } else {
        // Inserting the new ambulance into the database
        $query = "INSERT INTO ambulances (vehicle_number, type, status, location) 
                  VALUES ('$vehicle_number', '$type', '$status', '$location')";
        if (mysqli_query($conn, $query)) {
            header('Location: ambulances.php?message=Ambulance added successfully');
            exit;
        } else {
            $error = "Error adding ambulance: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Ambulance</title>
    <!-- Internal CSS for styling -->
     <!-- Include the reusable sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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
        <h1>Add New Ambulance</h1>
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="vehicle_number">Vehicle Number:</label>
            <input type="text" id="vehicle_number" name="vehicle_number" required>
            
            <label for="type">Type:</label>
            <input type="text" id="type" name="type" required>
            
            <label for="status">Status:</label>
            <select id="status" name="status" required>
                <option value="AVAILABLE">Available</option>
                <option value="IN_USE">In Use</option>
                <option value="MAINTENANCE">Maintenance</option>
            </select>
            
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" required>
            
            <input type="submit" value="Add Ambulance">
        </form>
    </div>
</body>
</html>