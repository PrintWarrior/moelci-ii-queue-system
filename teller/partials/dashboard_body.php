<body class="teller-app">
    <div class="teller-screen" id="tellerScreen">
    <svg class="svg-definitions" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs><symbol id="icon-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol><symbol id="icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></symbol><symbol id="icon-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol><symbol id="icon-next" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol><symbol id="icon-call" viewBox="0 0 24 24"><path d="M4 10v4h4l8 5V5l-8 5H4ZM19 8a8 8 0 0 1 0 8"/></symbol><symbol id="icon-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/></symbol><symbol id="icon-logout" viewBox="0 0 24 24"><path d="M9 4H4v16h5M9 12h12m-4-4 4 4-4 4"/></symbol><symbol id="icon-moon" viewBox="0 0 24 24"><path d="M20.5 13A9 9 0 0 1 11 3.5 9 9 0 1 0 20.5 13Z"/></symbol><symbol id="icon-sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></symbol></defs></svg>
    <header class="workspace-bar">
        <div class="brand"><img src="../assets/imgs/moelci_logo.png" alt="MOELCI-II logo" width="44" height="44"><div><strong>MOELCI-II</strong><span>Queue Management</span></div></div>
        <div class="workspace-tools">
            <button type="button" id="theme-toggle" class="theme-toggle" role="switch" aria-checked="false" aria-label="Dark mode" hidden><svg class="icon" aria-hidden="true"><use id="theme-icon" href="#icon-moon"></use></svg><span>Dark mode</span></button>
            <a href="../includes/logout.php" data-confirm-click="Sign out of your account?" class="logout-link" aria-label="Sign out"><svg class="icon" aria-hidden="true"><use href="#icon-logout"></use></svg><span>Sign out</span></a>
        </div>
    </header>
    <main class="dashboard-container">
        <div class="page-heading"><div><span class="eyebrow">TELLER WORKSPACE</span><h1>Welcome, <?php echo htmlspecialchars($teller_name); ?></h1><p>Keep the queue moving, one customer at a time.</p></div><div class="datetime-block"><svg class="icon" aria-hidden="true"><use href="#icon-clock"></use></svg><span id="datetime"></span></div></div>
        <?php if (!$teller_available): ?>
            <div class="queue-notice notice-warning" role="status"><svg class="icon" aria-hidden="true"><use href="#icon-info"></use></svg><div><strong>New customer calls are paused</strong><p>You are marked unavailable by the admin. You can finish your current customer. Ask the admin to mark you available before calling another.</p></div></div>
        <?php elseif ($priority_fallback): ?>
            <div class="queue-notice" role="status"><svg class="icon" aria-hidden="true"><use href="#icon-info"></use></svg><div><strong>Priority coverage is active</strong><p><?php echo htmlspecialchars($priority_teller_name); ?> is unavailable. You are serving PWD customers first, then regular customers.</p></div></div>
        <?php endif; ?>
        <section class="stats-grid" aria-label="Today's queue statistics">
            <div class="stat-card"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-clock"></use></svg></span><div><span class="stat-label">Waiting</span><strong class="stat-number"><?php echo (int) ($stats['waiting_count'] ?? 0); ?></strong></div></div>
            <div class="stat-card"><span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-user"></use></svg></span><div><span class="stat-label">In progress</span><strong class="stat-number"><?php echo (int) ($stats['in_progress_count'] ?? 0); ?></strong></div></div>
            <div class="stat-card"><span class="stat-icon stat-completed"><svg class="icon" aria-hidden="true"><use href="#icon-check"></use></svg></span><div><span class="stat-label">Served today</span><strong class="stat-number"><?php echo (int) ($stats['completed_today'] ?? 0); ?></strong></div></div>
        </section>
        <div class="queue-grid">
            <section class="now-serving queue-card" aria-labelledby="current-title">
                <div class="card-heading"><h2 id="current-title">Now serving</h2><span class="status-chip <?php echo $current ? 'status-active' : ''; ?>"><?php echo $current ? 'In progress' : 'Idle'; ?></span></div>
                <?php if ($current): ?>
                    <span class="ticket-caption">CURRENT TICKET</span>
                    <div class="ticket-number"><?php echo htmlspecialchars($current['ticket_number']); ?></div>
                    <div class="ticket-details"><span><?php echo htmlspecialchars($current['service_name']); ?></span><span class="priority-chip priority-<?php echo $current['priority'] === 'high' ? 'high' : 'normal'; ?>"><?php echo htmlspecialchars(ucfirst($current['priority'])); ?> priority</span></div>
                <?php else: ?>
                    <div class="empty-symbol"><svg class="icon" aria-hidden="true"><use href="#icon-user"></use></svg></div><h3>Ready for the next customer</h3><p class="empty-description">No customer is currently being served.</p>
                <?php endif; ?>
            </section>
            <section class="next-customer queue-card" aria-labelledby="next-title">
                <div class="card-heading"><h2 id="next-title">Up next</h2><svg class="icon" aria-hidden="true"><use href="#icon-next"></use></svg></div>
                <?php if ($next): ?>
                    <span class="ticket-caption">NEXT TICKET</span><div class="next-ticket-number" id="nextTicketNumber"><?php echo htmlspecialchars($next['ticket_number']); ?></div>
                    <p class="next-service"><?php echo htmlspecialchars($next['service_name']); ?></p><p class="queue-hint">Call this ticket when you are ready.</p>
                <?php else: ?>
                    <div class="empty-symbol"><svg class="icon" aria-hidden="true"><use href="#icon-check"></use></svg></div><h3><?php echo $teller_available ? 'Your queue is clear' : 'Calls are paused'; ?></h3><p class="empty-description"><?php echo $teller_available ? 'No customers are waiting for your services.' : 'New customer calls are paused while you are unavailable.'; ?></p>
                <?php endif; ?>
            </section>
        </div>
        <section class="actions-panel" aria-labelledby="actions-title"><div class="actions-heading"><h2 id="actions-title">Queue actions</h2><p>Choose the next step for your customer.</p></div>
            <form method="POST" class="buttons" id="actionForm">
                <button type="submit" name="call_next" data-confirm="<?php echo $current ? ($next ? 'Skip the current customer and call the next?' : 'Skip the current customer?') : 'Call the next customer?'; ?>" class="queue-button btn-call" <?php echo (!$teller_available || (!$next && !$current)) ? 'disabled' : ''; ?>><svg class="icon" aria-hidden="true"><use href="#icon-next"></use></svg>Call next customer</button>
                <button type="submit" name="mark_served" data-confirm="<?php echo ($next && $teller_available) ? 'Mark this customer as served and call the next?' : 'Mark this customer as served?'; ?>" class="queue-button btn-served" <?php echo !$current ? 'disabled' : ''; ?>><svg class="icon" aria-hidden="true"><use href="#icon-check"></use></svg>Mark as served</button>
                <button type="submit" name="callback_current" data-confirm="Call this customer again?" class="queue-button btn-callback" <?php echo !$current ? 'disabled' : ''; ?>><svg class="icon" aria-hidden="true"><use href="#icon-call"></use></svg>Call back customer</button>
            </form>
            <p class="action-note">Calling next skips any current customer. Marking served calls the next waiting customer when you are available.</p>
        </section>
        <footer class="workspace-footer"><span>MOELCI-II · Teller workspace</span><span>Queue updates automatically every 10 seconds</span></footer>
    </main>
    </div>
