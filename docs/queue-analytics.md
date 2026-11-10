# Queue analytics

Select the start and end dates and click **Apply**. Use the same date in both
fields to review one day, or select multiple days for combined results.

Analytics reads both `queue_tickets` and `queue_ticket_history`, filtered by
the original ticket creation timestamp, including the entire end date.
It does not change the tables or their records. The Transactions tab continues
to show current tickets, and Excel export continues to read archived history.

Every analytics metric uses the same combined source. Matching ticket IDs,
ticket numbers, and creation timestamps identify duplicate copies. Current
records take precedence over archived copies; when multiple archive copies
exist, the highest history ID takes precedence. Reused ticket numbers or IDs
on different creation dates remain separate transactions.

Wait time measures creation to call; service time measures call to completion.
Both retain the existing whole-minute calculation and one-decimal averages.
Statuses reflect the stored current or archived record, so a historical Waiting
count is not a reconstruction of the queue at a particular past time.
Deactivated tellers remain in the performance table when they have activity in
the selected range. Deleted records cannot be included unless restored.

The combined source uses SQL window functions, supported by MySQL 8.0+ and
MariaDB 10.2+.

Run `C:\xampp\php\php.exe tests/analytics_history_test.php` for temporary-table
regression checks. If the CLI cannot write to its configured session directory,
use a writable test session directory with PHP's `session.save_path` setting.
