<?php
include '../includes/db_connect.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'PARAMEDIC') {
    header('Location: ../login.php');
    exit;
}

// Fetch assigned incidents for the logged-in paramedic
$user_id = $_SESSION['user_id'];
$query = "SELECT d.id, i.id AS incident_id, i.narrative, i.location, i.severity 
          FROM dispatches d 
          JOIN incidents i ON d.incident_id = i.id 
          WHERE d.paramedic_id = $user_id AND d.status = 'PENDING'";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramedic Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .content { margin-left: 220px; padding: 20px; }
        .card { background-color: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; padding: 15px; margin-bottom: 20px; }
        .card h3 { margin-top: 0; color: #333; }
        .incident-list { list-style: none; padding: 0; }
        .incident-list li { margin-bottom: 10px; }
        a { color: #0066cc; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="content">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <div class="card">
            <h3>Assigned Incidents</h3>
            <?php if (mysqli_num_rows($result) > 0) { ?>
                <ul class="incident-list">
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <li>
                            <a href="incident_details.php?dispatch_id=<?php echo $row['id']; ?>">
                                Incident #<?php echo $row['incident_id']; ?> - 
                                <?php echo htmlspecialchars($row['narrative']); ?> 
                                (<?php echo htmlspecialchars($row['location']); ?>, Severity: <?php echo $row['severity']; ?>)
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            <?php } else { ?>
                <p>No assigned incidents at the moment.</p>
            <?php } ?>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>