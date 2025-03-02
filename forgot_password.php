<?php
// Handle forgot password logic here (e.g., send reset link to email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset'])) {
    $email = $_POST['email'];
    // Send reset link to email (placeholder logic)
    $message = "A password reset link has been sent to your email.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - E-Library</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Forgot Password</h1>
        <form action="forgot_password.php" method="POST">
            <?php if (isset($message)): ?>
                <p class="success"><?php echo $message; ?></p>
            <?php endif; ?>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <button type="submit" name="reset">Reset Password</button>
        </form>
        <a href="index.php">Back to Login</a>
    </div>
</body>
</html>