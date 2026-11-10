<?php
// Temporary fixtures shadow the real tables on a nonpersistent connection.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../includes/db_connection.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT => false,
]);
$pdo->exec('CREATE TEMPORARY TABLE roles (role_id INT PRIMARY KEY, role_name VARCHAR(20)) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE users (user_id INT PRIMARY KEY, role_id INT, is_active INT,
    first_name VARCHAR(50), last_name VARCHAR(50)) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE services (service_id INT PRIMARY KEY, service_name VARCHAR(100), is_active INT) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE teller_services (user_id INT, service_id INT, is_active INT) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE teller_availability (user_id INT PRIMARY KEY, is_available INT, last_seen DATETIME) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE queue_tickets (ticket_id INT PRIMARY KEY, ticket_number VARCHAR(20),
    service_id INT, assigned_user_id INT, status VARCHAR(20), created_at DATETIME,
    called_at DATETIME, completed_at DATETIME, customer_type VARCHAR(20),
    customer_id INT, customer_name VARCHAR(100)) ENGINE=InnoDB');
$pdo->exec('CREATE TEMPORARY TABLE queue_ticket_history (history_id INT PRIMARY KEY, ticket_id INT,
    ticket_number VARCHAR(20), service_id INT, assigned_user_id INT, status VARCHAR(20),
    created_at DATETIME, called_at DATETIME, completed_at DATETIME) ENGINE=InnoDB');
$pdo->exec("INSERT INTO roles VALUES (2, 'Teller')");
$pdo->exec("INSERT INTO users VALUES (5,2,1,'Teller','1'), (8,2,0,'Former','Teller')");
$pdo->exec("INSERT INTO services VALUES (1,'Payment',1), (3,'Notice of Billing',1)");
$pdo->exec('INSERT INTO teller_services VALUES (5,1,1)');
// Two archived tickets on October 19, including a duplicate archive snapshot.
$pdo->exec("INSERT INTO queue_ticket_history VALUES
    (1,1,'R0001',1,5,'waiting','2026-10-19 09:00:00',NULL,NULL),
    (2,1,'R0001',1,5,'completed','2026-10-19 09:00:00','2026-10-19 09:02:00','2026-10-19 09:05:00'),
    (3,2,'P0001',3,8,'skipped','2026-10-19 10:00:00','2026-10-19 10:04:00','2026-10-19 10:06:00'),
    (4,10,'R0001',1,5,'completed','2026-10-20 09:00:00','2026-10-20 09:01:00','2026-10-20 09:02:00')");
// Current record wins over its archived copy. Ticket IDs AND numbers can be reused on another day.
$pdo->exec("INSERT INTO queue_tickets VALUES
    (10,'R0001',1,5,'completed','2026-10-20 09:00:00','2026-10-20 09:06:00','2026-10-20 09:14:00','regular',NULL,'Customer'),
    (11,'R0002',3,NULL,'waiting','2026-10-20 23:59:59',NULL,NULL,'regular',NULL,'Customer'),
    (1,'R0001',1,5,'completed','2026-10-21 00:00:00','2026-10-21 00:01:00','2026-10-21 00:02:00','regular',NULL,'Customer')");

function analyticsCheck($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

session_id('analytics-history-test-' . bin2hex(random_bytes(8)));
session_start();
$_SESSION = ['user_id' => 1, 'role' => 'Admin'];
session_write_close();
$_SERVER['REQUEST_METHOD'] = 'GET';
$cases = [
    ['2026-10-19', '2026-10-19', 2, 1, 0, 1, 3.0, 2.5],
    ['2026-10-20', '2026-10-20', 2, 1, 1, 0, 6.0, 8.0],
    ['2026-10-19', '2026-10-20', 4, 2, 1, 1, 4.0, 4.3],
    ['2026-10-19', '2026-10-21', 5, 3, 1, 1, 3.3, 3.5],
    ['2026-10-20', '2026-10-19', 4, 2, 1, 1, 4.0, 4.3],
    ['2026-10-21', '2026-10-21', 1, 1, 0, 0, 1.0, 1.0],
    ['2026-10-18', '2026-10-18', 0, 0, 0, 0, 0.0, 0.0],
];
$checks = [];
foreach ($cases as [$start, $end, $total, $served, $waiting, $skipped, $wait, $service]) {
    $_GET = ['analytics_start' => $start, 'analytics_end' => $end];
    ob_start();
    require __DIR__ . '/../admin/admin_data.php';
    $result = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
    session_write_close();
    analyticsCheck(!isset($result['error']), $result['error'] ?? 'Analytics error');
    $summary = $result['analytics'];
    foreach (['total_tickets' => $total, 'total_served' => $served, 'currently_waiting' => $waiting, 'skipped_count' => $skipped] as $key => $expected) {
        analyticsCheck((int) $summary[$key] === $expected, "$start to $end: incorrect $key");
    }
    analyticsCheck(abs((float) $summary['avg_wait'] - $wait) < 0.01, 'Incorrect wait average');
    analyticsCheck(abs((float) $summary['avg_service'] - $service) < 0.01, 'Incorrect service average');
    analyticsCheck(array_sum(array_column($result['service_stats'], 'ticket_count')) == $total, 'Service chart disagrees with total');
    analyticsCheck(array_sum(array_column($result['hour_stats'], 'ticket_count')) == $total, 'Hourly chart disagrees with total');
    analyticsCheck(count($result['hour_stats']) === 24, 'Hourly chart must include all 24 hours');
    analyticsCheck(array_sum(array_column($result['teller_performance'], 'served_count')) == $served, 'Teller totals disagree');
    if ($start === '2026-10-19' && $end === $start) {
        $performance = array_column($result['teller_performance'], null, 'teller_name');
        analyticsCheck((int) $performance['Former Teller']['skipped_count'] === 1, 'Deactivated teller history omitted');
    }
    analyticsCheck(count($result['transactions']) === 3, 'Live transactions must remain separate from archived analytics');
    $checks[] = "$start to $end";
}
echo 'PASS: historical analytics, duplicate suppression, date boundaries, averages, charts, inactive tellers, and current transactions (' . count($checks) . " date ranges).\n";
