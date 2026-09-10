<?php
/**
 * admin-project.php (Root proxy)
 * Forwards requests to the canonical admin/project-detail.php.
 */
require_once __DIR__ . '/config/session.php';
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: admin/project-detail.php' . $query);
exit;
