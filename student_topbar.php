<!-- Student Topbar -->
<div class="student-topbar">
    <div class="logo">
        <h2>E-Library</h2>
    </div>
    
    <div class="student-actions">
        <div class="notifications" onclick="showNotifications()">
            <i class="fas fa-bell"></i>
            <?php if ($unreadNotifications > 0): ?>
                <span class="badge"><?= $unreadNotifications ?></span>
            <?php endif; ?>
        </div>
        
        <div class="student-profile" id="studentProfile">
            <img src="<?= !empty($_SESSION['user']['image']) ? $_SESSION['user']['image'] : 'assets/img/student.png' ?>" alt="Student Image">
            <div class="dropdown" id="dropdown">
                <a href="student_settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Profile dropdown
    const studentProfile = document.getElementById("studentProfile");
    const dropdown = document.getElementById("dropdown");

    if (studentProfile) {
        studentProfile.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
        });

        document.addEventListener("click", () => {
            dropdown.style.display = "none";
        });
    }
</script>