<?php
session_start();

// Logout releases the priority lane immediately; closed tabs expire after 60 seconds.
if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'Teller') {
    require_once __DIR__ . '/db_connection.php';
    require_once __DIR__ . '/teller_queue.php';
    ensureTellerAvailabilityTable($pdo);
    $stmt = $pdo->prepare('UPDATE teller_availability SET last_seen = NULL WHERE user_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
}

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: ../index.php");
exit;
?>
