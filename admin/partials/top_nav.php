<!-- Admin header and main section navigation. -->
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
