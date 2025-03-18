<?php
// config/db.php

$servername = "localhost"; // Change if your DB server is different
$username = "root"; // Your DB username
$password = ""; // Your DB password
$dbname = "elibrary"; // Your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$toastMessage = "";
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}else{
    
try {
   // Create the database if it doesn't exist
if (!$conn->query("CREATE DATABASE IF NOT EXISTS $dbname")) {
    die("Error creating database: " . $conn->error);
}
$conn->select_db($dbname); // Select the database

// Function to execute SQL queries with error handling
function executeQuery($conn, $sql) {
    if (!$conn->query($sql)) {
        die("Error executing query: " . $conn->error);
    }
}

// Create Users table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS users (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for user ID
        name VARCHAR(100) NOT NULL,
        role ENUM('Admin', 'Student') NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        student_id VARCHAR(20) UNIQUE, -- Optional for admin, mandatory for student
        image VARCHAR(255), -- Optional
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create Books table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS books (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for book ID
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image VARCHAR(255),
        number_available INT NOT NULL,
        number_collected INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create Books Loan table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS books_loan (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for loan ID
        student_id CHAR(36) NOT NULL,
        book_id CHAR(36) NOT NULL,
        date_collected DATE NOT NULL,
        date_to_return DATE NOT NULL,
        status ENUM('Returned', 'Still with student', 'Overdue') DEFAULT 'Still with student',
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (book_id) REFERENCES books(id)
    )
");

// Create Authors table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS authors (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for author ID
        name VARCHAR(100) NOT NULL,
        bio TEXT,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create Categories table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS categories (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for category ID
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create Publishers table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS publishers (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for publisher ID
        name VARCHAR(100) NOT NULL,
        address TEXT,
        contact VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Create Reviews table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS reviews (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for review ID
        student_id CHAR(36) NOT NULL,
        book_id CHAR(36) NOT NULL,
        rating TINYINT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (book_id) REFERENCES books(id)
    )
");

// Create Notifications table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS notifications (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for notification ID
        user_id CHAR(36) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('Unread', 'Read') DEFAULT 'Unread',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");

// Create Fines table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS fines (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()), -- UUID for fine ID
        student_id CHAR(36) NOT NULL,
        book_id CHAR(36) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (book_id) REFERENCES books(id)
    )
");

// Insert default admin user
$adminName = "Admin";
$adminRole = "Admin";
$adminEmail = "admin@ebook.com";
$adminPassword = password_hash("admin123", PASSWORD_DEFAULT); // Default password
$adminStudentId = NULL; // Admin doesn't need a student ID
$adminImage = NULL; // Admin image is optional

// Check if the admin user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $adminEmail);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // Admin user does not exist, so insert it
    $stmt = $conn->prepare("
        INSERT INTO users (name, role, email, password, student_id, image)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $adminName, $adminRole, $adminEmail, $adminPassword, $adminStudentId, $adminImage);

    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }
    $toastMessage = "Database Connected successfully";
} else {
    // Admin user already exists
    $toastMessage = "Admin user already exists.";
}


} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
}
// echo "<script src='global/toast/toast.js'></script>";
// echo "<script>showToast('$toastMessage');</script>";
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <style>
        /* Toast Notification Styles */
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
    left: 50%; /* Center horizontally */
    top: 50%; /* Center vertically */
    transform: translate(-50%, -50%); /* Adjust for exact center */
    font-size: 14px;
    opacity: 0; /* Start hidden */
    transition: opacity 0.5s, visibility 0.5s; /* Smooth fade-in and fade-out */
}

#toast.show {
    visibility: visible;
    opacity: 1; /* Fully visible */
}

        @keyframes fadein {
            from {bottom: 0; opacity: 0;}
            to {bottom: 20px; opacity: 1;}
        }

        @keyframes fadeout {
            from {bottom: 20px; opacity: 1;}
            to {bottom: 0; opacity: 0;}
        }
    </style>
</head>
<body>
    <!-- Toast Notification -->
    <div id="toast"></div>

    <script>
        // Function to show toast notification
        function showToast(message, duration = 3000) {
            const toast = document.getElementById("toast");
            toast.textContent = message;
            toast.classList.add("show");

            // Hide the toast after the specified duration
            setTimeout(() => {
                toast.classList.remove("show");
            }, duration);
        }

        // Show toast message
        showToast("$toastMessage");
    </script>
</body>
</html>
HTML;
?>
