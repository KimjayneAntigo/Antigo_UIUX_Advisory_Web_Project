<?php
/**
 * client-project.php (Root proxy)
 * Forwards requests to the canonical client/project-detail.php.
 */
require_once __DIR__ . '/config/session.php';
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: client/project-detail.php' . $query);
exit;
