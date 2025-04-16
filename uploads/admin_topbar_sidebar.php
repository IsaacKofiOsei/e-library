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
        <li class="<?= (basename($_SERVER['PHP_SELF']) === 'settings.php') ? 'active' : '' ?>">
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        </li>
        <li>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </li>
    </ul>
</div>

<script>
    // Sidebar toggle functionality
    const sidebar = document.getElementById("sidebar");
    const hamburger = document.getElementById("hamburger");
    const closeBtn = document.getElementById("closeBtn");

    if (hamburger) hamburger.addEventListener("click", () => sidebar.classList.add("active"));
    if (closeBtn) closeBtn.addEventListener("click", () => sidebar.classList.remove("active"));

    // Profile dropdown
    const adminProfile = document.getElementById("adminProfile");
    const dropdown = document.getElementById("dropdown");

    if (adminProfile) {
        adminProfile.addEventListener("click", () => {
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", (event) => {
            if (!adminProfile.contains(event.target)) {
                dropdown.style.display = "none";
            }
        });
    }
</script>