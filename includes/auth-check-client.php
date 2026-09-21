<?php
/**
 * Require an authenticated client session; redirect to login otherwise.
 */
require_once __DIR__ . '/../config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'error' => 'Authentication required as client.']);
        exit;
    }
    $loginPath = str_contains($_SERVER['SCRIPT_NAME'], '/client/') ? '../login.php' : 'login.php';
    header('Location: ' . $loginPath);
    exit;
}
?>