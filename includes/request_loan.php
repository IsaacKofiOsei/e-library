<?php
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Student') {
        throw new Exception('Unauthorized access');
    }

    $book_id = $conn->real_escape_string($_POST['book_id'] ?? '');
    $student_id = $_SESSION['user']['id'];

    $conn->begin_transaction();

    // Check book availability
    $check_query = "SELECT 
        total_copies,
        (total_copies - COALESCE(
            (SELECT COUNT(*) 
             FROM books_loan 
             WHERE book_id = books.id AND status IN ('Approved', 'Still with student'))
        , 0)) AS available_copies
        FROM books
        WHERE id = ? FOR UPDATE";
    
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();

    if (!$book) throw new Exception('Book not found');
    if ($book['available_copies'] <= 0) throw new Exception('No available copies');

    // Check existing loans
    $loan_check = $conn->prepare("SELECT id FROM books_loan 
                                 WHERE student_id = ? AND book_id = ? 
                                 AND status IN ('Pending', 'Approved', 'Still with student')");
    $loan_check->bind_param("ss", $student_id, $book_id);
    $loan_check->execute();
    if ($loan_check->get_result()->num_rows > 0) {
        throw new Exception('You already have a pending or active loan for this book');
    }

    // Create loan record
    $date_to_return = date('Y-m-d', strtotime('+14 days'));
    $insert_loan = $conn->prepare("INSERT INTO books_loan 
                                  (student_id, book_id, date_to_return, status)
                                  VALUES (?, ?, ?, 'Pending')");
    $insert_loan->bind_param("sss", $student_id, $book_id, $date_to_return);
    if (!$insert_loan->execute()) {
        throw new Exception('Failed to create loan record');
    }

    // Get book title safely
    $title_stmt = $conn->prepare("SELECT title FROM books WHERE id = ?");
    $title_stmt->bind_param("s", $book_id);
    $title_stmt->execute();
    $title_result = $title_stmt->get_result();
    $book_title = $title_result->fetch_assoc()['title'];

    // Notify admin using prepared statement
    $book_title = $conn->query("SELECT title FROM books WHERE id = '$book_id'")->fetch_assoc()['title'];
    $message = "New loan request from {$_SESSION['user']['name']} for '$book_title'";
    
    // Find admin users to notify
    $admin_query = "SELECT id FROM users WHERE role = 'Admin' LIMIT 1"; // Notify first admin found
    $admin_id = $conn->query($admin_query)->fetch_assoc()['id'];
    
    $notify_stmt = $conn->prepare("INSERT INTO notifications 
                                 (sender_id, receiver_id, message) 
                                 VALUES (?, ?, ?)");
    $notify_stmt->bind_param("sss", $student_id, $admin_id, $message);
    if (!$notify_stmt->execute()) {
        throw new Exception('Failed to create notification');
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Book loan requested. Waiting for admin approval.']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}