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

    // Enhanced Analytics data with counts
    $analyticsQuery = "
        SELECT 
            COUNT(CASE WHEN status = 'completed' THEN 1 END) AS total_served,
            COUNT(CASE WHEN status = 'waiting' THEN 1 END) AS currently_waiting,
            COALESCE(ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)), 1), 0) AS avg_wait,
            (
                SELECT s.service_name 
                FROM services s 
                JOIN queue_tickets qt2 ON s.service_id = qt2.service_id
                WHERE qt2.status = 'completed'
                GROUP BY s.service_name 
                ORDER BY COUNT(*) DESC 
                LIMIT 1
            ) AS busiest_service_name,
            (
                SELECT COUNT(*) 
                FROM queue_tickets qt2 
                JOIN services s ON qt2.service_id = s.service_id
                WHERE qt2.status = 'completed'
                GROUP BY s.service_name 
                ORDER BY COUNT(*) DESC 
                LIMIT 1
            ) AS busiest_service_count,
            (
                SELECT s.service_name 
                FROM services s 
                JOIN queue_tickets qt3 ON s.service_id = qt3.service_id
                WHERE qt3.status = 'completed'
                GROUP BY s.service_name 
                ORDER BY COUNT(*) ASC 
                LIMIT 1
            ) AS least_busiest_service_name,
            (
                SELECT COUNT(*) 
                FROM queue_tickets qt3 
                JOIN services s ON qt3.service_id = s.service_id
                WHERE qt3.status = 'completed'
                GROUP BY s.service_name 
                ORDER BY COUNT(*) ASC 
                LIMIT 1
            ) AS least_busiest_service_count,
            (
                SELECT CONCAT(u.first_name, ' ', u.last_name)
                FROM users u
                JOIN queue_tickets qt4 ON u.user_id = qt4.assigned_user_id
                WHERE qt4.status = 'completed'
                GROUP BY u.user_id
                ORDER BY COUNT(*) DESC
                LIMIT 1
            ) AS most_active_teller_name,
            (
                SELECT COUNT(*)
                FROM queue_tickets qt4
                WHERE qt4.assigned_user_id = (
                    SELECT u.user_id
                    FROM users u
                    JOIN queue_tickets qt5 ON u.user_id = qt5.assigned_user_id
                    WHERE qt5.status = 'completed'
                    GROUP BY u.user_id
                    ORDER BY COUNT(*) DESC
                    LIMIT 1
                )
                AND qt4.status = 'completed'
            ) AS most_active_teller_count,
            (
                SELECT CONCAT(u.first_name, ' ', u.last_name)
                FROM users u
                JOIN queue_tickets qt6 ON u.user_id = qt6.assigned_user_id
                WHERE qt6.status = 'completed'
                GROUP BY u.user_id
                ORDER BY COUNT(*) ASC
                LIMIT 1
            ) AS least_active_teller_name,
            (
                SELECT COUNT(*)
                FROM queue_tickets qt7
                WHERE qt7.assigned_user_id = (
                    SELECT u.user_id
                    FROM users u
                    JOIN queue_tickets qt8 ON u.user_id = qt8.assigned_user_id
                    WHERE qt8.status = 'completed'
                    GROUP BY u.user_id
                    ORDER BY COUNT(*) ASC
                    LIMIT 1
                )
                AND qt7.status = 'completed'
            ) AS least_active_teller_count
        FROM queue_tickets 
        WHERE status IN ('completed', 'waiting')
    ";
    
    $stmt = $pdo->prepare($analyticsQuery);
    $stmt->execute();
    $analyticsData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format analytics data to match the expected structure
    $response['analytics'] = [
        "total_served" => $analyticsData['total_served'] ?? 0,
        "currently_waiting" => $analyticsData['currently_waiting'] ?? 0,
        "avg_wait" => $analyticsData['avg_wait'] ?? 0,
        "busiest_service" => $analyticsData['busiest_service_name'] ? 
            $analyticsData['busiest_service_name'] . " (" . $analyticsData['busiest_service_count'] . ")" : "N/A",
        "least_busiest_service" => $analyticsData['least_busiest_service_name'] ? 
            $analyticsData['least_busiest_service_name'] . " (" . $analyticsData['least_busiest_service_count'] . ")" : "N/A",
        "most_active_teller" => $analyticsData['most_active_teller_name'] ? 
            $analyticsData['most_active_teller_name'] . " (" . $analyticsData['most_active_teller_count'] . ")" : "N/A",
        "least_active_teller" => $analyticsData['least_active_teller_name'] ? 
            $analyticsData['least_active_teller_name'] . " (" . $analyticsData['least_active_teller_count'] . ")" : "N/A"
    ];

    // Service statistics for chart (all time completed transactions)
    $serviceStatsQuery = "
        SELECT s.service_name, COUNT(qt.ticket_id) as ticket_count
        FROM services s
        LEFT JOIN queue_tickets qt ON s.service_id = qt.service_id AND qt.status = 'completed'
        GROUP BY s.service_id, s.service_name
    ";
    
    $stmt = $pdo->prepare($serviceStatsQuery);
    $stmt->execute();
    $response['service_stats'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

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