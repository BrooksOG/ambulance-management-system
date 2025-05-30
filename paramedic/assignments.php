<?php
// paramedic/assignments.php
session_start();
if ($_SESSION['role']!=='PARAMEDIC') { header('Location: ../login.php'); exit; }
require_once __DIR__.'/../includes/db_connect.php';
$pid = (int)$_SESSION['user_id'];

$res = $conn->query("
  SELECT d.id, i.type, d.status, d.dispatch_time 
  FROM dispatches d
  JOIN incidents i ON d.incident_id=i.id
  WHERE d.paramedic_id=$pid
  ORDER BY dispatch_time DESC
");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>My Assignments</title>
  <style>/* reuse table styles */</style>
</head><body>
<div class="wrap">
  <nav class="sidebar">
    <h2>Paramedic Menu</h2>
    <a href="dashboard.php">Dashboard</a>
    <a href="assignments.php">My Assignments</a>
    <a href="../logout.php">Logout</a>
  </nav>
  <main class="main">
    <h1>My Dispatches</h1>
    <table>
      <thead><tr><th>ID</th><th>Type</th><th>Status</th><th>Dispatched</th><th>Actions</th></tr></thead>
      <tbody>
      <?php while($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['type']) ?></td>
          <td><?= $r['status'] ?></td>
          <td><?= $r['dispatch_time'] ?></td>
          <td class="actions">
            <a href="view_dispatch.php?id=<?= $r['id'] ?>">View</a>
            <a href="update_status.php?id=<?= $r['id'] ?>">Update Status</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </main>
</div>
</body></html>
