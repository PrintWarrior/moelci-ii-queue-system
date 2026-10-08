<?php
// Teller dashboard bootstrap.
// Debug here when teller access, call-next actions, current ticket, next ticket, or stats fail.
session_start(); 
require_once '../includes/db_connection.php';

// Check if user is logged in as teller 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Teller') { 
    header("Location: ../index.php"); 
    exit; 
} 

// Set teller ID from session 
$user_id = $_SESSION['user_id']; 
$teller_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; 

function getTellerQueueRule($user_id) {
    $rules = [
        5  => ['service' => 'Payment', 'type' => 'regular'], // Teller 1
        8  => ['service' => 'Payment', 'type' => 'pwd_fallback'], // Teller 2
        10 => ['service' => 'Billing', 'type' => 'regular'], // Teller 3
        11 => ['service' => 'Billing', 'type' => 'pwd_fallback'], // Teller 4
    ];

    return $rules[$user_id] ?? null;
}

// ---- FUNCTION TO FETCH NEXT QUEUE ---- 
function getNextQueue($pdo, $user_id) {
    $rule = getTellerQueueRule($user_id);

    if (!$rule) return null;

    $service = $rule['service'];
    $typeRule = $rule['type'];

    // First, try PWD priority tickets if this teller supports fallback priority.
    if ($typeRule === 'pwd_fallback') {
        $stmt = $pdo->prepare("
            SELECT qt.*, s.service_name
            FROM queue_tickets qt
            JOIN services s ON qt.service_id = s.service_id
            WHERE qt.status = 'waiting'
              AND qt.customer_type = 'pwd'
              AND s.service_name LIKE ?
            ORDER BY qt.created_at ASC
            LIMIT 1
        ");
        $stmt->execute(["%$service%"]);
        $pwd = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pwd) return $pwd;
    }

    // Then, try regular customer tickets.
    $stmt = $pdo->prepare("
        SELECT qt.*, s.service_name
        FROM queue_tickets qt
        JOIN services s ON qt.service_id = s.service_id
        WHERE qt.status = 'waiting'
          AND qt.customer_type = 'regular'
          AND s.service_name LIKE ?
        ORDER BY qt.created_at ASC
        LIMIT 1
    ");
    $stmt->execute(["%$service%"]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTellerStats($pdo, $user_id) {
    $rule = getTellerQueueRule($user_id);

    $stats = [
        'waiting_count' => 0,
        'in_progress_count' => 0,
        'completed_today' => 0,
    ];

    if ($rule) {
        $customerTypes = $rule['type'] === 'pwd_fallback' ? ['pwd', 'regular'] : ['regular'];
        $placeholders = implode(',', array_fill(0, count($customerTypes), '?'));

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM queue_tickets qt
            JOIN services s ON qt.service_id = s.service_id
            WHERE qt.status = 'waiting'
              AND qt.customer_type IN ($placeholders)
              AND s.service_name LIKE ?
        ");
        $stmt->execute(array_merge($customerTypes, ['%' . $rule['service'] . '%']));
        $stats['waiting_count'] = (int) $stmt->fetchColumn();
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM queue_tickets
        WHERE status = 'in_progress'
          AND assigned_user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats['in_progress_count'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM queue_tickets
        WHERE status = 'completed'
          AND assigned_user_id = ?
          AND DATE(completed_at) = CURDATE()
    ");
    $stmt->execute([$user_id]);
    $stats['completed_today'] = (int) $stmt->fetchColumn();

    return $stats;
}
 

// ---- FETCH CURRENT SERVING QUEUE ---- 
// ---- FETCH CURRENT SERVING QUEUE ---- 
try { 
    // Get ONLY THE LATEST customer being served (not all in_progress)
    $currentQuery = "SELECT qt.*, s.service_name, 
                            COALESCE(CONCAT(u.first_name, ' ', u.last_name), qt.customer_name) as customer_name 
                     FROM queue_tickets qt 
                     JOIN services s ON qt.service_id = s.service_id 
                     LEFT JOIN users u ON qt.customer_id = u.user_id 
                     WHERE qt.assigned_user_id = ? 
                       AND qt.status = 'in_progress' 
                     ORDER BY qt.called_at DESC 
                     LIMIT 1";  // Show only the latest one
    
    $stmt = $pdo->prepare($currentQuery); 
    $stmt->execute([$user_id]); 
    $current = $stmt->fetch(PDO::FETCH_ASSOC); 

    // Continue with existing code...

    // ---- FETCH NEXT QUEUE ---- 
    $next = getNextQueue($pdo, $user_id); 

    // ---- HANDLE ACTIONS ---- 
if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    // CALL NEXT 
    if (isset($_POST['call_next'])) { 
        if ($current) { 
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='skipped', completed_at = NOW() WHERE ticket_id = ?"); 
            $updateStmt->execute([$current['ticket_id']]); 
        } 

        if ($next) { 
            // Assign next ticket to this teller and mark as in progress 
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='in_progress', assigned_user_id = ?, called_at = NOW() WHERE ticket_id = ?"); 
            $updateStmt->execute([$user_id, $next['ticket_id']]); 
            
            // Store the called ticket for speech synthesis
            $_SESSION['last_called_ticket'] = $next['ticket_number'];
            $_SESSION['last_called_teller'] = $teller_name;
        } 

        header("Location: teller_dashboard.php"); 
        exit; 
    } 

    // MARK SERVED/COMPLETED 
    if (isset($_POST['mark_served'])) { 
        if ($current) { 
            // Mark current as completed 
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='completed', completed_at = NOW() WHERE ticket_id = ?"); 
            $updateStmt->execute([$current['ticket_id']]); 

            if ($next) { 
                $updateStmt = $pdo->prepare("UPDATE queue_tickets SET status='in_progress', assigned_user_id = ?, called_at = NOW() WHERE ticket_id = ?"); 
                $updateStmt->execute([$user_id, $next['ticket_id']]); 

                $_SESSION['last_called_ticket'] = $next['ticket_number'];
                $_SESSION['last_called_teller'] = $teller_name;
            } 
        } 

        header("Location: teller_dashboard.php"); 
        exit; 
    } 

    // CALL BACK CURRENT 
    if (isset($_POST['callback_current'])) { 
        if ($current) { 
            // Keep the same ticket active and refresh called_at so the monitor announces it again.
            $updateStmt = $pdo->prepare("UPDATE queue_tickets SET called_at = NOW() WHERE ticket_id = ? AND status = 'in_progress'"); 
            $updateStmt->execute([$current['ticket_id']]); 

            $_SESSION['last_called_ticket'] = $current['ticket_number'];
            $_SESSION['last_called_teller'] = $teller_name;
        } 

        header("Location: teller_dashboard.php"); 
        exit; 
    } 
}

    $stats = getTellerStats($pdo, $user_id); 

} catch (PDOException $e) { 
    die("Database error: " . $e->getMessage()); 
} 
?> 
