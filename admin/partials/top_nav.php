<a class="skip-link" href="#main-content">Skip to content</a>
<svg class="svg-definitions" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs><symbol id="icon-dashboard" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></symbol>
<symbol id="icon-transactions" viewBox="0 0 24 24"><path d="M8 3h8l4 4v14H4V3h4Z"/><path d="M14 3v5h6M8 12h8M8 16h5"/></symbol>
<symbol id="icon-analytics" viewBox="0 0 24 24"><path d="M4 3v17h17M8 15v-4M13 15V7M18 15V4"/></symbol>
<symbol id="icon-settings" viewBox="0 0 24 24"><path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="3"/><circle cx="15" cy="17" r="3"/></symbol>
<symbol id="icon-logout" viewBox="0 0 24 24"><path d="M9 4H4v16h5M9 12h12m-4-4 4 4-4 4"/></symbol>
<symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol>
<symbol id="icon-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></symbol>
<symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol>
<symbol id="icon-phone" viewBox="0 0 24 24"><path d="m8 3 3 5-3 3a16 16 0 0 0 5 5l3-3 5 3c-1 8-9 4-14-1S-2 4 8 3Z"/></symbol>
<symbol id="icon-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol><symbol id="icon-moon" viewBox="0 0 24 24"><path d="M20.5 13A9 9 0 0 1 11 3.5 9 9 0 1 0 20.5 13Z"/></symbol>
<symbol id="icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></symbol></defs></svg>
<div class="mobile-bar"><span>MOELCI-II <small>Admin</small></span><button type="button" id="sidebar-toggle" aria-controls="admin-sidebar" aria-expanded="false"><svg class="icon" aria-hidden="true"><use href="#icon-menu"></use></svg><span>Menu</span></button></div>
<button type="button" class="sidebar-backdrop" aria-label="Close navigation" tabindex="-1" hidden></button>
<aside class="admin-sidebar" id="admin-sidebar" aria-label="Admin sidebar">
  <div class="sidebar-brand"><img src="../assets/imgs/moelci_logo.png" alt="MOELCI-II logo" width="48" height="48"><div><strong>MOELCI-II</strong><span>Queue Management</span></div></div>
  <span class="sidebar-label">WORKSPACE</span>
  <nav class="sidebar-nav" aria-label="Main navigation">
    <button type="button" class="sidebar-link active" data-section="dashboard" aria-controls="dashboard" aria-current="page" onclick="showSection('dashboard', this)"><svg class="icon" aria-hidden="true"><use href="#icon-dashboard"></use></svg><span>Dashboard</span></button>
    <button type="button" class="sidebar-link" data-section="transactions" aria-controls="transactions" onclick="showSection('transactions', this)"><svg class="icon" aria-hidden="true"><use href="#icon-transactions"></use></svg><span>Transactions</span></button>
    <button type="button" class="sidebar-link" data-section="analytics" aria-controls="analytics" onclick="showSection('analytics', this)"><svg class="icon" aria-hidden="true"><use href="#icon-analytics"></use></svg><span>Queue analytics</span></button>
    <button type="button" class="sidebar-link" data-section="settings" aria-controls="settings" onclick="showSection('settings', this)"><svg class="icon" aria-hidden="true"><use href="#icon-settings"></use></svg><span>Settings</span></button>
  </nav>
  <div class="sidebar-bottom"><button type="button" class="theme-toggle" id="theme-toggle" role="switch" aria-checked="false" aria-label="Dark mode" hidden><svg class="icon" aria-hidden="true"><use id="theme-icon" href="#icon-moon"></use></svg><span>Dark mode</span><span class="theme-switch" aria-hidden="true"></span></button><div class="workspace-note"><span class="workspace-dot"></span>Administration workspace</div><a class="sidebar-logout" data-confirm-click="Sign out of your account?" href="../includes/logout.php"><svg class="icon" aria-hidden="true"><use href="#icon-logout"></use></svg>Sign out</a></div>
</aside>
