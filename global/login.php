<?php
session_start(); // I start the session
require 'config/db.php'; // I include the database configuration file

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) { // I check if the request method is POST and the login form is submitted
    $email = $_POST['email']; // I get the email from the POST request
    $password = $_POST['password']; // I get the password from the POST request
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?"); // I prepare the SQL statement to select the user with the given email
    if (!$stmt) { // I check if the statement preparation failed
        die("Error preparing statement: " . $conn->error); // I display an error message and stop the script
    }

    $stmt->bind_param("s", $email); // I bind the email parameter to the SQL statement
    $stmt->execute(); // I execute the SQL statement
    $result = $stmt->get_result(); // I get the result of the executed statement

    if ($result->num_rows === 1) { // I check if exactly one user is found
        $user = $result->fetch_assoc(); // I fetch the user data as an associative array

        if (password_verify($password, $user['password'])) { // I verify the password

            $_SESSION['user'] = [ // I store user information in the session
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ];

            if($user['role'] === 'Admin') { // I check if the user is an admin
                header('Location: admin_dashboard.php'); // I redirect to the admin dashboard
            } else {
                header('Location: student_dashboard.php'); // I redirect to the user dashboard
            }
            exit(); // I stop the script
        } else {
            $error = "Invalid email or password!"; // I set an error message for invalid password
        }
    } else {
        $error = "Invalid email or password!"; // I set an error message for invalid email
    }

    $stmt->close(); // I close the statement
}

if (isset($error)) { // I check if there is an error
    header('Location: index.php?error=' . urlencode($error)); // I redirect to the index page with the error message
    exit(); // I stop the script
}
?>
