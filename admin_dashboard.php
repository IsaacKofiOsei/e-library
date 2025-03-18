<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php'); // Redirect to login page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Library</title>
    <link rel="stylesheet" href="assets/css/admin_dash_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <div class="admin-profile" id="adminProfile">
            <img src="assets/img/admin.png" alt="Admin Image">
            <div class="dropdown" id="dropdown">
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="notifications">
            <i class="fas fa-bell"></i>
            <span class="badge">3</span>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="close-btn" id="closeBtn">×</div>
        <h2>Admin Dashboard</h2>
        <ul>
            <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="manage_books.php"><i class="fas fa-book"></i> Manage Books</a></li>
            <li><a href="manage_students.php"><i class="fas fa-users"></i> Manage Students</a></li>
            <li><a href="manage_loans.php"><i class="fas fa-exchange-alt"></i> Manage Book Loans</a></li>
            <!-- <li><a href="manage_authors.php"><i class="fas fa-pen"></i> Manage Authors</a></li>
            <li><a href="manage_categories.php"><i class="fas fa-tags"></i> Manage Categories</a></li>
            <li><a href="manage_publishers.php"><i class="fas fa-building"></i> Manage Publishers</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li> -->
            <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Hamburger Menu -->
        <div class="hamburger" id="hamburger">
            <i class="fas fa-bars"></i>
        </div>

        <h1>Welcome, <?php echo $_SESSION['user']['name']; ?>!</h1>
        <p>This is the admin dashboard. Use the sidebar to navigate.</p>

        <!-- Dashboard Overview -->
        <div class="overview">
            <h2>Overview</h2>
            <div class="cards">
                <div class="card">
                    <h3>Total Books</h3>
                    <p>500</p>
                </div>
                <div class="card">
                    <h3>Total Students</h3>
                    <p>200</p>
                </div>
                <div class="card">
                    <h3>Overdue Books</h3>
                    <p>15</p>
                </div>
                <div class="card">
                    <h3>Books at Loan</h3>
                    <p>17</p>
                </div>
            </div>
        </div>
    </div>

    <script>

        const sidebar = document.getElementById("sidebar");
        const hamburger = document.getElementById("hamburger");
        const closeBtn = document.getElementById("closeBtn");

        hamburger.addEventListener("click", () => {
            sidebar.classList.add("active");
        });

        closeBtn.addEventListener("click", () => {
            sidebar.classList.remove("active");
        });

        const adminProfile = document.getElementById("adminProfile");
        const dropdown = document.getElementById("dropdown");

        adminProfile.addEventListener("click", () => {
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });
        document.addEventListener("click", (event) => {
            if (!adminProfile.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });
    </script>
</body>
</html>