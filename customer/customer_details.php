<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: ../index.php");
    exit();
}

// Check if ticket data is available from customer_get_number.php
if (!isset($_SESSION['generated_ticket']) || !isset($_SESSION['customer_type']) || 
    !isset($_SESSION['selected_service_name'])) {
    header("Location: customer_service.php");
    exit();
}

// Retrieve ticket data from session
$ticket_number = $_SESSION['generated_ticket'];
$customer_type = $_SESSION['customer_type'];
$service_name = $_SESSION['selected_service_name'];
$priority = $_SESSION['ticket_priority'];

// Format customer type for display
$display_customer_type = ($customer_type === 'pwd_elder') ? 'PWD/Elder' : 'Regular';

// Calculate estimated wait time
try {
    $waitTimeQuery = "SELECT COUNT(*) as waiting_ahead 
                      FROM queue_tickets 
                      WHERE status = 'waiting' 
                      AND created_at < NOW() 
                      AND service_id = ?";
    
    $stmt = $pdo->prepare($waitTimeQuery);
    $stmt->execute([$_SESSION['selected_service_id']]);
    $waitResult = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $waiting_ahead = $waitResult['waiting_ahead'] ?? 0;
    $estimated_wait = max(5, $waiting_ahead * 5); // 5 minutes per customer
    
} catch (PDOException $e) {
    $estimated_wait = 10; // Default fallback
}

date_default_timezone_set('Asia/Manila');

$timestamp = date('h:i A m/d/Y');

// Clear session variables after display (optional - you may want to keep them for a while)
// unset($_SESSION['generated_ticket']);
// unset($_SESSION['ticket_priority']);
// unset($_SESSION['customer_type']);
// unset($_SESSION['selected_service_id']);
// unset($_SESSION['selected_service_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MOELCI-II Customer Details</title>
    <link rel="stylesheet" href="../css/customer_details.css">
    <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
<style>
@media print {

  /* FORCE THERMAL SIZE */
  @page {
    size: 58mm 150mm;   /* width height */
    margin: 0;
  }

  body {
    margin: 0;
    padding: 0;
    width: 58mm;
  }

  /* PRINT ONLY THE TICKET */
  .buttons-container,
  .header {
    display: none !important;
  }

  .container {
    width: 58mm;
    padding: 0;
  }

  .ticket-container {
    width: 58mm;
    margin: 0;
  }

}

</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-container">
                <img src="../assets/imgs/moelci_logo.png" alt="Company Logo" class="logo-image" />
            </div>
        </div>
        
        <div class="ticket-container">
            <div class="ticket-title">YOUR QUEUE TICKET</div>
            <div class="ticket-number"><?php echo htmlspecialchars($ticket_number); ?></div>
            
            <div class="ticket-info">
                <div class="ticket-line">
                    <span class="info-label">Service:</span>
                    <?php echo htmlspecialchars($service_name); ?>
                </div>
                
                <div class="ticket-line">
                    <span class="info-label">Client Type:</span>
                    <?php echo htmlspecialchars($display_customer_type); ?>
                </div>
                
                <div class="ticket-line">
                    <span class="info-label">Priority:</span>
                    <?php echo ucfirst($priority); ?>
                </div>
                
                <div class="ticket-line">
                    <span class="info-label">Timestamp:</span>
                    <?php echo htmlspecialchars($timestamp); ?>
                </div>
                
                <!--<div class="ticket-line">
                    <span class="info-label">Est. Wait Time:</span>
                    ~<?php echo $estimated_wait; ?> mins
                </div> -->
            </div>
        </div>
        
        <div class="buttons-container">
            <button class="btn btn-print" onclick="window.print()">
                Print Ticket
            </button>
            <button class="btn btn-new-ticket" onclick="getNewTicket()">
                Get Another Ticket
            </button>
        </div>
    </div>

    <script>
        function cancelTicket() {
            if(confirm('Are you sure you want to cancel this ticket?')) {
                // Send AJAX request to cancel the ticket
                fetch('../includes/cancel_ticket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ticket_number=<?php echo $ticket_number; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Your ticket has been cancelled.');
                        window.location.href = 'customer_service.php';
                    } else {
                        alert('Error cancelling ticket.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error cancelling ticket.');
                });
            }
        }
        
        function getNewTicket() {
            if(confirm('Would you like to get another ticket for a different service?')) {
                window.location.href = 'customer_dashboard.php';
            }
        }
        
        // Auto-print option (optional)
       // window.onload = function() {
            // Uncomment the line below if you want to auto-print
window.onafterprint = function () {
    window.close();
};

    </script>
</body>
</html>