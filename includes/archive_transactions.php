<?php
// Archive a locked snapshot; never delete tickets that were not copied.
function archiveCurrentTransactions(PDO $pdo, int $adminId): int
{
    $pdo->beginTransaction();
    try {
        $tickets = $pdo->query('SELECT ticket_id FROM queue_tickets ORDER BY ticket_id FOR UPDATE')
            ->fetchAll(PDO::FETCH_COLUMN);
        if (!$tickets) {
            $pdo->commit();
            return 0;
        }
        $ids = implode(',', array_map('intval', $tickets));
        $stmt = $pdo->prepare("
            INSERT INTO queue_ticket_history (
                ticket_id, ticket_number, service_id, customer_id, customer_name,
                status, priority, created_at, called_at, completed_at,
                assigned_user_id, archived_by
            )
            SELECT ticket_id, ticket_number, service_id, customer_id, customer_name,
                   status, priority, created_at, called_at, completed_at,
                   assigned_user_id, ?
            FROM queue_tickets WHERE ticket_id IN ($ids)
        ");
        $stmt->execute([$adminId]);
        if ($stmt->rowCount() !== count($tickets)) {
            throw new RuntimeException('Not all selected transactions were archived.');
        }
        $removed = $pdo->exec("DELETE FROM queue_tickets WHERE ticket_id IN ($ids)");
        if ($removed !== count($tickets)) {
            throw new RuntimeException('The archived queue snapshot could not be cleared.');
        }
        $pdo->commit();
        return count($tickets);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
