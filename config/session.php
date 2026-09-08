<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
