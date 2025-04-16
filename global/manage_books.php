<?php

session_start();
require 'config/db.php'; // Database connection

// Check admin privileges
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    header('Location: index.php');
    exit();
}

// Handle search
$search = $_GET['search'] ?? '';
$where = '';
if (!empty($search)) {
    $where = "WHERE title LIKE '%$search%' OR author LIKE '%$search%' OR isbn LIKE '%$search%'";
}

// Fetch books
$books = $conn->query("SELECT * FROM books $where ORDER BY title");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Books - E-Library</title>
    <link rel="stylesheet" href="assets/css/admin_dash_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
       /* Toast Notification */
.toast {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    background: #2ecc71;
    color: white;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateX(150%);
    transition: transform 0.3s ease;
}

.toast.show {
    transform: translateX(0);
}

.toast.error {
    background: #e74c3c;
}

.toast i {
    font-size: 18px;
}

.toast-close {
    margin-left: 15px;
    cursor: pointer;
}
        
        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        /* Search Box */
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 250px;
        }
        
        .search-box button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* Add Book Button */
        .btn-add {
            padding: 8px 15px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Books Table */
        .books-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .books-table th, .books-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .books-table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        
        .books-table tr:hover {
            background-color: #f9f9f9;
        }
        
        /* Action Buttons */
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .edit-btn {
            background: #3498db;
            color: white;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
        }
        
        /* Book Cover */
        .book-cover {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 3px;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-btn {
            font-size: 24px;
            cursor: pointer;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .submit-btn {
            padding: 8px 15px;
            background: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .search-box input {
                min-width: auto;
                flex-grow: 1;
            }
            
            .books-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_topbar_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>Manage Books</h1>
                
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search books..." value="<?= htmlspecialchars($search) ?>">
                    <button onclick="searchBooks()"><i class="fas fa-search"></i> Search</button>
                </div>
                
                <button class="btn-add" onclick="showAddForm()">
                    <i class="fas fa-plus"></i> Add New Book
                </button>
            </div>
            
            <table class="books-table">
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Available</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($book = $books->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if (!empty($book['cover_image'])): ?>
                                <img src="<?= $book['cover_image'] ?>" class="book-cover">
                            <?php else: ?>
                                <div class="book-cover" style="background:#eee;display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-book" style="color:#999;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($book['title']) ?></td>
                        <td><?= htmlspecialchars($book['author']) ?></td>
                        <td><?= htmlspecialchars($book['isbn'] ?? 'N/A') ?></td>
                        <td><?= $book['available_copies'] ?></td>
                        <td><?= $book['total_copies'] ?></td>
                        <td>
                            <button class="action-btn edit-btn" onclick="showEditForm(<?= htmlspecialchars(json_encode($book)) ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteBook('<?= $book['id'] ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Book Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Book</h2>
                <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form id="addBookForm" action="save_book.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="title">Title*</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author*</label>
                    <input type="text" id="author" name="author" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="total_copies">Total Copies*</label>
                    <input type="number" id="total_copies" name="total_copies" class="form-control" min="1" value="1" required>
                </div>
                
                <div class="form-group">
                    <label for="available_copies">Available Copies*</label>
                    <input type="number" id="available_copies" name="available_copies" class="form-control" min="0" value="1" required>
                </div>
                
                <div class="form-group">
                    <label for="shelf_location">Shelf Location</label>
                    <input type="text" id="shelf_location" name="shelf_location" class="form-control">
                </div>
                <div class="form-group">
    <label for="publisher">Publisher</label>
    <input type="text" id="publisher" name="publisher" class="form-control">
</div>

<div class="form-group">
    <label for="publish_year">Publish Year</label>
    <input type="number" id="publish_year" name="publish_year" class="form-control" min="1000" max="<?= date('Y') ?>">
</div>

<div class="form-group">
    <label for="genre">Genre</label>
    <select id="genre" name="genre" class="form-control">
        <option value="">Select Genre</option>
        <option value="Fiction">Fiction</option>
        <option value="Non-Fiction">Non-Fiction</option>
        <option value="Science Fiction">Science Fiction</option>
        <option value="Fantasy">Fantasy</option>
        <option value="Mystery">Mystery</option>
        <option value="Thriller">Thriller</option>
        <option value="Romance">Romance</option>
        <option value="Biography">Biography</option>
        <option value="History">History</option>
        <option value="Science">Science</option>
        <option value="Textbook">Textbook</option>
        <option value="Reference">Reference</option>
    </select>
</div>
                
                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <input type="file" id="cover_image" name="cover_image" class="form-control" accept="image/*">
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Save Book
                </button>
            </form>
        </div>
    </div>
    
    <!-- Edit Book Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Book</h2>
                <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form id="editBookForm" action="save_book.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="form-group">
                    <label for="edit_title">Title*</label>
                    <input type="text" id="edit_title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_author">Author*</label>
                    <input type="text" id="edit_author" name="author" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_isbn">ISBN</label>
                    <input type="text" id="edit_isbn" name="isbn" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="edit_total_copies">Total Copies*</label>
                    <input type="number" id="edit_total_copies" name="total_copies" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_available_copies">Available Copies*</label>
                    <input type="number" id="edit_available_copies" name="available_copies" class="form-control" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_shelf_location">Shelf Location</label>
                    <input type="text" id="edit_shelf_location" name="shelf_location" class="form-control">
                </div>
                <div class="form-group">
    <label for="edit_publisher">Publisher</label>
    <input type="text" id="edit_publisher" name="publisher" class="form-control">
</div>

<div class="form-group">
    <label for="edit_publish_year">Publish Year</label>
    <input type="number" id="edit_publish_year" name="publish_year" class="form-control" min="1000" max="<?= date('Y') ?>">
</div>

<div class="form-group">
    <label for="edit_genre">Genre</label>
    <select id="edit_genre" name="genre" class="form-control">
        <option value="">Select Genre</option>
        <option value="Fiction">Fiction</option>
        <option value="Non-Fiction">Non-Fiction</option>
        <option value="Science Fiction">Science Fiction</option>
        <option value="Fantasy">Fantasy</option>
        <option value="Mystery">Mystery</option>
        <option value="Thriller">Thriller</option>
        <option value="Romance">Romance</option>
        <option value="Biography">Biography</option>
        <option value="History">History</option>
        <option value="Science">Science</option>
        <option value="Textbook">Textbook</option>
        <option value="Reference">Reference</option>
    </select>
</div>
                
                <div class="form-group">
                    <label for="edit_cover_image">Cover Image</label>
                    <input type="file" id="edit_cover_image" name="cover_image" class="form-control" accept="image/*">
                    <div id="currentCover" style="margin-top: 5px;"></div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i> Update Book
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Search function
        function searchBooks() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = `manage_books.php?search=${encodeURIComponent(searchTerm)}`;
        }
        
        // Modal functions
        function showAddForm() {
            document.getElementById('addModal').style.display = 'flex';
        }
        
        function showEditForm(book) {
            document.getElementById('edit_id').value = book.id;
            document.getElementById('edit_title').value = book.title;
            document.getElementById('edit_author').value = book.author;
            document.getElementById('edit_isbn').value = book.isbn || '';
            document.getElementById('edit_total_copies').value = book.total_copies;
            document.getElementById('edit_available_copies').value = book.available_copies;
            document.getElementById('edit_shelf_location').value = book.shelf_location || '';
            document.getElementById('edit_publisher').value = book.publisher || '';
document.getElementById('edit_publish_year').value = book.publish_year || '';
document.getElementById('edit_genre').value = book.genre || '';
            
            const currentCover = document.getElementById('currentCover');
            if (book.cover_image) {
                currentCover.innerHTML = `
                    <small>Current: ${book.cover_image.split('/').pop()}</small>
                    <img src="${book.cover_image}" style="max-width:100px; display:block; margin-top:5px;">
                `;
            } else {
                currentCover.innerHTML = '<small>No cover image</small>';
            }
            
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Delete book
        function deleteBook(bookId) {
            if (confirm('Are you sure you want to delete this book?')) {
                window.location.href = `delete_book.php?id=${bookId}`;
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // Allow pressing Enter in search box
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBooks();
            }
        });
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
    </script>


<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toast-message">Operation successful</span>
    <span class="toast-close" onclick="hideToast()">&times;</span>
</div>
</body>
</html>