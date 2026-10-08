<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'moelci-ii');
define('DB_USER', 'root');
define('DB_PASS', '');

if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => true // 🔥 CRITICAL
            ]
        );
    } catch (PDOException $e) {
        error_log("DB ERROR: " . $e->getMessage());
        die("Database connection failed.");
    }
}
?>