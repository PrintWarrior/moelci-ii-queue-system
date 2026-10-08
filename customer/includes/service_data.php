<?php
// Service selection data.
// Debug here if service buttons are missing or showing the wrong services.

$required_services = [
    'Payment',
    'Complain',
    'Notice of Billing',
    'Reconnection',
    'New Connection Seminar'
];

function loadCustomerServices(PDO $pdo, array $required_services): array
{
    if (empty($required_services)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($required_services), '?'));

    $stmt = $pdo->prepare("
        SELECT service_id, service_name
        FROM services
        WHERE service_name IN ($placeholders)
          AND is_active = 1
    ");
    $stmt->execute($required_services);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    $services = loadCustomerServices($pdo, $required_services);
} catch (PDOException $e) {
    error_log("Customer service load failed: " . $e->getMessage());
    $services = [];
}

// Fallback keeps the page usable if the services table is empty during setup/demo.
if (empty($services)) {
    $services = [
        ['service_id' => 1, 'service_name' => 'Payment'],
        ['service_id' => 3, 'service_name' => 'Notice of Billing'],
    ];
}
?>
