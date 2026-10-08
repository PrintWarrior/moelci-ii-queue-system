<!-- Settings tab: user management, transaction history filters, operating hours, and break schedules. -->
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
          <a class="nav-link" href="#announcements">Announcements</a>
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
            <tbody id="usersTableBody">
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
        <div class="table-pagination" id="usersPagination"></div>
      </div>

      <!-- Announcements Tab -->
      <div id="announcements" class="tab-content">
        <?php if (isset($announcement_message_success)): ?>
          <div class="alert alert-success"><?php echo $announcement_message_success; ?></div>
        <?php endif; ?>

        <form method="POST" action="#announcements" class="settings-section">
          <h5>Monitor Announcements</h5>
          <textarea
            name="announcement_message"
            class="form-control"
            rows="4"
            maxlength="1000"
            placeholder="Example: We will have a party coming Friday. No services available."
            required
          ></textarea>
          <button type="submit" name="add_announcement" class="btn btn-primary mt-3">Add Announcement</button>
        </form>

        <h5 class="mt-4">Active Announcements</h5>
        <div class="break-list">
          <?php if (empty($announcements)): ?>
            <div class="p-2 border-bottom text-muted">No active announcements.</div>
          <?php endif; ?>

          <?php foreach ($announcements as $announcement): ?>
            <div class="d-flex justify-content-between align-items-start gap-3 p-2 border-bottom">
              <span><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></span>
              <form method="POST" action="#announcements" style="display:inline;">
                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                <button type="submit" name="delete_announcement" class="btn btn-sm btn-danger">Remove</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      </div>


      
      <div id="history" class="tab-content">
  <h5>Transaction History</h5>
<div class="row mb-2">
  <div class="col-md-12 mb-3">
    <form method="GET" action="export_history.php" class="d-flex flex-wrap align-items-center gap-2">
      <select name="period" class="form-control" style="max-width: 180px;">
        <option value="daily">Daily</option>
        <option value="weekly">Weekly</option>
        <option value="monthly">Monthly</option>
        <option value="yearly">Yearly</option>
      </select>
      <input type="date" name="date" class="form-control" style="max-width: 180px;" value="<?php echo date('Y-m-d'); ?>">
      <button type="submit" class="btn btn-success">Export Excel</button>
    </form>
  </div>

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
  <div class="table-pagination" id="historyPagination"></div>
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
          <?php
            $status = strtolower($tx['status']);
            $status_class = match ($status) {
              'completed' => 'success',
              'waiting' => 'warning text-dark',
              'in_progress' => 'primary',
              'skipped' => 'secondary',
              'cancelled' => 'danger',
              default => 'dark',
            };
            $status_label = ucwords(str_replace('_', ' ', $status));
          ?>
          <tr>
  <td><?= htmlspecialchars($tx['ticket_number']) ?></td>
  <td><?= htmlspecialchars($tx['customer_name']) ?></td>
  <td><?= htmlspecialchars($tx['service_name']) ?></td>
  <td><?= htmlspecialchars($tx['teller_name'] ?? 'N/A') ?></td>
  <td><span class="badge bg-<?= $status_class ?>"><?= htmlspecialchars($status_label) ?></span></td>
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

        // Ticket number search only.
        if (ticketSearch && !ticketNo.includes(ticketSearch)) visible = false;

        // Service filter.
        if (service && serviceName !== service) visible = false;

        // Teller filter.
        if (teller && tellerName !== teller) visible = false;

        // Date filter.
        if (date && createdDate !== date) visible = false;

        // Customer type filter by R/P ticket prefix.
        if (customerType && !ticketNo.startsWith(customerType)) visible = false;

        rows[i].dataset.filterMatch = visible ? "1" : "0";
    }

    paginateHistoryTable(1);
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
  
