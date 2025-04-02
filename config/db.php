<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "elibrary";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to execute queries safely
if (!function_exists('executeQuery')) {
    function executeQuery($conn, $sql)
    {
        if (!$conn->query($sql)) {
            die("Error executing query: " . $conn->error);
        }
        return true;
    }
}

// Create tables if they don't exist
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS users (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
        name VARCHAR(100) NOT NULL,
        role ENUM('Admin', 'Student') NOT NULL,
        status ENUM('Active', 'Blocked') DEFAULT 'Active',
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        student_id VARCHAR(20) UNIQUE,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS books (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        isbn VARCHAR(20) UNIQUE,
        total_copies INT NOT NULL DEFAULT 1,
        available_copies INT NOT NULL DEFAULT 1,
        shelf_location VARCHAR(30),
        publisher VARCHAR(100),
        publish_year YEAR,
        genre VARCHAR(50),
        language VARCHAR(20) DEFAULT 'English',
        cover_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT chk_available CHECK (available_copies <= total_copies),
        CONSTRAINT chk_positive CHECK (available_copies >= 0)
    )
");

executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS books_loan (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
        student_id CHAR(36) NOT NULL,
        book_id CHAR(36) NOT NULL,
        date_collected DATE,
        date_to_return DATE,
        date_returned DATE NULL,
        status ENUM('Pending', 'Approved', 'Returned', 'Still with student', 'Overdue') DEFAULT 'Pending',
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE ON UPDATE CASCADE
    )
");

// I create the Reviews table
executeQuery($conn, "
       CREATE TABLE IF NOT EXISTS reviews (
           id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
           student_id CHAR(36) NOT NULL,
           book_id CHAR(36) NOT NULL,
           rating TINYINT NOT NULL,
           comment TEXT,
           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
           FOREIGN KEY (student_id) REFERENCES users(id),
           FOREIGN KEY (book_id) REFERENCES books(id)
       )
   ");

// I create the Notifications table
executeQuery($conn, "
    CREATE TABLE IF NOT EXISTS notifications (
    id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    sender_id CHAR(36) NOT NULL,
    receiver_id CHAR(36) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Unread', 'Read') DEFAULT 'Unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id)
);
   ");

// Create default admin if not exists
$adminEmail = "admin@elibrary.com";
$result = $conn->query("SELECT id FROM users WHERE email = '$adminEmail'");
if ($result->num_rows === 0) {
    $hashedPassword = password_hash("admin123", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (name, email, password, role) VALUES ('Admin', '$adminEmail', '$hashedPassword', 'Admin')");
}

if (!function_exists('getUnreadNotificationCount')) {
    function getUnreadNotificationCount($conn, $user_id) {
        $result = $conn->query("SELECT COUNT(*) FROM notifications WHERE receiver_id = '$user_id' AND status = 'Unread'");
        return $result ? $result->fetch_row()[0] : 0;
    }
}

if (!function_exists('getLatestNotifications')) {
    function getLatestNotifications($conn, $user_id, $limit = 5) {
        $notifications = $conn->query("SELECT * FROM notifications 
                                      WHERE receiver_id = '$user_id' 
                                      ORDER BY created_at DESC 
                                      LIMIT $limit");
        
        $output = '';
        if ($notifications && $notifications->num_rows > 0) {
            while ($note = $notifications->fetch_assoc()) {
                $time_ago = timeAgo($note['created_at']);
                $is_read = $note['status'] == 'Read' ? '' : 'unread';
                $output .= "<div class='notification-item $is_read' data-id='{$note['id']}'>
                            <p>{$note['message']}</p>
                            <small>$time_ago</small>
                            </div>";
            }
            $output .= "<a href='notifications.php' class='view-all'>View all notifications</a>";
        } else {
            $output = "<p>No notifications</p>";
        }
        return $output;
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = strtotime($datetime);
        $time_difference = time() - $time;

        if ($time_difference < 1) { return 'just now'; }
        $condition = [
            12 * 30 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];

        foreach ($condition as $secs => $str) {
            $d = $time_difference / $secs;
            if ($d >= 1) {
                $t = round($d);
                return $t . ' ' . $str . ($t > 1 ? 's' : '') . ' ago';
            }
        }
    }
}
?>