<?php
session_start();
require 'config/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Student') {
    header('Location: index.php');
    exit();
}

$student_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate name
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        // Update name
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("ss", $name, $student_id);
        if ($stmt->execute()) {
            $_SESSION['user']['name'] = $name;
            $success = 'Name updated successfully';
        } else {
            $error = 'Failed to update name';
        }
        $stmt->close();
    }
    
    // Handle password change if fields are filled
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required to change password';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("s", $student_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("ss", $hashed_password, $student_id);
                if ($stmt->execute()) {
                    $success = 'Password updated successfully';
                } else {
                    $error = 'Failed to update password';
                }
            } else {
                $error = 'Current password is incorrect';
            }
            $stmt->close();
        }
    }
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $upload_dir = 'assets/img/students/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = 'student_' . $student_id . '_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                // Update user image in database
                $stmt = $conn->prepare("UPDATE users SET image = ? WHERE id = ?");
                $stmt->bind_param("ss", $file_path, $student_id);
                if ($stmt->execute()) {
                    $_SESSION['user']['image'] = $file_path;
                    $success = 'Profile image updated successfully';
                } else {
                    $error = 'Failed to update profile image';
                }
                $stmt->close();
            } else {
                $error = 'Failed to upload image';
            }
        } else {
            $error = 'Only JPG, PNG, and GIF images are allowed';
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT name, email, image FROM users WHERE id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/student.css">
</head>
<body>
    <?php include 'includes/student_topbar.php'; ?>

    <main class="student-container">
        <div class="settings-container">
            <h1><i class="fas fa-cog"></i> Settings</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="settings-sections">
                <section class="profile-section">
                    <h2>Profile Information</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($student['email']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Profile Image</label>
                            <div class="image-upload">
                                <img src="<?php echo isset($student['image']) ? htmlspecialchars($student['image']) : 'assets/img/default-user.png'; ?>" alt="Profile Image" id="imagePreview">
                                <input type="file" id="image" name="image" accept="image/*">
                                <label for="image" class="upload-btn">Choose Image</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="save-btn">Save Changes</button>
                    </form>
                </section>
                
                <section class="password-section">
                    <h2>Change Password</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                        
                        <button type="submit" class="save-btn">Change Password</button>
                    </form>
                </section>
            </div>
        </div>
    </main>

    <script src="assets/js/student.js"></script>
    <script>
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>