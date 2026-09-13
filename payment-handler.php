<?php
/**
 * payment-handler.php
 * Client manual payment submission endpoint.
 * Accepts POST only and returns JSON.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/routing.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check-client.php';

header('Content-Type: application/json; charset=UTF-8');

// Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf_token($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token invalid or expired. Please refresh the page.']);
    exit;
}

// 1. Validate project_id
$projectId = (int) ($_POST['project_id'] ?? 0);
if ($projectId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid project reference.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? LIMIT 1');
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
        exit;
    }

    // 2. IDOR check: Verify project belongs to current authenticated client
    $userId = (int) $_SESSION['user_id'];
    if ((int) ($project['user_id'] ?? 0) !== $userId) {
        error_log("IDOR Security Alert: User {$userId} attempted to submit payment for Project {$projectId} owned by User " . ($project['user_id'] ?? 'null'));
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Project not found or access denied.']);
        exit;
    }

    // 3. Status check: Project must be completed
    if (($project['status_type'] ?? '') !== 'completed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Payment isn't available until the project is marked complete."]);
        exit;
    }

    // 4. Payment method whitelist
    $allowedMethods = ['GCash', 'Bank Transfer', 'Cash', 'Card'];
    $paymentMethod  = trim($_POST['payment_method'] ?? '');
    if (!in_array($paymentMethod, $allowedMethods, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please select a valid payment method (GCash, Bank Transfer, Cash, or Card).']);
        exit;
    }

    // 5. Compute amount server-side from project budget (never trust client payload)
    $amount = parse_budget_amount($project['budget'] ?? '');
    if ($amount <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Unable to determine project payable amount. Please contact the studio.']);
        exit;
    }

    // 6. Check there isn't already a pending or verified payment for this project
    $dupStmt = $pdo->prepare("SELECT id, status FROM payments WHERE project_id = ? AND status IN ('pending', 'verified') LIMIT 1");
    $dupStmt->execute([$projectId]);
    $existingPayment = $dupStmt->fetch();

    if ($existingPayment) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'A payment for this project has already been submitted.']);
        exit;
    }

    // Success: Insert payment declaration
    $insStmt = $pdo->prepare(
        'INSERT INTO payments (project_id, user_id, amount, payment_method, status, submitted_at)
         VALUES (?, ?, ?, ?, \'pending\', NOW())'
    );
    $insStmt->execute([$projectId, $userId, $amount, $paymentMethod]);

    $paymentId       = (int) $pdo->lastInsertId();
    $referenceNumber = format_payment_ref($paymentId);

    echo json_encode([
        'success'          => true,
        'payment_id'       => $paymentId,
        'reference_number' => $referenceNumber,
        'amount'           => number_format($amount, 2),
        'payment_method'   => $paymentMethod,
        'status'           => 'pending',
        'message'          => "Payment {$referenceNumber} recorded! Our team will verify receipt shortly.",
    ]);
    exit;

} catch (\PDOException $e) {
    error_log('payment-handler DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error recording payment. Please try again.']);
    exit;
}
