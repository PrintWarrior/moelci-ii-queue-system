<?php
session_start();
require_once 'db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$response = ['success' => false, 'error' => ''];

header('Content-Type: application/json');

try {
    $pdo->beginTransaction();

    $adminId = $_SESSION['user_id']; // logged-in admin

    // 1️⃣ Copy active tickets to history
    $insertHistory = "
        INSERT INTO queue_ticket_history (
            ticket_id,
            ticket_number,
            service_id,
            customer_id,
            customer_name,
            status,
            priority,
            created_at,
            called_at,
            completed_at,
            assigned_user_id,
            archived_by
        )
        SELECT
            ticket_id,
            ticket_number,
            service_id,
            customer_id,
            customer_name,
            status,
            priority,
            created_at,
            called_at,
            completed_at,
            assigned_user_id,
            :archived_by
        FROM queue_tickets
    ";

    $stmt = $pdo->prepare($insertHistory);
    $stmt->execute(['archived_by' => $adminId]);

    // 2️⃣ Delete active tickets
    $pdo->exec("DELETE FROM queue_tickets");

    $pdo->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}