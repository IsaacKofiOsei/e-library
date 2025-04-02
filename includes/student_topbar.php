<?php
if (!isset($_SESSION)) session_start();
require __DIR__ . '/../config/db.php';

// Get fresh user data
$user_id = $_SESSION['user']['id'];
$user = $conn->query("SELECT name, image FROM users WHERE id = '$user_id'")->fetch_assoc();

// Get notification count and recent notifications
$notification_count = $conn->query("SELECT COUNT(*) FROM notifications WHERE receiver_id = '$user_id' AND status = 'Unread'")->fetch_row()[0];
$recent_notifications = $conn->query("SELECT * FROM notifications WHERE receiver_id = '$user_id' ORDER BY created_at DESC LIMIT 5");
?>
<!-- Topbar -->
<div class="topbar">
    <!-- Hamburger menu (visible only on mobile) -->
    <div class="hamburger" id="hamburger">
        <i class="fas fa-bars"></i>
    </div>
    
    <div class="topbar-right">
        <div class="notifications" id="notificationsDropdown">
            <i class="fas fa-bell"></i>
            <?php if ($notification_count > 0): ?>
                <span class="badge"><?= $notification_count ?></span>
            <?php endif; ?>
            
            <div class="notifications-dropdown" id="notificationsList">
                <div class="notifications-header">
                    <h4>Notifications</h4>
                    <a href="notifications.php">View All</a>
                </div>
                <div class="notifications-content">
                    <?php if ($recent_notifications->num_rows > 0): ?>
                        <?php while ($notification = $recent_notifications->fetch_assoc()): ?>
                            <div class="notification-item <?= $notification['status'] === 'Unread' ? 'unread' : '' ?>">
                                <p><?= htmlspecialchars($notification['message']) ?></p>
                                <small><?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?></small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="notification-empty">
                            <p>No notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="student-profile" id="studentProfile">
            <img src="<?= htmlspecialchars($user['image'] ?? 'assets/img/default-user.png') ?>" 
                 alt="Student Image"
                 onerror="this.src='assets/img/default-user.png'">
            <span><?= htmlspecialchars($user['name']) ?></span>
            <div class="dropdown" id="dropdown">
                <a href="student_loans.php"><i class="fas fa-book"></i> My Loans</a>
                <a href="student_settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Menu (hidden by default) -->
<div class="mobile-menu" id="mobileMenu">
    <div class="close-btn" id="closeBtn">×</div>
    <ul>
        <li><a href="student_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="student_loans.php"><i class="fas fa-book"></i> My Loans</a></li>
        <li><a href="student_settings.php"><i class="fas fa-cog"></i> Settings</a></li>
        <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<script>
// Notification dropdown toggle
document.getElementById('notificationsDropdown').addEventListener('click', function(e) {
    e.stopPropagation();
    document.getElementById('notificationsList').classList.toggle('show');
    
    // Mark notifications as read when dropdown is opened
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'user_id=<?= $user_id ?>'
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function() {
    document.getElementById('notificationsList').classList.remove('show');
});
</script>