<?php
session_start();
require_once 'includes/db_connection.php';

// Initialize variables
$username = $password = "";
$error_message = "";

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    redirectBasedOnRole($_SESSION['role']);
}

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username and password.";
    } else {
        $user = authenticateUser($username, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];

            redirectBasedOnRole($user['role_name']);
        } else {
            $error_message = "Invalid username or password.";
        }
    }
}

function authenticateUser($username, $password)
{
    global $pdo;
    try {
        $sql = "SELECT u.user_id, u.username, u.password_hash, 
                       u.first_name, u.last_name, r.role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                WHERE u.username = :username AND u.is_active = 1";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password_hash'])) {
                return $user;
            }
        }
        return false;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function redirectBasedOnRole($role)
{
    switch ($role) {
        case 'Admin':
            header("Location: admin/admin_dashboard.php");
            break;
        case 'Teller':
            header("Location: teller/teller_dashboard.php");
            break;
        case 'Customer':
            header("Location: customer/customer_dashboard.php");
            break;
        default:
            header("Location: index.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/index.css">
    <style>
        body {
            position: relative;
            background-color: white;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: url('assets/imgs/background.png') center center / cover no-repeat;
            opacity: 0.3;
            z-index: 0;
            pointer-events: none;
        }

        .login-container {
            position: relative;
            z-index: 1;
        }
    </style>
    <link rel="icon" type="image/png" href="assets/imgs/moelci_logo.png">
    <title>Moelci-II Login</title>
</head>

<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/imgs/moelci_logo.png" alt="MOELCI Logo">
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>

</html>
