<?php
/**
 * inquiry-handler.php
 * Antigo UI/UX Advisory — Inquiry form submission handler.
 * Accepts POST (AJAX or direct form); validates in exact prompt order and returns JSON.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

// Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// INPUT COLLECTION & SANITISATION 
$name        = trim($_POST['name']        ?? '');
$email       = trim($_POST['email']       ?? '');
$company     = trim($_POST['company']     ?? '');
$projectType = trim($_POST['projectType'] ?? '');
$budget      = trim($_POST['budget']      ?? '');
$timeline    = trim($_POST['timeline']    ?? '');
$description = trim($_POST['description'] ?? '');
$fileName    = trim($_POST['fileName']    ?? '');   // client-provided filename only (no upload stored here)

// Allowed values for enum-style fields
$allowed_services = ['UI Design','UX Research','Wireframing','Interactive Prototyping','Responsive Web Design','Design Systems'];
$allowed_budgets  = ['Under ₱50,000','₱50,000 – ₱150,000','₱150,000 – ₱300,000','₱300,000+'];
$allowed_timelines= ['Urgent (< 2 weeks)','1 Month','2–3 Months','Flexible'];

// VALIDATION
$errors = [];

if (empty($name))                                           $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))             $errors[] = 'A valid email address is required.';
if (!in_array($projectType, $allowed_services, true))       $errors[] = 'Please select a valid primary service.';
if (!in_array($budget, $allowed_budgets, true))             $errors[] = 'Please select a valid budget range.';
if (!in_array($timeline, $allowed_timelines, true))         $errors[] = 'Please select a valid timeline.';
if (strlen($description) < 20)                              $errors[] = 'Project description must be at least 20 characters.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success'      => false,
        'field_errors' => $field_errors,
        'error'        => reset($field_errors), // First error message for alert fallback
    ]);
    exit;
}

// ── Save validated file to storage ───────────────────────────────────────────
if (!empty($_FILES['file']['name']) && empty($field_errors)) {
    $file      = $_FILES['file'];
    $origName  = basename($file['name']);
    $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $uploadDir = __DIR__ . '/uploads/inquiries/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeBase       = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
    $storedFileName = time() . '_' . substr($safeBase, 0, 40) . '.' . $ext;
    $destPath       = $uploadDir . $storedFileName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        error_log('inquiry-handler: move_uploaded_file failed for ' . $origName);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Server could not save uploaded file.']);
        exit;
    }
}

// ── Step 5: Sanitize every text field before insert ───────────────────────────
$name        = sanitize_input($raw_name);
$email       = sanitize_input($raw_email);
$phone       = sanitize_input($raw_phone);
$company     = sanitize_input($raw_company);
$projectType = sanitize_input($raw_projectType);
$budget      = sanitize_input($raw_budget ?: '$50,000 – $150,000');
$budget      = str_replace('₱', '$', $budget);
$timeline    = sanitize_input($raw_timeline);
$description = sanitize_input($raw_description);

// Check if user is logged in
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

// ── Database insert ───────────────────────────────────────────────────────────
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO inquiries
            (user_id, ref_code, name, email, phone, company, service,
             budget, timeline, description, attached_file, status, created_at)
         VALUES
            (?, \'PENDING\', ?, ?, ?, ?, ?, ?, ?, ?, ?, \'new\', NOW())'
    );

    $stmt->execute([
        $user_id,
        $name,
        $email,
        $phone,
        $company ?: null,
        $projectType,
        $budget,
        $timeline,
        $description,
        $storedFileName,
    ]);

$new_inquiry_id = (int) $pdo->lastInsertId();

// ── STORE TRUSTED-SESSION TOKEN ──────────────────────────────────────────────
// SECURITY: Store ONLY this record's ID in session — NOT the email.
// register.php will link by this ID, not by email match, preventing
// account-takeover via email enumeration.
if (!$user_id) {
    // Only store for guest submissions; logged-in clients are already linked
    $_SESSION['pending_link_inquiry_id'] = $new_inquiry_id;
}

// ── SUCCESS RESPONSE ─────────────────────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'inquiry_id' => $refCode,
    'raw_id'     => $newId,
    'name'       => $name,
    'email'      => $email,
    'phone'      => $phone,
    'service'    => $projectType,
    'budget'     => $budget,
    'timeline'   => $timeline,
]);
exit;
