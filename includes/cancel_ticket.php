<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if ticket_number is provided
if (!isset($_POST['ticket_number'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ticket number required']);
    exit();
}

$ticket_number = $_POST['ticket_number'];
$user_id = $_SESSION['user_id'];

try {
    // Verify that the ticket belongs to the current user
    $verifyQuery = "SELECT ticket_id FROM queue_tickets 
                    WHERE ticket_number = ? AND customer_id = ? AND status = 'waiting'";
    $stmt = $pdo->prepare($verifyQuery);
    $stmt->execute([$ticket_number, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ticket not found or already processed']);
        exit();
    }
    
    // Update the ticket status to 'cancelled'
    $updateQuery = "UPDATE queue_tickets SET status = 'cancelled', completed_at = NOW() 
                    WHERE ticket_id = ?";
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute([$ticket['ticket_id']]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Ticket cancelled successfully']);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>