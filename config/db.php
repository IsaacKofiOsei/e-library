<?php
// config/db.php

$servername = "localhost"; // Change if your DB server is different
$username = "root"; // Your DB username
$password = ""; // Your DB password
$dbname = "elibrary"; // Your DB name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
