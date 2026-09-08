<?php
/**
 * Require an authenticated client session; redirect to login otherwise.
 */
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    $loginPath = str_contains($_SERVER['SCRIPT_NAME'], '/client/') ? '../login.php' : 'login.php';
    header('Location: ' . $loginPath);
    exit;
}
?>
