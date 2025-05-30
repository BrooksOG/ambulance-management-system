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

// Fetching the user ID to delete
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Deleting the user from the database
$query = "DELETE FROM users WHERE id = $user_id";
if (mysqli_query($conn, $query)) {
    header('Location: users.php?message=User deleted successfully');
    exit;
} else {
    header('Location: users.php?error=Error deleting user: ' . mysqli_error($conn));
    exit;
}
?>