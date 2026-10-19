<?php
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/includes/service_data.php';

$is_ajax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if (isset($_GET['new_ticket'])) {
    unset($_SESSION['generated_ticket']);
    unset($_SESSION['ticket_priority']);
    unset($_SESSION['customer_type']);
    unset($_SESSION['selected_service_id']);
    unset($_SESSION['selected_service_name']);

    header("Location: customer_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_ticket') {
    unset($_SESSION['generated_ticket']);
    unset($_SESSION['ticket_priority']);
    unset($_SESSION['customer_type']);
    unset($_SESSION['selected_service_id']);
    unset($_SESSION['selected_service_name']);

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit();
    }

    header("Location: customer_dashboard.php");
    exit();
}

// Initial date/time shown before js/time.js starts updating the clock.
$current_date = date('l, F j, Y');
$current_time = date('H:i:s');
$error_message = "";
$show_ticket = false;
$locked_customer_type = $_SESSION['customer_type'] ?? null;

$lanes = [
    [
        'type' => 'regular',
        'class' => 'regular-lane',
        'label' => 'Regular lane',
        'description' => 'For regular transactions',
    ],
    [
        'type' => 'pwd_elder',
        'class' => 'pwd-lane',
        'label' => 'Priority lane',
        'description' => 'For senior citizens, persons with disabilities (PWD), and pregnant women.',
    ],
];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['service_id'], $_POST['service_name'], $_POST['customer_type'])
    && isset($_SESSION['generated_ticket'], $_SESSION['customer_type'])
    && $_POST['customer_type'] !== $_SESSION['customer_type']
) {
    $error_message = "Please finish or clear the current ticket before choosing the other queue lane.";

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error_message,
        ]);
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_id'], $_POST['service_name'], $_POST['customer_type'])) {
    define('TICKET_GENERATION_REDIRECT', false);
    require_once __DIR__ . '/includes/ticket_generation.php';

    if (!empty($ticket_generated)) {
        if ($is_ajax) {
            require_once __DIR__ . '/includes/ticket_details_data.php';

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ticket' => [
                    'ticket_number' => $ticket_number,
                    'service_name' => $service_name,
                    'customer_type' => $display_customer_type,
                    'customer_type_value' => $_SESSION['customer_type'],
                    'priority' => ucfirst($priority),
                    'timestamp' => $timestamp,
                ],
            ]);
            exit();
        }

        header("Location: customer_dashboard.php");
        exit();
    }

    if ($is_ajax && !empty($error_message)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $error_message,
        ]);
        exit();
    }
}

if (isset(
    $_SESSION['generated_ticket'],
    $_SESSION['customer_type'],
    $_SESSION['selected_service_id'],
    $_SESSION['selected_service_name'],
    $_SESSION['ticket_priority']
)) {
    require_once __DIR__ . '/includes/ticket_details_data.php';
    $show_ticket = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/customer_board.css">
    <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
    <title>MOELCI-II Customer Dashboard</title>
    <style>
        @media print {
            @page {
                size: 58mm 150mm;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                width: 58mm;
                background: #ffffff;
            }

            .header-panel,
            .fullscreen-button,
            .lane-grid,
            .empty-state,
            .buttons-container,
            .print-note,
            .customer-instructions,
            .customer-footer,
            .confirm-overlay {
                display: none !important;
            }

            .dashboard-shell,
            .ticket-panel {
                width: 58mm;
                min-height: auto;
                padding: 0;
                border: 0;
                box-shadow: none;
            }

            .ticket-container {
                width: 58mm;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <svg class="svg-definitions" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><defs><symbol id="customer-arrow" viewBox="0 0 24 24"><path d="M4 12h16m-6-6 6 6-6 6"/></symbol><symbol id="customer-print" viewBox="0 0 24 24"><path d="M7 8V3h10v5M7 17H3V9h18v8h-4M7 14h10v7H7ZM17 11h1"/></symbol><symbol id="customer-user" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></symbol><symbol id="customer-priority" viewBox="0 0 24 24"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/></symbol></defs></svg>
    <main class="dashboard-shell <?php echo $show_ticket ? 'has-ticket' : ''; ?>" id="dashboardShell">
        <section class="header-panel">
            <div class="brand-block">
                <img src="../assets/imgs/moelci_logo.png" alt="Company Logo" class="logo-image" />
                <div>
                    <p class="eyebrow">MOELCI-II QUEUING SYSTEM</p>
                    <h1>Get your queue ticket</h1>
                </div>
            </div>

            <div class="datetime" id="datetime">
                <?php echo $current_date . ' (' . $current_time . ')'; ?>
            </div>

            <button type="button" class="fullscreen-button" id="fullscreenButton" onclick="toggleFullscreen()">
                Full screen
            </button>
        </section>

        <?php if (!empty($error_message)): ?>
            <div class="error-message" id="errorMessage" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="error-message" id="errorMessage" role="alert" hidden></div>
        <?php endif; ?>

        <section class="customer-instructions" aria-label="How to get your ticket">
            <h2>Choose your lane, then tap a service.</h2>
            <ol><li><span>1</span>Choose a service below</li><li><span>2</span>Print your ticket</li><li><span>3</span>Wait for your number</li></ol>
        </section>
        <section class="lane-grid" aria-label="Queue ticket lanes">
            <?php foreach ($lanes as $lane): ?>
                <?php $lane_disabled = $show_ticket && $locked_customer_type !== $lane['type']; ?>
                <div
                    class="lane-panel <?php echo $lane['class']; ?> <?php echo $lane_disabled ? 'lane-disabled' : ''; ?>"
                    data-lane="<?php echo htmlspecialchars($lane['type']); ?>"
                >
                    <div class="lane-heading">
                        <div class="lane-title"><span class="lane-icon"><svg class="icon" aria-hidden="true"><use href="#customer-<?php echo $lane['type'] === 'regular' ? 'user' : 'priority'; ?>"></use></svg></span><h2><?php echo htmlspecialchars($lane['label']); ?></h2></div>
                        <p><?php echo htmlspecialchars($lane['description']); ?></p>
                    </div>

                    <div class="service-list">
                        <?php foreach ($services as $service): ?>
                            <form action="customer_dashboard.php" method="POST" class="ticket-form">
                                <input type="hidden" name="service_id" value="<?php echo (int) $service['service_id']; ?>">
                                <input type="hidden" name="service_name" value="<?php echo htmlspecialchars($service['service_name'], ENT_QUOTES); ?>">
                                <input type="hidden" name="customer_type" value="<?php echo htmlspecialchars($lane['type']); ?>">
                                <button
                                    type="submit"
                                    class="service-button <?php echo $service['service_name'] === 'Payment' ? 'payment-service' : ''; ?>"
                                    <?php echo $lane_disabled ? 'disabled' : ''; ?>
                                >
                                    <span><?php echo htmlspecialchars($service['service_name']); ?></span>
                                    <svg class="icon button-arrow" aria-hidden="true"><use href="#customer-arrow"></use></svg>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

            <section
                class="ticket-panel"
                id="ticketPanel"
                aria-label="Generated queue ticket"
                tabindex="-1"
                <?php echo $show_ticket ? '' : 'hidden'; ?>
            >
                <div class="ticket-container">
                    <div class="ticket-status">TICKET READY</div>
                    <div class="ticket-title">YOUR QUEUE TICKET</div>
                    <div class="ticket-number" id="ticketNumber"><?php echo $show_ticket ? htmlspecialchars($ticket_number) : ''; ?></div>

                    <div class="ticket-info">
                        <div class="ticket-line">
                            <span class="info-label">Service:</span>
                            <span id="ticketService"><?php echo $show_ticket ? htmlspecialchars($service_name) : ''; ?></span>
                        </div>

                        <div class="ticket-line">
                            <span class="info-label">Client Type:</span>
                            <span id="ticketCustomerType"><?php echo $show_ticket ? htmlspecialchars($display_customer_type) : ''; ?></span>
                        </div>

                        <div class="ticket-line">
                            <span class="info-label">Priority:</span>
                            <span id="ticketPriority"><?php echo $show_ticket ? ucfirst($priority) : ''; ?></span>
                        </div>

                        <div class="ticket-line">
                            <span class="info-label">Timestamp:</span>
                            <span id="ticketTimestamp"><?php echo $show_ticket ? htmlspecialchars($timestamp) : ''; ?></span>
                        </div>
                    </div>
                </div>

                <div class="buttons-container">
                    <button class="btn btn-print" onclick="printTicket()">
                        <svg class="icon" aria-hidden="true"><use href="#customer-print"></use></svg> Print ticket
                    </button>
                    <button class="btn btn-new-ticket" onclick="getNewTicket()">
                        Get Another Ticket
                    </button>
                </div>
                <p class="print-note">Print your ticket and keep it. Wait for your number to be called.</p>
            </section>

        <div class="confirm-overlay" id="confirmOverlay" hidden>
            <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmDescription">
                <h2 id="confirmTitle">Get another ticket?</h2>
                <p id="confirmDescription">Clear the ticket from this screen? Your queue number will stay active.</p>
                <div class="confirm-actions">
                    <button type="button" class="btn btn-new-ticket" id="confirmCancel">Cancel</button>
                    <button type="button" class="btn btn-print" id="confirmContinue">Continue</button>
                </div>
            </div>
        </div>

        <?php if (empty($services)): ?>
            <div class="empty-state">
                No active services are available right now.
            </div>
        <?php endif; ?>
        <footer class="customer-footer">Need help? Please ask a MOELCI-II staff member.</footer>
    </main>

    <script src="../js/time.js"></script>
    <script>
        const fullscreenButton = document.getElementById('fullscreenButton');
        const dashboardShell = document.getElementById('dashboardShell');
        const errorMessage = document.getElementById('errorMessage');
        const ticketPanel = document.getElementById('ticketPanel');
        const ticketForms = document.querySelectorAll('.ticket-form');
        const ticketNumber = document.getElementById('ticketNumber');
        const ticketService = document.getElementById('ticketService');
        const ticketCustomerType = document.getElementById('ticketCustomerType');
        const ticketPriority = document.getElementById('ticketPriority');
        const ticketTimestamp = document.getElementById('ticketTimestamp');
        const confirmOverlay = document.getElementById('confirmOverlay');
        const confirmCancel = document.getElementById('confirmCancel');
        const confirmContinue = document.getElementById('confirmContinue');

        let shouldRestoreFullscreen = false;
        let ticketRequestInProgress = false;
        let pendingConfirmation = null;
        let confirmationOpener = null;

        function updateFullscreenButton() {
            fullscreenButton.textContent = document.fullscreenElement ? 'Exit full screen' : 'Full screen';
        }

        function rememberFullscreen() {
            shouldRestoreFullscreen = Boolean(document.fullscreenElement);
        }

        function restoreFullscreen() {
            if (!shouldRestoreFullscreen || document.fullscreenElement) {
                return;
            }

            document.documentElement.requestFullscreen().catch(function () {
                shouldRestoreFullscreen = false;
            });
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(function () {
                    alert('Full screen is not available in this browser.');
                });
                return;
            }

            document.exitFullscreen();
            shouldRestoreFullscreen = false;
        }

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.hidden = false;
        }

        function clearError() {
            errorMessage.textContent = '';
            errorMessage.hidden = true;
        }

        function lockOtherLane(customerType) {
            document.querySelectorAll('.lane-panel').forEach(function (lanePanel) {
                const isDisabled = lanePanel.dataset.lane !== customerType;
                lanePanel.classList.toggle('lane-disabled', isDisabled);
                lanePanel.querySelectorAll('.service-button').forEach(function (button) {
                    button.disabled = isDisabled;
                });
            });
        }

        function unlockLanes() {
            document.querySelectorAll('.lane-panel').forEach(function (lanePanel) {
                lanePanel.classList.remove('lane-disabled');
                lanePanel.querySelectorAll('.service-button').forEach(function (button) {
                    button.disabled = false;
                });
            });
        }

        function showTicket(ticket) {
            ticketNumber.textContent = ticket.ticket_number;
            ticketService.textContent = ticket.service_name;
            ticketCustomerType.textContent = ticket.customer_type;
            ticketPriority.textContent = ticket.priority;
            ticketTimestamp.textContent = ticket.timestamp;
            ticketPanel.hidden = false;
            dashboardShell.classList.add('has-ticket');
            lockOtherLane(ticket.customer_type_value);
            ticketPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        ticketForms.forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                if (ticketRequestInProgress || pendingConfirmation) return;
                const service = form.elements.namedItem('service_name').value;
                const lane = form.elements.namedItem('customer_type').value === 'regular' ? 'Regular lane' : 'Priority lane';
                rememberFullscreen();
                const confirmed = await askCustomer('Is this correct?', lane + ' — ' + service, 'Create ticket');
                if (!confirmed) return;
                ticketRequestInProgress = true;
                clearError();

                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data.success) {
                            showError(data.message || 'Unable to generate a ticket right now. Please try again.');
                            return;
                        }

                        showTicket(data.ticket);
                        ticketPanel.focus({ preventScroll: true });
                    })
                    .catch(function () {
                        showError('Unable to generate a ticket right now. Please try again.');
                    })
                    .finally(function () { ticketRequestInProgress = false; });
            });
        });

        function printTicket() {
            rememberFullscreen();
            window.print();
            setTimeout(restoreFullscreen, 300);
        }

        function clearCurrentTicket() {
            fetch('customer_dashboard.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=clear_ticket',
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (!data.success) {
                        showError('Unable to clear the current ticket. Please try again.');
                        return;
                    }

                    clearError();
                    ticketPanel.hidden = true;
                    dashboardShell.classList.remove('has-ticket');
                    unlockLanes();
                    restoreFullscreen();
                })
                .catch(function () {
                    showError('Unable to clear the current ticket. Please try again.');
                    restoreFullscreen();
                });
        }

        function askCustomer(title, description, continueLabel) {
            if (pendingConfirmation) return Promise.resolve(false);
            confirmationOpener = document.activeElement;
            document.getElementById('confirmTitle').textContent = title;
            document.getElementById('confirmDescription').textContent = description;
            confirmContinue.textContent = continueLabel;
            confirmOverlay.hidden = false;
            const result = new Promise(resolve => { pendingConfirmation = resolve; });
            confirmCancel.focus();
            return result;
        }

        function closeConfirmation(confirmed) {
            if (!pendingConfirmation) return;
            const resolve = pendingConfirmation;
            pendingConfirmation = null;
            confirmOverlay.hidden = true;
            confirmationOpener?.focus();
            resolve(confirmed);
        }

        async function getNewTicket() {
            if (ticketRequestInProgress || pendingConfirmation) return;
            rememberFullscreen();
            if (await askCustomer('Get another ticket?', 'Clear this screen? Your current queue number will stay active.', 'Start again')) {
                clearCurrentTicket();
            }
        }

        confirmCancel.addEventListener('click', () => closeConfirmation(false));
        confirmContinue.addEventListener('click', () => closeConfirmation(true));
        document.addEventListener('keydown', function (event) {
            if (confirmOverlay.hidden) return;
            if (event.key === 'Escape') { event.preventDefault(); closeConfirmation(false); }
            if (event.key === 'Tab') {
                event.preventDefault();
                (document.activeElement === confirmCancel ? confirmContinue : confirmCancel).focus();
            }
        });

        window.addEventListener('afterprint', restoreFullscreen);
        document.addEventListener('fullscreenchange', updateFullscreenButton);
    </script>
</body>
</html>
