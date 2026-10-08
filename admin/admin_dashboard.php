<?php
// Main admin page: keep this file small; debug details live in includes/ and partials/.
require_once __DIR__ . '/includes/admin_bootstrap.php';
?>
<?php include __DIR__ . '/partials/head.php'; ?>
<body class="container-fluid p-4">
  <?php include __DIR__ . '/partials/top_nav.php'; ?>
  <?php include __DIR__ . '/partials/dashboard_section.php'; ?>
  <?php include __DIR__ . '/partials/transactions_section.php'; ?>
  <?php include __DIR__ . '/partials/analytics_section.php'; ?>
  <?php include __DIR__ . '/partials/settings_section.php'; ?>
  <?php include __DIR__ . '/partials/edit_user_modal.php'; ?>
  <?php include __DIR__ . '/partials/scripts.php'; ?>
</body>
</html>
