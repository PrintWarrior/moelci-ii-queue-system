<?php
// Admin dashboard bootstrap.
// Debug here when login/session checks, settings POST actions, or initial page data fail.
session_start();
require_once '../includes/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
  header("Location: ../index.php");
  exit;
}

$pdo->exec("
  CREATE TABLE IF NOT EXISTS announcements (
    id INT(11) NOT NULL AUTO_INCREMENT,
    message TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Handle form submissions for settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // User Management
    if (isset($_POST['add_user'])) {
      $username = $_POST['username'];
      $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
      $email = $_POST['email'];
      $first_name = $_POST['first_name'];
      $last_name = $_POST['last_name'];
      $role_id = $_POST['role_id'];

      $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([$username, $password, $email, $first_name, $last_name, $role_id]);
      $user_message = "User added successfully!";
    }

    if (isset($_POST['update_user'])) {
      $user_id = $_POST['user_id'];
      $username = $_POST['username'];
      $email = $_POST['email'];
      $first_name = $_POST['first_name'];
      $last_name = $_POST['last_name'];
      $role_id = $_POST['role_id'];
      $is_active = isset($_POST['is_active']) ? 1 : 0;

      $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, first_name=?, last_name=?, role_id=?, is_active=? WHERE user_id=?");
      $stmt->execute([$username, $email, $first_name, $last_name, $role_id, $is_active, $user_id]);

      // Update teller services if user is a teller
      if ($role_id == 2) { // Assuming 2 is Teller role ID
        // First, remove existing service assignments
        $stmt = $pdo->prepare("DELETE FROM teller_services WHERE user_id = ?");
        $stmt->execute([$user_id]);

        // Add new service assignments
        if (isset($_POST['services']) && is_array($_POST['services'])) {
          foreach ($_POST['services'] as $service_id) {
            $stmt = $pdo->prepare("INSERT INTO teller_services (user_id, service_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $service_id]);
          }
        }
      }

      $user_message = "User updated successfully!";
    }

    if (isset($_POST['delete_user'])) {
      $user_id = $_POST['user_id'];
      $stmt = $pdo->prepare("UPDATE users SET is_active=0 WHERE user_id=?");
      $stmt->execute([$user_id]);
      $user_message = "User deactivated successfully!";
    }
    

    // Operating Hours
    if (isset($_POST['update_hours'])) {
      foreach ($_POST['hours'] as $day => $hours) {
        $open_time = $hours['open_time'] ?: NULL;
        $close_time = $hours['close_time'] ?: NULL;
        $is_closed = isset($hours['is_closed']) ? 1 : 0;

        $stmt = $pdo->prepare("UPDATE operating_hours SET open_time=?, close_time=?, is_closed=? WHERE day_of_week=?");
        $stmt->execute([$open_time, $close_time, $is_closed, $day]);
      }
      $hours_message = "Operating hours updated successfully!";
    }


    // Breaks
    if (isset($_POST['add_break'])) {
      $break_name = $_POST['break_name'];
      $start_time = $_POST['start_time'];
      $end_time = $_POST['end_time'];

      $stmt = $pdo->prepare("INSERT INTO breaks_schedule (break_name, start_time, end_time) VALUES (?, ?, ?)");
      $stmt->execute([$break_name, $start_time, $end_time]);
      $break_message = "Break schedule added successfully!";
    }

    if (isset($_POST['delete_break'])) {
      $break_id = $_POST['break_id'];
      $stmt = $pdo->prepare("UPDATE breaks_schedule SET is_active=0 WHERE id=?");
      $stmt->execute([$break_id]);
      $break_message = "Break schedule removed successfully!";
    }

    // Announcements
    if (isset($_POST['add_announcement'])) {
      $announcement_message = trim($_POST['announcement_message'] ?? '');

      if ($announcement_message !== '') {
        $stmt = $pdo->prepare("INSERT INTO announcements (message) VALUES (?)");
        $stmt->execute([$announcement_message]);
        $announcement_message_success = "Announcement added successfully!";
      }
    }

    if (isset($_POST['delete_announcement'])) {
      $announcement_id = $_POST['announcement_id'];
      $stmt = $pdo->prepare("UPDATE announcements SET is_active=0 WHERE id=?");
      $stmt->execute([$announcement_id]);
      $announcement_message_success = "Announcement removed successfully!";
    }
  } catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
  }
}

// Fetch History
$transaction_history = $pdo->query("
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
    ORDER BY h.archived_at DESC
")->fetchAll();

//LIST
$services = $pdo->query("
    SELECT service_name FROM services ORDER BY service_name
")->fetchAll();

//LIST
$tellers = $pdo->query("
    SELECT DISTINCT 
        u.user_id,
        CONCAT(u.first_name, ' ', u.last_name) AS teller_name
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    WHERE r.role_name = 'Teller'
    ORDER BY teller_name
")->fetchAll();



// Fetch data for settings
$users = $pdo->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.is_active = 1")->fetchAll();

$operating_hours = $pdo->query("SELECT * FROM operating_hours ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")->fetchAll();

$breaks = $pdo->query("SELECT * FROM breaks_schedule WHERE is_active = 1 ORDER BY start_time")->fetchAll();

$announcements = $pdo->query("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC")->fetchAll();

// Add User should only create Teller/Customer accounts from the dashboard.
$roles = $pdo->query("SELECT * FROM roles WHERE role_name IN ('Teller', 'Customer')")->fetchAll();

// Edit User must include every existing role so Admin accounts can be saved without changing role.
$edit_roles = $pdo->query("SELECT * FROM roles ORDER BY role_id")->fetchAll();

$services = $pdo->query("SELECT * FROM services WHERE is_active = 1")->fetchAll();

// Fetch teller services for pre-populating the modal
$teller_services = [];
foreach ($users as $user) {
  if ($user['role_id'] == 2) { // Teller role
    $stmt = $pdo->prepare("SELECT service_id FROM teller_services WHERE user_id = ?");
    $stmt->execute([$user['user_id']]);
    $teller_services[$user['user_id']] = $stmt->fetchAll(PDO::FETCH_COLUMN);
  }
}
?>
