<?php
session_start();

// Check if user is logged in as student
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

require 'config/db.php'; // Database connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - E-Library</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Topbar Styles */
        .topbar {
            background: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .topbar-right {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .profile-dropdown {
            position: relative;
            cursor: pointer;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            min-width: 160px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .dropdown-menu a {
            color: #333;
            padding: 10px 15px;
            display: block;
            text-decoration: none;
        }

        .dropdown-menu a:hover {
            background: #f1f1f1;
        }

        /* Main Content */
        .main-content {
            padding: 20px;
            margin-top: 70px; /* Account for topbar */
        }

        /* Responsive */
        @media (max-width: 768px) {
            .topbar {
                flex-direction: column;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Topbar -->
    <div class="topbar">
        <h2>E-Library Student Portal</h2>
        <div class="topbar-right">
            <div class="notifications">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>
            </div>
            <div class="profile-dropdown">
                <img src="<?php echo $_SESSION['user']['image'] ?? 'assets/default-user.png'; ?>" class="profile-img">
                <div class="dropdown-menu">
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>Welcome, <?php echo $_SESSION['user']['name']; ?>!</h1>
        
        <!-- Book Search -->
        <div class="search-box">
            <input type="text" placeholder="Search books...">
            <button><i class="fas fa-search"></i></button>
        </div>

        <!-- Available Books Section -->
        <section class="books-section">
            <h2>Available Books</h2>
            <div class="books-grid">
                <!-- Books will be loaded here via PHP -->
                <?php
                $books = $conn->query("SELECT * FROM books LIMIT 6");
                while($book = $books->fetch_assoc()):
                ?>
                <div class="book-card">
                    <img src="<?php echo $book['image']; ?>" alt="<?php echo $book['title']; ?>">
                    <h3><?php echo $book['title']; ?></h3>
                    <p><?php echo $book['author']; ?></p>
                    <button class="borrow-btn" data-book-id="<?php echo $book['id']; ?>">
                        Borrow Book
                    </button>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
    </div>

    <script>
        // Toggle dropdown menu
        document.querySelector('.profile-dropdown').addEventListener('click', function() {
            this.querySelector('.dropdown-menu').style.display = 
                this.querySelector('.dropdown-menu').style.display === 'block' ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.profile-dropdown')) {
                document.querySelector('.dropdown-menu').style.display = 'none';
            }
        });
    </script>
</body>
</html>