<?php
// dispatcher/dispatches.php
session_start();
if ($_SESSION['role']!=='DISPATCHER') { header('Location: ../login.php'); exit; }
require_once __DIR__.'/../includes/db_connect.php';

$res = $conn->query("
  SELECT d.id, d.status, i.type, a.vehicle_number 
  FROM dispatches d
  JOIN incidents i ON d.incident_id=i.id
  JOIN ambulances a ON d.ambulance_id=a.id
  ORDER BY dispatch_time DESC
");
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Dispatches</title>
  <style>/* reuse table styles */</style>
</head><body>
<div class="wrap">
  <nav class="sidebar">…</nav>
  <main class="main">
    <h1>Dispatches</h1>
    <a href="create_dispatch.php" class="btn-create">+ Create Dispatch</a>
    <table>…</table>
  </main>
</div>
</body></html>
