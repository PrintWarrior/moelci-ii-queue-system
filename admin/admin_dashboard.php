<?php
require_once __DIR__ . '/includes/admin_bootstrap.php';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<body class="admin-app">
  <?php include __DIR__ . '/partials/top_nav.php'; ?>
  <main class="admin-main" id="main-content">
    <header class="page-header">
      <div>
        <span class="page-eyebrow">MOELCI-II / ADMINISTRATION</span>
        <h1 id="page-title">Dashboard</h1>
        <p id="page-description">An overview of your tellers and today's queue.</p>
      </div>
      <div class="admin-profile"><span class="profile-avatar" aria-hidden="true"><svg class="icon"><use href="#icon-user"></use></svg></span><div><strong><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></strong><span>Administrator</span></div></div>
    </header>
    <?php include __DIR__ . '/partials/dashboard_section.php'; ?>
    <?php include __DIR__ . '/partials/transactions_section.php'; ?>
    <?php include __DIR__ . '/partials/analytics_section.php'; ?>
    <?php include __DIR__ . '/partials/settings_section.php'; ?>
  </main>
  <?php include __DIR__ . '/partials/edit_user_modal.php'; ?>
  <?php include __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
