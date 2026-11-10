<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'error' => 'Use the Move to History button.']);
    exit;
}
if (empty($_SESSION['availability_csrf'])
    || !hash_equals($_SESSION['availability_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Please refresh the dashboard and try again.']);
    exit;
}
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/archive_transactions.php';
try {
    $count = archiveCurrentTransactions($pdo, (int) $_SESSION['user_id']);
    echo json_encode(['success' => true, 'archived_count' => $count]);
} catch (Throwable $e) {
    error_log('Manual queue archive failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to move transactions to history. Please try again.']);
}
