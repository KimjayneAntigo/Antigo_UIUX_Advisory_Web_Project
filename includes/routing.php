<?php
/**
 * includes/routing.php
 * Central routing helpers for Antigo UI/UX Advisory Web App.
 * Single source of truth for "where is home".
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the contextual home URL based on the current user's session role.
 * - Guest: home.php
 * - Admin: admin-dashboard.php
 * - Client: client-dashboard.php
 * Automatically handles subdirectory paths (e.g. inside /client/ or /admin/).
 */
function home_url(): string {
    $prefix = (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/client/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/')) ? '../' : '';

    if (!isset($_SESSION['role'])) {
        return $prefix . 'home.php'; // guest
    }

    return $_SESSION['role'] === 'admin' ? $prefix . 'admin-dashboard.php' : $prefix . 'client-dashboard.php';
}

/**
 * Checks if the current visitor is authenticated.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}
