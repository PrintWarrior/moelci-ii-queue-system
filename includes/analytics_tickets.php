<?php
// Read-only source shared by every analytics calculation.
// Bind the selected start/end timestamps twice: once per source table.
function analyticsTicketSourceSql(): string
{
    return "
        SELECT ticket_id, ticket_number, service_id, assigned_user_id,
               status, created_at, called_at, completed_at
        FROM (
            SELECT combined.*,
                ROW_NUMBER() OVER (
                    PARTITION BY ticket_id, ticket_number, created_at
                    ORDER BY source_priority, archive_sequence DESC
                ) AS record_rank
            FROM (
                SELECT ticket_id, ticket_number, service_id, assigned_user_id,
                       status, created_at, called_at, completed_at,
                       0 AS source_priority, 0 AS archive_sequence
                FROM queue_tickets
                WHERE created_at BETWEEN ? AND ?
                UNION ALL
                SELECT ticket_id, ticket_number, service_id, assigned_user_id,
                       status, created_at, called_at, completed_at,
                       1 AS source_priority, history_id AS archive_sequence
                FROM queue_ticket_history
                WHERE created_at BETWEEN ? AND ?
            ) combined
        ) ranked
        WHERE record_rank = 1
    ";
}
