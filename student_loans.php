<?php
session_start();
require 'config/db.php';

// Authentication check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Student') {
    header('Location: index.php');
    exit();
}

$student_id = $_SESSION['user']['id'];

// Handle loan actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // [Previous POST handling code remains the same]
}

// Get all loans for current student
$loans_query = "SELECT bl.*, b.title, b.author, b.cover_image,
                CASE 
                    WHEN bl.status = 'Pending' THEN 'Pending Approval'
                    WHEN bl.status = 'Approved' AND bl.date_collected IS NULL THEN 'Ready for Collection'
                    WHEN bl.status = 'Still with student' AND bl.date_to_return < CURDATE() THEN 'Overdue'
                    WHEN bl.status = 'Still with student' THEN 'On Loan'
                    ELSE bl.status
                END AS display_status
                FROM books_loan bl
                JOIN books b ON bl.book_id = b.id
                WHERE bl.student_id = ?
                ORDER BY 
                    CASE 
                        WHEN bl.status = 'Pending' THEN 1
                        WHEN bl.status = 'Approved' AND bl.date_collected IS NULL THEN 2
                        WHEN bl.status = 'Still with student' THEN 3
                        ELSE 4
                    END,
                    bl.date_to_return ASC";
                
$stmt = $conn->prepare($loans_query);
$stmt->bind_param("s", $student_id);
$stmt->execute();
$loans_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Book Loans</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/student.css">
    <style>
        /* Main Container */
        .student-loans-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* Header */
        .loans-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .loans-header h1 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        
        .back-btn {
            padding: 8px 15px;
            background: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #3a5bbf;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Loans Table */
        .loans-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .loans-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .loans-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        
        .loans-table tr:hover td {
            background-color: #f8f9fa;
        }
        
        /* Book Cover */
        .book-cover-cell {
            width: 60px;
            padding-right: 0 !important;
        }
        
        .book-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Book Info */
        .book-info {
            min-width: 200px;
        }
        
        .book-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 3px;
        }
        
        .book-author {
            font-size: 13px;
            color: #6c757d;
        }
        
        /* Loan Dates */
        .loan-dates {
            min-width: 180px;
        }
        
        .date-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            width: 80px;
        }
        
        .date-value {
            color: #333;
        }
        
        /* Status */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-ready {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-returned {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .return-btn {
            background: #28a745;
            color: white;
        }
        
        .return-btn:hover {
            background: #218838;
        }
        
        .cancel-btn {
            background: #dc3545;
            color: white;
        }
        
        .cancel-btn:hover {
            background: #c82333;
        }
        
        /* No Loans */
        .no-loans {
            text-align: center;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .no-loans i {
            font-size: 50px;
            color: #adb5bd;
            margin-bottom: 15px;
        }
        
        .no-loans h3 {
            color: #495057;
            margin-bottom: 10px;
        }
        
        .no-loans p {
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .browse-btn {
            display: inline-block;
            padding: 8px 20px;
            background: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .browse-btn:hover {
            background: #3a5bbf;
        }
        
        /* Modal styles (same as before) */
        /* ... */
    </style>
</head>
<body>
    <?php include 'includes/student_topbar.php'; ?>

    <main class="student-container">
        <div class="student-loans-container">
            <div class="loans-header">
                <h1><i class="fas fa-book"></i> My Book Loans</h1>
                <a href="student_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if ($loans_result->num_rows > 0): ?>
                <table class="loans-table">
                    <thead>
                        <tr>
                            <th class="book-cover-cell"></th>
                            <th>Book</th>
                            <th>Loan Dates</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($loan = $loans_result->fetch_assoc()): ?>
                            <tr>
                                <td class="book-cover-cell">
                                    <img src="<?php echo htmlspecialchars($loan['cover_image'] ?? 'assets/img/default-book.png'); ?>" 
                                         class="book-cover" 
                                         alt="<?php echo htmlspecialchars($loan['title']); ?>">
                                </td>
                                <td class="book-info">
                                    <div class="book-title"><?php echo htmlspecialchars($loan['title']); ?></div>
                                    <div class="book-author"><?php echo htmlspecialchars($loan['author']); ?></div>
                                </td>
                                <td class="loan-dates">
                                    <?php if ($loan['status'] === 'Pending'): ?>
                                        <div><span class="date-label">Requested:</span> 
                                        <span class="date-value"><?php echo date('M d, Y', strtotime($loan['created_at'])); ?></span></div>
                                    <?php else: ?>
                                        <?php if ($loan['date_collected']): ?>
                                            <div><span class="date-label">Collected:</span> 
                                            <span class="date-value"><?php echo date('M d, Y', strtotime($loan['date_collected'])); ?></span></div>
                                        <?php endif; ?>
                                        <div><span class="date-label">Due:</span> 
                                        <span class="date-value"><?php echo date('M d, Y', strtotime($loan['date_to_return'])); ?></span></div>
                                        <?php if ($loan['date_returned']): ?>
                                            <div><span class="date-label">Returned:</span> 
                                            <span class="date-value"><?php echo date('M d, Y', strtotime($loan['date_returned'])); ?></span></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $loan['display_status'])); ?>">
                                        <?php echo $loan['display_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($loan['status'] === 'Still with student'): ?>
                                        <button class="action-btn return-btn open-return-modal" 
                                                data-loan-id="<?php echo $loan['id']; ?>"
                                                data-book-title="<?php echo htmlspecialchars($loan['title']); ?>">
                                            <i class="fas fa-undo"></i> Return
                                        </button>
                                    <?php elseif ($loan['status'] === 'Pending'): ?>
                                        <button class="action-btn cancel-btn open-cancel-modal" 
                                                data-loan-id="<?php echo $loan['id']; ?>"
                                                data-book-title="<?php echo htmlspecialchars($loan['title']); ?>">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-loans">
                    <i class="fas fa-book-open"></i>
                    <h3>No Active Loans</h3>
                    <p>You don't have any book loans at the moment</p>
                    <a href="student_dashboard.php" class="browse-btn">Browse Books</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- [Modals and JavaScript remain the same as before] -->
    <script>
        // [Previous JavaScript code remains the same]
    </script>
</body>
</html>