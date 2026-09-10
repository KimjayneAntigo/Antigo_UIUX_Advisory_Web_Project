<?php
// Configuration template: copy this file to db.php and update with your local credentials.
define('DB_HOST', 'localhost');
define('DB_NAME', 'antigo_advisory_db');
define('DB_USER', 'root');
define('DB_PASS', '');   // Default XAMPP password is empty
define('DB_CHARSET', 'utf8mb4');

// display_errors Off in production — errors logged, never printed directly
ini_set('display_errors', 0);
error_reporting(E_ALL);

$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Attempt connection across standard XAMPP MariaDB ports (3307 and 3306)
$ports = [3307, 3306];
$pdo = null;
$lastException = null;

foreach ($ports as $port) {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        break;
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    error_log('DB Connection Error: ' . ($lastException ? $lastException->getMessage() : 'Unknown error'));

    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isAjax) {
        http_response_code(503);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Database service temporarily unavailable.']);
        exit;
    }

    if (!headers_sent()) {
        $prefix = (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/client/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) ? '../' : '';
        header('Location: ' . $prefix . '500.php?error=db');
        exit;
    }
    exit('Database service temporarily unavailable. Please try again later.');
}
?>