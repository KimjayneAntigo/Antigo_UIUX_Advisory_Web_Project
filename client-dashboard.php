<?php
/**
 * client-dashboard.php (Root proxy)
 * Forwards requests to the canonical client/dashboard.php.
 */
require_once __DIR__ . '/config/session.php';
header('Location: client/dashboard.php');
exit;
