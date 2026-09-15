<?php
/**
 * admin-settings.php (Root proxy)
 * Forwards requests to canonical admin/profile.php.
 */
require_once __DIR__ . '/config/session.php';
header('Location: admin/profile.php');
exit;
