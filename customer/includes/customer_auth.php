<?php
// Shared customer guard.
// Include this before any HTML output on pages that only customers can open.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Customer') {
    header("Location: ../index.php");
    exit();
}
?>
