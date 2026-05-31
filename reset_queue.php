<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Manila');
require_once __DIR__ . '/includes/db_connection.php';

try {
    $today = date('Y-m-d');

    // 🔒 Prevent double reset
    $check = $pdo->prepare("
        SELECT 1 FROM queue_reset_log WHERE reset_date = ?
    ");
    $check->execute([$today]);

    if ($check->fetch()) {
        exit; // Already reset today
    }

    $pdo->beginTransaction();

    // Archive tickets
    $pdo->exec("
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
            1
        FROM queue_tickets
    ");

    // Clear queue
    $pdo->exec("DELETE FROM queue_tickets");

    // Log success
    $log = $pdo->prepare("
        INSERT INTO queue_reset_log (reset_date, reset_at)
        VALUES (?, NOW())
    ");
    $log->execute([$today]);

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    file_put_contents(
        __DIR__ . '/cron_errors.log',
        date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND
    );
}
