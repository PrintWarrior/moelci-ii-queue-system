<?php
// Shared queue eligibility and presence for teller and admin dashboards.
const TELLER_OFFLINE_SECONDS = 60;

function ensureTellerAvailabilityTable($pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS teller_availability (
        user_id INT NOT NULL PRIMARY KEY,
        is_available TINYINT(1) NOT NULL DEFAULT 1,
        last_seen DATETIME NULL,
        CONSTRAINT fk_teller_availability_user FOREIGN KEY (user_id)
            REFERENCES users (user_id) ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function recordTellerHeartbeat($pdo, $userId) {
    // A heartbeat must never undo an admin's unavailable setting.
    $stmt = $pdo->prepare("INSERT INTO teller_availability (user_id, last_seen)
        VALUES (?, NOW()) ON DUPLICATE KEY UPDATE last_seen = NOW()");
    $stmt->execute([$userId]);
}

function isTellerAvailable($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u
        JOIN roles r ON r.role_id = u.role_id
        JOIN teller_availability ta ON ta.user_id = u.user_id
        WHERE u.user_id = ? AND u.is_active = 1 AND r.role_name = 'Teller'
          AND ta.is_available = 1
          AND ta.last_seen >= DATE_SUB(NOW(), INTERVAL " . TELLER_OFFLINE_SECONDS . " SECOND)");
    $stmt->execute([$userId]);
    return (bool) $stmt->fetchColumn();
}

function getTellerQueueRule($userId) {
    $rules = [
        5  => ['service' => 'Payment', 'type' => 'regular', 'priority_teller' => 8, 'priority_teller_name' => 'Teller 2'],
        8  => ['service' => 'Payment', 'type' => 'pwd_fallback'],
        10 => ['service' => 'Billing', 'type' => 'regular', 'priority_teller' => 11, 'priority_teller_name' => 'Teller 4'],
        11 => ['service' => 'Billing', 'type' => 'pwd_fallback'],
    ];
    return $rules[$userId] ?? null;
}

function getTellerCustomerTypes($pdo, $userId) {
    $rule = getTellerQueueRule($userId);
    if (!$rule || !isTellerAvailable($pdo, $userId)) return [];

    $canServePwd = $rule['type'] === 'pwd_fallback'
        || (isset($rule['priority_teller']) && !isTellerAvailable($pdo, $rule['priority_teller']));
    return $canServePwd ? ['pwd', 'regular'] : ['regular'];
}

function getNextQueue($pdo, $userId, $lock = false) {
    $rule = getTellerQueueRule($userId);
    $types = getTellerCustomerTypes($pdo, $userId);
    if (!$types) return null;

    $placeholders = implode(',', array_fill(0, count($types), '?'));
    $stmt = $pdo->prepare("SELECT qt.*, s.service_name
        FROM queue_tickets qt JOIN services s ON s.service_id = qt.service_id
        WHERE qt.status = 'waiting' AND qt.customer_type IN ($placeholders)
          AND s.service_name LIKE ?
        ORDER BY CASE WHEN qt.customer_type = 'pwd' THEN 0 ELSE 1 END,
                 qt.created_at ASC, qt.ticket_id ASC
        LIMIT 1" . ($lock ? ' FOR UPDATE' : ''));
    $stmt->execute(array_merge($types, ['%' . $rule['service'] . '%']));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getTellerStats($pdo, $userId) {
    $stats = ['waiting_count' => 0, 'in_progress_count' => 0, 'completed_today' => 0];
    $rule = getTellerQueueRule($userId);
    $types = getTellerCustomerTypes($pdo, $userId);
    if ($types) {
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM queue_tickets qt
            JOIN services s ON s.service_id = qt.service_id
            WHERE qt.status = 'waiting' AND qt.customer_type IN ($placeholders)
              AND s.service_name LIKE ?");
        $stmt->execute(array_merge($types, ['%' . $rule['service'] . '%']));
        $stats['waiting_count'] = (int) $stmt->fetchColumn();
    }
    $stmt = $pdo->prepare("SELECT
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) AS in_progress_count,
        COUNT(CASE WHEN status = 'completed' AND DATE(completed_at) = CURDATE() THEN 1 END) AS completed_today
        FROM queue_tickets WHERE assigned_user_id = ?");
    $stmt->execute([$userId]);
    return array_merge($stats, array_map('intval', $stmt->fetch(PDO::FETCH_ASSOC)));
}
