<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = $_GET['id'] ?? '';

// Check for active loans
$hasLoans = $conn->query("SELECT COUNT(*) FROM books_loan WHERE student_id = '$userId' AND status != 'Returned'")->fetch_row()[0];
if ($hasLoans > 0) {
    echo json_encode(['success' => false, 'error' => 'User has active loans and cannot be deleted']);
    exit();
}

// Delete user
if ($conn->query("DELETE FROM users WHERE id = '$userId'")) {
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: '.$conn->error]);
}
?>