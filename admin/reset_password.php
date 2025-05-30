<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['user_id'])) {
    $uid = (int) $_POST['user_id'];
    $newRaw  = 'Password123';
    $newHash = md5($newRaw);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $newHash, $uid);
    if ($stmt->execute()) {
        $_SESSION['flash'] = "Password for user #{$uid} reset to <strong>{$newRaw}</strong>.";
    } else {
        $_SESSION['flash'] = "Error: " . $conn->error;
    }
    $stmt->close();
}
header('Location: dashboard.php');
exit;
