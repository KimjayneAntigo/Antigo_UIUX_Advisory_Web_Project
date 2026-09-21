<?php
/**
 * Automated verification test for Invoice-Gated Payment Flow.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$results = [];

function assert_true(string $name, bool $cond, string $msg = ''): void {
    global $results;
    $results[] = [
        'name' => $name,
        'pass' => $cond,
        'msg'  => $msg
    ];
    echo ($cond ? "[PASS] " : "[FAIL] ") . $name . ($msg ? " - {$msg}" : "") . "\n";
}

echo "=== Running Invoice-Gated Payment Flow Tests ===\n\n";

// Verify schema: invoice_sent_at column exists on projects
$colStmt = $pdo->query("SHOW COLUMNS FROM projects LIKE 'invoice_sent_at'");
$col = $colStmt->fetch();
assert_true('Schema: projects.invoice_sent_at exists', !empty($col), "Type: " . ($col['Type'] ?? 'none'));

// Get a test client user
$clientUser = $pdo->query("SELECT * FROM users WHERE role = 'client' LIMIT 1")->fetch();
assert_true('Setup: Found test client user', !empty($clientUser), "Client: " . ($clientUser['email'] ?? 'none'));
$clientId = (int)$clientUser['id'];

// Get an admin user
$adminUser = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1")->fetch();
assert_true('Setup: Found test admin user', !empty($adminUser), "Admin: " . ($adminUser['email'] ?? 'none'));

// Create a new test project in 'in_design' status
$stmt = $pdo->prepare("INSERT INTO projects (user_id, project_code, title, category, client_name, client_email, company, budget, status, status_type, current_phase, phase_name, progress, invoice_sent_at, created_at, updated_at)
VALUES (?, 'PRJ-TEST-INV', 'Invoice Flow QA Project', 'UI/UX Design', 'Demo Client', 'demo@client.com', 'Demo Co', '$2,500.00', 'In Design', 'in_design', 2, 'Wireframing', 40, NULL, NOW(), NOW())");
$stmt->execute([$clientId]);
$testProjectId = (int)$pdo->lastInsertId();
assert_true('Setup: Created test project', $testProjectId > 0, "Project ID: {$testProjectId}");

try {
    // State: In Progress / In Design
    // Query project
    $p = $pdo->query("SELECT * FROM projects WHERE id = {$testProjectId}")->fetch();
    assert_true('State 1: Project status_type is in_design', $p['status_type'] === 'in_design');
    assert_true('State 1: invoice_sent_at is NULL', $p['invoice_sent_at'] === null);

    // Test API gate: Try to submit payment declaration while not completed
    // Simulate what payment-handler.php does
    $canPayState1 = (($p['status_type'] ?? '') === 'completed' && !empty($p['invoice_sent_at']));
    assert_true('Backend Gate: Payment disallowed when not completed', $canPayState1 === false);

    // Mark Complete, but do NOT send invoice yet
    $upd = $pdo->prepare("UPDATE projects SET status_type = 'completed', status = 'Completed', updated_at = NOW() WHERE id = ?");
    $upd->execute([$testProjectId]);
    $p = $pdo->query("SELECT * FROM projects WHERE id = {$testProjectId}")->fetch();
    assert_true('State 2: Project status_type is completed', $p['status_type'] === 'completed');
    assert_true('State 2: invoice_sent_at is still NULL', $p['invoice_sent_at'] === null);

    // Test API gate: Try to submit payment declaration while completed but invoice_sent_at is NULL
    $canPayState2 = (($p['status_type'] ?? '') === 'completed' && !empty($p['invoice_sent_at']));
    assert_true('Backend Gate: Payment disallowed when completed without invoice_sent_at', $canPayState2 === false);

    // Check dashboard banner query: Should NOT find this project
    $bStmt = $pdo->prepare("SELECT p.id FROM projects p LEFT JOIN payments pay ON pay.project_id = p.id AND pay.status = 'verified' WHERE p.user_id = :uid AND p.status_type = 'completed' AND p.invoice_sent_at IS NOT NULL AND pay.id IS NULL AND p.id = :pid");
    $bStmt->execute(['uid' => $clientId, 'pid' => $testProjectId]);
    assert_true('Dashboard Banner: Not visible before invoice is sent', empty($bStmt->fetch()));

    // Admin sends invoice
    $invStmt = $pdo->prepare("UPDATE projects SET invoice_sent_at = NOW(), updated_at = NOW() WHERE id = ? AND status_type = 'completed'");
    $invStmt->execute([$testProjectId]);
    assert_true('Action: Admin sends invoice', $invStmt->rowCount() > 0);

    $p = $pdo->query("SELECT * FROM projects WHERE id = {$testProjectId}")->fetch();
    assert_true('State 3: invoice_sent_at is now set', !empty($p['invoice_sent_at']), "Timestamp: " . $p['invoice_sent_at']);

    // Test API gate: Payment is now allowed!
    $canPayState3 = (($p['status_type'] ?? '') === 'completed' && !empty($p['invoice_sent_at']));
    assert_true('Backend Gate: Payment unlocked once invoice is sent', $canPayState3 === true);

    // Check dashboard banner query: SHOULD find this project!
    $bStmt->execute(['uid' => $clientId, 'pid' => $testProjectId]);
    $bannerProj = $bStmt->fetch();
    assert_true('Dashboard Banner: Visible once invoice is sent and awaiting payment', !empty($bannerProj) && (int)$bannerProj['id'] === $testProjectId);

    // Submit Payment declaration
    $payableAmount = parse_budget_amount($p['budget']);
    assert_true('Budget parser: Correctly parsed $2,500.00', $payableAmount === 2500.00);

    $payStmt = $pdo->prepare("INSERT INTO payments (project_id, user_id, amount, payment_method, status, submitted_at) VALUES (?, ?, ?, 'GCash', 'pending', NOW())");
    $payStmt->execute([$testProjectId, $clientId, $payableAmount]);
    $paymentId = (int)$pdo->lastInsertId();
    assert_true('Action: Payment declaration created', $paymentId > 0, "Payment ID: {$paymentId}");

    // Reference code generation
    $ref = format_payment_ref($paymentId);
    assert_true('Reference Number: Generates PAY- format', str_starts_with($ref, 'PAY-'), "Ref: {$ref}");

    // Admin project-detail logic: Latest payment found
    $latestPay = get_project_latest_payment($pdo, $testProjectId);
    assert_true('Admin Hub: Found latest payment', !empty($latestPay) && $latestPay['status'] === 'pending');

    // Admin verifies payment
    $vStmt = $pdo->prepare("UPDATE payments SET status = 'verified', verified_at = NOW() WHERE id = ?");
    $vStmt->execute([$paymentId]);
    assert_true('Action: Admin verifies payment', $vStmt->rowCount() > 0);

    // Dashboard banner should now disappear because verified payment exists
    $bStmt->execute(['uid' => $clientId, 'pid' => $testProjectId]);
    assert_true('Dashboard Banner: Removed once payment is verified', empty($bStmt->fetch()));

    // Client latest payment shows verified
    $latestPay = get_project_latest_payment($pdo, $testProjectId);
    assert_true('Client Card: Shows verified status', $latestPay['status'] === 'verified');

} finally {
    // Cleanup test data
    $pdo->exec("DELETE FROM payments WHERE project_id = {$testProjectId}");
    $pdo->exec("DELETE FROM projects WHERE id = {$testProjectId}");
    echo "\nCleanup: Removed test project {$testProjectId} and payments.\n";
}

$allPass = true;
foreach ($results as $r) {
    if (!$r['pass']) {
        $allPass = false;
        break;
    }
}

echo "\nSummary: " . count($results) . " assertions. All passed: " . ($allPass ? "YES" : "NO") . "\n";
exit($allPass ? 0 : 1);