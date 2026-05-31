<?php
// Database configuration
define('DB_HOST', '148.222.53.6');
define('DB_NAME', 'u127667912_moelci2');
define('DB_USER', 'u127667912_tangubcity');
define('DB_PASS', 'Moelci-2tangubcity');

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