<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

require __DIR__ . '/config/db.php';

// Fetch statistics
$totalBooks = $conn->query("SELECT COUNT(*) FROM books")->fetch_row()[0];
$totalStudents = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'Student' AND status = 'Active'")->fetch_row()[0];
$overdueBooks = $conn->query("SELECT COUNT(*) FROM books_loan WHERE status = 'Overdue'")->fetch_row()[0];
$booksOnLoan = $conn->query("SELECT COUNT(*) FROM books_loan WHERE status IN ('Approved', 'Still with student')")->fetch_row()[0];
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
    <?php include 'admin_topbar_sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Hamburger Menu -->
        <div class="hamburger" id="hamburger">
            <i class="fas fa-bars"></i>
        </div>

        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>!</h1>
        <p>This is the admin dashboard. Use the sidebar to navigate.</p>

        <!-- Dashboard Overview -->
        <div class="overview">
            <h2>Overview</h2>
            <div class="cards">
                <div class="card">
                    <h3>Total Books</h3>
                    <p><?php echo $totalBooks; ?></p>
                </div>
                <div class="card">
                    <h3>Total Students</h3>
                    <p><?php echo $totalStudents; ?></p>
                </div>
                <div class="card">
                    <h3>Overdue Books</h3>
                    <p><?php echo $overdueBooks; ?></p>
                </div>
                <div class="card">
                    <h3>Books on Loan</h3>
                    <p><?php echo $booksOnLoan; ?></p>
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