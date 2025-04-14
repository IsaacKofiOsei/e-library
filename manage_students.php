<?php
session_start();
require 'config/db.php';

// Check admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

// Handle toast messages from redirects
$toast = $_GET['toast'] ?? null;
$error = $_GET['error'] ?? null;

// Pagination setup
$perPage = 10;
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * $perPage;

// Search functionality
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
if (!empty($search)) {
    $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR student_id LIKE '%$search%')";
}

// Get total users count
$totalUsers = $conn->query("SELECT COUNT(*) FROM users $where")->fetch_row()[0];
$totalPages = ceil($totalUsers / $perPage);

// Get users for current page
$users = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC LIMIT $offset, $perPage");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - E-Library</title>
    <link rel="stylesheet" href="assets/css/admin_dash_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Toast Notification */
        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0 20px;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        form label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }

        form button,
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        form button:hover,
        .btn:hover {
            background-color: #0056b3;
        }

        .form-container {
            max-width: 500px;
            background: #fff;
            padding: 25px 30px;
            /* ✅ Added side padding */
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
            margin: auto;
            box-sizing: border-box;
        }

        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 4px;
            color: white;
            display: flex;
            align-items: center;
            z-index: 1001;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(150%);
            transition: transform 0.3s ease;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            background: #28a745;
        }

        .toast.error {
            background: #dc3545;
        }

        .toast i {
            margin-right: 10px;
        }

        .toast-close {
            margin-left: 15px;
            cursor: pointer;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            padding: 20px;

            /* left: ; */
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            width: 100%;
            margin: auto;
            padding: 20px;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Table Styles */
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th,
        .users-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .users-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-edit {
            background-color: #17a2b8;
            color: white;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include 'admin_topbar_sidebar.php'; ?>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message"></span>
        <span class="toast-close" onclick="hideToast()">&times;</span>
    </div>

    <div class="main-content">
        <div class="container">
            <h1>Manage Users</h1>

            <div style="margin: 20px 0; display: flex; justify-content: space-between;">
                <button class="btn-add" onclick="showAddModal()">
                    <i class="fas fa-user-plus"></i> Add User
                </button>

                <div style="display: flex;">
                    <input type="text" id="searchInput" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                    <button onclick="searchUsers()" style="margin-left: 10px;">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>

            <table class="users-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student ID</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if (!empty($user['image'])): ?>
                                    <img src="<?= htmlspecialchars($user['image']) ?>" alt="User Image" width="40" height="40" style="border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <span style="color: #888;">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['student_id'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td>
                                <button class="action-btn btn-edit" onclick="showEditModal('<?= $user['id'] ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="action-btn btn-delete" onclick="deleteUser('<?= $user['id'] ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 20px;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                            style="padding: 8px 12px; margin: 0 5px; border: 1px solid #ddd; border-radius: 4px;
                              <?= $i == $page ? 'background: #007bff; color: white;' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
                <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form action="add_user.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required class="form-control">
                            <option value="Student">Student</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group" id="studentIdGroup">
                        <label>Student ID *</label>
                        <input type="text" name="student_id" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Profile Image</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <p style="margin: 0 0 5px 0;"><strong>Default Password:</strong> 123456</p>
                        <small>User will be prompted to change password on first login</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form id="editForm" method="POST" action="update_user.php" enctype="multipart/form-data">
                <input type="hidden" name="id" id="editUserId">
                <input type="hidden" name="page" value="<?= $page ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" id="editName" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="editEmail" required class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" id="editRole" required class="form-control">
                            <option value="Student">Student</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div class="form-group" id="editStudentIdGroup">
                        <label>Student ID *</label>
                        <input type="text" name="student_id" id="editStudentId" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Profile Image</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                        <div id="currentImage" style="margin-top: 10px;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toast Notification Functions
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = toast.querySelector('i');

            toastMessage.textContent = message;
            toast.className = isError ? 'toast error show' : 'toast success show';
            toastIcon.className = isError ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';

            setTimeout(() => {
                hideToast();
            }, 5000);
        }

        function hideToast() {
            document.getElementById('toast').classList.remove('show');
        }

        // Show any existing toast on page load
        window.onload = function() {
            <?php if ($toast): ?>
                showToast("<?= addslashes($toast) ?>");
            <?php endif; ?>

            <?php if ($error): ?>
                showToast("<?= addslashes($error) ?>", true);
            <?php endif; ?>
        };

        // Modal Functions
        function showAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        async function showEditModal(userId) {
            try {
                const response = await fetch(`get_user.php?id=${userId}`);
                const user = await response.json();

                document.getElementById('editUserId').value = user.id;
                document.getElementById('editName').value = user.name;
                document.getElementById('editEmail').value = user.email;
                document.getElementById('editRole').value = user.role;
                document.getElementById('editStudentId').value = user.student_id || '';

                const studentIdGroup = document.getElementById('editStudentIdGroup');
                if (user.role === 'Student') {
                    studentIdGroup.style.display = 'block';
                    document.getElementById('editStudentId').required = true;
                } else {
                    studentIdGroup.style.display = 'none';
                    document.getElementById('editStudentId').required = false;
                }

                const currentImage = document.getElementById('currentImage');
                if (user.image) {
                    currentImage.innerHTML = `
                        <p>Current Image:</p>
                        <img src="${user.image}" style="max-width: 100px; max-height: 100px;">
                    `;
                } else {
                    currentImage.innerHTML = '<p>No image uploaded</p>';
                }

                document.getElementById('editModal').style.display = 'flex';
            } catch (error) {
                showToast("Failed to load user data", true);
                console.error(error);
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Delete User Function
        async function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user?')) return;

            try {
                const response = await fetch(`delete_user.php?id=${userId}&page=<?= $page ?>&search=<?= urlencode($search) ?>`);
                const result = await response.json();

                if (result.success) {
                    showToast(result.message);
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(result.error, true);
                }
            } catch (error) {
                showToast("Failed to delete user", true);
                console.error(error);
            }
        }

        // Search Function
        function searchUsers() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = `manage_users.php?search=${encodeURIComponent(searchTerm)}`;
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        });

        // Allow pressing Enter in search box
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchUsers();
            }
        });

        // Handle edit form submission with AJAX
        document.getElementById('editForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast(result.message);
                    closeModal('editModal');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(result.error, true);
                }
            } catch (error) {
                showToast("Failed to update user", true);
                console.error(error);
            }
        });
    </script>
</body>

</html>