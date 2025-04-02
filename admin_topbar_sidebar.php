<?php
if (!isset($_SESSION)) session_start();
require __DIR__ . '/config/db.php';

// Get admin data
$admin_id = $_SESSION['user']['id'];
$admin = $conn->query("SELECT name, image FROM users WHERE id = '$admin_id'")->fetch_assoc();

// Get notification count and recent notifications
$notification_count = $conn->query("SELECT COUNT(*) FROM notifications WHERE receiver_id = '$admin_id' AND status = 'Unread'")->fetch_row()[0];
$recent_notifications = $conn->query("SELECT * FROM notifications WHERE receiver_id = '$admin_id' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ========== TOPBAR STYLES ========== */
        .topbar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 20px;
            height: 60px;
            background: #2c3e50;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .topbar-items {
            display: flex;
            align-items: center;
            gap: 25px;
            height: 100%;
        }

        /* Notification Styles */
        .notifications {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
            color: white;
            display: flex;
            align-items: center;
        }

        .notifications .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .notifications-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            width: 350px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 1001;
        }

        .notifications-dropdown.show {
            display: block;
        }

        .notifications-header {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notifications-content {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item {
            padding: 12px 15px;
            border-bottom: 1px solid #f5f5f5;
        }

        /* Profile Styles */
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            height: 100%;
            position: relative;
        }

        .admin-profile img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 200px;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 1001;
        }

        .dropdown a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
        }

        .dropdown a:hover {
            background: #f5f5f5;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 250px;
            height: calc(100vh - 60px);
            background: #34495e;
            color: white;
            z-index: 999;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .sidebar h2 {
            padding: 20px;
            margin: 0;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }

        .sidebar li a:hover {
            background: rgba(0,0,0,0.1);
            padding-left: 25px;
            color: white;
        }

        .sidebar li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .sidebar li.active {
            background: #2c3e50;
        }

        .sidebar li.active a {
            color: white;
            font-weight: 500;
        }

        /* Mobile Styles */
        .hamburger, .close-btn {
            display: none;
            cursor: pointer;
            font-size: 1.5rem;
            position: absolute; 
            left: 20px; 
        }

        @media (max-width: 992px) {
            .hamburger {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .close-btn {
                display: block;
                position: absolute;
                top: 15px;
                right: 15px;
            }
            
            .admin-profile span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <div class="hamburger" id="hamburger">
            <i class="fas fa-bars"></i>
        </div>
        
        <div class="topbar-items">
            <!-- Notifications -->
            <div class="notifications" id="notificationsDropdown">
                <i class="fas fa-bell"></i>
                <?php if ($notification_count > 0): ?>
                    <span class="badge"><?= $notification_count ?></span>
                <?php endif; ?>
                
                <div class="notifications-dropdown" id="notificationsList">
                    <div class="notifications-header">
                        <h4>Notifications</h4>
                        <a href="admin_notifications.php">View All</a>
                    </div>
                    <div class="notifications-content">
                        <?php if ($recent_notifications->num_rows > 0): ?>
                            <?php while ($notification = $recent_notifications->fetch_assoc()): ?>
                                <div class="notification-item">
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
            
            <!-- Admin Profile -->
            <div class="admin-profile" id="adminProfile">
                <img src="<?= htmlspecialchars($admin['image'] ?? 'assets/img/admin.png') ?>" 
                     alt="Admin Image"
                     onerror="this.src='assets/img/admin.png'">
                <span><?= htmlspecialchars($admin['name']) ?></span>
                <div class="dropdown" id="dropdown">
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="close-btn" id="closeBtn">×</div>
        <h2>Admin Dashboard</h2>
        <ul>
            <li class="<?= (basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php') ? 'active' : '' ?>">
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            </li>
            <li class="<?= (basename($_SERVER['PHP_SELF']) === 'manage_books.php') ? 'active' : '' ?>">
                <a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a>
            </li>
            <li class="<?= (basename($_SERVER['PHP_SELF']) === 'manage_students.php') ? 'active' : '' ?>">
                <a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a>
            </li>
            <li class="<?= (basename($_SERVER['PHP_SELF']) === 'manage_loans.php') ? 'active' : '' ?>">
                <a href="manage_loans.php"><i class="fas fa-exchange-alt"></i> Manage Loans</a>
            </li>
            <li class="<?= (basename($_SERVER['PHP_SELF']) === 'admin_notifications.php') ? 'active' : '' ?>">
                <a href="admin_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
            </li>
            <li class="<?= (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'active' : '' ?>">
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            </li>
            <li>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </li>
        </ul>
    </div>

    <script>
        // Toggle sidebar on mobile
        document.getElementById('hamburger').addEventListener('click', function() {
            document.getElementById('sidebar').classList.add('active');
        });
        
        document.getElementById('closeBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.remove('active');
        });

        // Toggle profile dropdown
        const adminProfile = document.getElementById('adminProfile');
        const dropdown = document.getElementById('dropdown');

        if (adminProfile) {
            adminProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            });

            document.addEventListener('click', function() {
                dropdown.style.display = 'none';
            });
        }

        // Toggle notifications dropdown
        document.getElementById('notificationsDropdown').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notificationsList').classList.toggle('show');
            
            // Mark notifications as read when opened
            fetch('mark_notifications_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=<?= $admin_id ?>'
            }).then(() => {
                const badge = document.querySelector('.notifications .badge');
                if (badge) badge.remove();
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('notificationsList').classList.remove('show');
        });
    </script>
</body>
</html>