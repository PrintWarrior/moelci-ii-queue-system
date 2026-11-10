<?php
// Run with PHP CLI. All fixtures use connection-local temporary tables.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/teller_queue.php';

// Avoid persistent connections so temporary fixtures cannot survive this process.
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec("CREATE TEMPORARY TABLE roles (role_id INT PRIMARY KEY, role_name VARCHAR(30)) ENGINE=InnoDB");
$pdo->exec("CREATE TEMPORARY TABLE users (user_id INT PRIMARY KEY, role_id INT, is_active INT,
    first_name VARCHAR(50), last_name VARCHAR(50)) ENGINE=InnoDB");
$pdo->exec("CREATE TEMPORARY TABLE services (service_id INT PRIMARY KEY, service_name VARCHAR(100), is_active INT DEFAULT 1) ENGINE=InnoDB");
$pdo->exec("CREATE TEMPORARY TABLE teller_availability (user_id INT PRIMARY KEY,
    is_available INT DEFAULT 1, last_seen DATETIME NULL) ENGINE=InnoDB");
$pdo->exec("CREATE TEMPORARY TABLE queue_tickets (ticket_id INT PRIMARY KEY,
    ticket_number VARCHAR(30), service_id INT, customer_type VARCHAR(20), status VARCHAR(20),
    created_at DATETIME, called_at DATETIME NULL, completed_at DATETIME NULL,
    assigned_user_id INT NULL, customer_id INT NULL, customer_name VARCHAR(100)) ENGINE=InnoDB");
$pdo->exec("CREATE TEMPORARY TABLE queue_ticket_history (
    history_id INT PRIMARY KEY, ticket_id INT, ticket_number VARCHAR(30), service_id INT,
    assigned_user_id INT NULL, status VARCHAR(20), created_at DATETIME,
    called_at DATETIME NULL, completed_at DATETIME NULL) ENGINE=InnoDB");
$pdo->exec("INSERT INTO roles VALUES (2, 'Teller')");
$pdo->exec("INSERT INTO users VALUES (5,2,1,'Teller','1'), (8,2,1,'Teller','2'),
    (10,2,1,'Teller','3'), (11,2,1,'Teller','4')");
$pdo->exec("INSERT INTO services (service_id, service_name) VALUES (1,'Payment'), (3,'Billing')");
$pdo->exec("INSERT INTO teller_availability VALUES (5,1,NOW()), (8,1,NOW()), (10,1,NOW()), (11,1,NOW())");
$pdo->exec("INSERT INTO queue_tickets (ticket_id,ticket_number,service_id,customer_type,status,created_at)
    VALUES (1,'R0001',1,'regular','waiting',DATE_SUB(NOW(), INTERVAL 5 MINUTE)),
    (2,'P0001',1,'pwd','waiting',DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
    (3,'P0002',1,'pwd','waiting',DATE_SUB(NOW(), INTERVAL 2 MINUTE)),
    (4,'P0003',3,'pwd','waiting',NOW())");

function check($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}

$action = $argv[1] ?? null;
$billing = ($argv[2] ?? '') === 'billing';
$regularTeller = $billing ? 10 : 5;
$priorityTeller = $billing ? 11 : 8;
if ($billing) {
    // Reuse action fixtures for the other service while preserving service separation.
    $pdo->exec('UPDATE queue_tickets SET service_id = CASE WHEN service_id = 1 THEN 3 ELSE 1 END');
}
if ($action && strpos($action, 'admin-') === 0) {
    session_id('queue-admin-test-' . bin2hex(random_bytes(8)));
    session_start();
    $_SESSION = ['user_id' => 1, 'role' => 'Admin', 'availability_csrf' => 'test-token'];
    session_write_close();
    $_SERVER['REQUEST_METHOD'] = $action === 'admin-data' ? 'GET' : 'POST';
    $_POST = ['user_id' => (string) $priorityTeller, 'is_available' => '0', 'csrf_token' => 'test-token'];
    if ($action === 'admin-reject-csrf') $_POST['csrf_token'] = 'wrong-token';
    if ($action === 'admin-invalid-teller') $_POST['user_id'] = '999';
    ob_start();
    register_shutdown_function(function () use ($pdo, $action, $regularTeller, $priorityTeller) {
        $output = ob_get_clean();
        try {
            $data = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
            if ($action === 'admin-data') {
                check(!isset($data['error']), 'Admin data must load: ' . ($data['error'] ?? ''));
                $tellers = array_column($data['tellers'], null, 'user_id');
                check($tellers[$priorityTeller]['status'] === 'Idle', 'Online teller waiting for customers is Idle');
                check($tellers[$regularTeller]['next_customer'] === 'R0001', 'Admin preview respects regular-only rule');
                check($tellers[$priorityTeller]['next_customer'] === 'P0001', 'Admin preview prioritizes PWD');
                check($tellers[$regularTeller]['queue_length'] === 1, 'Admin waiting count respects eligibility');
            } elseif ($action === 'admin-unavailable') {
                check(!empty($data['success']), 'Admin control must succeed');
                check(!isTellerAvailable($pdo, $priorityTeller), 'Admin can pause teller');
                check(getNextQueue($pdo, $regularTeller)['ticket_number'] === 'P0001', 'Admin pause enables fallback');
            } else {
                check(isset($data['error']), 'Invalid admin request must fail');
                check(isTellerAvailable($pdo, $priorityTeller), 'Rejected request must preserve availability');
            }
            echo "PASS: $action\n";
        } catch (Throwable $e) {
            fwrite(STDERR, "FAIL: " . $e->getMessage() . "\nResponse: $output\n");
            exit(1);
        }
    });
    require __DIR__ . '/../admin/admin_data.php';
    exit;
}
if ($action) {
    session_id('queue-test-' . bin2hex(random_bytes(8)));
    session_start();
    $_SESSION = ['user_id' => $regularTeller, 'role' => 'Teller', 'first_name' => 'Teller', 'last_name' => $billing ? '3' : '1'];
    session_write_close();
    $_SERVER['REQUEST_METHOD'] = 'POST';
    if ($action === 'call-fallback') {
        $pdo->exec('UPDATE teller_availability SET last_seen = NULL WHERE user_id = ' . $priorityTeller);
        $_POST = ['call_next' => ''];
    } else {
        $pdo->exec("UPDATE queue_tickets SET status = 'in_progress', assigned_user_id = $regularTeller, called_at = NOW() WHERE ticket_id = 2");
        $_POST = ['mark_served' => ''];
        if (strpos($action, 'unavailable-') === 0) {
            $pdo->exec('UPDATE teller_availability SET is_available = 0 WHERE user_id = ' . $regularTeller);
        }
        if ($action === 'unavailable-call') $_POST = ['call_next' => ''];
        if ($action === 'callback') $_POST = ['callback_current' => ''];
    }
    register_shutdown_function(function () use ($pdo, $action, $regularTeller) {
        try {
            $rows = $pdo->query('SELECT ticket_id, status, assigned_user_id FROM queue_tickets ORDER BY ticket_id')->fetchAll(PDO::FETCH_UNIQUE);
            if ($action === 'call-fallback') {
                check($rows[2]['status'] === 'in_progress' && (int) $rows[2]['assigned_user_id'] === $regularTeller, 'Fallback must claim PWD first');
                check($rows[1]['status'] === 'waiting', 'Older regular must wait behind PWD');
            } elseif ($action === 'served-return') {
                check($rows[2]['status'] === 'completed', 'Existing PWD must finish with the current teller');
                check($rows[1]['status'] === 'in_progress', 'Returning priority teller restores regular-only next call');
                check($rows[3]['status'] === 'waiting', 'Remaining PWD belongs to priority teller');
            } elseif ($action === 'unavailable-served') {
                check($rows[2]['status'] === 'completed', 'Unavailable teller can complete current customer');
                check($rows[1]['status'] === 'waiting' && $rows[3]['status'] === 'waiting', 'Unavailable teller must not auto-call');
            } else {
                check($rows[2]['status'] === 'in_progress', 'Current ticket must remain with the current teller');
                check($rows[1]['status'] === 'waiting' && $rows[3]['status'] === 'waiting', 'No new ticket may be claimed');
            }
            echo "PASS: $action\n";
        } catch (Throwable $e) {
            fwrite(STDERR, "FAIL: " . $e->getMessage() . "\n");
            exit(1);
        }
    });
    require __DIR__ . '/../teller/includes/teller_bootstrap.php';
    exit;
}

check(getNextQueue($pdo, 5)['ticket_number'] === 'R0001', 'Both online: Teller 1 regular only');
check(getNextQueue($pdo, 8)['ticket_number'] === 'P0001', 'Teller 2 prioritizes PWD, ties use ticket ID');
check(getTellerStats($pdo, 5)['waiting_count'] === 1, 'Normal waiting count excludes PWD');
$pdo->exec('UPDATE teller_availability SET is_available = 0 WHERE user_id = 8');
recordTellerHeartbeat($pdo, 8);
check(!isTellerAvailable($pdo, 8), 'Heartbeat must preserve admin override');
check(getNextQueue($pdo, 5)['ticket_number'] === 'P0001', 'Unavailable Teller 2 enables fallback');
check(getTellerStats($pdo, 5)['waiting_count'] === 3, 'Fallback waiting count includes regular and PWD');
check(getNextQueue($pdo, 8) === null, 'Unavailable teller cannot call');
$pdo->exec('UPDATE teller_availability SET is_available = 1, last_seen = DATE_SUB(NOW(), INTERVAL 61 SECOND) WHERE user_id = 8');
check(getNextQueue($pdo, 5)['ticket_number'] === 'P0001', 'Stale heartbeat enables fallback');
$pdo->exec('UPDATE teller_availability SET last_seen = NULL WHERE user_id = 8');
check(getNextQueue($pdo, 5)['ticket_number'] === 'P0001', 'Logout enables fallback');
recordTellerHeartbeat($pdo, 8);
check(getNextQueue($pdo, 5)['ticket_number'] === 'R0001', 'Online idle Teller 2 restores restriction');
$pdo->exec('UPDATE users SET is_active = 0 WHERE user_id = 8');
check(getNextQueue($pdo, 5)['ticket_number'] === 'P0001', 'Inactive Teller 2 enables fallback');
$pdo->exec('UPDATE users SET is_active = 1 WHERE user_id = 8');
$pdo->exec("UPDATE queue_tickets SET status = 'completed' WHERE customer_type = 'pwd' AND service_id = 1");
check(getNextQueue($pdo, 8)['ticket_number'] === 'R0001', 'Teller 2 serves regular when no PWD wait');
check(getNextQueue($pdo, 10) === false, 'Online Teller 4 keeps Teller 3 regular-only');
check(getNextQueue($pdo, 11)['ticket_number'] === 'P0003', 'Billing priority teller keeps own service');
$pdo->exec('UPDATE teller_availability SET is_available = 0 WHERE user_id = 11');
recordTellerHeartbeat($pdo, 11);
check(getNextQueue($pdo, 10)['ticket_number'] === 'P0003', 'Unavailable Teller 4 enables billing fallback');
check(getTellerStats($pdo, 10)['waiting_count'] === 1, 'Billing count includes eligible PWD');
check(getNextQueue($pdo, 11) === null, 'Unavailable Teller 4 cannot call');
check(getNextQueue($pdo, 5)['ticket_number'] === 'R0001', 'Billing fallback cannot affect payment');
$pdo->exec('UPDATE teller_availability SET is_available = 1, last_seen = DATE_SUB(NOW(), INTERVAL 61 SECOND) WHERE user_id = 11');
check(getNextQueue($pdo, 10)['ticket_number'] === 'P0003', 'Offline Teller 4 enables billing fallback');
$pdo->exec('UPDATE teller_availability SET last_seen = NULL WHERE user_id = 11');
check(getNextQueue($pdo, 10)['ticket_number'] === 'P0003', 'Logged-out Teller 4 enables billing fallback');
recordTellerHeartbeat($pdo, 11);
check(getNextQueue($pdo, 10) === false, 'Returning Teller 4 restores regular-only billing');
check(getNextQueue($pdo, 999) === null, 'Unknown teller has no eligible queue');
echo "PASS: queue eligibility, presence, priority, counts, service boundaries\n";
