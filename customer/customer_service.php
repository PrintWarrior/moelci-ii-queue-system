<?php
session_start();
require_once '../includes/db_connection.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Customer') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Define the specific service types you want to display
$required_services = ['Payment', 'Complain', 'Notice of Billing', 'Reconnection', 'New Connection Seminar'];

// Get only the required services from database
try {
    $placeholders = str_repeat('?,', count($required_services) - 1) . '?';
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_name IN ($placeholders) AND is_active = 1");
    $stmt->execute($required_services);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching services: " . $e->getMessage());
}

// If services don't exist in database, create a fallback array
if (empty($services)) {
    $services = [
        ['service_id' => 1, 'service_name' => 'Payment'],
        //['service_id' => 2, 'service_name' => 'Complain'],
        ['service_id' => 3, 'service_name' => 'Notice of Billing'],
        //['service_id' => 4, 'service_name' => 'Reconnection'],
        //['service_id' => 5, 'service_name' => 'New Connection Seminar']
    ];
}

// Debug: Check what services are being fetched
error_log("Fetched services: " . print_r($services, true));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="../css/customer_service.css">
    <link rel="icon" type="image/png" href="../assets/imgs/moelci_logo.png">
    <title>MOELCI-II Service Selection</title>
</head>
<body>
    <div class="container">
        <div class="logo-container">
            <div class="logo-circle">
                <img src="../assets/imgs/moelci_logo.png" alt="Company Logo" class="logo-image" />
            </div>
        </div>
        
        <div class="buttons-container">
            <?php foreach ($services as $service): ?>
            <div class="<?php echo $service['service_name'] === 'Payment' ? 'payment-button' : ''; ?>">
                <form action="customer_get_number.php" method="POST">
                    <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                    <input type="hidden" name="service_name" value="<?php echo htmlspecialchars($service['service_name']); ?>">
                    <button type="submit" class="menu-button">
                        <?php echo htmlspecialchars($service['service_name']); ?>
                        <?php if (isset($service[''])): ?>
                            <br><small><?php echo htmlspecialchars($service['']); ?></small>
                        <?php endif; ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Debug script to check form data -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach((form, index) => {
                form.addEventListener('submit', function(e) {
                    const serviceId = this.querySelector('input[name="service_id"]').value;
                    const serviceName = this.querySelector('input[name="service_name"]').value;
                    console.log('Submitting form:', index, 'Service ID:', serviceId, 'Service Name:', serviceName);
                });
            });
        });
    </script>
</body>
</html>
