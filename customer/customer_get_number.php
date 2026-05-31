<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: ../index.php");
    exit();
}

// Debug: Log what's coming in POST and SESSION
error_log("POST data: " . print_r($_POST, true));
error_log("SESSION data: " . print_r($_SESSION, true));

// Check if service data is provided via POST
if (isset($_POST['service_id']) && isset($_POST['service_name'])) {
    // Store service info in session from POST data
    $_SESSION['selected_service_id'] = $_POST['service_id'];
    $_SESSION['selected_service_name'] = $_POST['service_name'];
    error_log("Service data stored in session: " . $_POST['service_name']);
}

// Check if service data exists in session
if (!isset($_SESSION['selected_service_id']) || !isset($_SESSION['selected_service_name'])) {
    error_log("No service data found - redirecting to customer_service.php");
    header("Location: customer_service.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$customer_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$service_id = $_SESSION['selected_service_id'];
$service_name = $_SESSION['selected_service_name'];

error_log("Current service: " . $service_name . " (ID: " . $service_id . ")");

// Handle customer type selection and ticket generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_type'])) {
    $customer_type = $_POST['customer_type'];
    
    // Map to database enum
    $customer_type_db = ($customer_type === 'pwd_elder') ? 'pwd' : 'regular';

    error_log("Generating ticket for: " . $customer_type . ", Service: " . $service_name);

    try {
        // Determine prefix and priority
$prefix = ($customer_type_db === 'pwd') ? 'P' : 'R';
$priority = ($customer_type_db === 'pwd') ? 'high' : 'normal';

        // Get the next ticket number for today with the same prefix
        $ticketQuery = "SELECT MAX(CAST(SUBSTRING(ticket_number, 2) AS UNSIGNED)) as last_number 
                        FROM queue_tickets 
                        WHERE ticket_number LIKE ? 
                        AND DATE(created_at) = CURDATE()";

        $stmt = $pdo->prepare($ticketQuery);
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $next_number = ($result['last_number'] ? $result['last_number'] + 1 : 1);
        $ticket_number = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);

        // Insert the new ticket
        $insertQuery = "INSERT INTO queue_tickets 
                (ticket_number, service_id, customer_id, customer_name, customer_type, priority, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'waiting', NOW())";

$stmt = $pdo->prepare($insertQuery);
$stmt->execute([$ticket_number, $service_id, $user_id, $customer_name, $customer_type_db, $priority]);



        // Store ticket info in session for display on next page
        $_SESSION['generated_ticket'] = $ticket_number;
        $_SESSION['ticket_priority'] = $priority;
        $_SESSION['customer_type'] = $customer_type;

        error_log("Ticket generated: " . $ticket_number . " for service: " . $service_name);

        // Redirect to ticket display page
        header("Location: customer_details.php");
        exit();
    } catch (PDOException $e) {
        $error_message = "Error generating ticket: " . $e->getMessage();
        error_log("Ticket generation error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/customer_number.css">
    <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
    <title>MOELCI-II Get Queue Number</title>
</head>

<body>
    <div class="container">
        <div class="logo-container">
            <img src="../assets/imgs/moelci_logo.png" alt="Company Logo" class="logo-image" />
        </div>

        <!-- Debug information -->
        <!-- <div class="debug-info">
            Debug: Service ID: <?php echo $service_id; ?>, Service Name: <?php echo htmlspecialchars($service_name); ?>
        </div> -->

        <!--  <div class="service-info">
            <h2>Selected Service: <?php echo htmlspecialchars($service_name); ?></h2>
        </div> -->

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <p><?php echo htmlspecialchars($error_message); ?></p>
            </div>
        <?php endif; ?>

        <!--  <h1>Select Customer Type</h1>
        <p>Choose your category to generate queue number:</p> -->

        <!-- PWD/Elder Option -->
        <div class="customer-type-option pwd-option">
            <!-- <div class="option-title pwd-title">PWD/Elder</div>
            <p>Priority lane for Persons with Disabilities and Senior Citizens</p> -->
            <form method="POST">
                <input type="hidden" name="customer_type" value="pwd_elder">
                <button type="submit" class="get-number-btn pwd-btn">Get PWD/Elder Number</button>
            </form>
        </div>

        <!-- Regular Option -->
        <div class="customer-type-option regular-option">
            <!--  <div class="option-title regular-title">Regular</div>
            <p>Standard lane for regular customers</p> -->
            <form method="POST">
                <input type="hidden" name="customer_type" value="regular">
                <button type="submit" class="get-number-btn regular-btn">Get Regular Number</button>
            </form>
        </div>

        <!-- Back Button -->
        <form method="POST" action="customer_service.php">
            <button type="submit" class="back-btn">← Back to Services</button>
        </form>
    </div>

    <!-- Debug script -->
    <script>
        console.log('Current service:', '<?php echo $service_name; ?>');
    </script>
</body>

</html>