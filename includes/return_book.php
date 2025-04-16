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

    $loan_id = $conn->real_escape_string($_POST['loan_id'] ?? '');
    $student_id = $_SESSION['user']['id'];

    $conn->begin_transaction();

    // Verify loan
    $loan_query = "SELECT bl.id, b.title, b.id as book_id 
                  FROM books_loan bl
                  JOIN books b ON bl.book_id = b.id
                  WHERE bl.id = ? AND bl.student_id = ? 
                  AND bl.status = 'Still with student' FOR UPDATE";
    
    $stmt = $conn->prepare($loan_query);
    $stmt->bind_param("ss", $loan_id, $student_id);
    $stmt->execute();
    $loan = $stmt->get_result()->fetch_assoc();

    if (!$loan) throw new Exception('Loan not found or already returned');

    // Update loan status
    $update_loan = $conn->prepare("UPDATE books_loan 
                                 SET status = 'Returned', 
                                 date_returned = CURDATE()
                                 WHERE id = ?");
    $update_loan->bind_param("s", $loan_id);
    if (!$update_loan->execute()) {
        throw new Exception('Failed to update loan status');
    }

    // Update book availability
    $update_book = $conn->prepare("UPDATE books 
                                 SET available_copies = available_copies + 1 
                                 WHERE id = ?");
    $update_book->bind_param("s", $loan['book_id']);
    if (!$update_book->execute()) {
        throw new Exception('Failed to update book availability');
    }

    // Notify admin
    $message = "Book '{$loan['title']}' returned by {$_SESSION['user']['name']}. Please confirm.";

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

    echo json_encode(['success' => true, 'message' => 'Book return requested. Waiting for admin confirmation.']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>