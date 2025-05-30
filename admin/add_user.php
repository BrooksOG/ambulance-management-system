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

// Handling form submission to add a new user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = md5($_POST['password']); // Using MD5 for password hashing as per request
    $role = $_POST['role'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {
        // Inserting the new user into the database
        $query = "INSERT INTO users (username, email, password, role) 
                  VALUES ('$username', '$email', '$password', '$role')";
        if (mysqli_query($conn, $query)) {
            header('Location: users.php?message=User added successfully');
            exit;
        } else {
            $error = "Error adding user: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User</title>
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
        <h1>Add New User</h1>
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="ADMIN">Admin</option>
                <option value="DISPATCHER">Dispatcher</option>
                <option value="PARAMEDIC">Paramedic</option>
                <option value="DRIVER">Driver</option>
            </select>
            
            <input type="submit" value="Add User">
        </form>
    </div>
</body>
</html>