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

// Fetching the ambulance ID to delete
$ambulance_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Deleting the ambulance from the database
$query = "DELETE FROM ambulances WHERE id = $ambulance_id";
if (mysqli_query($conn, $query)) {
    header('Location: ambulances.php?message=Ambulance deleted successfully');
    exit;
} else {
    header('Location: ambulances.php?error=Error deleting ambulance: ' . mysqli_error($conn));
    exit;
}
?>