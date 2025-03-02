<?php
session_start();
// Include the login logic file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    require 'login.php'; // Include the login logic
}
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
        <form action="index.php" method="POST">
            <h2>Login</h2>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
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