<?php
session_start();

// I include the database connection file
require 'config/db.php';

// I handle signup logic here (e.g., save student data to a database)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // I hash the password
    $image = $_FILES['image']['name'];
    $target = "uploads/" . basename($image);

    // I validate inputs (you can add more validation as needed)
    if (empty($name) || empty($student_id) || empty($email) || empty($password) || empty($image)) {
        $error = "All fields are required!";
    } else {
        // I check if the email or student ID already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR student_id = ?");
        $stmt->bind_param("ss", $email, $student_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email or Student ID already exists!";
        } else {
            // I save the image to the uploads folder
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                // I save student data to the database
                $stmt = $conn->prepare("
                    INSERT INTO users (name, student_id, email, password, image, role)
                    VALUES (?, ?, ?, ?, ?, 'Student')
                ");
                $stmt->bind_param("sssss", $name, $student_id, $email, $password, $target);

                if ($stmt->execute()) {
                    // Signup is successful
                    $_SESSION['user'] = [
                        'id' => $stmt->insert_id,
                        'name' => $name,
                        'email' => $email,
                        'role' => 'Student'
                    ];

                    // I redirect to the dashboard with a success message
                    header('Location: index.php?toast=Signup successful!');
                    exit();
                } else {
                    $error = "Error saving student data: " . $stmt->error;
                }
            } else {
                $error = "Failed to upload image!";
            }
        }

        $stmt->close();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* I style the toast notification */
        #toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.5s, visibility 0.5s;
        }

        #toast.show {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- I create the toast notification -->
    <div id="toast"></div>

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

    <script>
        // I create a function to show the toast notification
        function showToast(message, duration = 3000) {
            const toast = document.getElementById("toast");
            toast.textContent = message;
            toast.classList.add("show");

            // I hide the toast after the specified duration
            setTimeout(() => {
                toast.classList.remove("show");
            }, duration);
        }

        // I show the toast message if PHP sets one
        <?php if (isset($_GET['toast'])): ?>
            showToast("<?php echo $_GET['toast']; ?>");
        <?php endif; ?>
    </script>
</body>
</html>