<?php
  // Including the database connection file from the root includes folder
  include '../includes/db_connect.php';
  // Starting the session to verify user authentication and role
  session_start();
  // Checking if the user is logged in and has the dispatcher role
  if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'DISPATCHER') {
    header('Location:../login.php');
    exit;
  }
  // Fetching analytics data using MySQLi
  // Total number of incidents
  $incidents_query = "SELECT COUNT(*) as total FROM incidents";
  $incidents_result = mysqli_query($conn, $incidents_query);
  $incidents_count = mysqli_fetch_assoc($incidents_result)['total'];
  // Total number of drivers
  $drivers_query = "SELECT COUNT(*) as total FROM users WHERE role = 'DRIVER'";
  $drivers_result = mysqli_query($conn, $drivers_query);
  $drivers_count = mysqli_fetch_assoc($drivers_result)['total'];
  // Total number of paramedics
  $paramedics_query = "SELECT COUNT(*) as total FROM users WHERE role = 'PARAMEDIC'";
  $paramedics_result = mysqli_query($conn, $paramedics_query);
  $paramedics_count = mysqli_fetch_assoc($paramedics_result)['total'];
  // Total number of ambulances
  $ambulances_query = "SELECT COUNT(*) as total FROM ambulances";
  $ambulances_result = mysqli_query($conn, $ambulances_query);
  $ambulances_count = mysqli_fetch_assoc($ambulances_result)['total'];
  // Close the database connection to ensure fresh data on next load
  mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dispatcher Dashboard</title>
  <!-- Internal CSS for styling the content and analytics cards -->
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
    .analytics {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-bottom: 20px;
    }
    .card {
      background-color: #f2f2f2;
      padding: 20px;
      width: 200px;
      text-align: center;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      cursor: pointer;
      transition: background-color 0.3s;
    }
    .card:hover {
      background-color: #e0e0e0;
    }
    .card h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: #333;
    }
    .card p {
      margin: 0;
      font-size: 24px;
      color: #4CAF50;
    }
  </style>
</head>
<body>
<!-- Include the reusable sidebar from the dispatcher includes folder -->
<?php
  include 'includes/sidebar.php';
?>
<!-- Main content area for dispatcher dashboard -->
<div class="content">
  <h1>Welcome, <?php echo $_SESSION['username'];?>!</h1>
  <p>Dispatcher dashboard to manage incidents and dispatches.</p>
  <!-- Analytics cards with clickable links -->
  <div class="analytics">
    <a href="incidents.php" style="text-decoration: none;">
      <div class="card">
        <h3>Total Incidents</h3>
        <p><?php echo $incidents_count;?></p>
      </div>
    </a>
    <a href="drivers.php" style="text-decoration: none;">
      <div class="card">
        <h3>Total Drivers</h3>
        <p><?php echo $drivers_count;?></p>
      </div>
    </a>
    <a href="paramedics.php" style="text-decoration: none;">
      <div class="card">
        <h3>Total Paramedics</h3>
        <p><?php echo $paramedics_count;?></p>
      </div>
    </a>
    <a href="ambulances.php" style="text-decoration: none;">
      <div class="card">
        <h3>Total Ambulances</h3>
        <p><?php echo $ambulances_count;?></p>
      </div>
    </a>
  </div>
  <!-- Additional guidance for the dispatcher -->
  <p>Use the sidebar to manage incidents, ambulances, drivers, paramedics, and assignments.</p>
</div>
</body>
</html>