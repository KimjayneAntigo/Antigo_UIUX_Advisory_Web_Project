<?php

session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
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
$allowed_budgets  = ['Under $50,000','$50,000 – $150,000','$150,000 – $300,000','$300,000+'];
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
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

// INSERT INTO INQUIRIES
// nullable user_id — guest submission; linked later via session token on registration
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$stmt = $pdo->prepare(
    'INSERT INTO inquiries
        (user_id, name, email, company, service, budget, timeline, description, attached_file, status, created_at)
     VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, \'new\', NOW())'
);
$stmt->execute([
    $user_id,
    $name,
    $email,
    $company     ?: null,
    $projectType,
    $budget,
    $timeline,
    $description,
    $fileName    ?: null,
]);

$new_inquiry_id = (int) $pdo->lastInsertId();

// STORE TRUSTED-SESSION 
// Store ONLY this record's ID in session NOT the email.
// register.php will link by this ID, not by email match, preventing account-takeover via email enumeration.
if (!$user_id) {
    // Only store for guest submissions, logged-in clients are already linked
    $_SESSION['pending_link_inquiry_id'] = $new_inquiry_id;
}

//SUCCESS RESPONSE
echo json_encode([
    'success'    => true,
    'inquiry_id' => 'INQ-' . str_pad($new_inquiry_id, 4, '0', STR_PAD_LEFT),
    'raw_id'     => $new_inquiry_id,
    'name'       => $name,
    'service'    => $projectType,
    'budget'     => $budget,
]);
exit;
