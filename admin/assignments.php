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

// Fetching all assignments for display
$query = "SELECT * FROM ambulance_assignments";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments</title>
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
    </style>
</head>
<body>
    <!-- Include the reusable sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main content area -->
    <div class="content">
        <h1>Manage Assignments</h1>
        <p><a href="add_assignment.php">Add New Assignment</a></p>
        <table>
            <tr>
                <th>ID</th>
                <th>Ambulance ID</th>
                <th>Paramedic ID</th>
                <th>Driver ID</th>
                <th>Active</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['ambulance_id']; ?></td>
                    <td><?php echo $row['paramedic_id']; ?></td>
                    <td><?php echo $row['driver_id']; ?></td>
                    <td><?php echo $row['active'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="edit_assignment.php?id=<?php echo $row['id']; ?>">Edit</a> |
                        <a href="delete_assignment.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>