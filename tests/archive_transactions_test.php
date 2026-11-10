<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/archive_transactions.php';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_PERSISTENT => false,
]);
// Only temporary fixtures are modified; the real tables are shadowed.
$pdo->exec("CREATE TEMPORARY TABLE queue_tickets (
    ticket_id INT PRIMARY KEY, ticket_number VARCHAR(20), service_id INT,
    customer_id INT, customer_name VARCHAR(100), status VARCHAR(20), priority VARCHAR(20),
    created_at DATETIME, called_at DATETIME, completed_at DATETIME,
    assigned_user_id INT) ENGINE=InnoDB");
$pdo->exec("CREATE TEMPORARY TABLE queue_ticket_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT, ticket_number VARCHAR(20), service_id INT,
    customer_id INT, customer_name VARCHAR(100), status VARCHAR(20), priority VARCHAR(20),
    created_at DATETIME, called_at DATETIME, completed_at DATETIME,
    assigned_user_id INT, archived_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    archived_by INT NOT NULL CHECK (archived_by > 0)) ENGINE=InnoDB");
$pdo->exec("INSERT INTO queue_tickets VALUES
    (1,'R0001',1,3,'Customer','waiting','normal','2026-10-28 08:00:00',NULL,NULL,NULL),
    (2,'P0001',1,3,'Customer','in_progress','high','2026-10-28 08:01:00','2026-10-28 08:03:00',NULL,8),
    (3,'R0002',3,3,'Customer','completed','normal','2026-10-28 08:02:00','2026-10-28 08:07:00','2026-10-28 08:15:00',10)");
function archiveCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$original = $pdo->query('SELECT * FROM queue_tickets ORDER BY ticket_id')->fetchAll();
$scenario = $argv[1] ?? 'helper';
if ($scenario !== 'helper') {
    session_id('archive-test-' . bin2hex(random_bytes(8)));
    session_start();
    $_SESSION = ['user_id' => 1, 'role' => 'Admin', 'availability_csrf' => 'archive-token'];
    if ($scenario === 'unauthorized') $_SESSION['role'] = 'Teller';
    session_write_close();
    $_SERVER['REQUEST_METHOD'] = $scenario === 'get' ? 'GET' : 'POST';
    $_POST = ['csrf_token' => $scenario === 'csrf' ? 'wrong-token' : 'archive-token'];
    ob_start();
    register_shutdown_function(function () use ($pdo, $scenario) {
        $result = json_decode(ob_get_clean(), true, 512, JSON_THROW_ON_ERROR);
        $success = $scenario === 'success';
        archiveCheck(!empty($result['success']) === $success, 'Incorrect endpoint result');
        archiveCheck((int) $pdo->query('SELECT COUNT(*) FROM queue_tickets')->fetchColumn() === ($success ? 0 : 3), 'Unexpected queue changes');
        archiveCheck((int) $pdo->query('SELECT COUNT(*) FROM queue_ticket_history')->fetchColumn() === ($success ? 3 : 0), 'Unexpected history changes');
        archiveCheck(http_response_code() === ($success ? 200 : ($scenario === 'get' ? 405 : 403)) || ($success && http_response_code() === false), 'Incorrect HTTP status');
        echo "PASS: manual archive endpoint $scenario\n";
    });
    require __DIR__ . '/../includes/clear_transactions.php';
    exit;
}

// A history insert failure must keep all current transactions intact.
try {
    archiveCurrentTransactions($pdo, 0);
    throw new RuntimeException('Expected invalid archive user to fail');
} catch (PDOException $e) {
    archiveCheck(!$pdo->inTransaction(), 'Failed operation left a transaction open');
}
archiveCheck($pdo->query('SELECT * FROM queue_tickets ORDER BY ticket_id')->fetchAll() === $original, 'Rollback did not preserve tickets');
archiveCheck((int) $pdo->query('SELECT COUNT(*) FROM queue_ticket_history')->fetchColumn() === 0, 'Failed archive inserted history');
archiveCheck(archiveCurrentTransactions($pdo, 1) === 3, 'Not all statuses archived');
archiveCheck((int) $pdo->query('SELECT COUNT(*) FROM queue_tickets')->fetchColumn() === 0, 'Current queue not cleared');
$columns = implode(',', array_keys($original[0]));
$archived = $pdo->query("SELECT $columns FROM queue_ticket_history ORDER BY ticket_id")->fetchAll();
archiveCheck($archived === $original, 'Transaction details changed during archive');
archiveCheck((int) $pdo->query('SELECT COUNT(*) FROM queue_ticket_history WHERE archived_by=1 AND archived_at IS NOT NULL')->fetchColumn() === 3, 'Archive attribution missing');
archiveCheck(archiveCurrentTransactions($pdo, 1) === 0, 'Empty repeat archive should do nothing');
archiveCheck((int) $pdo->query('SELECT COUNT(*) FROM queue_ticket_history')->fetchColumn() === 3, 'Repeat request duplicated history');
echo "PASS: archive all statuses, preserve details, rollback on failure, and safe empty retry\n";
