<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

$userId = $_SESSION['user']['id'];

// Get notifications
$notifications = $conn->query("
    SELECT * FROM notifications 
    WHERE user_id = '$userId' 
    ORDER BY created_at DESC
    LIMIT 10
");

// Mark as read
$conn->query("UPDATE notifications SET status = 'Read' WHERE user_id = '$userId' AND status = 'Unread'");

if ($notifications->num_rows > 0) {
    while ($notification = $notifications->fetch_assoc()) {
        $timeAgo = time_elapsed_string($notification['created_at']);
        echo "<div class='notification-item'>";
        echo "<p>{$notification['message']}</p>";
        echo "<small>{$timeAgo}</small>";
        echo "</div>";
    }
} else {
    echo "<p>No notifications found.</p>";
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Remove weeks calculation as $w is not a predefined property

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>