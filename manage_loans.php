<?php
session_start();
require 'config/db.php';

// Check admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

// Initialize variables
$loans_result = null;
$filter = $_GET['filter'] ?? 'pending';
$error_message = '';
$success_message = '';

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle loan actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loan_id = $_POST['loan_id'] ?? '';
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'approve':
            $conn->begin_transaction();
            try {
                // Get due date from form
                $due_date = $_POST['due_date'] ?? '';
                if (empty($due_date)) {
                    throw new Exception('Due date is required');
                }

                // Validate due date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
                    throw new Exception('Invalid date format. Use YYYY-MM-DD');
                }

                // Get loan details
                $loan_query = "SELECT bl.student_id, bl.book_id, b.title, b.available_copies 
                             FROM books_loan bl
                             JOIN books b ON bl.book_id = b.id
                             WHERE bl.id = ? AND bl.status = 'Pending' FOR UPDATE";
                $stmt = $conn->prepare($loan_query);
                $stmt->bind_param("s", $loan_id);
                $stmt->execute();
                $loan = $stmt->get_result()->fetch_assoc();

                if (!$loan) throw new Exception('Loan not found or already processed');
                if ($loan['available_copies'] <= 0) throw new Exception('No available copies');

                // Update loan status with admin-set due date
                $date_collected = date('Y-m-d');
                $update_loan = $conn->prepare("UPDATE books_loan 
                                             SET status = 'Still with student', 
                                                 date_collected = ?, 
                                                 date_to_return = ?
                                             WHERE id = ?");
                $update_loan->bind_param("sss", $date_collected, $due_date, $loan_id);
                $update_loan->execute();

                // Update book availability
                $update_book = $conn->prepare("UPDATE books 
                                             SET available_copies = available_copies - 1 
                                             WHERE id = ?");
                $update_book->bind_param("s", $loan['book_id']);
                $update_book->execute();

                // Notify student
                // In the 'approve' case:
                $message = "Your loan request for '" . htmlspecialchars($loan['title']) . "' has been approved (Due: " . date('M d, Y', strtotime($due_date)) . ")";
                if (empty($message)) {
                    throw new Exception('Notification message cannot be empty');
                }
                $notify = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $notify->bind_param("sss", $_SESSION['user']['id'], $loan['student_id'], $message);
                if (!$notify->execute()) {
                    throw new Exception('Failed to send notification: ' . $conn->error);
                }

                $conn->commit();
                $success_message = 'Loan approved successfully';
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
            break;

        case 'reject':
            $conn->begin_transaction();
            try {
                // Get loan details
                $loan = $conn->query("SELECT student_id, b.title FROM books_loan bl JOIN books b ON bl.book_id = b.id WHERE bl.id = '$loan_id'")->fetch_assoc();

                // Update loan status
                $stmt = $conn->prepare("UPDATE books_loan SET status = 'Returned' WHERE id = ?");
                $stmt->bind_param("s", $loan_id);
                $stmt->execute();

                // Notify student
                $message = "Your loan request for '" . htmlspecialchars($loan['title']) . "' has been rejected";
                if (empty($message)) {
                    throw new Exception('Notification message cannot be empty');
                }
                $notify = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $notify->bind_param("sss", $_SESSION['user']['id'], $loan['student_id'], $message);
                if (!$notify->execute()) {
                    throw new Exception('Failed to send notification: ' . $conn->error);
                }

                $conn->commit();
                $success_message = 'Loan rejected successfully';
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
            break;

        case 'confirm_return':
            $conn->begin_transaction();
            try {
                // Get loan details
                $loan_query = "SELECT bl.student_id, bl.book_id, b.title 
                             FROM books_loan bl
                             JOIN books b ON bl.book_id = b.id
                             WHERE bl.id = ? AND bl.status = 'Still with student' FOR UPDATE";
                $stmt = $conn->prepare($loan_query);
                $stmt->bind_param("s", $loan_id);
                $stmt->execute();
                $loan = $stmt->get_result()->fetch_assoc();

                if (!$loan) throw new Exception('Loan not found or already returned');

                // Update loan status
                $date_returned = date('Y-m-d');
                $update_loan = $conn->prepare("UPDATE books_loan 
                                             SET status = 'Returned', 
                                                 date_returned = ?
                                             WHERE id = ?");
                $update_loan->bind_param("ss", $date_returned, $loan_id);
                $update_loan->execute();

                // Update book availability
                $update_book = $conn->prepare("UPDATE books 
                                             SET available_copies = available_copies + 1 
                                             WHERE id = ?");
                $update_book->bind_param("s", $loan['book_id']);
                $update_book->execute();

                // Notify student
                $message = "Your return of '" . htmlspecialchars($loan['title']) . "' has been confirmed";
                if (empty($message)) {
                    throw new Exception('Notification message cannot be empty');
                }
                $notify = $conn->prepare("INSERT INTO notifications (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                $notify->bind_param("sss", $_SESSION['user']['id'], $loan['student_id'], $message);
                if (!$notify->execute()) {
                    throw new Exception('Failed to send notification: ' . $conn->error);
                }

                $conn->commit();
                $success_message = 'Book return confirmed';
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = $e->getMessage();
            }
            break;
    }
}

// Build and execute the loans query
try {
    $base_query = "SELECT bl.*, b.title, b.author, u.name as student_name, 
                  u.email as student_email, u.image as student_image, bl.date_collected,
                  CASE 
                      WHEN bl.status = 'Pending' THEN 'Pending Approval'
                      WHEN bl.status = 'Approved' AND bl.date_collected IS NULL THEN 'Ready for Collection'
                      WHEN bl.status = 'Still with student' AND bl.date_to_return < CURDATE() THEN 'Overdue'
                      ELSE bl.status
                  END AS display_status
                  FROM books_loan bl
                  JOIN books b ON bl.book_id = b.id
                  JOIN users u ON bl.student_id = u.id";

    // Add WHERE clause based on filter
    switch ($filter) {
        case 'pending':
            $where = " WHERE bl.status = 'Pending'";
            break;
        case 'active':
            $where = " WHERE bl.status = 'Still with student'";
            break;
        case 'overdue':
            $where = " WHERE bl.status = 'Still with student' AND bl.date_to_return < CURDATE()";
            break;
        case 'approved':
            $where = " WHERE bl.status = 'Approved' AND bl.date_collected IS NULL";
            break;
        case 'all':
        default:
            $where = "";
            break;
    }

    // Add ORDER BY clause
    $order_by = " ORDER BY 
                  CASE 
                      WHEN bl.status = 'Pending' THEN 1
                      WHEN bl.status = 'Approved' AND bl.date_collected IS NULL THEN 2
                      WHEN bl.status = 'Still with student' THEN 3
                      ELSE 4
                  END,
                  bl.date_to_return ASC";

    // Combine the full query
    $full_query = $base_query . $where . $order_by;

    // Execute the query
    $loans_result = $conn->query($full_query);

    if (!$loans_result) {
        throw new Exception("Database error: " . $conn->error);
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Book Loans</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin_dash_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .loan-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #4e73df;
            color: white;
            border-color: #4e73df;
        }

        .loans-table {
            width: 100%;
            border-collapse: collapse;
        }

        .loans-table th,
        .loans-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .loans-table th {
            background: #f8f9fc;
            font-weight: 600;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.pending-approval {
            background: #fff3bf;
            color: #5f3dc4;
        }

        .status-badge.ready-for-collection {
            background: #d3f9d8;
            color: #2b8a3e;
        }

        .status-badge.still-with-student {
            background: #d0ebff;
            color: #1864ab;
        }

        .status-badge.overdue {
            background: #ffd8d8;
            color: #c92a2a;
        }

        .status-badge.returned {
            background: #e6f7ee;
            color: #0ca678;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: all 0.2s;
        }

        .approve-btn {
            background: #2b8a3e;
            color: white;
        }

        .reject-btn {
            background: #c92a2a;
            color: white;
        }

        .confirm-btn {
            background: #1864ab;
            color: white;
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        .student-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .no-loans {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .no-loans i {
            font-size: 50px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            max-width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-modal {
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <?php include 'admin_topbar_sidebar.php'; ?>

    <div class="main-content">
        <div class="container">
            <h1>Manage Book Loans</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="loan-filters">
                <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    Pending Approval
                </a>
                <a href="?filter=approved" class="filter-btn <?php echo $filter === 'approved' ? 'active' : ''; ?>">
                    Ready for Collection
                </a>
                <a href="?filter=active" class="filter-btn <?php echo $filter === 'active' ? 'active' : ''; ?>">
                    Active Loans
                </a>
                <a href="?filter=overdue" class="filter-btn <?php echo $filter === 'overdue' ? 'active' : ''; ?>">
                    Overdue Loans
                </a>
                <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    All Loans
                </a>
            </div>

            <?php if ($loans_result && $loans_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="loans-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Student</th>
                                <th>Request Date</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($loan = $loans_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($loan['title']); ?></strong><br>
                                        <em><?php echo htmlspecialchars($loan['author']); ?></em>
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <img src="<?php echo !empty($loan['student_image']) ? htmlspecialchars($loan['student_image']) : 'assets/img/default-user.png'; ?>"
                                                class="student-avatar"
                                                alt="Student">
                                            <div>
                                                <?php echo htmlspecialchars($loan['student_name']); ?><br>
                                                <small><?php echo htmlspecialchars($loan['student_email']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M d, Y', strtotime($loan['date_collected'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($loan['status'] === 'Still with student' || $loan['status'] === 'Approved'): ?>
                                            <?php echo date('M d, Y', strtotime($loan['date_to_return'])); ?>
                                        <?php elseif ($loan['status'] === 'Returned'): ?>
                                            <em>Completed</em>
                                        <?php else: ?>
                                            <em>Pending</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower(str_replace(' ', '-', $loan['display_status'])); ?>">
                                            <?php echo htmlspecialchars($loan['display_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($loan['status'] === 'Pending'): ?>
                                            <button class="action-btn approve-btn open-approve-modal"
                                                data-loan-id="<?php echo htmlspecialchars($loan['id']); ?>"
                                                data-book-title="<?php echo htmlspecialchars($loan['title']); ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="loan_id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="action-btn reject-btn">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php elseif ($loan['status'] === 'Still with student'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="loan_id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                                                <input type="hidden" name="action" value="confirm_return">
                                                <button type="submit" class="action-btn confirm-btn">
                                                    <i class="fas fa-check-circle"></i> Confirm Return
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-loans">
                    <i class="fas fa-book-open"></i>
                    <h3>No loans found</h3>
                    <p>There are currently no loans matching your selected filter</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Approve Loan Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Approve Loan Request</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form id="approveLoanForm" method="POST">
                <input type="hidden" name="loan_id" id="modalLoanId">
                <input type="hidden" name="action" value="approve">

                <div class="form-group">
                    <label>Book Title</label>
                    <input type="text" id="modalBookTitle" readonly>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date *</label>
                    <input type="date" id="due_date" name="due_date" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="action-btn reject-btn close-modal">Cancel</button>
                    <button type="submit" class="action-btn approve-btn">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date picker
        flatpickr("#due_date", {
            minDate: "today",
            dateFormat: "Y-m-d",
            defaultDate: new Date().fp_incr(14) // Default to 14 days from now
        });

        // Modal handling
        const modal = document.getElementById('approveModal');
        const openModalBtns = document.querySelectorAll('.open-approve-modal');
        const closeModalBtn = document.querySelector('.close-modal');
        const modalLoanId = document.getElementById('modalLoanId');
        const modalBookTitle = document.getElementById('modalBookTitle');

        openModalBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                modalLoanId.value = this.getAttribute('data-loan-id');
                modalBookTitle.value = this.getAttribute('data-book-title');
                modal.style.display = 'block';
            });
        });

        closeModalBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>

</html>