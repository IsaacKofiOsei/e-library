
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
    <?php include 'admin_topbar_sidebar.php'; ?>
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