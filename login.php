<?php
// login.php - Handle login logic

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate credentials (this is a placeholder, replace with actual validation)
    if ($email === 'test@example.com' && $password === 'password') {
        $_SESSION['user'] = $email;
        header('Location: dashboard.php'); // Redirect to dashboard
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>