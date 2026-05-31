<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
  header("Location: ../index.php");
  exit;
}

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
$roles = $pdo->query("SELECT * FROM roles WHERE role_name IN ('Teller', 'Customer')")->fetchAll();
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
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MOELCI-II Admin Dashboard</title>
  <link rel="stylesheet" href="../css/admin_board.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
  <style>
    .settings-tab {
      margin-bottom: 20px;
    }

    .settings-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    .time-inputs {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .holiday-list,
    .break-list {
      max-height: 200px;
      overflow-y: auto;
    }

    .user-actions {
      display: flex;
      gap: 5px;
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .service-checkboxes {
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #dee2e6;
      padding: 10px;
      border-radius: 5px;
    }

    .service-checkbox-item {
      margin-bottom: 8px;
    }

    .modal-lg {
      max-width: 600px;
    }

    .history-table-wrapper {
    max-height: 375px;      /* adjust as needed */
    overflow-y: auto;
    border: 1px solid #ddd;
}

  </style>
</head>

<body class="container-fluid p-4">

  <!-- Header -->
  <div class="header">MOELCI-II Queueing System - Admin Dashboard</div>
  <div class="mb-3">Welcome, <?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?> (Admin)</div>

  <!-- Navbar -->
  <div class="navbar text-white">
    <button class="btn btn-light btn-sm active" onclick="showSection('dashboard', this)">Dashboard</button>
    <button class="btn btn-light btn-sm" onclick="showSection('transactions', this)">View All Transactions</button>
    <button class="btn btn-light btn-sm" onclick="showSection('analytics', this)">View Queue Analytics</button>
    <button class="btn btn-light btn-sm" onclick="showSection('settings', this)">Settings</button>
    <button class="btn btn-danger btn-sm float-end" style="background-color: red; color: black;" onclick="location.href='../includes/logout.php'">Logout</button>
  </div>

  <!-- Dashboard Section -->
  <div id="dashboard" class="section">
    <div class="row mt-3" id="tellers-container"></div>
    <div class="reminders">
      <p>📅 Seminar every Friday (for Application and Notice of Billing)</p>
      <p>📞 MOELCI-II Hotline: <span class="hotline">0946 024 5379</span></p>
    </div>
  </div>

  <!-- Transactions Section -->
<div id="transactions" class="section" style="display:none;">
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="section-title mb-0">All Transactions</h6>
           <!-- <button class="btn btn-danger btn-sm" onclick="confirmClearTransactions()">
                Clear All Transactions
            </button> -->
        </div>
        <div class="table-scroll">
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Ticket No</th>
                        <th>Customer Name</th>
                        <th>Service</th>
                        <th>Teller</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody id="transactions-list-page"></tbody>
            </table>
        </div>
    </div>
</div>

  <!-- Analytics Section -->
  <div id="analytics" class="section" style="display:none;">
    <div class="card p-3">
      <h6 class="section-title">Queue Analytics</h6>
      <div id="analytics-summary" class="mb-3"></div>
      <canvas id="tellerChart" height="50"></canvas>
      <div style="width: 550px; height: 450px; margin: auto;">
        <canvas id="serviceChart" height="20" class="mt-4"></canvas>
      </div>
    </div>
  </div>

  <!-- Settings Section -->
  <div id="settings" class="section" style="display:none;">
    <div class="card p-3">
      <h6 class="section-title">System Settings</h6>

      <!-- Settings Tabs -->
      <ul class="nav nav-tabs" id="settingsTabs">
        <li class="nav-item">
          <a class="nav-link active" href="#userManagement">User Management</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#operatingHours">Operating Hours</a>
        </li>
        <li class="nav-item">
  <a class="nav-link" href="#history">History</a>
</li>
      </ul>

      <!-- User Management Tab -->
      <div id="userManagement" class="tab-content active">
        <?php if (isset($user_message)): ?>
          <div class="alert alert-success"><?php echo $user_message; ?></div>
        <?php endif; ?>

        <h5>Add New User</h5>
        <form method="POST" class="settings-section">
          <div class="form-grid">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="email" name="email" placeholder="Email">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <select name="role_id" id="roleSelect" required onchange="toggleServiceAssignment()">
              <option value="">Select Role</option>
              <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['role_id']; ?>"><?php echo $role['role_name']; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div id="serviceAssignment" style="display: none;">
            <h6 class="mt-3">Assign Services (for Tellers)</h6>
            <div class="service-checkboxes">
              <?php foreach ($services as $service): ?>
                <div class="service-checkbox-item form-check">
                  <input class="form-check-input" type="checkbox" name="services[]" value="<?php echo $service['service_id']; ?>" id="service_<?php echo $service['service_id']; ?>">
                  <label class="form-check-label" for="service_<?php echo $service['service_id']; ?>">
                    <?php echo htmlspecialchars($service['service_name']); ?>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <button type="submit" name="add_user" class="btn btn-primary mt-3">Add User</button>
        </form>

        <h5 class="mt-4">Existing Users</h5>
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Username</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Assigned Services</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
                <tr>
                  <td><?php echo htmlspecialchars($user['username']); ?></td>
                  <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                  <td><?php echo htmlspecialchars($user['email']); ?></td>
                  <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                  <td>
                    <?php
                    if ($user['role_id'] == 2 && isset($teller_services[$user['user_id']])) {
                      $assigned_services = [];
                      foreach ($teller_services[$user['user_id']] as $service_id) {
                        foreach ($services as $service) {
                          if ($service['service_id'] == $service_id) {
                            $assigned_services[] = $service['service_name'];
                            break;
                          }
                        }
                      }
                      echo implode(', ', $assigned_services) ?: 'No services assigned';
                    } else {
                      echo 'N/A';
                    }
                    ?>
                  </td>
                  <td><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></td>
                  <td class="user-actions">
                    <button class="btn btn-sm btn-warning" onclick="openEditModal(<?php echo $user['user_id']; ?>)">Edit</button>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                      <button type="submit" name="delete_user" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
     


      
      <div id="history" class="tab-content">
  <h5>Transaction History</h5>
<div class="row mb-2">
  <div class="col-md-3">
    <select id="serviceFilter" class="form-control" onchange="applyFilters()">
      <option value="">All Services</option>
      <?php foreach ($services as $s): ?>
        <option value="<?= htmlspecialchars($s['service_name']) ?>">
          <?= htmlspecialchars($s['service_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <select id="tellerFilter" class="form-control" onchange="applyFilters()">
      <option value="">All Tellers</option>
      <?php foreach ($tellers as $t): ?>
        <option value="<?= htmlspecialchars($t['teller_name']) ?>">
          <?= htmlspecialchars($t['teller_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-3">
    <input type="date" id="dateFilter" class="form-control" onchange="applyFilters()">
  </div>
  <div class="col-md-3">
  <select id="customerTypeFilter" class="form-control" onchange="applyFilters()">
    <option value="">All Customer Types</option>
    <option value="R">Regular</option>
    <option value="P">PWD</option>
  </select>
</div>


  <div class="col-md-3">
    <input
      type="text"
      id="ticketSearch"
      class="form-control"
      placeholder="Search Ticket Number..."
      onkeyup="applyFilters()"
    >
  </div>
</div>

  <div class="history-table-wrapper">

  <table id="historyTable" class="table table-striped table-sm">
      <thead>
        <tr>
        <th>Ticket No</th>
        <th>Customer</th>
        <th>Service</th>
        <th>Teller</th>
        <th>Status</th>
        <th>Created</th>
        <th>Completed</th>
      </tr>
      </thead>
      <tbody>
        <?php foreach ($transaction_history as $tx): ?>
          <tr>
  <td><?= htmlspecialchars($tx['ticket_number']) ?></td>
  <td><?= htmlspecialchars($tx['customer_name']) ?></td>
  <td><?= htmlspecialchars($tx['service_name']) ?></td>
  <td><?= htmlspecialchars($tx['teller_name'] ?? 'N/A') ?></td>
  <td><?= htmlspecialchars($tx['status']) ?></td>
  <td><?= $tx['created_at'] ?></td>
  <td><?= $tx['completed_at'] ?? 'N/A' ?></td>
</tr>

        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script> //SEARCH NUMBER
function searchTicketNumber() {
    const input = document.getElementById("ticketSearch").value.toUpperCase();
    const table = document.getElementById("historyTable");
    const rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
        const ticketCell = rows[i].getElementsByTagName("td")[0];
        if (ticketCell) {
            const txtValue = ticketCell.textContent || ticketCell.innerText;
            rows[i].style.display = txtValue.toUpperCase().includes(input)
                ? ""
                : "none";
        }
    }
}
</script>

<script> //FILTER
function applyFilters() {
    const service = document.getElementById("serviceFilter").value.toUpperCase();
    const teller = document.getElementById("tellerFilter").value.toUpperCase();
    const date = document.getElementById("dateFilter").value;
    const customerType = document.getElementById("customerTypeFilter").value.toUpperCase();
    const ticketSearch = document.getElementById("ticketSearch").value.toUpperCase();

    const table = document.getElementById("historyTable");
    const rows = table.getElementsByTagName("tr");

    for (let i = 1; i < rows.length; i++) {
        const cols = rows[i].getElementsByTagName("td");

        const ticketNo = cols[0].innerText.toUpperCase();
        const serviceName = cols[2].innerText.toUpperCase();
        const tellerName = cols[3].innerText.toUpperCase();
        const createdDate = cols[5].innerText.split(" ")[0]; // YYYY-MM-DD

        let visible = true;

        // 🔍 Ticket number search (ONLY ticket number)
        if (ticketSearch && !ticketNo.includes(ticketSearch)) visible = false;

        // 🧾 Service filter
        if (service && serviceName !== service) visible = false;

        // 👨‍💼 Teller filter
        if (teller && tellerName !== teller) visible = false;

        // 📅 Date filter
        if (date && createdDate !== date) visible = false;

        // 🧑‍🦽 Customer type filter (R / P prefix)
        if (customerType && !ticketNo.startsWith(customerType)) visible = false;

        rows[i].style.display = visible ? "" : "none";
    }
}
</script>




      <!-- Operating Hours Tab -->
      <div id="operatingHours" class="tab-content">
        <?php if (isset($hours_message)): ?>
          <div class="alert alert-success"><?php echo $hours_message; ?></div>
        <?php endif; ?>

        <form method="POST" class="settings-section">
          <h5>Regular Operating Hours</h5>
          <?php foreach ($operating_hours as $day): ?>
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
              <strong><?php echo $day['day_of_week']; ?></strong>
              <div class="time-inputs">
                <input type="time" name="hours[<?php echo $day['day_of_week']; ?>][open_time]" value="<?php echo $day['open_time']; ?>">
                <span>to</span>
                <input type="time" name="hours[<?php echo $day['day_of_week']; ?>][close_time]" value="<?php echo $day['close_time']; ?>">
                <label class="ms-3">
                  <input type="checkbox" name="hours[<?php echo $day['day_of_week']; ?>][is_closed]" <?php echo $day['is_closed'] ? 'checked' : ''; ?>> Closed
                </label>
              </div>
            </div>
          <?php endforeach; ?>
          <button type="submit" name="update_hours" class="btn btn-primary mt-3">Update Hours</button>
        </form>

        <div class="row mt-4">

          <div class="col-md-6">
            <h5>Break Schedules</h5>
            <?php if (isset($break_message)): ?>
              <div class="alert alert-success"><?php echo $break_message; ?></div>
            <?php endif; ?>

            <form method="POST" class="mb-3">
              <div class="input-group">
                <input type="text" name="break_name" placeholder="Break Name" class="form-control" required>
                <input type="time" name="start_time" class="form-control" required>
                <input type="time" name="end_time" class="form-control" required>
                <button type="submit" name="add_break" class="btn btn-success">Add</button>
              </div>
            </form>

            <div class="break-list">
              <?php foreach ($breaks as $break): ?>
                <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                  <span><?php echo htmlspecialchars($break['break_name']); ?> (<?php echo $break['start_time'] . ' - ' . $break['end_time']; ?>)</span>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="break_id" value="<?php echo $break['id']; ?>">
                    <button type="submit" name="delete_break" class="btn btn-sm btn-danger">Remove</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" id="editUserForm">
          <div class="modal-body">
            <input type="hidden" name="user_id" id="editUserId">

            <div class="form-grid mb-3">
              <div>
                <label class="form-label">Username</label>
                <input type="text" name="username" id="editUsername" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" id="editEmail" class="form-control">
              </div>
              <div>
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" id="editFirstName" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" id="editLastName" class="form-control" required>
              </div>
              <div>
                <label class="form-label">Role</label>
                <select name="role_id" id="editRoleId" class="form-control" required onchange="toggleEditServiceAssignment()">
                  <option value="">Select Role</option>
                  <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['role_id']; ?>"><?php echo $role['role_name']; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label class="form-label">Status</label>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1" checked>
                  <label class="form-check-label" for="editIsActive">Active</label>
                </div>
              </div>
            </div>

            <div id="editServiceAssignment" style="display: none;">
              <h6>Assign Services (for Tellers)</h6>
              <div class="service-checkboxes">
                <?php foreach ($services as $service): ?>
                  <div class="service-checkbox-item form-check">
                    <input class="form-check-input service-checkbox" type="checkbox" name="services[]" value="<?php echo $service['service_id']; ?>" id="edit_service_<?php echo $service['service_id']; ?>">
                    <label class="form-check-label" for="edit_service_<?php echo $service['service_id']; ?>">
                      <?php echo htmlspecialchars($service['service_name']); ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function showSection(id, btn) {
      document.querySelectorAll('.section').forEach(sec => sec.style.display = "none");
      document.getElementById(id).style.display = "block";
      document.querySelectorAll('.navbar .btn').forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    }

    function confirmClearTransactions() {
    if (confirm('⚠️ ARE YOU SURE YOU WANT TO CLEAR ALL TRANSACTIONS?\n\nThis action will permanently delete ALL transaction records and cannot be undone!')) {
        clearAllTransactions();
    }
}

function clearAllTransactions() {
    // Show loading state
    const clearBtn = document.querySelector('button[onclick="confirmClearTransactions()"]');
    const originalText = clearBtn.innerHTML;
    clearBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Clearing...';
    clearBtn.disabled = true;

    fetch('../includes/clear_transactions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ All transactions have been cleared successfully!');
            // Reload the transactions list
            loadData();
        } else {
            alert('❌ Error clearing transactions: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Network error occurred while clearing transactions');
    })
    .finally(() => {
        // Restore button state
        clearBtn.innerHTML = originalText;
        clearBtn.disabled = false;
    });
}

    // Settings tabs functionality
    document.addEventListener('DOMContentLoaded', function() {
      const tabLinks = document.querySelectorAll('#settingsTabs .nav-link');
      tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const targetId = this.getAttribute('href').substring(1);

          document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
          });

          document.getElementById(targetId).classList.add('active');

          tabLinks.forEach(tab => tab.classList.remove('active'));
          this.classList.add('active');
        });
      });
    });

    // Toggle service assignment for new user form
    function toggleServiceAssignment() {
      const roleSelect = document.getElementById('roleSelect');
      const serviceAssignment = document.getElementById('serviceAssignment');

      if (roleSelect.value == '2') { // Teller role ID
        serviceAssignment.style.display = 'block';
      } else {
        serviceAssignment.style.display = 'none';
        // Uncheck all service checkboxes
        document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
          checkbox.checked = false;
        });
      }
    }

    // Toggle service assignment for edit modal
    function toggleEditServiceAssignment() {
      const roleSelect = document.getElementById('editRoleId');
      const serviceAssignment = document.getElementById('editServiceAssignment');

      if (roleSelect.value == '2') { // Teller role ID
        serviceAssignment.style.display = 'block';
      } else {
        serviceAssignment.style.display = 'none';
        // Uncheck all service checkboxes
        document.querySelectorAll('.service-checkbox').forEach(checkbox => {
          checkbox.checked = false;
        });
      }
    }

    // Open edit modal with user data
    function openEditModal(userId) {
      // Fetch user data (in a real app, you'd fetch from server via AJAX)
      // For now, we'll use the data from PHP
      const user = <?php echo json_encode($users); ?>.find(u => u.user_id == userId);
      const tellerServices = <?php echo json_encode($teller_services); ?>;

      if (user) {
        document.getElementById('editUserId').value = user.user_id;
        document.getElementById('editUsername').value = user.username;
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editFirstName').value = user.first_name;
        document.getElementById('editLastName').value = user.last_name;
        document.getElementById('editRoleId').value = user.role_id;
        document.getElementById('editIsActive').checked = user.is_active == 1;

        // Toggle service assignment based on role
        toggleEditServiceAssignment();

        // Set assigned services for tellers
        if (user.role_id == 2 && tellerServices[user.user_id]) {
          document.querySelectorAll('.service-checkbox').forEach(checkbox => {
            checkbox.checked = tellerServices[user.user_id].includes(parseInt(checkbox.value));
          });
        } else {
          document.querySelectorAll('.service-checkbox').forEach(checkbox => {
            checkbox.checked = false;
          });
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
      }
    }

    let tellerChart, serviceChart;

    function loadData() {
      fetch("admin_data.php")
        .then(res => res.json())
        .then(data => {
          // Dashboard -> Tellers
          let tellersHtml = "";
          data.tellers.forEach(t => {
            let statusClass = (t.status.toLowerCase() === "active") ? "status-active" : "status-idle";
            tellersHtml += `
    <div class="col-md-3">
        <div class="card p-3">
            <h6>${t.teller_name}</h6>
            <p><b>Service:</b> ${t.services || 'None Assigned'}</p>
            <p><b>Status:</b> <span class="status-indicator ${statusClass}">${t.status || 'N/A'}</span></p>
              <p><b>Queue Length:</b> ${t.queue_length || 0}</p>
              <p><b>Next Customer:</b> ${t.next_customer || 'None'}</p>
              <p><b>Average wait time:</b> ${t.avg_wait || 0} mins</p>
            </div>
          </div>`;
          });
          document.getElementById("tellers-container").innerHTML = tellersHtml;

          // Transactions
          let txHtml = "";
          data.transactions.forEach(tx => {
            txHtml += `<tr>
          <td>${tx.ticket_number}</td>
          <td>${tx.customer_name}</td>
          <td>${tx.service_name}</td>
          <td>${tx.teller_name}</td>
          <td>${tx.status}</td>
          <td>${tx.created_at}</td>
          <td>${tx.completed_at || 'N/A'}</td>
        </tr>`;
          });
          document.getElementById("transactions-list-page").innerHTML = txHtml;

          // Analytics Summary
          let summaryHtml = `
        <p><b>Total Customers Served:</b> ${data.analytics.total_served}</p>
        <p><b>Currently Waiting:</b> ${data.analytics.currently_waiting}</p>
        <p><b>Average Wait Time:</b> ${data.analytics.avg_wait} mins</p>
        <p><b>Busiest Service:</b> ${data.analytics.busiest_service}</p>
        <p><b>Least Busiest Service:</b> ${data.analytics.least_busiest_service}</p>
        <p><b>Most Active Teller:</b> ${data.analytics.most_active_teller}</p>
        <p><b>Least Active Teller:</b> ${data.analytics.least_active_teller}</p>`;
          document.getElementById("analytics-summary").innerHTML = summaryHtml;

          // Charts
          let tellerLabels = data.tellers.map(t => t.teller_name);
          let tellerQueues = data.tellers.map(t => t.queue_length);
          if (tellerChart) tellerChart.destroy();
          tellerChart = new Chart(document.getElementById("tellerChart"), {
            type: 'bar',
            data: {
              labels: tellerLabels,
              datasets: [{
                label: 'Queue Length per Teller',
                data: tellerQueues,
                backgroundColor: '#1b263b'
              }]
            }
          });

          let serviceLabels = data.service_stats.map(s => s.service_name);
          let serviceCounts = data.service_stats.map(s => s.ticket_count);
          let totalTickets = serviceCounts.reduce((a, b) => a + b, 0);

          if (serviceChart) serviceChart.destroy();
          serviceChart = new Chart(document.getElementById("serviceChart"), {
            type: 'pie',
            data: {
              labels: serviceLabels,
              datasets: [{
                data: serviceCounts,
                backgroundColor: [
                  '#457b9d', '#e63946', '#2a9d8f', '#f4a261', '#9d4edd',
                  '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57',
                  '#ff9ff3', '#54a0ff', '#5f27cd', '#00d2d3', '#ff9f43'
                ]
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'right',
                  labels: {
                    boxWidth: 12,
                    padding: 15
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const label = context.label || '';
                      const value = context.raw || 0;
                      const percentage = totalTickets > 0 ? ((value / totalTickets) * 100).toFixed(1) : 0;
                      return `${label}: ${value} (${percentage}%)`;
                    }
                  }
                },
                // This plugin displays the numbers directly on the chart
                datalabels: {
                  color: '#fff',
                  font: {
                    weight: 'bold',
                    size: 11
                  },
                  formatter: (value, context) => {
                    const percentage = totalTickets > 0 ? ((value / totalTickets) * 50).toFixed(1) : 0;
                    return `${value}\n(${percentage}%)`;
                  },
                  textAlign: 'center',
                  textStrokeColor: 'rgba(0,0,0,0.5)',
                  textStrokeWidth: 2
                }
              }
            },
            plugins: [ChartDataLabels] // Add this plugin
          });

          // Settings
          let settingsHtml = `
        <h6 class="section-title">System Settings</h6>
        <p><b>Total Services:</b> ${data.settings.total_services}</p>
        <p><b>Active Tellers:</b> ${data.settings.active_tellers}</p>
        <p><b>Total Customers Today:</b> ${data.settings.customers_today}</p>
        <p><b>System Status:</b> <span class="status-active">Active</span></p>`;
          document.getElementById("settings-page").innerHTML = settingsHtml;
        });
    }

    setInterval(loadData, 300000);
    loadData();
  </script>

</body>

</html>