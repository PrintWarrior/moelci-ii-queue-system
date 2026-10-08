<?php
// Ticket generation setup.
// Debug here if service selection is not remembered or ticket creation fails.

$error_message = "";

if (isset($_POST['service_id'], $_POST['service_name'])) {
    $_SESSION['selected_service_id'] = (int) $_POST['service_id'];
    $_SESSION['selected_service_name'] = trim($_POST['service_name']);
}

if (!isset($_SESSION['selected_service_id'], $_SESSION['selected_service_name'])) {
    header("Location: customer_dashboard.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$customer_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
$service_id = (int) $_SESSION['selected_service_id'];
$service_name = $_SESSION['selected_service_name'];

function generateQueueTicket(PDO $pdo, int $service_id, int $user_id, string $customer_name, string $customer_type): array
{
    $customer_type_db = ($customer_type === 'pwd_elder') ? 'pwd' : 'regular';
    $prefix = ($customer_type_db === 'pwd') ? 'P' : 'R';
    $priority = ($customer_type_db === 'pwd') ? 'high' : 'normal';

    // Numbering rule: P0001/P0002 for priority, R0001/R0002 for regular, reset daily.
    // If duplicate ticket errors happen during heavy traffic, inspect this MAX()+1 query first.
    $ticketQuery = "
        SELECT MAX(CAST(SUBSTRING(ticket_number, 2) AS UNSIGNED)) AS last_number
        FROM queue_tickets
        WHERE ticket_number LIKE ?
          AND DATE(created_at) = CURDATE()
    ";

    $stmt = $pdo->prepare($ticketQuery);
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $next_number = ($result['last_number'] ? $result['last_number'] + 1 : 1);
    $ticket_number = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);

    $insertQuery = "
        INSERT INTO queue_tickets
            (ticket_number, service_id, customer_id, customer_name, customer_type, priority, status, created_at)
        VALUES
            (?, ?, ?, ?, ?, ?, 'waiting', NOW())
    ";

    $stmt = $pdo->prepare($insertQuery);
    $stmt->execute([$ticket_number, $service_id, $user_id, $customer_name, $customer_type_db, $priority]);

    return [
        'ticket_number' => $ticket_number,
        'priority' => $priority,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_type'])) {
    try {
        $ticket = generateQueueTicket($pdo, $service_id, $user_id, $customer_name, $_POST['customer_type']);

        $_SESSION['generated_ticket'] = $ticket['ticket_number'];
        $_SESSION['ticket_priority'] = $ticket['priority'];
        $_SESSION['customer_type'] = $_POST['customer_type'];

        $ticket_generated = true;

        if (!defined('TICKET_GENERATION_REDIRECT') || TICKET_GENERATION_REDIRECT) {
            header("Location: customer_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Ticket generation error: " . $e->getMessage());
        $error_message = "Unable to generate a ticket right now. Please try again.";
    }
}
?>
