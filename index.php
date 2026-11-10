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
    <script src="js/confirm_actions.js" defer></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f4c542">
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" type="image/png" href="assets/imgs/moelci_logo.png">
    <title>Sign in | MOELCI-II Queue System</title>
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-labelledby="brand-title">
            <div class="brand-identity">
                <img src="assets/imgs/moelci_logo.png" alt="MOELCI-II logo" width="76" height="76">
                <div><span class="brand-name">MOELCI-II</span><span class="brand-caption">Electric Cooperative</span></div>
            </div>
            <div class="brand-message">
                <span class="eyebrow">QUEUE MANAGEMENT SYSTEM</span>
                <h1 id="brand-title">Better service.<br>One turn at a time.</h1>
                <p>A simpler way to manage the queue and keep every customer moving forward.</p>
                <div class="queue-illustration" aria-hidden="true">
                    <div class="ticket-line"><span class="ticket-dot"></span><span></span><span></span></div>
                    <div class="sample-ticket"><span>NOW SERVING</span><strong>077</strong><div><span>YOUR TURN, MADE SIMPLE</span><span>→</span></div></div>
                </div>
            </div>
            <p class="brand-footer">Misamis Occidental II Electric Cooperative, Inc.</p>
        </section>
        <section class="login-panel" aria-labelledby="login-title">
            <div class="login-content">
                <span class="eyebrow login-eyebrow">WELCOME TO MOELCI-II</span>
                <h2 id="login-title">Sign in to your account</h2>
                <p class="login-description">Enter your credentials to access the queue system.</p>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message" role="alert" id="login-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <form method="POST" data-confirm="Sign in to your account?" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>"<?php if (!empty($error_message)): ?> aria-describedby="login-error"<?php endif; ?>>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" placeholder="Enter your username" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" aria-controls="password" aria-label="Show password" aria-pressed="false" hidden>Show</button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login"><span>Sign in</span><span aria-hidden="true">→</span></button>
                </form>
                <a class="btn-monitor" href="https://moelci-2tangub-city.com/php/monitor_display.php">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M12 17v4M8 21h8"/></svg>
                    <span>Open Queue Monitor</span>
                    <span aria-hidden="true">&rarr;</span>
                </a>
                <p class="account-help">Need help signing in? Contact your administrator.</p>
            </div>
            <p class="login-footer">MOELCI-II <span aria-hidden="true">·</span> Queue Management System</p>
        </section>
    </main>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.querySelector('.password-toggle');
        passwordToggle.hidden = false;
        passwordToggle.addEventListener('click', function () {
            const showPassword = passwordInput.type === 'password';
            passwordInput.type = showPassword ? 'text' : 'password';
            passwordToggle.textContent = showPassword ? 'Hide' : 'Show';
            passwordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            passwordToggle.setAttribute('aria-pressed', String(showPassword));
        });
    </script>
</body>
</html>
