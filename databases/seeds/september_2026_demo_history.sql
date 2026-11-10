-- SEPTEMBER 2026 DEMO HISTORY - test data only; choose your target database first.
-- 166 tickets across 9 randomly selected Monday/Wednesday/Friday dates.
-- All other September dates are deliberately left empty by this script.
-- Current queue, accounts, services, operating hours, and schema are unchanged.
-- Negative original ticket IDs distinguish these fixtures; customer names use the selected account.
-- These are generated transactions, not actual September activity.
-- Teller IDs follow this project's existing rules: 5/8 Payment, 10/11 Billing.
-- Importing again skips demo records already present (no duplicate inflation).
-- Populated dates and record counts:
-- 2026-09-04: 21
-- 2026-09-09: 21
-- 2026-09-11: 20
-- 2026-09-14: 21
-- 2026-09-16: 22
-- 2026-09-18: 17
-- 2026-09-23: 17
-- 2026-09-25: 11
-- 2026-09-30: 16

SET @demo_admin = (SELECT u.user_id FROM users u JOIN roles r ON r.role_id=u.role_id WHERE r.role_name='Admin' AND u.is_active=1 ORDER BY u.user_id LIMIT 1);
SET @demo_customer = (SELECT u.user_id FROM users u JOIN roles r ON r.role_id=u.role_id WHERE r.role_name='Customer' AND u.is_active=1 ORDER BY u.user_id LIMIT 1);
SET @demo_customer_name = (SELECT COALESCE(NULLIF(TRIM(CONCAT_WS(' ', first_name, last_name)), ''), username) FROM users WHERE user_id=@demo_customer);
SET @demo_payment_service = (SELECT service_id FROM services WHERE service_name='Payment' AND is_active=1 ORDER BY service_id LIMIT 1);
SET @demo_billing_service = (SELECT service_id FROM services WHERE service_name IN ('Notice of Billing','Billing') AND is_active=1 ORDER BY service_id LIMIT 1);

DROP TEMPORARY TABLE IF EXISTS demo_september_2026_history;
CREATE TEMPORARY TABLE demo_september_2026_history LIKE queue_ticket_history;

START TRANSACTION;
INSERT INTO demo_september_2026_history (ticket_id, ticket_number, service_id, customer_id, customer_name, status, priority, created_at, called_at, completed_at, assigned_user_id, archived_at, archived_by) VALUES
(-2026090401, 'R0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 08:13:33', '2026-09-04 08:28:33', '2026-09-04 08:34:33', 5, '2026-09-04 17:29:00', @demo_admin),
(-2026090402, 'R0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 08:15:49', '2026-09-04 08:22:49', '2026-09-04 08:32:49', 5, '2026-09-04 17:34:00', @demo_admin),
(-2026090403, 'R0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-04 08:23:01', '2026-09-04 08:57:01', '2026-09-04 09:13:01', 10, '2026-09-04 17:21:00', @demo_admin),
(-2026090404, 'P0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-04 09:01:45', '2026-09-04 09:13:45', '2026-09-04 09:24:45', 11, '2026-09-04 17:23:00', @demo_admin),
(-2026090405, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 09:05:15', '2026-09-04 09:22:15', '2026-09-04 09:37:15', 5, '2026-09-04 17:38:00', @demo_admin),
(-2026090406, 'P0002', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-04 09:07:04', '2026-09-04 09:16:04', '2026-09-04 09:29:04', 11, '2026-09-04 17:38:00', @demo_admin),
(-2026090407, 'R0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-04 09:13:16', '2026-09-04 09:42:16', '2026-09-04 09:57:16', 5, '2026-09-04 17:31:00', @demo_admin),
(-2026090408, 'R0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 09:19:45', '2026-09-04 09:37:45', '2026-09-04 09:51:45', 10, '2026-09-04 17:25:00', @demo_admin),
(-2026090409, 'R0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 09:34:00', '2026-09-04 09:52:00', '2026-09-04 10:00:00', 5, '2026-09-04 17:09:00', @demo_admin),
(-2026090410, 'R0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 09:44:23', '2026-09-04 10:05:23', '2026-09-04 10:18:23', 5, '2026-09-04 17:36:00', @demo_admin),
(-2026090411, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 09:50:10', '2026-09-04 09:56:10', '2026-09-04 10:11:10', 5, '2026-09-04 17:32:00', @demo_admin),
(-2026090412, 'R0010', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 11:09:09', '2026-09-04 11:23:09', '2026-09-04 11:35:09', 5, '2026-09-04 17:32:00', @demo_admin),
(-2026090413, 'R0011', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 12:12:16', '2026-09-04 12:43:16', '2026-09-04 12:51:16', 5, '2026-09-04 17:39:00', @demo_admin),
(-2026090414, 'R0012', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 12:31:03', '2026-09-04 13:01:03', '2026-09-04 13:14:03', 5, '2026-09-04 17:32:00', @demo_admin),
(-2026090415, 'R0013', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 12:43:22', '2026-09-04 12:47:22', '2026-09-04 13:04:22', 10, '2026-09-04 17:19:00', @demo_admin),
(-2026090416, 'P0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-04 13:43:11', '2026-09-04 13:53:11', '2026-09-04 14:13:11', 11, '2026-09-04 17:32:00', @demo_admin),
(-2026090417, 'R0014', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 14:38:23', '2026-09-04 15:06:23', '2026-09-04 15:18:23', 5, '2026-09-04 17:37:00', @demo_admin),
(-2026090418, 'R0015', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 15:24:21', '2026-09-04 15:48:21', '2026-09-04 15:57:21', 5, '2026-09-04 17:42:00', @demo_admin),
(-2026090419, 'R0016', @demo_billing_service, @demo_customer, @demo_customer_name, 'cancelled', 'normal', '2026-09-04 15:25:06', NULL, '2026-09-04 15:48:06', NULL, '2026-09-04 17:08:00', @demo_admin),
(-2026090420, 'R0017', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-04 15:34:03', '2026-09-04 15:38:03', '2026-09-04 15:47:03', 5, '2026-09-04 17:06:00', @demo_admin),
(-2026090421, 'P0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-04 15:34:51', '2026-09-04 15:37:51', '2026-09-04 15:47:51', 8, '2026-09-04 17:31:00', @demo_admin),
(-2026090901, 'R0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 08:36:02', '2026-09-09 09:01:02', '2026-09-09 09:07:02', 10, '2026-09-09 17:22:00', @demo_admin),
(-2026090902, 'P0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-09 08:36:18', '2026-09-09 08:42:18', '2026-09-09 09:00:18', 11, '2026-09-09 17:13:00', @demo_admin),
(-2026090903, 'R0002', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-09 08:43:38', '2026-09-09 09:10:38', '2026-09-09 09:21:38', 10, '2026-09-09 17:10:00', @demo_admin),
(-2026090904, 'R0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'cancelled', 'normal', '2026-09-09 09:31:30', NULL, '2026-09-09 10:08:30', NULL, '2026-09-09 17:37:00', @demo_admin),
(-2026090905, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 09:32:17', '2026-09-09 10:05:17', '2026-09-09 10:10:17', 5, '2026-09-09 17:19:00', @demo_admin),
(-2026090906, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-09 09:50:33', '2026-09-09 09:59:33', '2026-09-09 10:06:33', 8, '2026-09-09 17:22:00', @demo_admin),
(-2026090907, 'R0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-09 09:56:42', '2026-09-09 10:04:42', '2026-09-09 10:09:42', 5, '2026-09-09 17:34:00', @demo_admin),
(-2026090908, 'R0006', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 10:08:18', '2026-09-09 10:19:18', '2026-09-09 10:33:18', 5, '2026-09-09 17:24:00', @demo_admin),
(-2026090909, 'P0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-09 10:31:42', '2026-09-09 10:38:42', '2026-09-09 10:47:42', 8, '2026-09-09 17:41:00', @demo_admin),
(-2026090910, 'P0004', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-09 11:13:20', '2026-09-09 11:14:20', '2026-09-09 11:20:20', 11, '2026-09-09 17:13:00', @demo_admin),
(-2026090911, 'R0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 11:32:35', '2026-09-09 11:49:35', '2026-09-09 11:58:35', 5, '2026-09-09 17:26:00', @demo_admin),
(-2026090912, 'R0008', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 11:33:08', '2026-09-09 12:06:08', '2026-09-09 12:12:08', 10, '2026-09-09 17:41:00', @demo_admin),
(-2026090913, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 11:46:56', '2026-09-09 12:11:56', '2026-09-09 12:14:56', 5, '2026-09-09 17:10:00', @demo_admin),
(-2026090914, 'R0010', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-09 12:41:13', '2026-09-09 12:48:13', '2026-09-09 12:52:13', 5, '2026-09-09 17:21:00', @demo_admin),
(-2026090915, 'R0011', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-09 12:42:53', '2026-09-09 13:10:53', '2026-09-09 13:25:53', 10, '2026-09-09 17:08:00', @demo_admin),
(-2026090916, 'R0012', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 12:54:00', '2026-09-09 13:20:00', '2026-09-09 13:25:00', 5, '2026-09-09 17:15:00', @demo_admin),
(-2026090917, 'R0013', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 13:22:02', '2026-09-09 13:53:02', '2026-09-09 14:05:02', 5, '2026-09-09 17:45:00', @demo_admin),
(-2026090918, 'R0014', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 13:23:35', '2026-09-09 13:40:35', '2026-09-09 13:44:35', 10, '2026-09-09 17:16:00', @demo_admin),
(-2026090919, 'R0015', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 13:30:53', '2026-09-09 13:36:53', '2026-09-09 13:44:53', 5, '2026-09-09 17:33:00', @demo_admin),
(-2026090920, 'R0016', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 13:59:40', '2026-09-09 14:04:40', '2026-09-09 14:21:40', 10, '2026-09-09 17:11:00', @demo_admin),
(-2026090921, 'R0017', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-09 15:38:55', '2026-09-09 16:13:55', '2026-09-09 16:25:55', 5, '2026-09-09 17:20:00', @demo_admin),
(-2026091101, 'P0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-11 08:50:57', '2026-09-11 08:53:57', '2026-09-11 09:01:57', 11, '2026-09-11 17:18:00', @demo_admin),
(-2026091102, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-11 09:49:51', '2026-09-11 09:57:51', '2026-09-11 10:01:51', 8, '2026-09-11 17:06:00', @demo_admin),
(-2026091103, 'R0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 10:34:03', '2026-09-11 10:51:03', '2026-09-11 11:03:03', 5, '2026-09-11 17:34:00', @demo_admin),
(-2026091104, 'R0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'cancelled', 'normal', '2026-09-11 10:42:53', NULL, '2026-09-11 11:27:53', NULL, '2026-09-11 17:35:00', @demo_admin),
(-2026091105, 'R0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 10:44:28', '2026-09-11 10:50:28', '2026-09-11 10:56:28', 5, '2026-09-11 17:21:00', @demo_admin),
(-2026091106, 'P0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'cancelled', 'high', '2026-09-11 11:00:29', NULL, '2026-09-11 11:18:29', NULL, '2026-09-11 17:43:00', @demo_admin),
(-2026091107, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 11:15:02', '2026-09-11 11:30:02', '2026-09-11 11:42:02', 5, '2026-09-11 17:40:00', @demo_admin),
(-2026091108, 'P0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-11 11:42:03', '2026-09-11 11:50:03', '2026-09-11 11:53:03', 8, '2026-09-11 17:27:00', @demo_admin),
(-2026091109, 'P0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-11 11:46:43', '2026-09-11 11:57:43', '2026-09-11 12:04:43', 8, '2026-09-11 17:17:00', @demo_admin),
(-2026091110, 'R0005', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 12:15:14', '2026-09-11 12:25:14', '2026-09-11 12:39:14', 10, '2026-09-11 17:11:00', @demo_admin),
(-2026091111, 'R0006', @demo_payment_service, @demo_customer, @demo_customer_name, 'cancelled', 'normal', '2026-09-11 12:34:56', NULL, '2026-09-11 12:46:56', NULL, '2026-09-11 17:23:00', @demo_admin),
(-2026091112, 'P0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'cancelled', 'high', '2026-09-11 12:51:16', NULL, '2026-09-11 13:11:16', NULL, '2026-09-11 17:14:00', @demo_admin),
(-2026091113, 'R0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-11 13:31:54', '2026-09-11 13:54:54', '2026-09-11 13:58:54', 10, '2026-09-11 17:40:00', @demo_admin),
(-2026091114, 'R0008', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 13:52:14', '2026-09-11 14:09:14', '2026-09-11 14:29:14', 10, '2026-09-11 17:10:00', @demo_admin),
(-2026091115, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 14:13:25', '2026-09-11 14:47:25', '2026-09-11 15:00:25', 5, '2026-09-11 17:26:00', @demo_admin),
(-2026091116, 'P0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-11 14:13:33', '2026-09-11 14:15:33', '2026-09-11 14:26:33', 8, '2026-09-11 17:17:00', @demo_admin),
(-2026091117, 'R0010', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 14:23:01', '2026-09-11 14:42:01', '2026-09-11 14:49:01', 5, '2026-09-11 17:17:00', @demo_admin),
(-2026091118, 'R0011', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-11 15:07:35', '2026-09-11 15:28:35', '2026-09-11 15:31:35', 10, '2026-09-11 17:19:00', @demo_admin),
(-2026091119, 'P0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-11 15:23:53', '2026-09-11 15:28:53', '2026-09-11 15:42:53', 8, '2026-09-11 17:09:00', @demo_admin),
(-2026091120, 'R0012', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-11 15:47:31', '2026-09-11 16:07:31', '2026-09-11 16:23:31', 10, '2026-09-11 17:26:00', @demo_admin),
(-2026091401, 'R0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 08:06:44', '2026-09-14 08:12:44', '2026-09-14 08:17:44', 5, '2026-09-14 17:28:00', @demo_admin),
(-2026091402, 'P0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-14 08:13:57', '2026-09-14 08:16:57', '2026-09-14 08:28:57', 11, '2026-09-14 17:43:00', @demo_admin),
(-2026091403, 'R0002', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 09:02:22', '2026-09-14 09:29:22', '2026-09-14 09:35:22', 10, '2026-09-14 17:12:00', @demo_admin),
(-2026091404, 'R0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 09:15:37', '2026-09-14 09:38:37', '2026-09-14 09:41:37', 5, '2026-09-14 17:33:00', @demo_admin),
(-2026091405, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-14 09:44:20', '2026-09-14 09:50:20', '2026-09-14 10:03:20', 8, '2026-09-14 17:35:00', @demo_admin),
(-2026091406, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 11:07:50', '2026-09-14 11:35:50', '2026-09-14 11:46:50', 5, '2026-09-14 17:07:00', @demo_admin),
(-2026091407, 'R0005', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-14 11:32:02', '2026-09-14 11:40:02', '2026-09-14 12:01:02', 10, '2026-09-14 17:12:00', @demo_admin),
(-2026091408, 'P0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-14 11:34:21', '2026-09-14 11:43:21', '2026-09-14 12:02:21', 11, '2026-09-14 17:37:00', @demo_admin),
(-2026091409, 'R0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-14 11:53:55', '2026-09-14 12:11:55', '2026-09-14 12:33:55', 10, '2026-09-14 17:06:00', @demo_admin),
(-2026091410, 'R0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 12:34:47', '2026-09-14 12:50:47', '2026-09-14 13:05:47', 5, '2026-09-14 17:33:00', @demo_admin),
(-2026091411, 'R0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 12:44:39', '2026-09-14 12:58:39', '2026-09-14 13:10:39', 5, '2026-09-14 17:06:00', @demo_admin),
(-2026091412, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 13:03:57', '2026-09-14 13:12:57', '2026-09-14 13:21:57', 5, '2026-09-14 17:22:00', @demo_admin),
(-2026091413, 'R0010', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 13:17:54', '2026-09-14 13:50:54', '2026-09-14 14:01:54', 5, '2026-09-14 17:43:00', @demo_admin),
(-2026091414, 'R0011', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 14:09:20', '2026-09-14 14:34:20', '2026-09-14 14:39:20', 10, '2026-09-14 17:28:00', @demo_admin),
(-2026091415, 'R0012', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-14 14:18:46', '2026-09-14 14:34:46', '2026-09-14 14:41:46', 10, '2026-09-14 17:05:00', @demo_admin),
(-2026091416, 'P0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-14 14:28:25', '2026-09-14 14:29:25', '2026-09-14 14:41:25', 8, '2026-09-14 17:44:00', @demo_admin),
(-2026091417, 'P0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-14 14:42:10', '2026-09-14 14:51:10', '2026-09-14 14:57:10', 8, '2026-09-14 17:19:00', @demo_admin),
(-2026091418, 'R0013', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 14:45:00', '2026-09-14 15:06:00', '2026-09-14 15:16:00', 5, '2026-09-14 17:43:00', @demo_admin),
(-2026091419, 'R0014', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 15:19:56', '2026-09-14 15:30:56', '2026-09-14 15:50:56', 10, '2026-09-14 17:21:00', @demo_admin),
(-2026091420, 'R0015', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-14 15:24:07', '2026-09-14 15:33:07', '2026-09-14 15:45:07', 5, '2026-09-14 17:43:00', @demo_admin),
(-2026091421, 'R0016', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-14 15:36:45', '2026-09-14 15:40:45', '2026-09-14 15:50:45', 10, '2026-09-14 17:34:00', @demo_admin),
(-2026091601, 'R0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 08:27:45', '2026-09-16 08:55:45', '2026-09-16 09:11:45', 10, '2026-09-16 17:08:00', @demo_admin),
(-2026091602, 'P0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-16 08:58:57', '2026-09-16 09:08:57', '2026-09-16 09:13:57', 8, '2026-09-16 17:19:00', @demo_admin),
(-2026091603, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-16 09:13:08', '2026-09-16 09:17:08', '2026-09-16 09:29:08', 8, '2026-09-16 17:15:00', @demo_admin),
(-2026091604, 'R0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 09:22:24', '2026-09-16 09:47:24', '2026-09-16 09:55:24', 5, '2026-09-16 17:18:00', @demo_admin),
(-2026091605, 'R0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 10:14:00', '2026-09-16 10:38:00', '2026-09-16 10:41:00', 10, '2026-09-16 17:27:00', @demo_admin),
(-2026091606, 'P0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-16 10:23:36', '2026-09-16 10:37:36', '2026-09-16 10:48:36', 8, '2026-09-16 17:43:00', @demo_admin),
(-2026091607, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-16 10:42:04', '2026-09-16 11:17:04', '2026-09-16 11:25:04', 5, '2026-09-16 17:33:00', @demo_admin),
(-2026091608, 'P0004', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-16 11:08:41', '2026-09-16 11:17:41', '2026-09-16 11:23:41', 11, '2026-09-16 17:42:00', @demo_admin),
(-2026091609, 'R0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 11:13:29', '2026-09-16 11:47:29', '2026-09-16 11:59:29', 5, '2026-09-16 17:26:00', @demo_admin),
(-2026091610, 'R0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-16 12:04:32', '2026-09-16 12:08:32', '2026-09-16 12:15:32', 10, '2026-09-16 17:06:00', @demo_admin),
(-2026091611, 'R0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 12:21:23', '2026-09-16 12:38:23', '2026-09-16 12:53:23', 10, '2026-09-16 17:14:00', @demo_admin),
(-2026091612, 'R0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 12:42:41', '2026-09-16 12:59:41', '2026-09-16 13:07:41', 5, '2026-09-16 17:33:00', @demo_admin),
(-2026091613, 'P0005', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-16 12:48:32', '2026-09-16 12:52:32', '2026-09-16 13:06:32', 11, '2026-09-16 17:44:00', @demo_admin),
(-2026091614, 'P0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-16 12:57:31', '2026-09-16 13:03:31', '2026-09-16 13:14:31', 11, '2026-09-16 17:43:00', @demo_admin),
(-2026091615, 'P0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-16 13:14:10', '2026-09-16 13:17:10', '2026-09-16 13:26:10', 11, '2026-09-16 17:06:00', @demo_admin),
(-2026091616, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 13:23:17', '2026-09-16 13:41:17', '2026-09-16 13:46:17', 5, '2026-09-16 17:36:00', @demo_admin),
(-2026091617, 'R0010', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 14:57:16', '2026-09-16 15:25:16', '2026-09-16 15:29:16', 5, '2026-09-16 17:21:00', @demo_admin),
(-2026091618, 'R0011', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 14:58:42', '2026-09-16 15:16:42', '2026-09-16 15:28:42', 10, '2026-09-16 17:43:00', @demo_admin),
(-2026091619, 'R0012', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 14:58:52', '2026-09-16 15:29:52', '2026-09-16 15:42:52', 10, '2026-09-16 17:12:00', @demo_admin),
(-2026091620, 'P0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'cancelled', 'high', '2026-09-16 15:37:29', NULL, '2026-09-16 15:54:29', NULL, '2026-09-16 17:30:00', @demo_admin),
(-2026091621, 'R0013', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 15:38:06', '2026-09-16 16:08:06', '2026-09-16 16:16:06', 5, '2026-09-16 17:21:00', @demo_admin),
(-2026091622, 'R0014', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-16 15:39:48', '2026-09-16 16:01:48', '2026-09-16 16:15:48', 10, '2026-09-16 17:09:00', @demo_admin),
(-2026091801, 'R0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 08:22:39', '2026-09-18 08:32:39', '2026-09-18 08:51:39', 10, '2026-09-18 17:40:00', @demo_admin),
(-2026091802, 'P0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-18 09:27:31', '2026-09-18 09:40:31', '2026-09-18 09:49:31', 11, '2026-09-18 17:19:00', @demo_admin),
(-2026091803, 'R0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 09:59:11', '2026-09-18 10:02:11', '2026-09-18 10:16:11', 5, '2026-09-18 17:06:00', @demo_admin),
(-2026091804, 'R0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 11:07:58', '2026-09-18 11:24:58', '2026-09-18 11:41:58', 10, '2026-09-18 17:06:00', @demo_admin),
(-2026091805, 'P0002', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-18 11:12:16', '2026-09-18 11:17:16', '2026-09-18 11:31:16', 11, '2026-09-18 17:11:00', @demo_admin),
(-2026091806, 'R0004', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 11:19:41', '2026-09-18 11:44:41', '2026-09-18 11:48:41', 10, '2026-09-18 17:43:00', @demo_admin),
(-2026091807, 'R0005', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 11:20:02', '2026-09-18 11:31:02', '2026-09-18 11:46:02', 10, '2026-09-18 17:39:00', @demo_admin),
(-2026091808, 'R0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'normal', '2026-09-18 11:26:30', '2026-09-18 11:29:30', '2026-09-18 11:32:30', 10, '2026-09-18 17:06:00', @demo_admin),
(-2026091809, 'R0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 11:41:44', '2026-09-18 11:54:44', '2026-09-18 12:07:44', 10, '2026-09-18 17:32:00', @demo_admin),
(-2026091810, 'P0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-18 11:56:22', '2026-09-18 12:01:22', '2026-09-18 12:04:22', 8, '2026-09-18 17:11:00', @demo_admin),
(-2026091811, 'P0004', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-18 12:50:48', '2026-09-18 13:02:48', '2026-09-18 13:11:48', 11, '2026-09-18 17:30:00', @demo_admin),
(-2026091812, 'R0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 13:30:04', '2026-09-18 13:45:04', '2026-09-18 13:48:04', 5, '2026-09-18 17:15:00', @demo_admin),
(-2026091813, 'P0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-18 13:41:59', '2026-09-18 13:44:59', '2026-09-18 13:48:59', 8, '2026-09-18 17:33:00', @demo_admin),
(-2026091814, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 13:47:17', '2026-09-18 13:57:17', '2026-09-18 14:04:17', 5, '2026-09-18 17:12:00', @demo_admin),
(-2026091815, 'R0010', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-18 14:20:34', '2026-09-18 14:25:34', '2026-09-18 14:42:34', 10, '2026-09-18 17:20:00', @demo_admin),
(-2026091816, 'P0006', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-18 14:58:27', '2026-09-18 15:09:27', '2026-09-18 15:12:27', 8, '2026-09-18 17:27:00', @demo_admin),
(-2026091817, 'P0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'cancelled', 'high', '2026-09-18 15:15:07', NULL, '2026-09-18 15:44:07', NULL, '2026-09-18 17:42:00', @demo_admin),
(-2026092301, 'R0001', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 08:23:31', '2026-09-23 08:36:31', '2026-09-23 08:58:31', 10, '2026-09-23 17:07:00', @demo_admin),
(-2026092302, 'P0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-23 08:52:16', '2026-09-23 09:04:16', '2026-09-23 09:08:16', 8, '2026-09-23 17:24:00', @demo_admin),
(-2026092303, 'R0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 08:56:04', '2026-09-23 09:15:04', '2026-09-23 09:22:04', 5, '2026-09-23 17:26:00', @demo_admin),
(-2026092304, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'cancelled', 'high', '2026-09-23 09:43:18', NULL, '2026-09-23 10:02:18', NULL, '2026-09-23 17:45:00', @demo_admin),
(-2026092305, 'R0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 09:46:27', '2026-09-23 10:21:27', '2026-09-23 10:28:27', 10, '2026-09-23 17:06:00', @demo_admin),
(-2026092306, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 09:54:02', '2026-09-23 10:14:02', '2026-09-23 10:19:02', 5, '2026-09-23 17:40:00', @demo_admin),
(-2026092307, 'R0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 10:05:50', '2026-09-23 10:29:50', '2026-09-23 10:36:50', 5, '2026-09-23 17:21:00', @demo_admin),
(-2026092308, 'R0006', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 10:30:39', '2026-09-23 10:33:39', '2026-09-23 10:40:39', 5, '2026-09-23 17:06:00', @demo_admin),
(-2026092309, 'R0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 10:47:06', '2026-09-23 11:12:06', '2026-09-23 11:22:06', 10, '2026-09-23 17:20:00', @demo_admin),
(-2026092310, 'R0008', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 12:15:42', '2026-09-23 12:37:42', '2026-09-23 12:59:42', 10, '2026-09-23 17:25:00', @demo_admin),
(-2026092311, 'P0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-23 12:24:08', '2026-09-23 12:34:08', '2026-09-23 12:46:08', 11, '2026-09-23 17:15:00', @demo_admin),
(-2026092312, 'R0009', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 13:01:55', '2026-09-23 13:21:55', '2026-09-23 13:34:55', 5, '2026-09-23 17:13:00', @demo_admin),
(-2026092313, 'P0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-23 13:05:34', '2026-09-23 13:10:34', '2026-09-23 13:13:34', 8, '2026-09-23 17:08:00', @demo_admin),
(-2026092314, 'P0005', @demo_billing_service, @demo_customer, @demo_customer_name, 'cancelled', 'high', '2026-09-23 13:45:19', NULL, '2026-09-23 14:13:19', NULL, '2026-09-23 17:19:00', @demo_admin),
(-2026092315, 'R0010', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-23 14:47:22', '2026-09-23 14:54:22', '2026-09-23 15:03:22', 5, '2026-09-23 17:13:00', @demo_admin),
(-2026092316, 'P0006', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-23 15:14:27', '2026-09-23 15:25:27', '2026-09-23 15:28:27', 8, '2026-09-23 17:06:00', @demo_admin),
(-2026092317, 'P0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-23 15:35:43', '2026-09-23 15:43:43', '2026-09-23 16:04:43', 11, '2026-09-23 17:25:00', @demo_admin),
(-2026092501, 'R0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 09:16:26', '2026-09-25 09:34:26', '2026-09-25 09:43:26', 5, '2026-09-25 17:17:00', @demo_admin),
(-2026092502, 'P0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-25 10:20:35', '2026-09-25 10:26:35', '2026-09-25 10:31:35', 8, '2026-09-25 17:39:00', @demo_admin),
(-2026092503, 'R0002', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 11:03:07', '2026-09-25 11:09:07', '2026-09-25 11:21:07', 10, '2026-09-25 17:30:00', @demo_admin),
(-2026092504, 'R0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 11:39:39', '2026-09-25 12:03:39', '2026-09-25 12:09:39', 5, '2026-09-25 17:13:00', @demo_admin),
(-2026092505, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 11:51:42', '2026-09-25 12:22:42', '2026-09-25 12:26:42', 5, '2026-09-25 17:14:00', @demo_admin),
(-2026092506, 'R0005', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 12:19:40', '2026-09-25 12:49:40', '2026-09-25 12:58:40', 10, '2026-09-25 17:37:00', @demo_admin),
(-2026092507, 'R0006', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 13:24:12', '2026-09-25 13:35:12', '2026-09-25 13:38:12', 5, '2026-09-25 17:09:00', @demo_admin),
(-2026092508, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-25 13:27:29', '2026-09-25 13:28:29', '2026-09-25 13:41:29', 8, '2026-09-25 17:41:00', @demo_admin),
(-2026092509, 'R0007', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 13:50:46', '2026-09-25 13:59:46', '2026-09-25 14:17:46', 10, '2026-09-25 17:43:00', @demo_admin),
(-2026092510, 'P0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-25 13:57:51', '2026-09-25 14:07:51', '2026-09-25 14:14:51', 11, '2026-09-25 17:29:00', @demo_admin),
(-2026092511, 'R0008', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-25 15:21:52', '2026-09-25 15:33:52', '2026-09-25 15:40:52', 10, '2026-09-25 17:23:00', @demo_admin),
(-2026093001, 'P0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-30 08:13:11', '2026-09-30 08:17:11', '2026-09-30 08:26:11', 8, '2026-09-30 17:12:00', @demo_admin),
(-2026093002, 'P0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-30 08:22:31', '2026-09-30 08:32:31', '2026-09-30 08:43:31', 8, '2026-09-30 17:24:00', @demo_admin),
(-2026093003, 'R0001', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 08:34:01', '2026-09-30 08:54:01', '2026-09-30 09:02:01', 5, '2026-09-30 17:24:00', @demo_admin),
(-2026093004, 'R0002', @demo_payment_service, @demo_customer, @demo_customer_name, 'cancelled', 'normal', '2026-09-30 10:14:15', NULL, '2026-09-30 10:30:15', NULL, '2026-09-30 17:25:00', @demo_admin),
(-2026093005, 'P0003', @demo_payment_service, @demo_customer, @demo_customer_name, 'skipped', 'high', '2026-09-30 10:51:23', '2026-09-30 11:00:23', '2026-09-30 11:05:23', 8, '2026-09-30 17:41:00', @demo_admin),
(-2026093006, 'R0003', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 11:00:50', '2026-09-30 11:04:50', '2026-09-30 11:17:50', 10, '2026-09-30 17:40:00', @demo_admin),
(-2026093007, 'P0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-30 12:51:34', '2026-09-30 13:00:34', '2026-09-30 13:08:34', 8, '2026-09-30 17:17:00', @demo_admin),
(-2026093008, 'R0004', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 13:12:40', '2026-09-30 13:31:40', '2026-09-30 13:34:40', 5, '2026-09-30 17:21:00', @demo_admin),
(-2026093009, 'P0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-30 13:15:40', '2026-09-30 13:24:40', '2026-09-30 13:33:40', 8, '2026-09-30 17:33:00', @demo_admin),
(-2026093010, 'R0005', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 13:53:33', '2026-09-30 14:08:33', '2026-09-30 14:22:33', 5, '2026-09-30 17:43:00', @demo_admin),
(-2026093011, 'P0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-30 14:02:30', '2026-09-30 14:13:30', '2026-09-30 14:29:30', 11, '2026-09-30 17:26:00', @demo_admin),
(-2026093012, 'R0006', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 14:43:07', '2026-09-30 15:06:07', '2026-09-30 15:19:07', 10, '2026-09-30 17:18:00', @demo_admin),
(-2026093013, 'R0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 14:51:42', '2026-09-30 15:24:42', '2026-09-30 15:28:42', 5, '2026-09-30 17:36:00', @demo_admin),
(-2026093014, 'R0008', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 15:09:36', '2026-09-30 15:35:36', '2026-09-30 15:48:36', 5, '2026-09-30 17:19:00', @demo_admin),
(-2026093015, 'R0009', @demo_billing_service, @demo_customer, @demo_customer_name, 'completed', 'normal', '2026-09-30 15:25:16', '2026-09-30 15:44:16', '2026-09-30 15:47:16', 10, '2026-09-30 17:07:00', @demo_admin),
(-2026093016, 'P0007', @demo_payment_service, @demo_customer, @demo_customer_name, 'completed', 'high', '2026-09-30 15:27:34', '2026-09-30 15:35:34', '2026-09-30 15:44:34', 8, '2026-09-30 17:27:00', @demo_admin);

INSERT INTO queue_ticket_history (ticket_id, ticket_number, service_id, customer_id, customer_name, status, priority, created_at, called_at, completed_at, assigned_user_id, archived_at, archived_by)
SELECT demo.ticket_id, demo.ticket_number, demo.service_id, demo.customer_id, demo.customer_name, demo.status, demo.priority, demo.created_at, demo.called_at, demo.completed_at, demo.assigned_user_id, demo.archived_at, demo.archived_by
FROM demo_september_2026_history demo
WHERE NOT EXISTS (
    SELECT 1 FROM queue_ticket_history existing
    WHERE existing.ticket_id = demo.ticket_id
      AND existing.ticket_number = demo.ticket_number
      AND existing.created_at = demo.created_at
);
COMMIT;
DROP TEMPORARY TABLE demo_september_2026_history;

-- Verify the imported demo totals by date.
SELECT DATE(created_at) AS demo_date, COUNT(*) AS tickets,
       SUM(status='completed') AS served, SUM(status='skipped') AS skipped,
       SUM(status='cancelled') AS cancelled
FROM queue_ticket_history
WHERE ticket_id BETWEEN -2026093099 AND -2026090101
  AND created_at >= '2026-09-01' AND created_at < '2026-10-01'
GROUP BY DATE(created_at)
ORDER BY demo_date;

-- Optional removal of ONLY these fixtures, when testing is finished:
-- DELETE FROM queue_ticket_history
-- WHERE ticket_id BETWEEN -2026093099 AND -2026090101
--   AND created_at >= '2026-09-01' AND created_at < '2026-10-01';
