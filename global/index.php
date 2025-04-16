<?php
require 'config/db.php';
session_start();


$error = isset($_GET['error']) ? $_GET['error'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Library Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Welcome to E-Library</h1>
        <form action="login.php" method="POST">
            <h2>Login</h2>
            <?php if (!empty($error)): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit" name="login">Login</button>
            <a href="forgot_password.php">Forgot Password?</a>
        </form>

        <div class="signup-section">
            <h2>Sign Up</h2>
            <p>New student? Register here!</p>
            <a href="signup.php">Sign Up</a>
        </div>
    </div>
</body>
</html>