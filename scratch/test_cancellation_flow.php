<?php
/**
 * Comprehensive automated verification for Project Cancellation (Client + Admin).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = 'http://localhost/Antigo_WebApp';
$results = [];

function assert_test(string $name, bool $cond, string $msg = ''): void {
    global $results;
    $results[] = ['name' => $name, 'pass' => $cond, 'msg' => $msg];
    echo ($cond ? "[PASS] " : "[FAIL] ") . $name . ($msg ? " - {$msg}" : "") . "\n";
}

function http_req(string $url, string $method = 'GET', $data = null, string $cookieFile = '', array $extraHeaders = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    
    if (!empty($extraHeaders)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
    }
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headerStr = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    $err = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'headers' => $headerStr,
        'body' => $body,
        'error' => $err
    ];
}

function extract_csrf(string $html): string {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

echo "========================================================\n";
echo "=== Project Cancellation (Client + Admin) Test Suite ===\n";
echo "========================================================\n\n";

// ---  SCHEMA VERIFICATION ---
echo "--- 1. Database Schema Checks ---\n";
$colStmt = $pdo->query("SHOW COLUMNS FROM projects LIKE 'status_type'");
$colStatus = $colStmt->fetch();
assert_test('Schema: status_type includes cancelled', strpos($colStatus['Type'], "'cancelled'") !== false, $colStatus['Type']);

$colAt = $pdo->query("SHOW COLUMNS FROM projects LIKE 'cancelled_at'")->fetch();
assert_test('Schema: cancelled_at column exists', !empty($colAt));

$colBy = $pdo->query("SHOW COLUMNS FROM projects LIKE 'cancelled_by'")->fetch();
assert_test('Schema: cancelled_by column exists', !empty($colBy));

$colReason = $pdo->query("SHOW COLUMNS FROM projects LIKE 'cancellation_reason'")->fetch();
assert_test('Schema: cancellation_reason column exists', !empty($colReason));

$fkCheck = $pdo->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = 'antigo_advisory_db' AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'cancelled_by' AND REFERENCED_TABLE_NAME = 'users'")->fetch();
assert_test('Schema: fk_projects_cancelled_by exists', !empty($fkCheck), $fkCheck['CONSTRAINT_NAME'] ?? 'missing');

// --- USER SETUP & AUTH PREP ---
echo "\n--- 2. Users Setup & Authentication ---\n";
// Admin
$adminUser = $pdo->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1")->fetch();
assert_test('User: Admin exists', !empty($adminUser));

// Client 1 (demo@client.com)
$client1 = $pdo->query("SELECT * FROM users WHERE email = 'demo@client.com'")->fetch();
if (!$client1) {
    $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES ('Demo Client', 'demo@client.com', ?, 'client', NOW())")
        ->execute([password_hash('password', PASSWORD_DEFAULT)]);
    $client1 = $pdo->query("SELECT * FROM users WHERE email = 'demo@client.com'")->fetch();
}
assert_test('User: Client 1 (demo@client.com) exists', !empty($client1));

// Client 2 (other client for IDOR tests)
$client2 = $pdo->query("SELECT * FROM users WHERE email = 'client2_test@client.com'")->fetch();
if (!$client2) {
    $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES ('Other Client', 'client2_test@client.com', ?, 'client', NOW())")
        ->execute([password_hash('password', PASSWORD_DEFAULT)]);
    $client2 = $pdo->query("SELECT * FROM users WHERE email = 'client2_test@client.com'")->fetch();
}
assert_test('User: Client 2 (client2_test@client.com) exists', !empty($client2));

$adminCookie   = __DIR__ . '/cookie_admin_cancel.txt';
$client1Cookie = __DIR__ . '/cookie_client1_cancel.txt';
$client2Cookie = __DIR__ . '/cookie_client2_cancel.txt';
@unlink($adminCookie);
@unlink($client1Cookie);
@unlink($client2Cookie);

// Login Admin
$res = http_req("{$baseUrl}/login.php", 'GET', null, $adminCookie);
$csrf = extract_csrf($res['body']);
$res = http_req("{$baseUrl}/login.php", 'POST', ['email' => $adminUser['email'], 'password' => 'password', 'role' => 'admin', 'csrf_token' => $csrf], $adminCookie);
assert_test('Auth: Admin logged in', strpos($res['body'], 'Studio Command Center') !== false || strpos($res['headers'], 'admin-dashboard.php') !== false || $res['code'] === 200);

// Login Client 1
$res = http_req("{$baseUrl}/login.php", 'GET', null, $client1Cookie);
$csrf = extract_csrf($res['body']);
$res = http_req("{$baseUrl}/login.php", 'POST', ['email' => 'demo@client.com', 'password' => 'password', 'role' => 'client', 'csrf_token' => $csrf], $client1Cookie);
assert_test('Auth: Client 1 logged in', strpos($res['body'], 'Client Portal') !== false || strpos($res['headers'], 'client/dashboard.php') !== false || $res['code'] === 200);

// Login Client 2
$res = http_req("{$baseUrl}/login.php", 'GET', null, $client2Cookie);
$csrf = extract_csrf($res['body']);
$res = http_req("{$baseUrl}/login.php", 'POST', ['email' => 'client2_test@client.com', 'password' => 'password', 'role' => 'client', 'csrf_token' => $csrf], $client2Cookie);
assert_test('Auth: Client 2 logged in', strpos($res['body'], 'Client Portal') !== false || strpos($res['headers'], 'client/dashboard.php') !== false || $res['code'] === 200);

// Extract active CSRF tokens from logged-in sessions
$res = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $client1Cookie);
$client1Csrf = extract_csrf($res['body']);
$res = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $client2Cookie);
$client2Csrf = extract_csrf($res['body']);
$res = http_req("{$baseUrl}/admin-dashboard.php", 'GET', null, $adminCookie);
$adminCsrf = extract_csrf($res['body']);

// --- CREATE TEST PROJECTS ---
echo "\n--- 3. Creating Test Projects ---\n";
$pdo->exec("DELETE FROM projects WHERE project_code LIKE 'PRJ-TEST-CANCEL%'");

$uid = substr(uniqid(), -4);
$codeA = "PRJ-TC-A-{$uid}";
$codeB = "PRJ-TC-B-{$uid}";
$codeC = "PRJ-TC-C-{$uid}";

// Project A: Owned by Client 1 (In Design)
$stmt = $pdo->prepare("INSERT INTO projects (user_id, project_code, title, category, client_name, client_email, company, budget, due_date, status, status_type, current_phase, phase_name, progress, created_at, updated_at)
VALUES (?, ?, 'Cancellation Test Project A', 'UI/UX Design', 'Demo Client', 'demo@client.com', 'Demo Co', '$5,000.00', '2026-12-31', 'In Design', 'in_design', 2, 'Wireframing', 40, NOW(), NOW())");
$stmt->execute([(int)$client1['id'], $codeA]);
$projAId = (int)$pdo->lastInsertId();

// Project B: Owned by Client 1 (Completed)
$stmt = $pdo->prepare("INSERT INTO projects (user_id, project_code, title, category, client_name, client_email, company, budget, due_date, status, status_type, current_phase, phase_name, progress, invoice_sent_at, created_at, updated_at)
VALUES (?, ?, 'Completed Project B', 'Brand Identity', 'Demo Client', 'demo@client.com', 'Demo Co', '$8,000.00', '2026-12-31', 'Completed', 'completed', 5, 'Final Handoff', 100, NOW(), NOW(), NOW())");
$stmt->execute([(int)$client1['id'], $codeB]);
$projBId = (int)$pdo->lastInsertId();

// Project C: Owned by Client 1 (In Design, will be cancelled by Admin)
$stmt = $pdo->prepare("INSERT INTO projects (user_id, project_code, title, category, client_name, client_email, company, budget, due_date, status, status_type, current_phase, phase_name, progress, created_at, updated_at)
VALUES (?, ?, 'Cancellation Test Project C', 'Web Development', 'Demo Client', 'demo@client.com', 'Demo Co', '$12,000.00', '2026-12-31', 'In Design', 'in_design', 3, 'Prototyping', 60, NOW(), NOW())");
$stmt->execute([(int)$client1['id'], $codeC]);
$projCId = (int)$pdo->lastInsertId();

echo "Created Proj A: {$projAId}, Proj B: {$projBId}, Proj C: {$projCId}\n";

// Add sample message and file to Project A to verify history preservation
$pdo->prepare("INSERT INTO project_messages (project_id, sender, role, message, created_at)
VALUES (?, 'Demo Client', 'client', 'Hello, this is a test pre-cancellation message.', NOW())")->execute([$projAId]);

try {
    // --- GUEST / UNAUTHENTICATED PROTECTION ---
    echo "\n--- 4. Unauthenticated & CSRF Protection ---\n";
    $guestRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projAId, 'reason' => 'test']);
    assert_test('Security: Guest cannot cancel project', $guestRes['code'] === 401 || strpos($guestRes['headers'], 'login.php') !== false || strpos($guestRes['body'], 'Unauthorized') !== false);

    $badCsrfRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projAId, 'reason' => 'test', 'csrf_token' => 'invalid_csrf'], $client1Cookie, ['Accept: application/json']);
    $badCsrfJson = json_decode($badCsrfRes['body'], true);
    assert_test('Security: Invalid CSRF rejected', ($badCsrfJson['success'] ?? true) === false, $badCsrfRes['body']);

    // --- ANTI-IDOR CHECK ---
    echo "\n--- 5. Anti-IDOR Authorization Tests ---\n";
    // Client 2 attempts to cancel Project A (owned by Client 1)
    $idorRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projAId, 'reason' => 'Malicious cancellation', 'csrf_token' => $client2Csrf], $client2Cookie, ['Accept: application/json']);
    $idorJson = json_decode($idorRes['body'], true);
    assert_test('Anti-IDOR: Client 2 cannot cancel Client 1 project', ($idorJson['success'] ?? true) === false, $idorRes['body']);

    // Verify Project A is still in_design
    $checkA = $pdo->query("SELECT status_type FROM projects WHERE id = {$projAId}")->fetch();
    assert_test('Anti-IDOR: Project A status remains untouched', $checkA['status_type'] === 'in_design');

    // --- CLIENT CANCELLING THEIR OWN PROJECT ---
    echo "\n--- 6. Client Cancels Own Project ---\n";
    $cancelReasonA = "Client decided to pivot internal strategy and pause external design.";
    $clientCancelRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projAId, 'reason' => $cancelReasonA, 'csrf_token' => $client1Csrf], $client1Cookie, ['Accept: application/json']);
    $clientCancelJson = json_decode($clientCancelRes['body'], true);
    assert_test('Cancellation: Client 1 successfully cancels Project A', ($clientCancelJson['success'] ?? false) === true, $clientCancelRes['body']);

    // DB Verification for Project A
    $rowA = $pdo->query("SELECT * FROM projects WHERE id = {$projAId}")->fetch();
    assert_test('DB Verify: Project A status_type is cancelled', $rowA['status_type'] === 'cancelled');
    assert_test('DB Verify: Project A status is Cancelled', $rowA['status'] === 'Cancelled');
    assert_test('DB Verify: Project A cancelled_at is populated', !empty($rowA['cancelled_at']));
    assert_test('DB Verify: Project A cancelled_by equals Client 1 ID', (int)$rowA['cancelled_by'] === (int)$client1['id']);
    assert_test('DB Verify: Project A cancellation_reason saved correctly', $rowA['cancellation_reason'] === $cancelReasonA);

    // --- STATE GUARD CHECKS ---
    echo "\n--- 7. State Guard Tests ---\n";
    // Attempt to cancel an already cancelled project
    $alreadyRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projAId, 'reason' => 'repeat', 'csrf_token' => $client1Csrf], $client1Cookie, ['Accept: application/json']);
    $alreadyJson = json_decode($alreadyRes['body'], true);
    assert_test('State Guard: Cannot cancel an already cancelled project', ($alreadyJson['success'] ?? true) === false && strpos($alreadyJson['error'] ?? '', 'already cancelled') !== false, $alreadyRes['body']);

    // Attempt to cancel a completed project (Project B)
    $compRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projBId, 'reason' => 'cancel completed', 'csrf_token' => $client1Csrf], $client1Cookie, ['Accept: application/json']);
    $compJson = json_decode($compRes['body'], true);
    assert_test('State Guard: Cannot cancel a completed project', ($compJson['success'] ?? true) === false && strpos($compJson['error'] ?? '', 'Completed') !== false, $compRes['body']);

    // --- ADMIN CANCELLING A PROJECT ---
    echo "\n--- 8. Admin Cancels Project C ---\n";
    $cancelReasonC = "Project cancelled by Admin due to resource re-allocation.";
    $adminCancelRes = http_req("{$baseUrl}/cancel-project-handler.php", 'POST', ['project_id' => $projCId, 'reason' => $cancelReasonC, 'csrf_token' => $adminCsrf], $adminCookie, ['Accept: application/json']);
    $adminCancelJson = json_decode($adminCancelRes['body'], true);
    assert_test('Cancellation: Admin cancels Project C', ($adminCancelJson['success'] ?? false) === true, $adminCancelRes['body']);

    // DB Verification for Project C
    $rowC = $pdo->query("SELECT * FROM projects WHERE id = {$projCId}")->fetch();
    assert_test('DB Verify: Project C status_type is cancelled', $rowC['status_type'] === 'cancelled');
    assert_test('DB Verify: Project C cancelled_by equals Admin ID', (int)$rowC['cancelled_by'] === (int)$adminUser['id']);
    assert_test('DB Verify: Project C cancellation_reason matches', $rowC['cancellation_reason'] === $cancelReasonC);

    // --- MUTATION LOCKS ON CANCELLED PROJECTS ---
    echo "\n--- 9. Server-Side Mutation Lock Tests ---\n";
    // Client attempts to send message on cancelled Project A
    $clientMsgRes = http_req("{$baseUrl}/client/project-detail.php?id={$projAId}", 'POST', ['message' => 'Post-cancellation message', 'csrf_token' => $client1Csrf], $client1Cookie);
    $msgCountA = $pdo->query("SELECT COUNT(*) FROM project_messages WHERE project_id = {$projAId}")->fetchColumn();
    assert_test('Mutation Lock: Client cannot post message to cancelled project', (int)$msgCountA === 1); // Only initial sample message exists

    // Admin attempts to update stage on cancelled Project A
    $adminStageRes = http_req("{$baseUrl}/admin/project-detail.php?id={$projAId}", 'POST', ['action' => 'update_stage', 'stage' => 3, 'csrf_token' => $adminCsrf], $adminCookie);
    $checkStageA = $pdo->query("SELECT current_phase FROM projects WHERE id = {$projAId}")->fetchColumn();
    assert_test('Mutation Lock: Admin cannot update phase on cancelled project', (int)$checkStageA === 2); // Remained at 2

    // Admin attempts to send invoice on cancelled Project A
    $adminInvRes = http_req("{$baseUrl}/admin/project-detail.php?id={$projAId}", 'POST', ['action' => 'send_invoice', 'csrf_token' => $adminCsrf], $adminCookie);
    $checkInvA = $pdo->query("SELECT invoice_sent_at FROM projects WHERE id = {$projAId}")->fetchColumn();
    assert_test('Mutation Lock: Admin cannot send invoice on cancelled project', empty($checkInvA));

    // Client attempts to submit payment on cancelled Project A
    $payRes = http_req("{$baseUrl}/payment-handler.php", 'POST', ['project_id' => $projAId, 'amount' => 5000, 'payment_method' => 'bank_transfer', 'reference_number' => 'REF123', 'csrf_token' => $client1Csrf], $client1Cookie, ['Accept: application/json']);
    $payJson = json_decode($payRes['body'], true);
    assert_test('Mutation Lock: Payment handler rejects cancelled project', ($payJson['success'] ?? true) === false && strpos($payJson['error'] ?? '', 'cancelled') !== false, $payRes['body']);

    // --- UI & DETAIL PAGE RENDERING ---
    echo "\n--- 10. Read-Only Detail View UI Rendering ---\n";
    // Client Detail View for Project A
    $clientDetailRes = http_req("{$baseUrl}/client/project-detail.php?id={$projAId}", 'GET', null, $client1Cookie);
    assert_test('Client UI: Shows Cancellation Banner', strpos($clientDetailRes['body'], 'This project was cancelled') !== false);
    assert_test('Client UI: Displays Cancellation Reason', strpos($clientDetailRes['body'], $cancelReasonA) !== false);
    assert_test('Client UI: Shows Canceller Name', strpos($clientDetailRes['body'], $client1['name']) !== false);
    assert_test('Client UI: Cancelling button is not rendered', strpos($clientDetailRes['body'], 'id="openCancelModalBtn"') === false);
    assert_test('Client UI: Message form disabled / read-only note shown', strpos($clientDetailRes['body'], 'Messaging is closed for this project') !== false);
    assert_test('Client UI: Historical messages preserved & displayed', strpos($clientDetailRes['body'], 'Hello, this is a test pre-cancellation message.') !== false);

    // Admin Detail View for Project A
    $adminDetailRes = http_req("{$baseUrl}/admin/project-detail.php?id={$projAId}", 'GET', null, $adminCookie);
    assert_test('Admin UI: Shows Cancellation Banner', strpos($adminDetailRes['body'], 'This project was cancelled') !== false);
    assert_test('Admin UI: Displays Cancellation Reason', strpos($adminDetailRes['body'], $cancelReasonA) !== false);
    assert_test('Admin UI: Shows "Project Cancelled" badge in header', strpos($adminDetailRes['body'], 'Project Cancelled') !== false);
    assert_test('Admin UI: Shows locked stage updater notice', strpos($adminDetailRes['body'], 'Phase controls are locked because this project has been cancelled') !== false);
    assert_test('Admin UI: File uploads locked note shown', strpos($adminDetailRes['body'], 'File uploads are locked because this project has been cancelled') !== false);
    assert_test('Admin UI: Message input locked note shown', strpos($adminDetailRes['body'], 'Messaging is closed for this project because it has been cancelled') !== false);

    // --- DSHBOARD RENDERING ---
    echo "\n--- 11. Dashboard Displays & Calculations ---\n";
    // Client Dashboard
    $clientDashRes = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $client1Cookie);
    assert_test('Client Dash: Renders Cancelled Projects Section', strpos($clientDashRes['body'], 'Cancelled Projects') !== false);
    assert_test('Client Dash: Project A listed in Cancelled Section', strpos($clientDashRes['body'], 'Cancellation Test Project A') !== false);
    assert_test('Client Dash: Shows "Cancelled" badge', strpos($clientDashRes['body'], 'Cancelled') !== false);

    // Admin Dashboard
    $adminDashRes = http_req("{$baseUrl}/admin-dashboard.php", 'GET', null, $adminCookie);
    assert_test('Admin Dash: Renders red Cancelled badge for Project A', strpos($adminDashRes['body'], 'color: #ef4444') !== false);
    assert_test('Admin Dash: Project A link still works', strpos($adminDashRes['body'], "admin/project-detail.php?id={$projAId}") !== false);

} finally {
    // ---  CLEANUP ---
    echo "\n--- 12. Cleanup Test Records ---\n";
    $pdo->prepare("DELETE FROM project_messages WHERE project_id IN (?, ?, ?)")->execute([$projAId, $projBId, $projCId]);
    $pdo->prepare("DELETE FROM projects WHERE id IN (?, ?, ?)")->execute([$projAId, $projBId, $projCId]);
    $pdo->prepare("DELETE FROM users WHERE email = 'client2_test@client.com'")->execute();
    @unlink($adminCookie);
    @unlink($client1Cookie);
    @unlink($client2Cookie);
    echo "Cleaned up test projects and temporary test users.\n";
}

$passCount = count(array_filter($results, fn($r) => $r['pass']));
$totalCount = count($results);
echo "\n========================================================\n";
echo "Cancellation Test Results: {$passCount}/{$totalCount} passed\n";
echo "========================================================\n";

if ($passCount !== $totalCount) {
    exit(1);
}