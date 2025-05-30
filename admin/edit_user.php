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

// Fetching the user to edit
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    header('Location: users.php?error=User not found');
    exit;
}

// Handling form submission to update the user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = $_POST['role'];
    $password = !empty($_POST['password']) ? md5($_POST['password']) : $user['password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($role)) {
        $error = "Username, email, and role are required.";
    } else {
        // Updating the user in the database
        $query = "UPDATE users SET username = '$username', email = '$email', password = '$password', role = '$role' 
                  WHERE id = $user_id";
        if (mysqli_query($conn, $query)) {
            header('Location: users.php?message=User updated successfully');
            exit;
        } else {
            $error = "Error updating user: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
        <h1>Edit User</h1>
        <?php if (isset($error)) { ?>
            <p class="error"><?php echo $error; ?></p>
        <?php } ?>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            
            <label for="password">Password (leave blank to keep unchanged):</label>
            <input type="password" id="password" name="password">
            
            <label for="role">Role:</label>
            <select id="role" name="role" required>
                <option value="ADMIN" <?php if ($user['role'] === 'ADMIN') echo 'selected'; ?>>Admin</option>
                <option value="DISPATCHER" <?php if ($user['role'] === 'DISPATCHER') echo 'selected'; ?>>Dispatcher</option>
                <option value="PARAMEDIC" <?php if ($user['role'] === 'PARAMEDIC') echo 'selected'; ?>>Paramedic</option>
                <option value="DRIVER" <?php if ($user['role'] === 'DRIVER') echo 'selected'; ?>>Driver</option>
            </select>
            
            <input type="submit" value="Update User">
        </form>
    </div>
</body>
</html>