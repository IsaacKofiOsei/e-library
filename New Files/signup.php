<?php
session_start();
// Handle signup logic here (e.g., save student data to a database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    // Save image to uploads folder
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        // Save student data to database (placeholder logic)
        // Example: INSERT INTO students (name, student_id, email, password, image) VALUES (...)
        $_SESSION['user'] = $email;
        header('Location: dashboard.php'); // Redirect to dashboard
        exit();
    } else {
        $error = "Failed to upload image!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - E-Library</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Student Sign Up</h1>
        <form action="signup.php" method="POST" enctype="multipart/form-data">
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required>
            
            <label for="student_id">Student ID:</label>
            <input type="text" id="student_id" name="student_id" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <label for="image">Student Image:</label>
            <input type="file" id="image" name="image" required>
            
            <button type="submit" name="signup">Sign Up</button>
        </form>
        <a href="index.php">Already have an account? Login</a>
    </div>
</body>
</html>