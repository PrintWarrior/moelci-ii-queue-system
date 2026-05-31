<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: ../index.php");
    exit();
}

// Check if ticket was generated
if (!isset($_SESSION['generated_ticket'])) {
    header("Location: customer_service.php");
    exit();
}

$ticket_number = $_SESSION['generated_ticket'];
$customer_type = $_SESSION['customer_type'];
$service_name = $_SESSION['selected_service_name'];
$priority = $_SESSION['ticket_priority'];

// Calculate estimated wait time
try {
    $waitTimeQuery = "SELECT COUNT(*) as waiting_ahead 
                      FROM queue_tickets 
                      WHERE status = 'waiting' 
                      AND created_at < NOW() 
                      AND service_id = ?";
    
    $stmt = $pdo->prepare($waitTimeQuery);
    $stmt->execute([$_SESSION['selected_service_id']]);
    $waitResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $waiting_ahead = $waitResult['waiting_ahead'] ?? 0;
    $estimated_wait = max(5, $waiting_ahead * 5); // 5 minutes per customer
    
} catch (PDOException $e) {
    $estimated_wait = 10; // Default fallback
}

$timestamp = date('h:i A m/d/Y');

// Clear session variables after display
unset($_SESSION['generated_ticket']);
unset($_SESSION['ticket_priority']);
unset($_SESSION['customer_type']);
unset($_SESSION['selected_service_id']);
unset($_SESSION['selected_service_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOELCI-II Your Queue Ticket</title>
    <style>
        /* Add your ticket display styling here */
        /* Similar to the previous customer_details.php styling */
    </style>
</head>
<body>
    <div class="container">
        <!-- Your ticket display code from customer_details.php -->
        <!-- Display the generated ticket number, service, customer type, etc. -->
    </div>
</body>
</html>