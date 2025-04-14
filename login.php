<?php
session_start();
require 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $selectedRole = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (
            password_verify($password, $user['password']) &&
            $selectedRole === $user['role']
        ) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            if ($user['role'] === 'Admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: student_dashboard.php');
            }
            exit();
        } else {
            $error = "Invalid email, password, or role selection!";
        }
    } else {
        $error = "Invalid email, password, or role selection!";
    }

    $stmt->close();
}

if (isset($error)) {
    header('Location: index.php?error=' . urlencode($error));
    exit();
}
