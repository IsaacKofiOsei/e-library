<?php
session_start();
require __DIR__ . '/config/db.php';

// Authentication check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Student') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user']['id'];

// Mark all notifications as read when page loads
$conn->query("UPDATE notifications SET status = 'Read' WHERE receiver_id = '$user_id' AND status = 'Unread'");

// Get all notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE receiver_id = '$user_id' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/student.css">
    <style>
        .notifications-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        
        .notification-item.unread {
            background-color: #f8f9fa;
        }
        
        .notification-message {
            margin-bottom: 5px;
            color: #333;
        }
        
        .notification-time {
            font-size: 12px;
            color: #6c757d;
        }
        
        .no-notifications {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .clear-notifications {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/student_topbar.php'; ?>

    <main class="student-container">
        <div class="notifications-container">
            <div class="notifications-header">
                <h1><i class="fas fa-bell"></i> My Notifications</h1>
                <a href="student_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if ($notifications->num_rows > 0): ?>
                <?php while ($notification = $notifications->fetch_assoc()): ?>
                    <div class="notification-item">
                        <div class="notification-message">
                            <?= htmlspecialchars($notification['message']) ?>
                        </div>
                        <div class="notification-time">
                            <?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <a href="clear_notifications.php" class="clear-notifications">
                    <i class="fas fa-trash-alt"></i> Clear All Notifications
                </a>
            <?php else: ?>
                <div class="no-notifications">
                    <i class="fas fa-bell-slash fa-3x"></i>
                    <h3>No notifications yet</h3>
                    <p>You'll see notifications here when you have them</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>