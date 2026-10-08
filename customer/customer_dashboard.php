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
        'label' => 'Regular',
        'description' => 'Standard queue lane',
    ],
    [
        'type' => 'pwd_elder',
        'class' => 'pwd-lane',
        'label' => 'PWD / Elder',
        'description' => 'Priority queue lane',
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
            .print-note {
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
            <div class="error-message" id="errorMessage">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="error-message" id="errorMessage" hidden></div>
        <?php endif; ?>

        <section class="lane-grid" aria-label="Queue ticket lanes">
            <?php foreach ($lanes as $lane): ?>
                <?php $lane_disabled = $show_ticket && $locked_customer_type !== $lane['type']; ?>
                <div
                    class="lane-panel <?php echo $lane['class']; ?> <?php echo $lane_disabled ? 'lane-disabled' : ''; ?>"
                    data-lane="<?php echo htmlspecialchars($lane['type']); ?>"
                >
                    <div class="lane-heading">
                        <p><?php echo htmlspecialchars($lane['description']); ?></p>
                        <h2><?php echo htmlspecialchars($lane['label']); ?></h2>
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
                                    <span class="button-arrow">&rarr;</span>
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
                        Print ticket &rarr;
                    </button>
                    <button class="btn btn-new-ticket" onclick="getNewTicket()">
                        Get Another Ticket
                    </button>
                </div>
                <p class="print-note">Please keep this number and wait for it to be called.</p>
            </section>

        <div class="confirm-overlay" id="confirmOverlay" hidden>
            <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
                <h2 id="confirmTitle">Get another ticket?</h2>
                <p>Clear this ticket and choose a different service.</p>
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
            form.addEventListener('submit', function (event) {
                event.preventDefault();
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
                    })
                    .catch(function () {
                        showError('Unable to generate a ticket right now. Please try again.');
                    });
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

        function getNewTicket() {
            rememberFullscreen();
            confirmOverlay.hidden = false;
            confirmContinue.focus();
        }

        confirmCancel.addEventListener('click', function () {
            confirmOverlay.hidden = true;
            restoreFullscreen();
        });

        confirmContinue.addEventListener('click', function () {
            confirmOverlay.hidden = true;
            clearCurrentTicket();
        });

        window.addEventListener('afterprint', restoreFullscreen);
        document.addEventListener('fullscreenchange', updateFullscreenButton);
    </script>
</body>
</html>
