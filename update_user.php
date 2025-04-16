<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = $_POST['id'] ?? '';
$name = $conn->real_escape_string($_POST['name'] ?? '');
$email = $conn->real_escape_string($_POST['email'] ?? '');
$role = $conn->real_escape_string($_POST['role'] ?? '');
$student_id = ($role === 'Student') ? $conn->real_escape_string($_POST['student_id'] ?? '') : null;

// Handle file upload
$image = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/users/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid().'.'.$extension;
    $destination = $uploadDir.$filename;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
        $image = $destination;
        // Delete old image if exists
        $oldImage = $conn->query("SELECT image FROM users WHERE id = '$userId'")->fetch_row()[0];
        if ($oldImage && file_exists($oldImage)) {
            unlink($oldImage);
        }
    }
}

// Check if email exists (excluding current user)
$emailCheck = $conn->query("SELECT COUNT(*) FROM users WHERE email = '$email' AND id != '$userId'")->fetch_row()[0];
if ($emailCheck > 0) {
    echo json_encode(['success' => false, 'error' => 'Email already exists']);
    exit();
}

// Check if student ID exists (if student, excluding current user)
if ($role === 'Student' && $student_id) {
    $idCheck = $conn->query("SELECT COUNT(*) FROM users WHERE student_id = '$student_id' AND id != '$userId'")->fetch_row()[0];
    if ($idCheck > 0) {
        echo json_encode(['success' => false, 'error' => 'Student ID already exists']);
        exit();
    }
}

// Update user
$imageSql = $image ? ", image = '$image'" : "";
$query = "UPDATE users SET 
          name = '$name', 
          email = '$email', 
          role = '$role', 
          student_id = " . ($student_id ? "'$student_id'" : "NULL") . "
          $imageSql
          WHERE id = '$userId'";

if ($conn->query($query)) {
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: '.$conn->error]);
}
?>