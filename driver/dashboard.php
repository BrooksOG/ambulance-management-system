<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DRIVER') {
    header('Location: ../login.php');
    exit;
}

$driver_id = $_SESSION['user_id'];

// Fetch driver assignments with ambulance details and paramedic name
$query = "SELECT d.id, a.vehicle_number, a.status, u.username AS paramedic_name
          FROM dispatches d
          JOIN ambulances a ON d.ambulance_id = a.id
          LEFT JOIN users u ON d.paramedic_id = u.id AND u.role = 'PARAMEDIC'
          WHERE d.driver_id = $driver_id AND d.status = 'PENDING'";
$result = mysqli_query($conn, $query);
if ($result === FALSE) {
    $error = "Error fetching assignments: " . mysqli_error($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .sidebar {
            width: 200px;
            height: 100%;
            position: fixed;
            top: 0;
            left: 0;
            background-color: black;
            padding-top: 20px;
            color: white;
        }
        .sidebar a {
            padding: 15px;
            text-decoration: none;
            font-size: 13px;
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
        .welcome {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .description {
            color: #666;
            margin-bottom: 20px;
        }
        .assignment-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 10px;
        }
        .assignment-card div {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        .assignment-card .label {
            font-weight: bold;
            color: #444;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }
        @media (max-width: 600px) {
            .assignment-card {
                grid-template-columns: 1fr;
            }
            .content {
                margin-left: 0;
                padding: 10px;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="dashboard.php">Dashboard</a>
        <a href="../logout.php">Logout</a>
    </div>
    <div class="content">
        <h1 class="welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Driver'); ?>!</h1>
        <p class="description">Driver dashboard to view your assignments.</p>
        <?php if (isset($error)) { echo '<p class="error">' . $error . '</p>'; } ?>
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="assignment-card">
                    <div><span class="label">ID:</span> <?php echo $row['id']; ?></div>
                    <div><span class="label">Ambulance:</span> <?php echo htmlspecialchars($row['vehicle_number'] ?? 'N/A'); ?></div>
                    <div><span class="label">Status:</span> <?php echo $row['status']; ?></div>
                    <div><span class="label">Paramedic:</span> <?php echo htmlspecialchars($row['paramedic_name'] ?? 'Not Assigned'); ?></div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No assignments available.</p>
        <?php } ?>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>