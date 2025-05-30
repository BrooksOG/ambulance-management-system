<?php
// logout.php
session_start();

// Unset all of the session variables.
$_SESSION = [];

// If you want to kill the session cookie as well:
if (ini_get("session.use_cookies")) {
    setcookie(
        session_name(), 
        '', 
        time() - 42000, 
        '/', 
        '', 
        isset($_SERVER['HTTPS']), 
        true
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to login page (or home page).
header('Location: login.php');
exit;
