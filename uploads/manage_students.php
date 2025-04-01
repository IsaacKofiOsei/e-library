<?php
session_start();
require 'config/db.php';

// Check admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

// Pagination setup
$perPage = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// Handle search
$search = $_GET['search'] ?? '';
$where = "WHERE role = 'Student'";
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR student_id LIKE '%$search%')";
}

// Fetch students with pagination
$totalStudents = $conn->query("SELECT COUNT(*) FROM users $where")->fetch_row()[0];
$totalPages = ceil($totalStudents / $perPage);
$students = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $offset, $perPage");

// Handle student actions
if (isset($_GET['action'])) {
    $studentId = $_GET['id'];
    
    switch ($_GET['action']) {
        case 'block':
            $conn->query("UPDATE users SET status = 'Blocked' WHERE id = '$studentId'");
            header("Location: manage_students.php?toast=Student blocked successfully&page=$page&search=".urlencode($search));
            exit();
            
        case 'unblock':
            $conn->query("UPDATE users SET status = 'Active' WHERE id = '$studentId'");
            header("Location: manage_students.php?toast=Student unblocked successfully&page=$page&search=".urlencode($search));
            exit();
            
        case 'delete':
            $hasLoans = $conn->query("SELECT COUNT(*) FROM books_loan WHERE student_id = '$studentId' AND status != 'Returned'")->fetch_row()[0];
            if ($hasLoans > 0) {
                header("Location: manage_students.php?error=Student has active loans and cannot be deleted&page=$page&search=".urlencode($search));
                exit();
            }
            $conn->query("DELETE FROM users WHERE id = '$studentId'");
            header("Location: manage_students.php?toast=Student deleted successfully&page=$page&search=".urlencode($search));
            exit();
    }
}

// Handle notification sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $userId = $_POST['user_id'];
    $message = $conn->real_escape_string($_POST['message']);
    
    $conn->query("INSERT INTO notifications (user_id, message) VALUES ('$userId', '$message')");
    header("Location: manage_students.php?toast=Notification sent successfully&page=$page&search=".urlencode($search));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - E-Library</title>
    <link rel="stylesheet" href="assets/css/admin_dash_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Table Styles */
        .students-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 20px;
            font-size: 14px;
        }
        
        .students-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .students-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        
        .students-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Student Avatar */
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e0e0e0;
        }
        
        .avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f1f3f5;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #868e96;
        }
        
        /* Status Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-active {
            background: #e6f7ee;
            color: #0ca678;
        }
        .modal {
    display: none; 
    position: fixed;

}
        
        .badge-blocked {
            background: #ffebee;
            color: #f03e3e;
        }
        
        .badge-loan {
            background: #e7f5ff;
            color: #1971c2;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            transform: translateY(-1px);
        }
        
        .btn-notify {
            background: #fff3bf;
            color: #5f3dc4;
        }
        
        .btn-unblock {
            background: #d3f9d8;
            color: #2b8a3e;
        }
        
        .btn-block {
            background: #ffd8d8;
            color: #c92a2a;
        }
        
        .btn-delete {
            background: #f1f3f5;
            color: #495057;
        }
        
        .btn-view {
            background: #d0ebff;
            color: #1864ab;
        }
        
        /* Action Container */
        .action-container {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            color: #495057;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .pagination a:hover, .pagination a.active {
            background: #1971c2;
            color: white;
            border-color: #1971c2;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .students-table {
                display: block;
                overflow-x: auto;
            }
            
            .action-container {
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_topbar_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Manage Students</h1>
                
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search students..." value="<?= htmlspecialchars($search) ?>">
                    <button onclick="searchStudents()"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>
            
            <table class="students-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Student</th>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Loans</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students->fetch_assoc()): 
                        $loanCount = $conn->query("SELECT COUNT(*) FROM books_loan WHERE student_id = '{$student['id']}' AND status != 'Returned'")->fetch_row()[0];
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($student['image'])): ?>
                                <img src="<?= $student['image'] ?>" class="student-avatar" alt="<?= htmlspecialchars($student['name']) ?>">
                            <?php else: ?>
                                <div class="avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 500;"><?= htmlspecialchars($student['name']) ?></div>
                            <div style="font-size: 12px; color: #868e96;"><?= htmlspecialchars($student['email']) ?></div>
                        </td>
                        <td><?= htmlspecialchars($student['student_id'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge <?= ($student['status'] ?? 'Active') === 'Blocked' ? 'badge-blocked' : 'badge-active' ?>">
                                <?= ($student['status'] ?? 'Active') ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($loanCount > 0): ?>
                                <span class="badge badge-loan"><?= $loanCount ?> active</span>
                                <button class="action-btn btn-view" onclick="showLoansModal('<?= $student['id'] ?>', '<?= htmlspecialchars($student['name']) ?>')">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            <?php else: ?>
                                No loans
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-container">
                                <!-- <button class="action-btn btn-notify" onclick="showNotificationModal('<?= $student['id'] ?>', '<?= htmlspecialchars($student['name']) ?>')">
                                    <i class="fas fa-bell"></i> Notify
                                </button> -->
                                
                                <?php if (($student['status'] ?? 'Active') === 'Blocked'): ?>
                                    <button class="action-btn btn-unblock" onclick="unblockStudent('<?= $student['id'] ?>')">
                                        <i class="fas fa-lock-open"></i> Unblock
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn btn-block" onclick="blockStudent('<?= $student['id'] ?>')">
                                        <i class="fas fa-ban"></i> Block
                                    </button>
                                <?php endif; ?>
                                
                                <!-- <button class="action-btn btn-delete" onclick="deleteStudent('<?= $student['id'] ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button> -->
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" <?= $i == $page ? 'class="active"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Notification Modal -->
    <div id="notificationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Send Notification</h2>
                <span class="close-btn" onclick="closeModal('notificationModal')">&times;</span>
            </div>
            <form method="POST" action="manage_students.php">
                <input type="hidden" name="user_id" id="notificationUserId">
                <input type="hidden" name="send_notification" value="1">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                
                <div class="form-group">
                    <label for="notificationRecipient">To:</label>
                    <input type="text" id="notificationRecipient" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label for="message">Message:</label>
                    <textarea id="message" name="message" class="form-control" required style="min-height: 120px;"></textarea>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Send Notification
                </button>
            </form>
        </div>
    </div>
    
    <!-- Loans Modal -->
    <div id="loansModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="loansModalTitle">Student's Active Loans</h2>
                <span class="close-btn" onclick="closeModal('loansModal')">&times;</span>
            </div>
            <div id="loansList">
                <p>Loading loan information...</p>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message">Operation successful</span>
        <span class="toast-close" onclick="hideToast()">&times;</span>
    </div>
    
    <script>
        // Search function
        function searchStudents() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = `manage_students.php?search=${encodeURIComponent(searchTerm)}`;
        }
        
        // Student actions
        function blockStudent(studentId) {
            if (confirm('Block this student? They won\'t be able to request books.')) {
                window.location.href = `manage_students.php?action=block&id=${studentId}&page=<?= $page ?>&search=<?= urlencode($search) ?>`;
            }
        }
        
        function unblockStudent(studentId) {
            if (confirm('Unblock this student?')) {
                window.location.href = `manage_students.php?action=unblock&id=${studentId}&page=<?= $page ?>&search=<?= urlencode($search) ?>`;
            }
        }
        
        function deleteStudent(studentId) {
            if (confirm('Permanently delete this student? This cannot be undone.')) {
                window.location.href = `manage_students.php?action=delete&id=${studentId}&page=<?= $page ?>&search=<?= urlencode($search) ?>`;
            }
        }
        
        // Modal functions
        function showNotificationModal(userId, userName) {
            document.getElementById('notificationUserId').value = userId;
            document.getElementById('notificationRecipient').value = userName;
            document.getElementById('notificationModal').style.display = 'flex';
        }
        
        function showLoansModal(userId, userName) {
            document.getElementById('loansModalTitle').textContent = `${userName}'s Active Loans`;
            document.getElementById('loansModal').style.display = 'flex';
            
            // Load loans via AJAX
            fetch(`get_student_loans.php?user_id=${userId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('loansList').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('loansList').innerHTML = `<p class="text-danger">Error loading loans: ${error}</p>`;
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Toast notification
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = toast.querySelector('i');
            
            toastMessage.textContent = message;
            toast.className = isError ? 'toast error' : 'toast';
            toastIcon.className = isError ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            
            toast.classList.add('show');
            
            setTimeout(() => {
                hideToast();
            }, 5000);
        }
        
        function hideToast() {
            document.getElementById('toast').classList.remove('show');
        }
        
        // Check for toast message in URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const toastMessage = urlParams.get('toast');
            const errorMessage = urlParams.get('error');
            
            if (toastMessage) {
                showToast(toastMessage);
            }
            
            if (errorMessage) {
                showToast(errorMessage, true);
            }
        };
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // Allow pressing Enter in search box
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudents();
            }
        });
    </script>
</body>
</html>