<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

// Get form data
$name = $conn->real_escape_string($_POST['name']);
$email = $conn->real_escape_string($_POST['email']);
$role = $conn->real_escape_string($_POST['role']);
$student_id = ($role === 'Student') ? $conn->real_escape_string($_POST['student_id']) : null;

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
    }
}

// Default password
$password = password_hash('123456', PASSWORD_DEFAULT);

// Check if email exists
$emailCheck = $conn->query("SELECT COUNT(*) FROM users WHERE email = '$email'")->fetch_row()[0];
if ($emailCheck > 0) {
    header("Location: manage_users.php?error=Email+already+exists&page={$_POST['page']}&search=".urlencode($_POST['search']));
    exit();
}

// Check if student ID exists (if student)
if ($role === 'Student' && $student_id) {
    $idCheck = $conn->query("SELECT COUNT(*) FROM users WHERE student_id = '$student_id'")->fetch_row()[0];
    if ($idCheck > 0) {
        header("Location: manage_users.php?error=Student+ID+already+exists&page={$_POST['page']}&search=".urlencode($_POST['search']));
        exit();
    }
}

// Insert new user
$query = "INSERT INTO users (name, email, role, status, password, student_id, image) 
          VALUES ('$name', '$email', '$role', 'Active', '$password', " . 
          ($student_id ? "'$student_id'" : "NULL") . ", " . 
          ($image ? "'$image'" : "NULL") . ")";

          if ($conn->query($query)) {
            echo json_encode(['success' => true, 'message' => 'User created successfully']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error: '.$conn->error]);
        }
exit();
?>