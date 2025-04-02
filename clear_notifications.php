<?php
session_start();
require __DIR__ . '/config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$conn->query("DELETE FROM notifications WHERE receiver_id = '$user_id'");

$_SESSION['success'] = 'All notifications cleared successfully';
header('Location: notifications.php');
exit();