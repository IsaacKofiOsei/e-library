<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    die('Unauthorized');
}

$userId = $_GET['user_id'];

// Fetch active loans with book details
$loans = $conn->query("
    SELECT bl.*, b.title, b.author, b.cover_image 
    FROM books_loan bl
    JOIN books b ON bl.book_id = b.id
    WHERE bl.student_id = '$userId' AND bl.status != 'Returned'
    ORDER BY bl.date_to_return
");

if ($loans->num_rows > 0) {
    echo '<table style="width:100%;border-collapse:collapse;">';
    echo '<thead><tr>
            <th style="padding:8px;border-bottom:1px solid #ddd;text-align:left;">Book</th>
            <th style="padding:8px;border-bottom:1px solid #ddd;text-align:left;">Collected</th>
            <th style="padding:8px;border-bottom:1px solid #ddd;text-align:left;">Due</th>
            <th style="padding:8px;border-bottom:1px solid #ddd;text-align:left;">Status</th>
            <th style="padding:8px;border-bottom:1px solid #ddd;text-align:left;">Days Left</th>
          </tr></thead>';
    echo '<tbody>';
    
    while ($loan = $loans->fetch_assoc()) {
        $dueDate = new DateTime($loan['date_to_return']);
        $today = new DateTime();
        $daysLeft = $today->diff($dueDate)->format('%r%a');
        
        // Determine status text and color
        $statusText = $loan['status'];
        $statusColor = '#3498db'; // Default blue
        
        if ($loan['status'] === 'Overdue') {
            $statusColor = '#e74c3c'; // Red
            $daysLeftText = "Overdue by " . abs($daysLeft) . " days";
        } else if ($loan['status'] === 'Still with student') {
            $statusColor = ($daysLeft < 3) ? '#f39c12' : '#2ecc71'; // Orange if due soon, else green
            $daysLeftText = $daysLeft . " days";
        }
        
        echo '<tr>';
        echo '<td style="padding:8px;border-bottom:1px solid #ddd;">
                <div style="display:flex;align-items:center;gap:10px;">
                    ' . ($loan['cover_image'] ? 
                        '<img src="' . $loan['cover_image'] . '" style="width:40px;height:60px;object-fit:cover;">' : 
                        '<div style="width:40px;height:60px;background:#eee;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-book" style="color:#999;"></i>
                        </div>') . '
                    <div>
                        <strong>' . htmlspecialchars($loan['title']) . '</strong><br>
                        <small>' . htmlspecialchars($loan['author']) . '</small>
                    </div>
                </div>
              </td>';
        echo '<td style="padding:8px;border-bottom:1px solid #ddd;">' . date('M j, Y', strtotime($loan['date_collected'])) . '</td>';
        echo '<td style="padding:8px;border-bottom:1px solid #ddd;">' . date('M j, Y', strtotime($loan['date_to_return'])) . '</td>';
        echo '<td style="padding:8px;border-bottom:1px solid #ddd;color:' . $statusColor . '">
                ' . $statusText . '
              </td>';
        echo '<td style="padding:8px;border-bottom:1px solid #ddd;color:' . ($daysLeft < 0 ? '#e74c3c' : ($daysLeft < 3 ? '#f39c12' : '#2ecc71')) . '">
                ' . $daysLeftText . '
              </td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
} else {
    echo '<p>This student has no active loans.</p>';
}
?>