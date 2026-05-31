<?php
session_start();

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: ../index.php");
    exit();
}

// Date and time
$current_date = date('l, F j, Y');
$current_time = date('H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/customer_board.css">
    <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
    <title>MOELCI-II Customer Dashboard</title>
</head>
<body>
    <div class="logo-container">
        <div class="company-logo">
            <img src="../assets/imgs/moelci_logo.png" alt="Company Logo" class="logo-image" />
        </div>
        
        <div class="welcome-text">
            <h1>Welcome to Moelci 2 Queuing System</h1>
        </div>

       <a href="customer_service.php" class="transaction-button">
    Select a Transaction
</a>

        <!-- Date and time -->
        <div class="datetime" id="datetime">
            <?php echo $current_date . ' (' . $current_time . ')'; ?>
        </div>
    </div>

    <script src="../js/time.js"></script>
</body>
</html>
