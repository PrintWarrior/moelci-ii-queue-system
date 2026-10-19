<?php
session_start();
require_once __DIR__ . '/../../includes/db_connection.php';
require_once __DIR__ . '/../../includes/teller_queue.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Teller') {
    header('Location: ../index.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$teller_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

try {
    ensureTellerAvailabilityTable($pdo);
    recordTellerHeartbeat($pdo, $user_id);

    // Serialize queue actions for each service so two tellers cannot claim one ticket.
    $isAction = $_SERVER['REQUEST_METHOD'] === 'POST'
        && (isset($_POST['call_next']) || isset($_POST['mark_served']) || isset($_POST['callback_current']));
    if ($isAction) {
        $pdo->beginTransaction();
        $rule = getTellerQueueRule($user_id);
        $serviceTellers = $rule && $rule['service'] === 'Payment' ? [5, 8] : [10, 11];
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE user_id IN (?, ?) ORDER BY user_id FOR UPDATE');
        $stmt->execute($serviceTellers);
        $stmt->fetchAll();
    }

    $stmt = $pdo->prepare("SELECT qt.*, s.service_name,
        COALESCE(CONCAT(u.first_name, ' ', u.last_name), qt.customer_name) AS customer_name
        FROM queue_tickets qt
        JOIN services s ON s.service_id = qt.service_id
        LEFT JOIN users u ON u.user_id = qt.customer_id
        WHERE qt.assigned_user_id = ? AND qt.status = 'in_progress'
        ORDER BY qt.called_at DESC LIMIT 1" . ($isAction ? ' FOR UPDATE' : ''));
    $stmt->execute([$user_id]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($isAction) {
        if (isset($_POST['callback_current'])) {
            if ($current) {
                $stmt = $pdo->prepare("UPDATE queue_tickets SET called_at = NOW() WHERE ticket_id = ? AND status = 'in_progress'");
                $stmt->execute([$current['ticket_id']]);
                $_SESSION['last_called_ticket'] = $current['ticket_number'];
                $_SESSION['last_called_teller'] = $teller_name;
            }
        } else {
            // Re-evaluate presence at action time, never trust the rendered preview.
            $next = getNextQueue($pdo, $user_id, true);
            $canCallNext = isTellerAvailable($pdo, $user_id);
            if (isset($_POST['mark_served']) || $canCallNext) {
                $hadCurrent = (bool) $current;
                if ($current) {
                    $status = isset($_POST['mark_served']) ? 'completed' : 'skipped';
                    $stmt = $pdo->prepare("UPDATE queue_tickets SET status = ?, completed_at = NOW() WHERE ticket_id = ? AND status = 'in_progress'");
                    $stmt->execute([$status, $current['ticket_id']]);
                }
                // Unavailable tellers can finish their customer without taking another.
                if ($next && $canCallNext && (isset($_POST['call_next']) || $hadCurrent)) {
                    $stmt = $pdo->prepare("UPDATE queue_tickets
                        SET status = 'in_progress', assigned_user_id = ?, called_at = NOW()
                        WHERE ticket_id = ? AND status = 'waiting'");
                    $stmt->execute([$user_id, $next['ticket_id']]);
                    $_SESSION['last_called_ticket'] = $next['ticket_number'];
                    $_SESSION['last_called_teller'] = $teller_name;
                }
            }
        }
        $pdo->commit();
        header('Location: teller_dashboard.php');
        exit;
    }

    $teller_available = isTellerAvailable($pdo, $user_id);
    $customer_types = getTellerCustomerTypes($pdo, $user_id);
    $rule = getTellerQueueRule($user_id);
    $priority_fallback = isset($rule['priority_teller']) && in_array('pwd', $customer_types, true);
    $priority_teller_name = $rule['priority_teller_name'] ?? '';
    $next = getNextQueue($pdo, $user_id);
    $stats = getTellerStats($pdo, $user_id);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Teller queue error: ' . $e->getMessage());
    http_response_code(500);
    die('Unable to load or update the teller queue. Please try again.');
}
