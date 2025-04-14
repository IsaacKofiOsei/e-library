<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$userId = $_GET['id'] ?? '';
$user = $conn->query("SELECT * FROM users WHERE id = '$userId'")->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($user);
?>