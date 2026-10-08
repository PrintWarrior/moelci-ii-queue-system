<?php
// Ticket display data.
// Debug here if the printed ticket has missing service, priority, or timestamp values.

if (!isset(
    $_SESSION['generated_ticket'],
    $_SESSION['customer_type'],
    $_SESSION['selected_service_id'],
    $_SESSION['selected_service_name'],
    $_SESSION['ticket_priority']
)) {
    header("Location: customer_dashboard.php");
    exit();
}

$ticket_number = $_SESSION['generated_ticket'];
$customer_type = $_SESSION['customer_type'];
$service_id = (int) $_SESSION['selected_service_id'];
$service_name = $_SESSION['selected_service_name'];
$priority = $_SESSION['ticket_priority'];
$display_customer_type = ($customer_type === 'pwd_elder') ? 'PWD/Elder' : 'Regular';

try {
    $waitTimeQuery = "
        SELECT COUNT(*) AS waiting_ahead
        FROM queue_tickets
        WHERE status = 'waiting'
          AND created_at < NOW()
          AND service_id = ?
    ";

    $stmt = $pdo->prepare($waitTimeQuery);
    $stmt->execute([$service_id]);
    $waitResult = $stmt->fetch(PDO::FETCH_ASSOC);

    $waiting_ahead = $waitResult['waiting_ahead'] ?? 0;
    $estimated_wait = max(5, $waiting_ahead * 5);
} catch (PDOException $e) {
    error_log("Ticket wait-time calculation failed: " . $e->getMessage());
    $estimated_wait = 10;
}

date_default_timezone_set('Asia/Manila');
$timestamp = date('h:i A m/d/Y');
?>
