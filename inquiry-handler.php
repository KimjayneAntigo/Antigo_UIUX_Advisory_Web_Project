<?php
/**
 * inquiry-handler.php
 * Inquiry form submission handler.
 * Accepts POST (AJAX or direct form); validates and returns JSON.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/routing.php';

header('Content-Type: application/json; charset=UTF-8');

// Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// Input collection
$name        = trim($_POST['name']        ?? '');
$email       = trim($_POST['email']       ?? '');
$phone       = trim($_POST['phone']       ?? '');
$company     = trim($_POST['company']     ?? '');
$projectType = trim($_POST['projectType'] ?? '');
$budget      = trim($_POST['budget']      ?? '');
$timeline    = trim($_POST['timeline']    ?? '');
$description = trim($_POST['description'] ?? '');

// If user is authenticated as client, lock identity to session values
$user_id = is_logged_in() ? (int)$_SESSION['user_id'] : null;
if ($user_id) {
    if (!empty($_SESSION['user_name'])) {
        $name = $_SESSION['user_name'];
    }
    if (!empty($_SESSION['email'])) {
        $email = $_SESSION['email'];
    }
}

// Allowed values for enum-style fields
$allowed_services = [
    'UI Design',
    'UX Research',
    'Wireframing',
    'Interactive Prototyping',
    'Responsive Web Design',
    'Design Systems',
];

$allowed_budgets = [
    'Under ₱50,000',
    '₱50,000 – ₱150,000',
    '₱150,000 – ₱300,000',
    '₱300,000+',
    'Under $50,000',
    '$50,000 – $150,000',
    '$150,000 – $300,000',
    '$300,000+',
];

$allowed_timelines = [
    'Urgent (< 2 weeks)',
    '1 Month',
    '2–3 Months',
    'Flexible',
];

// Validation
$field_errors = [];

if (empty($name)) {
    $field_errors['name'] = 'Full name is required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $field_errors['email'] = 'A valid email address is required.';
}
if (empty($phone)) {
    $field_errors['phone'] = 'Phone number is required.';
}
if (!in_array($projectType, $allowed_services, true)) {
    $field_errors['projectType'] = 'Please select a valid primary service.';
}
if (!in_array($budget, $allowed_budgets, true)) {
    $field_errors['budget'] = 'Please select a valid budget range.';
}
if (!in_array($timeline, $allowed_timelines, true)) {
    $field_errors['timeline'] = 'Please select a valid timeline.';
}
if (mb_strlen($description) < 20) {
    $field_errors['description'] = 'Project description must be at least 20 characters.';
}

if (!empty($field_errors)) {
    http_response_code(422);
    echo json_encode([
        'success'      => false,
        'field_errors' => $field_errors,
        'error'        => reset($field_errors),
    ]);
    exit;
}

// File upload handling (optional)
$storedFileName = null;
if (!empty($_FILES['file']['name'])) {
    $file      = $_FILES['file'];
    $origName  = basename($file['name']);
    $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    $allowed_exts = ['pdf', 'doc', 'docx', 'zip', 'fig', 'png', 'jpg', 'jpeg'];

    if (!in_array($ext, $allowed_exts, true)) {
        http_response_code(422);
        echo json_encode([
            'success'      => false,
            'field_errors' => ['file' => 'Invalid file format. Allowed: PDF, DOC, DOCX, ZIP, FIG, PNG, JPG.'],
            'error'        => 'Invalid file format.',
        ]);
        exit;
    }

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

// Sanitization for storage
$sanitizedName        = sanitize_input($name);
$sanitizedEmail       = sanitize_input($email);
$sanitizedPhone       = sanitize_input($phone);
$sanitizedCompany     = sanitize_input($company);
$sanitizedProjectType = sanitize_input($projectType);
$sanitizedBudget      = str_replace('₱', '$', sanitize_input($budget));
$sanitizedTimeline    = sanitize_input($timeline);
$sanitizedDescription = sanitize_input($description);

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
        $sanitizedName,
        $sanitizedEmail,
        $sanitizedPhone,
        $sanitizedCompany ?: null,
        $sanitizedProjectType,
        $sanitizedBudget,
        $sanitizedTimeline,
        $sanitizedDescription,
        $storedFileName,
    ]);

    $new_inquiry_id = (int) $pdo->lastInsertId();
    $ref_code       = 'INQ-' . str_pad((string)$new_inquiry_id, 4, '0', STR_PAD_LEFT);

    $updStmt = $pdo->prepare('UPDATE inquiries SET ref_code = ? WHERE id = ?');
    $updStmt->execute([$ref_code, $new_inquiry_id]);

    $pdo->commit();

    // Session linking logic
    if (!$user_id) {
        // Guest submission: track in session for auto-linking upon registration
        $_SESSION['pending_link_inquiry_id'] = $new_inquiry_id;
    } else {
        // Logged-in client: flash confirmation message
        set_flash('success', 'Your project inquiry has been submitted! Our team will review it shortly.');
    }

    echo json_encode([
        'success'       => true,
        'is_logged_in'  => (bool)$user_id,
        'redirect'      => $user_id ? 'client-dashboard.php' : null,
        'inquiry_id'    => $ref_code,
        'raw_id'        => $new_inquiry_id,
        'name'          => $sanitizedName,
        'email'         => $sanitizedEmail,
        'phone'         => $sanitizedPhone,
        'service'       => $sanitizedProjectType,
        'budget'        => $sanitizedBudget,
        'timeline'      => $sanitizedTimeline,
    ]);
    exit;

} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('inquiry-handler database exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error processing your inquiry. Please try again.',
    ]);
    exit;
}
