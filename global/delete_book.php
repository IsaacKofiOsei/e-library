<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    die('Unauthorized');
}

$bookId = $_GET['id'];
if ($conn->query("DELETE FROM books WHERE id = '$bookId'")) {
    header("Location: manage_books.php?toast=Book deleted successfully");
} else {
    header("Location: manage_books.php?error=Error deleting book");
}
exit();
?>