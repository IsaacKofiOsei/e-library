<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/config/db.php';
session_start();

// Authentication check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Student') {
    header('Location: index.php');
    exit();
}

// Get available books with accurate availability count
$books_query = "SELECT b.*, 
    (b.total_copies - COALESCE(
        (SELECT COUNT(*) 
         FROM books_loan 
         WHERE book_id = b.id AND status IN ('Approved', 'Still with student')), 
    0)) AS available_copies
    FROM books b
    WHERE b.total_copies > 0
    ORDER BY b.title";

$books_result = $conn->query($books_query);
if (!$books_result) {
    die("Error fetching books: " . $conn->error);
}

// Get student's active loans
$student_id = $_SESSION['user']['id'];
$loans_query = "SELECT bl.*, b.title, b.author 
               FROM books_loan bl
               JOIN books b ON bl.book_id = b.id
               WHERE bl.student_id = ? 
               AND bl.status IN ('Approved', 'Still with student')
               ORDER BY bl.date_to_return ASC";
$loans_stmt = $conn->prepare($loans_query);
$loans_stmt->bind_param("s", $student_id);
$loans_stmt->execute();
$loans_result = $loans_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/student.css">
</head>
<body>
    <?php include 'includes/student_topbar.php'; ?>
    <main class="student-container">
        <div class="welcome-section">
            <h1>Welcome, <?= htmlspecialchars($_SESSION['user']['name']) ?></h1>
        </div>

        <div class="dashboard-sections">
            <!-- Available Books Section -->
            <section class="available-books">
                <h2><i class="fas fa-book"></i> Available Books</h2>
                <div class="books-grid">
                    <?php while ($book = $books_result->fetch_assoc()): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <img src="<?= htmlspecialchars($book['cover_image'] ?? 'assets/img/default-book.png') ?>" 
                                     alt="Book Cover"
                                     onerror="this.src='assets/img/default-book.png'">
                            </div>
                            <div class="book-info">
                                <h3><?= htmlspecialchars($book['title']) ?></h3>
                                <p>By <?= htmlspecialchars($book['author']) ?></p>
                                <p>Available: <?= $book['available_copies'] ?>/<?= $book['total_copies'] ?></p>
                                <?php if ($book['available_copies'] > 0): ?>
                                    <button class="loan-btn" data-book-id="<?= $book['id'] ?>">
                                        Request Loan
                                    </button>
                                <?php else: ?>
                                    <button class="loan-btn disabled" disabled>Not Available</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- My Loans Section
            <section class="my-loans">
                <h2><i class="fas fa-exchange-alt"></i> My Book Loans</h2>
                <?php if ($loans_result->num_rows > 0): ?>
                    <div class="loans-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($loan = $loans_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($loan['title']) ?></td>
                                        <td><?= date('M d, Y', strtotime($loan['date_to_return'])) ?></td>
                                        <td>
                                            <span class="status-badge <?= strtolower(str_replace(' ', '-', $loan['status'])) ?>">
                                                <?= $loan['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($loan['status'] === 'Still with student'): ?>
                                                <button class="return-btn" data-loan-id="<?= $loan['id'] ?>">
                                                    Return Book
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>You don't have any active book loans.</p>
                <?php endif; ?>
            </section> -->
        </div>
    </main>

    <script src="assets/js/student.js"></script>
</body>
</html>