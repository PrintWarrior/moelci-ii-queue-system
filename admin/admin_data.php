<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$response = [];

try {
    // Analytics date range. Defaults to today so the charts answer "what is happening now?"
    $analyticsStart = $_GET['analytics_start'] ?? date('Y-m-d');
    $analyticsEnd = $_GET['analytics_end'] ?? $analyticsStart;

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $analyticsStart)) {
        $analyticsStart = date('Y-m-d');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $analyticsEnd)) {
        $analyticsEnd = $analyticsStart;
    }

    if ($analyticsEnd < $analyticsStart) {
        [$analyticsStart, $analyticsEnd] = [$analyticsEnd, $analyticsStart];
    }

    $analyticsStartDateTime = $analyticsStart . ' 00:00:00';
    $analyticsEndDateTime = $analyticsEnd . ' 23:59:59';

    // Get tellers with their queue information
    $tellerQuery = "
        SELECT 
            u.user_id,
            CONCAT(u.first_name, ' ', u.last_name) AS teller_name,
            GROUP_CONCAT(DISTINCT s.service_name ORDER BY s.service_name SEPARATOR ', ') AS services,

            -- FIXED: count waiting tickets for teller's services
            (
                SELECT COUNT(*)
                FROM queue_tickets qt2
                WHERE qt2.service_id IN (
                    SELECT ts2.service_id
                    FROM teller_services ts2
                    WHERE ts2.user_id = u.user_id
                      AND ts2.is_active = 1
                )
                AND qt2.status = 'waiting'
            ) AS queue_length,

            -- FIXED: get next customer (smallest ticket for teller's services)
            (
                SELECT MIN(qt3.ticket_number)
                FROM queue_tickets qt3
                WHERE qt3.service_id IN (
                    SELECT ts3.service_id
                    FROM teller_services ts3
                    WHERE ts3.user_id = u.user_id
                      AND ts3.is_active = 1
                )
                AND qt3.status = 'waiting'
            ) AS next_customer,

            COALESCE(AVG(TIMESTAMPDIFF(MINUTE, qt.created_at, qt.called_at)), 0) AS avg_wait,

            CASE 
                WHEN COUNT(CASE WHEN qt.status = 'in_progress' THEN 1 END) > 0 THEN 'Active'
                ELSE 'Idle'
            END AS status

        FROM users u
        LEFT JOIN teller_services ts ON u.user_id = ts.user_id AND ts.is_active = 1
        LEFT JOIN services s ON ts.service_id = s.service_id
        LEFT JOIN queue_tickets qt ON u.user_id = qt.assigned_user_id AND qt.status IN ('waiting', 'in_progress')
        WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Teller') 
          AND u.is_active = 1
        GROUP BY u.user_id, teller_name
    ";
    
    $stmt = $pdo->prepare($tellerQuery);
    $stmt->execute();
    $response['tellers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all transactions (queue tickets)
    $transactionsQuery = "
        SELECT 
            qt.ticket_number,
            COALESCE(CONCAT(u.first_name, ' ', u.last_name), qt.customer_name) as customer_name,
            s.service_name,
            COALESCE(teller.first_name, 'Unassigned') as teller_name,
            qt.status,
            DATE_FORMAT(qt.created_at, '%Y-%m-%d %H:%i') as created_at,
            DATE_FORMAT(qt.completed_at, '%Y-%m-%d %H:%i') as completed_at
        FROM queue_tickets qt
        LEFT JOIN services s ON qt.service_id = s.service_id
        LEFT JOIN users u ON qt.customer_id = u.user_id
        LEFT JOIN users teller ON qt.assigned_user_id = teller.user_id
        ORDER BY qt.created_at DESC
        LIMIT 100
    ";
    
    $stmt = $pdo->prepare($transactionsQuery);
    $stmt->execute();
    $response['transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Analytics summary for the selected date range.
    $analyticsQuery = "
        SELECT
            COUNT(*) AS total_tickets,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) AS total_served,
            COUNT(CASE WHEN status = 'waiting' THEN 1 END) AS currently_waiting,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled_count,
            COUNT(CASE WHEN status = 'skipped' THEN 1 END) AS skipped_count,
            COALESCE(ROUND(AVG(CASE WHEN called_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, created_at, called_at) END), 1), 0) AS avg_wait,
            COALESCE(ROUND(AVG(CASE WHEN called_at IS NOT NULL AND completed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, called_at, completed_at) END), 1), 0) AS avg_service
        FROM queue_tickets
        WHERE created_at BETWEEN ? AND ?
    ";

    $stmt = $pdo->prepare($analyticsQuery);
    $stmt->execute([$analyticsStartDateTime, $analyticsEndDateTime]);
    $analyticsData = $stmt->fetch(PDO::FETCH_ASSOC);

    $busiestServiceQuery = "
        SELECT s.service_name, COUNT(qt.ticket_id) AS ticket_count
        FROM queue_tickets qt
        JOIN services s ON qt.service_id = s.service_id
        WHERE qt.created_at BETWEEN ? AND ?
        GROUP BY s.service_id, s.service_name
        ORDER BY ticket_count DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($busiestServiceQuery);
    $stmt->execute([$analyticsStartDateTime, $analyticsEndDateTime]);
    $busiestService = $stmt->fetch(PDO::FETCH_ASSOC);

    $mostActiveTellerQuery = "
        SELECT CONCAT(u.first_name, ' ', u.last_name) AS teller_name, COUNT(qt.ticket_id) AS served_count
        FROM queue_tickets qt
        JOIN users u ON qt.assigned_user_id = u.user_id
        WHERE qt.status = 'completed'
          AND qt.created_at BETWEEN ? AND ?
        GROUP BY u.user_id, teller_name
        ORDER BY served_count DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($mostActiveTellerQuery);
    $stmt->execute([$analyticsStartDateTime, $analyticsEndDateTime]);
    $mostActiveTeller = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['analytics'] = [
        "date_range" => $analyticsStart . " to " . $analyticsEnd,
        "total_tickets" => $analyticsData['total_tickets'] ?? 0,
        "total_served" => $analyticsData['total_served'] ?? 0,
        "currently_waiting" => $analyticsData['currently_waiting'] ?? 0,
        "cancelled_count" => $analyticsData['cancelled_count'] ?? 0,
        "skipped_count" => $analyticsData['skipped_count'] ?? 0,
        "avg_wait" => $analyticsData['avg_wait'] ?? 0,
        "avg_service" => $analyticsData['avg_service'] ?? 0,
        "busiest_service" => $busiestService ? $busiestService['service_name'] . " (" . $busiestService['ticket_count'] . ")" : "N/A",
        "most_active_teller" => $mostActiveTeller ? $mostActiveTeller['teller_name'] . " (" . $mostActiveTeller['served_count'] . ")" : "N/A"
    ];

    // Service statistics for the selected date range.
    $serviceStatsQuery = "
        SELECT s.service_name, COUNT(qt.ticket_id) AS ticket_count
        FROM services s
        LEFT JOIN queue_tickets qt
            ON s.service_id = qt.service_id
           AND qt.created_at BETWEEN ? AND ?
        GROUP BY s.service_id, s.service_name
        ORDER BY ticket_count DESC, s.service_name ASC
    ";

    $stmt = $pdo->prepare($serviceStatsQuery);
    $stmt->execute([$analyticsStartDateTime, $analyticsEndDateTime]);
    $response['service_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hourly demand for staffing decisions.
    $hourStatsQuery = "
        SELECT HOUR(created_at) AS hour_number, COUNT(*) AS ticket_count
        FROM queue_tickets
        WHERE created_at BETWEEN ? AND ?
        GROUP BY HOUR(created_at)
        ORDER BY hour_number
    ";

    $stmt = $pdo->prepare($hourStatsQuery);
    $stmt->execute([$analyticsStartDateTime, $analyticsEndDateTime]);
    $hourRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hourStats = [];
    for ($hour = 0; $hour < 24; $hour++) {
        $hourStats[$hour] = [
            'hour_label' => date('g A', strtotime(sprintf('%02d:00', $hour))),
            'ticket_count' => 0
        ];
    }

    foreach ($hourRows as $row) {
        $hour = (int) $row['hour_number'];
        $hourStats[$hour]['ticket_count'] = (int) $row['ticket_count'];
    }

    $response['hour_stats'] = array_values($hourStats);

    // Teller performance table for the selected date range.
    $tellerPerformanceQuery = "
        SELECT
            CONCAT(u.first_name, ' ', u.last_name) AS teller_name,
            COUNT(CASE WHEN qt.status = 'completed' THEN 1 END) AS served_count,
            COUNT(CASE WHEN qt.status = 'cancelled' THEN 1 END) AS cancelled_count,
            COUNT(CASE WHEN qt.status = 'skipped' THEN 1 END) AS skipped_count,
            COALESCE(ROUND(AVG(CASE WHEN qt.called_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, qt.created_at, qt.called_at) END), 1), 0) AS avg_wait,
            COALESCE(ROUND(AVG(CASE WHEN qt.called_at IS NOT NULL AND qt.completed_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, qt.called_at, qt.completed_at) END), 1), 0) AS avg_service
        FROM users u
        LEFT JOIN queue_tickets qt
            ON u.user_id = qt.assigned_user_id
           AND qt.created_at BETWEEN ? AND ?
        WHERE u.role_id = (SELECT role_id FROM roles WHERE role_name = 'Teller')
          AND u.is_active = 1
        GROUP BY u.user_id, teller_name
        ORDER BY served_count DESC, teller_name ASC
    ";

    $stmt = $pdo->prepare($tellerPerformanceQuery);
    $stmt->execute([$analyticsStartDateTime, $analyticsEndDateTime]);
    $response['teller_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Settings data
    $settingsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM services WHERE is_active = 1) as total_services,
            (SELECT COUNT(*) FROM users WHERE role_id = (SELECT role_id FROM roles WHERE role_name = 'Teller') AND is_active = 1) as active_tellers,
            (SELECT COUNT(*) FROM queue_tickets WHERE DATE(created_at) = CURDATE()) as total_customers_today
    ";
    
    $stmt = $pdo->prepare($settingsQuery);
    $stmt->execute();
    $response['settings'] = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $response['error'] = "Database error: " . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
