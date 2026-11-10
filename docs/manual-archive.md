# Manual transaction archive

In Admin → Transactions, click **Move All to History** and confirm after service
has ended. This archives every current ticket, including waiting and in-progress
customers, then removes those copied records from the current queue. The page
opens Settings → History with the updated records. History exports and analytics
can then use those records normally.

The operation requires an authenticated admin, a POST request, and a valid CSRF
token. Copy and deletion use a single transaction with locked ticket rows. If
archiving fails, the operation rolls back. Clicking again on an empty queue does
not add duplicates. Account records, assignments, and operating hours are untouched.

This button is a fallback when the scheduled reset did not run. It still needs
access to the application server and database. It does not mark the entire day
as reset in `queue_reset_log`, so a later scheduled reset can still process any
new tickets created afterward. Ticket statuses are preserved as recorded.

Run `C:\xampp\php\php.exe tests/archive_transactions_test.php` for temporary-table
checks. Additional endpoint scenarios: `success`, `get`, `csrf`, `unauthorized`.
The tests never archive the real queue.
