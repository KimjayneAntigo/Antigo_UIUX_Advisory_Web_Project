<?php
/**
 * cancel-project-handler.php
 * Handles project cancellation by authenticated client or admin.
 * Preserves project history and sets status_type = 'cancelled'.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/routing.php';

$isJson = (
    (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_POST['format']) && $_POST['format'] === 'json')
);

// 1. Authentication Check
if (!isset($_SESSION['user_id']) || empty($_SESSION['role'])) {
    if ($isJson) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required.']);
        exit;
    }
    set_flash('error', 'Please log in to continue.');
    safe_redirect('login.php');
}

$currentUserId = (int) $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'];

// Only POST method is permitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isJson) {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Method Not Allowed.']);
        exit;
    }
    safe_redirect(home_url());
}

// 2. CSRF Token Verification
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    if ($isJson) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid or expired security token. Please refresh and try again.']);
        exit;
    }
    set_flash('error', 'Invalid or expired security token.');
    safe_redirect(home_url());
}

// 3. Project ID Validation
$projectId = (int) ($_POST['project_id'] ?? 0);
if ($projectId <= 0) {
    if ($isJson) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid project reference.']);
        exit;
    }
    set_flash('error', 'Invalid project reference.');
    safe_redirect(home_url());
}

try {
    // 4. Query project row
    $stmt = $pdo->prepare('SELECT id, user_id, project_code, title, status_type FROM projects WHERE id = ? LIMIT 1');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        if ($isJson) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
            exit;
        }
        set_flash('error', 'Project not found or access denied.');
        safe_redirect(home_url());
    }

    // 5. Role-based authorization (Anti-IDOR for clients)
    if ($currentUserRole === 'client') {
        if ((int)($project['user_id'] ?? 0) !== $currentUserId) {
            error_log("IDOR Security Alert: Client User {$currentUserId} attempted to cancel Project {$projectId} owned by User " . ($project['user_id'] ?? 'null'));
            if ($isJson) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
                exit;
            }
            set_flash('error', 'Project not found or access denied.');
            safe_redirect('client/dashboard.php');
        }
    }

    // 6. State Guard: Cannot cancel completed or already cancelled projects
    $currentStatusType = $project['status_type'] ?? '';
    if ($currentStatusType === 'cancelled') {
        $msg = 'This project is already cancelled.';
        if ($isJson) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $msg]);
            exit;
        }
        set_flash('error', $msg);
        $returnUrl = ($currentUserRole === 'admin')
            ? "admin/project-detail.php?id={$projectId}"
            : "client/project-detail.php?id={$projectId}";
        safe_redirect($returnUrl);
    }

    if ($currentStatusType === 'completed') {
        $msg = 'Completed projects cannot be cancelled.';
        if ($isJson) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $msg]);
            exit;
        }
        set_flash('error', $msg);
        $returnUrl = ($currentUserRole === 'admin')
            ? "admin/project-detail.php?id={$projectId}"
            : "client/project-detail.php?id={$projectId}";
        safe_redirect($returnUrl);
    }

    // 7. Sanitize optional cancellation reason (max 500 characters)
    $reason = trim($_POST['cancellation_reason'] ?? $_POST['reason'] ?? '');
    if ($reason !== '') {
        $reason = mb_substr(strip_tags($reason), 0, 500);
    } else {
        $reason = null;
    }

    // 8. Execute Cancellation
    $updStmt = $pdo->prepare(
        "UPDATE projects
         SET status_type = 'cancelled',
             status = 'Cancelled',
             cancelled_at = NOW(),
             cancelled_by = :user_id,
             cancellation_reason = :reason,
             updated_at = NOW()
         WHERE id = :project_id AND status_type NOT IN ('completed', 'cancelled')"
    );
    $updStmt->execute([
        'user_id'    => $currentUserId,
        'reason'     => $reason,
        'project_id' => $projectId,
    ]);

    if ($updStmt->rowCount() === 0) {
        $msg = 'This project can no longer be cancelled.';
        if ($isJson) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $msg]);
            exit;
        }
        set_flash('error', $msg);
        safe_redirect(home_url());
    }

    // Determine target redirect
    $customReturn = trim($_POST['return_to'] ?? '');
    if (!empty($customReturn) && !str_starts_with($customReturn, 'http://') && !str_starts_with($customReturn, 'https://') && !str_starts_with($customReturn, '//')) {
        $redirectUrl = $customReturn;
    } else {
        $redirectUrl = ($currentUserRole === 'admin')
            ? "admin/project-detail.php?id={$projectId}"
            : "client/project-detail.php?id={$projectId}";
    }

    $successMsg = "Project {$project['project_code']} has been cancelled.";
    set_flash('success', $successMsg);

    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode([
            'success'      => true,
            'message'      => $successMsg,
            'redirect_url' => $redirectUrl,
            'cancelled_at' => date('M j, Y g:i A'),
        ]);
        exit;
    }

    safe_redirect($redirectUrl);

} catch (\PDOException $e) {
    error_log('cancel-project-handler error: ' . $e->getMessage());
    if ($isJson) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database error cancelling project. Please try again.']);
        exit;
    }
    set_flash('error', 'Database error cancelling project. Please try again.');
    safe_redirect(home_url());
}
