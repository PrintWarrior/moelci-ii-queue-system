<?php
session_start();
require_once __DIR__ . '/../includes/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo "Excel export dependency is missing.\n\n";
    echo "Install Composer, then run this in the project root:\n";
    echo "composer install\n";
    exit;
}

require_once $autoloadPath;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$period = $_GET['period'] ?? 'daily';
$baseDateInput = $_GET['date'] ?? date('Y-m-d');

if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly'], true)) {
    $period = 'daily';
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $baseDateInput)) {
    $baseDateInput = date('Y-m-d');
}

$baseDate = new DateTime($baseDateInput);
$startDate = clone $baseDate;
$endDate = clone $baseDate;

switch ($period) {
    case 'weekly':
        $startDate->modify('monday this week')->setTime(0, 0, 0);
        $endDate->modify('sunday this week')->setTime(23, 59, 59);
        break;
    case 'monthly':
        $startDate->modify('first day of this month')->setTime(0, 0, 0);
        $endDate->modify('last day of this month')->setTime(23, 59, 59);
        break;
    case 'yearly':
        $startDate->setDate((int) $baseDate->format('Y'), 1, 1)->setTime(0, 0, 0);
        $endDate->setDate((int) $baseDate->format('Y'), 12, 31)->setTime(23, 59, 59);
        break;
    case 'daily':
    default:
        $startDate->setTime(0, 0, 0);
        $endDate->setTime(23, 59, 59);
        break;
}

$stmt = $pdo->prepare("
    SELECT
        h.ticket_number,
        h.customer_name,
        s.service_name,
        CONCAT(t.first_name, ' ', t.last_name) AS teller_name,
        h.status,
        h.created_at,
        h.completed_at
    FROM queue_ticket_history h
    JOIN services s ON h.service_id = s.service_id
    LEFT JOIN users t ON h.assigned_user_id = t.user_id
    WHERE h.created_at BETWEEN ? AND ?
    ORDER BY h.created_at ASC
");
$stmt->execute([
    $startDate->format('Y-m-d H:i:s'),
    $endDate->format('Y-m-d H:i:s'),
]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Transaction History');

$headers = ['Ticket No', 'Customer', 'Service', 'Teller', 'Status', 'Created', 'Completed'];
$sheet->fromArray($headers, null, 'A1');

$rowNumber = 2;
foreach ($rows as $row) {
    $sheet->fromArray([
        $row['ticket_number'],
        $row['customer_name'],
        $row['service_name'],
        $row['teller_name'] ?: 'N/A',
        $row['status'],
        $row['created_at'],
        $row['completed_at'] ?: 'N/A',
    ], null, 'A' . $rowNumber);
    $rowNumber++;
}

foreach (range('A', 'G') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$sheet->getStyle('A1:G1')->getFont()->setBold(true);
$sheet->freezePane('A2');

$filename = sprintf(
    'transaction-history-%s-%s-to-%s.xlsx',
    $period,
    $startDate->format('Y-m-d'),
    $endDate->format('Y-m-d')
);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
