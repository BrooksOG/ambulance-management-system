<?php
// driver/assignments.php
session_start();
if ($_SESSION['role']!=='DRIVER') { header('Location: ../login.php'); exit; }
require_once __DIR__.'/../includes/db_connect.php';
$did = (int)$_SESSION['user_id'];

$res = $conn->query("
  SELECT aa.id, a.vehicle_number, d.status, d.dispatch_time
  FROM ambulance_assignments aa
  JOIN ambulances a ON aa.ambulance_id=a.id
  JOIN dispatches d ON aa.ambulance_id=d.ambulance_id
  WHERE aa.driver_id=$did
  ORDER BY aa.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>My Assignments</title>
  <style>/* reuse table styles */</style>
</head><body>
<div class="wrap">
  <nav class="sidebar">
    <h2>Driver Menu</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="assignments.php">My Assignments</a>
    <a href="../logout.php">Logout</a>
  </nav>
  <main class="main">
    <h1>My Ambulance Assignments</h1>
    <table>
      <thead><tr><th>ID</th><th>Vehicle</th><th>Dispatch Status</th><th>Dispatch Time</th><th>Actions</th></tr></thead>
      <tbody>
      <?php while($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['vehicle_number']) ?></td>
          <td><?= $r['status'] ?></td>
          <td><?= $r['dispatch_time'] ?></td>
          <td class="actions">
            <a href="view_assignment.php?id=<?= $r['id'] ?>">View</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </main>
</div>
</body></html>
