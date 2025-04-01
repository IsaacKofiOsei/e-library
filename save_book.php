<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    die('Unauthorized');
}

$action = $_POST['action'];

// Handle file upload
$coverImage = '';
if (!empty($_FILES['cover_image']['name'])) {
    $targetDir = "uploads/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $coverImage = $targetDir . basename($_FILES['cover_image']['name']);
    move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverImage);
}

if ($action === 'add') {
    // Add new book
    $stmt = $conn->prepare("INSERT INTO books 
        (title, author, isbn, total_copies, available_copies, shelf_location, cover_image, publisher, publish_year, genre) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiisssis", 
        $_POST['title'],
        $_POST['author'],
        $_POST['isbn'],
        $_POST['total_copies'],
        $_POST['available_copies'],
        $_POST['shelf_location'],
        $coverImage,
        $_POST['publisher'],
        $_POST['publish_year'],
        $_POST['genre']
    );
} else {
    // Update existing book
    $bookId = $_POST['id'];
    $imageUpdate = $coverImage ? ", cover_image = '$coverImage'" : "";
    
    $stmt = $conn->prepare("UPDATE books SET 
        title = ?,
        author = ?,
        isbn = ?,
        total_copies = ?,
        available_copies = ?,
        shelf_location = ?,
        publisher = ?,
        publish_year = ?,
        genre = ?
        $imageUpdate
        WHERE id = ?");
    
    $stmt->bind_param("sssiisssis", 
        $_POST['title'],
        $_POST['author'],
        $_POST['isbn'],
        $_POST['total_copies'],
        $_POST['available_copies'],
        $_POST['shelf_location'],
        $_POST['publisher'],
        $_POST['publish_year'],
        $_POST['genre'],
        $bookId
    );
}

if ($stmt->execute()) {
    header("Location: manage_books.php?toast=Book " . ($action === 'add' ? 'added' : 'updated') . " successfully");
} else {
    header("Location: manage_books.php?error=Error: " . $conn->error);
}
exit();
?>