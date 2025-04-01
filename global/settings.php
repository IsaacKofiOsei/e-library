<?php
session_start();
require 'config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user']['id'];
$error = '';
$success = '';

// Fetch current user data
$user = $conn->query("SELECT * FROM users WHERE id = '$userId'")->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Password change
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        if (password_verify($_POST['current_password'], $user['password'])) {
            $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$newPassword' WHERE id = '$userId'");
            $success = 'Password updated successfully';
        } else {
            $error = 'Current password is incorrect';
        }
    }

    // Profile image upload
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/profiles/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = basename($_FILES['profile_image']['name']);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;
        
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            // Delete old image if it exists
            if (!empty($user['image']) && file_exists($user['image'])) {
                unlink($user['image']);
            }
            
            $conn->query("UPDATE users SET image = '$targetFile' WHERE id = '$userId'");
            $_SESSION['user']['image'] = $targetFile;
            $user['image'] = $targetFile;
            $success = $success ? $success . ' and profile image updated' : 'Profile image updated';
        } else {
            $error = $error ? $error . ' / Failed to upload image' : 'Failed to upload image';
        }
    }

    // Update other information
    $name = $conn->real_escape_string($_POST['name']);
    $conn->query("UPDATE users SET name = '$name' WHERE id = '$userId'");
    $_SESSION['user']['name'] = $name;
    
    if (!$error) {
        $success = $success ?: 'Profile updated successfully';
        // Refresh user data
        $user = $conn->query("SELECT * FROM users WHERE id = '$userId'")->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin_dash_styles.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .avatar-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e0e0e0;
            display: <?= !empty($user['image']) ? 'block' : 'none' ?>;
            margin: 0 auto 10px;
        }
        
        .avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f8f9fa;
            display: <?= empty($user['image']) ? 'flex' : 'none' ?>;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            color: #6c757d;
            font-size: 40px;
        }
        
        .btn-submit {
            background: #4e73df;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-upload {
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .readonly-field {
            background: #f8f9fa;
            cursor: not-allowed;
        }
        
        #profileImage {
            display: none;
        }
    </style>
</head>
<body>
    <?php include 'admin_topbar_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="settings-container">
            <h1>Account Settings</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="avatar-container">
                    <?php if (!empty($user['image'])): ?>
                        <img src="<?= $user['image'] ?>" class="avatar-preview" id="avatarPreview">
                    <?php endif; ?>
                    <div class="avatar-placeholder" id="avatarPlaceholder">
                        <i class="fas fa-user"></i>
                    </div>
                    <input type="file" name="profile_image" id="profileImage" accept="image/*">
                    <button type="button" onclick="document.getElementById('profileImage').click()" class="btn-upload">
                        <i class="fas fa-camera"></i> <?= empty($user['image']) ? 'Upload Photo' : 'Change Photo' ?>
                    </button>
                    <?php if (!empty($user['image'])): ?>
                        <button type="button" onclick="removeProfileImage()" class="btn-upload" style="margin-left: 10px; background: #dc3545;">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" class="form-control readonly-field" value="<?= htmlspecialchars($user['student_id'] ?? 'N/A') ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control readonly-field" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                
                <h3>Change Password</h3>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                    <small style="color: #6c757d;">Leave blank to keep current password</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('profileImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('avatarPreview');
                    const placeholder = document.getElementById('avatarPlaceholder');
                    
                    if (!preview) {
                        const img = document.createElement('img');
                        img.id = 'avatarPreview';
                        img.className = 'avatar-preview';
                        document.querySelector('.avatar-container').prepend(img);
                        preview = img;
                    }
                    
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        function removeProfileImage() {
            if (confirm('Are you sure you want to remove your profile image?')) {
                fetch('remove_profile_image.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'user_id=<?= $userId ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const preview = document.getElementById('avatarPreview');
                        const placeholder = document.getElementById('avatarPlaceholder');
                        
                        if (preview) {
                            preview.style.display = 'none';
                        }
                        placeholder.style.display = 'flex';
                        location.reload();
                    }
                });
            }
        }

        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword && newPassword !== confirmPassword) {
                alert('New password and confirmation do not match');
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 