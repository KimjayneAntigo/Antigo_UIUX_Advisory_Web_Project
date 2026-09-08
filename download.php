<?php
/**
 * download.php
 * Secure role-based file streaming endpoint for Antigo WebApp.
 * Verifies authentication and project ownership before sending file data.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Authentication guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    safe_redirect('login.php');
}

$fileId = (int) ($_GET['file_id'] ?? 0);
if ($fileId <= 0) {
    http_response_code(400);
    exit('Invalid file request.');
}

try {
    $stmt = $pdo->prepare(
        'SELECT pf.*, p.user_id AS project_owner_id, p.title AS project_title
         FROM project_files pf
         JOIN projects p ON pf.project_id = p.id
         WHERE pf.id = ?
         LIMIT 1'
    );
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();

    if (!$file) {
        http_response_code(404);
        exit('File not found.');
    }

    $currentUserRole   = $_SESSION['role'] ?? '';
    $currentUserId     = (int) $_SESSION['user_id'];
    $projectOwnerId    = (int) ($file['project_owner_id'] ?? 0);

    // Access control:
    // Admin has access to all project files.
    // Client has access ONLY if the project's user_id matches the session user_id.
    if ($currentUserRole !== 'admin' && ($currentUserRole !== 'client' || $projectOwnerId !== $currentUserId)) {
        error_log("Security: Unauthorized file download attempt (user $currentUserId for file $fileId)");
        http_response_code(403);
        exit('Access denied: You do not have permission to download this file.');
    }

    // Resolve file path safely
    $storedPath = $file['file_path'];
    $fullPath   = __DIR__ . '/' . ltrim($storedPath, '/\\');

    // Prevent directory traversal
    $realFullPath = realpath($fullPath);
    $uploadsDir   = realpath(__DIR__ . '/uploads');

    if ($realFullPath === false || !file_exists($realFullPath) || !str_starts_with($realFullPath, $uploadsDir)) {
        // Try fallback in uploads/projects/{project_id}/
        $fallbackPath = __DIR__ . '/uploads/projects/' . $file['project_id'] . '/' . basename($file['name']);
        if (file_exists($fallbackPath)) {
            $realFullPath = realpath($fallbackPath);
        } else {
            error_log("download.php: file path on disk missing ($fullPath)");
            http_response_code(404);
            exit('File is unavailable on disk.');
        }
    }

    // Determine mime type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf'  => 'application/pdf',
        'fig'  => 'application/octet-stream',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'zip'  => 'application/zip',
    ];
    $contentType = $mimeTypes[$ext] ?? 'application/octet-stream';
    $downloadName = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', basename($file['name']));

    // Clear output buffer to prevent corrupted file downloads
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Description: File Transfer');
    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($realFullPath));

    readfile($realFullPath);
    exit;

} catch (\PDOException $e) {
    error_log('download.php DB error: ' . $e->getMessage());
    http_response_code(500);
    exit('Database error processing file download.');
}
