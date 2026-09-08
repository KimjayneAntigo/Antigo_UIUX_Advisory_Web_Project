<?php
/**
 * includes/auth-check-admin.php
 * Require an authenticated admin session; redirect to login otherwise.
 */
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $loginPath = str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../login.php' : 'login.php';
    header('Location: ' . $loginPath);
    exit;
}
?>
