<?php
require_once "db_connect.php";
$res = $conn->query("SELECT id, username, role FROM users");
if ($res) {
    echo "<h2>Users:</h2><ul>";
    while ($u = $res->fetch_assoc()) {
        echo "<li>{$u['id']}: {$u['username']} ({$u['role']})</li>";
    }
    echo "</ul>";
} else {
    echo "Error: " . $conn->error;
}
?>