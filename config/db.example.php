<?php
// Configuration template: rename or copy this file to db.php and set real credentials.
define('DB_HOST', 'localhost');
define('DB_NAME', 'antigo_advisory_db');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
} catch (PDOException $e) {
    error_log('DB Connection Error: ' . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed. Please try again later.']));
}
?>