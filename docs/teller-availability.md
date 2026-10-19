# Teller availability

Teller 1 serves regular payment customers while Teller 2 is online and available.
When Teller 2 logs out, loses connection for 60 seconds, is deactivated, or is
marked unavailable by the admin, Teller 1 serves waiting PWD customers first,
then regular customers. Teller 2 returning restores regular-only eligibility for
Teller 1 without moving an already called customer.

The same rule applies to Notice of Billing: Teller 3 serves regular customers
while Teller 4 is online and available. When Teller 4 is unavailable or offline,
Teller 3 serves PWD customers first, then regular customers. Teller 4 returning
restores Teller 3 to regular-only eligibility while preserving current transactions.

The admin teller cards distinguish Active (serving), Idle (online and waiting),
Offline, and Unavailable. Use **Mark Unavailable** for an absence or break and
**Mark Available** on return. Marking available does not bring an offline teller
online; their teller dashboard must be open. Admin overrides persist through
refreshes and logout until changed by the admin.

The `teller_availability` table is created automatically on the first admin data
or teller request. Existing queue tickets and service assignments are preserved.

Run `C:\xampp\php\php.exe tests/teller_queue_test.php` for queue rule checks.
Action scenarios accept `call-fallback`, `served-return`, `unavailable-served`,
`unavailable-call`, or `callback` as an argument. Tests use connection-local
temporary tables without changing customer or teller records.
Add `billing` as a second argument to run an action scenario for Tellers 3 and 4.

The availability record belongs to exactly one user through
`teller_availability.user_id -> users.user_id`. The user ID is both the
primary key and a foreign key, so a user has zero or one availability record.
The foreign key rejects nonexistent users and restricts deleting or changing a
referenced user ID. Account deactivation (`is_active = 0`) is unaffected.

For existing databases, apply
`databases/migrations/20261019_add_teller_availability_user_fk.sql` once
after checking for orphaned availability records. Newly created availability
tables include the constraint automatically.
