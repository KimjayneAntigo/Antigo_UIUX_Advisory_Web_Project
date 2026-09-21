<?php
/**
 * HTTP-level end-to-end test simulating Admin and Client browser sessions.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$baseUrl = 'http://localhost/Antigo_WebApp';

function http_req(string $url, string $method = 'GET', $data = null, string $cookieFile = ''): array {
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

$adminCookie = __DIR__ . '/cookie_admin_inv.txt';
$clientCookie = __DIR__ . '/cookie_client_inv.txt';
if (file_exists($adminCookie)) unlink($adminCookie);
if (file_exists($clientCookie)) unlink($clientCookie);

echo "=== Running HTTP End-to-End Invoice-Gated Payment Flow Tests ===\n\n";

// 1. Admin login
$loginPage = http_req("{$baseUrl}/login.php", 'GET', null, $adminCookie);
$csrf = extract_csrf($loginPage['body']);
$adminLogin = http_req("{$baseUrl}/login.php", 'POST', [
    'email' => 'admin@antigo.com',
    'password' => 'password',
    'role' => 'admin',
    'csrf_token' => $csrf
], $adminCookie);

echo "Admin Login Code: {$adminLogin['code']}\n";

// 2. Client login
$loginPage = http_req("{$baseUrl}/login.php", 'GET', null, $clientCookie);
$csrf = extract_csrf($loginPage['body']);
$clientLogin = http_req("{$baseUrl}/login.php", 'POST', [
    'email' => 'demo@client.com',
    'password' => 'password',
    'role' => 'client',
    'csrf_token' => $csrf
], $clientCookie);

echo "Client Login Code: {$clientLogin['code']}\n";

$clientUser = $pdo->query("SELECT id FROM users WHERE email = 'demo@client.com'")->fetch();
$clientId = (int)$clientUser['id'];

// Create test project in 'in_design'
$stmt = $pdo->prepare("INSERT INTO projects (user_id, project_code, title, category, client_name, client_email, company, budget, status, status_type, current_phase, phase_name, progress, invoice_sent_at, created_at, updated_at)
VALUES (?, 'PRJ-E2E-INV', 'E2E Invoice QA Test', 'UI/UX Design', 'Demo Client', 'demo@client.com', 'Demo Co', '$3,200.00', 'In Design', 'in_design', 2, 'Wireframing', 40, NULL, NOW(), NOW())");
$stmt->execute([$clientId]);
$pid = (int)$pdo->lastInsertId();
echo "Created Test Project ID: {$pid}\n";

try {
    // Admin checks detail page
    $adminView = http_req("{$baseUrl}/admin/project-detail.php?id={$pid}", 'GET', null, $adminCookie);
    $hasDisabledButton = strpos($adminView['body'], 'Available once project is marked complete') !== false;
    echo "[TEST A1] Admin sees disabled 'Send Invoice' with tooltip: " . ($hasDisabledButton ? "PASS" : "FAIL") . "\n";

    // Client checks detail page: No payment card or notice (since not complete)
    $clientView = http_req("{$baseUrl}/client/project-detail.php?id={$pid}", 'GET', null, $clientCookie);
    $hasPaymentCardA = strpos($clientView['body'], 'id="paymentCardContainer"') !== false;
    echo "[TEST A2] Client does NOT see payment card while in_design: " . (!$hasPaymentCardA ? "PASS" : "FAIL") . "\n";

    // Client dashboard: No banner
    $dashView = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $clientCookie);
    $hasBannerA = strpos($dashView['body'], 'is complete — invoice ready') !== false;
    echo "[TEST A3] Client dashboard has no invoice banner: " . (!$hasBannerA ? "PASS" : "FAIL") . "\n";

    // Direct payment attempt by client should fail
    $csrfClient = extract_csrf($clientView['body']);
    $payAttempt1 = http_req("{$baseUrl}/payment-handler.php", 'POST', [
        'project_id' => $pid,
        'payment_method' => 'GCash',
        'csrf_token' => $csrfClient
    ], $clientCookie);
    $resp1 = json_decode($payAttempt1['body'], true);
    $payBlockedA = ($payAttempt1['code'] === 400 && strpos($resp1['error'] ?? '', 'invoice') !== false);
    echo "[TEST A4] Payment-handler rejects payment before completion & invoice (HTTP {$payAttempt1['code']}): " . ($payBlockedA ? "PASS" : "FAIL") . "\n";

    // Project marked complete, but invoice NOT sent
    $adminCsrf = extract_csrf($adminView['body']);
    $archiveReq = http_req("{$baseUrl}/admin/project-detail.php?id={$pid}", 'POST', [
        'action' => 'archive',
        'csrf_token' => $adminCsrf
    ], $adminCookie);

    // Verify DB status
    $p = $pdo->query("SELECT * FROM projects WHERE id = {$pid}")->fetch();
    echo "[TEST B1] Project status_type is now completed: " . ($p['status_type'] === 'completed' ? "PASS" : "FAIL") . "\n";

    // Admin detail page now has active "Send Invoice" button
    $adminViewB = http_req("{$baseUrl}/admin/project-detail.php?id={$pid}", 'GET', null, $adminCookie);
    $hasActiveSendInv = (strpos($adminViewB['body'], 'value="send_invoice"') !== false && strpos($adminViewB['body'], 'Send Invoice') !== false);
    echo "[TEST B2] Admin sees active 'Send Invoice' button: " . ($hasActiveSendInv ? "PASS" : "FAIL") . "\n";

    // Client detail page now shows the QUIETER notice: "Your project is complete — the studio will send your final invoice shortly"
    $clientViewB = http_req("{$baseUrl}/client/project-detail.php?id={$pid}", 'GET', null, $clientCookie);
    $hasQuietNotice = strpos($clientViewB['body'], 'Your project is complete — the studio will send your final invoice shortly') !== false;
    $hasPaymentFormB = strpos($clientViewB['body'], 'id="paymentSubmitForm"') !== false;
    echo "[TEST B3] Client sees quiet completion notice: " . ($hasQuietNotice ? "PASS" : "FAIL") . "\n";
    echo "[TEST B4] Client does NOT see payment form yet: " . (!$hasPaymentFormB ? "PASS" : "FAIL") . "\n";

    // Client dashboard still does NOT have the invoice banner
    $dashViewB = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $clientCookie);
    $hasBannerB = strpos($dashViewB['body'], 'is complete — invoice ready') !== false;
    echo "[TEST B5] Client dashboard still has no invoice banner: " . (!$hasBannerB ? "PASS" : "FAIL") . "\n";

    // Direct payment attempt still rejected
    $csrfClientB = extract_csrf($clientViewB['body']);
    $payAttempt2 = http_req("{$baseUrl}/payment-handler.php", 'POST', [
        'project_id' => $pid,
        'payment_method' => 'GCash',
        'csrf_token' => $csrfClientB
    ], $clientCookie);
    $resp2 = json_decode($payAttempt2['body'], true);
    $payBlockedB = ($payAttempt2['code'] === 400 && strpos($resp2['error'] ?? '', 'invoice') !== false);
    echo "[TEST B6] Payment-handler rejects payment when complete but invoice not sent (HTTP {$payAttempt2['code']}): " . ($payBlockedB ? "PASS" : "FAIL") . "\n";

    //Admin sends the invoice
    $adminCsrfB = extract_csrf($adminViewB['body']);
    $sendInvReq = http_req("{$baseUrl}/admin/project-detail.php?id={$pid}", 'POST', [
        'action' => 'send_invoice',
        'csrf_token' => $adminCsrfB
    ], $adminCookie);

    $pC = $pdo->query("SELECT * FROM projects WHERE id = {$pid}")->fetch();
    echo "[TEST C1] Project invoice_sent_at is set in DB: " . (!empty($pC['invoice_sent_at']) ? "PASS ({$pC['invoice_sent_at']})" : "FAIL") . "\n";

    // Admin detail page now shows "Invoice sent [date]" status indicator
    $adminViewC = http_req("{$baseUrl}/admin/project-detail.php?id={$pid}", 'GET', null, $adminCookie);
    $hasSentIndicator = strpos($adminViewC['body'], 'Invoice sent') !== false;
    $hasResendButton = strpos($adminViewC['body'], 'value="send_invoice"') !== false;
    echo "[TEST C2] Admin sees 'Invoice sent [date]' indicator: " . ($hasSentIndicator ? "PASS" : "FAIL") . "\n";
    echo "[TEST C3] Admin cannot re-send repeatedly (no send_invoice form): " . (!$hasResendButton ? "PASS" : "FAIL") . "\n";

    // Client dashboard now shows the prominent banner: "Your project '[title]' is complete — invoice ready. [Pay Now →]"
    $dashViewC = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $clientCookie);
    $hasBannerC = (strpos($dashViewC['body'], 'is complete — invoice ready') !== false && strpos($dashViewC['body'], 'Pay Now') !== false);
    echo "[TEST C4] Client dashboard shows top notification banner: " . ($hasBannerC ? "PASS" : "FAIL") . "\n";

    // Client detail page now shows the full payment declaration form
    $clientViewC = http_req("{$baseUrl}/client/project-detail.php?id={$pid}", 'GET', null, $clientCookie);
    $hasPaymentFormC = strpos($clientViewC['body'], 'id="paymentSubmitForm"') !== false;
    echo "[TEST C5] Client project-detail displays payment declaration form: " . ($hasPaymentFormC ? "PASS" : "FAIL") . "\n";

    // Client submits payment declaration
    $csrfClientC = extract_csrf($clientViewC['body']);
    $payAttempt3 = http_req("{$baseUrl}/payment-handler.php", 'POST', [
        'project_id' => $pid,
        'payment_method' => 'GCash',
        'csrf_token' => $csrfClientC
    ], $clientCookie);
    $resp3 = json_decode($payAttempt3['body'], true);
    $paySuccess = ($payAttempt3['code'] === 200 && !empty($resp3['success']) && !empty($resp3['reference_number']));
    echo "[TEST D1] Payment declaration successfully submitted (HTTP {$payAttempt3['code']}): " . ($paySuccess ? "PASS ({$resp3['reference_number']})" : "FAIL") . "\n";

    // Client project detail now reflects Pending payment state with reference number
    $clientViewD = http_req("{$baseUrl}/client/project-detail.php?id={$pid}", 'GET', null, $clientCookie);
    $hasPendingRef = strpos($clientViewD['body'], $resp3['reference_number']) !== false;
    echo "[TEST D2] Client project-detail displays payment reference number ({$resp3['reference_number']}): " . ($hasPendingRef ? "PASS" : "FAIL") . "\n";

    // Admin detail page now shows the payment status badge (Pending: PAY-XXXXX) instead of invoice button
    $adminViewD = http_req("{$baseUrl}/admin/project-detail.php?id={$pid}", 'GET', null, $adminCookie);
    $hasAdminPaymentBadge = (strpos($adminViewD['body'], $resp3['reference_number']) !== false && strpos($adminViewD['body'], 'Pending:') !== false);
    echo "[TEST D3] Admin project-detail displays Payment badge with reference number: " . ($hasAdminPaymentBadge ? "PASS" : "FAIL") . "\n";

    // Admin payments hub displays this payment
    $adminHub = http_req("{$baseUrl}/admin/payments.php", 'GET', null, $adminCookie);
    $hasInPaymentsHub = strpos($adminHub['body'], $resp3['reference_number']) !== false;
    echo "[TEST D4] Admin Payments Hub lists payment reference: " . ($hasInPaymentsHub ? "PASS" : "FAIL") . "\n";

    //Admin verifies payment
    $payId = (int)$pdo->query("SELECT id FROM payments WHERE project_id = {$pid}")->fetchColumn();
    $hubCsrf = extract_csrf($adminHub['body']);
    $verifyReq = http_req("{$baseUrl}/admin/payments.php", 'POST', [
        'action' => 'verify',
        'payment_id' => $payId,
        'csrf_token' => $hubCsrf
    ], $adminCookie);

    // Client dashboard banner should now be gone because payment is verified!
    $dashViewE = http_req("{$baseUrl}/client/dashboard.php", 'GET', null, $clientCookie);
    $hasBannerE = strpos($dashViewE['body'], "Your project '{$p['title']}' is complete — invoice ready") !== false;
    echo "[TEST E1] Client dashboard banner automatically cleared after payment verification: " . (!$hasBannerE ? "PASS" : "FAIL") . "\n";

    // Client project detail displays Paid status
    $clientViewE = http_req("{$baseUrl}/client/project-detail.php?id={$pid}", 'GET', null, $clientCookie);
    $hasPaidClient = strpos($clientViewE['body'], 'Thank you! Your payment has been verified') !== false;
    echo "[TEST E2] Client sees verified payment badge & thank you note: " . ($hasPaidClient ? "PASS" : "FAIL") . "\n";

} finally {
    // Cleanup test data and cookies
    $pdo->exec("DELETE FROM payments WHERE project_id = {$pid}");
    $pdo->exec("DELETE FROM projects WHERE id = {$pid}");
    if (file_exists($adminCookie)) unlink($adminCookie);
    if (file_exists($clientCookie)) unlink($clientCookie);
    echo "\nTest cleanup completed.\n";
}