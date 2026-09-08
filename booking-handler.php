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

// ── INPUT COLLECTION & SANITISATION ─────────────────────────────────────────
$guest_name   = trim($_POST['guest_name']   ?? '');
$guest_email  = trim($_POST['guest_email']  ?? '');
$service      = trim($_POST['service']      ?? '');
$duration     = (int)($_POST['duration']    ?? 60);
$price        = (int)($_POST['price']       ?? 0);
$date_raw     = trim($_POST['date']         ?? '');     // e.g. "2026-09-10"
$time_raw     = trim($_POST['time']         ?? '');     // e.g. "10:00 AM"
$format       = trim($_POST['format']       ?? '');
$inquiry_id   = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : null;

// Allowed values
$allowed_services = ['UI Design','UX Research','Wireframing','Interactive Prototyping','Responsive Web Design','Design Systems'];
$allowed_durations= [30, 60];
$allowed_formats  = ['Video Call (Google Meet)','Phone Call (+63)','In-Person Studio (Dumaguete)'];
$allowed_times    = ['09:00 AM','10:00 AM','11:30 AM','01:30 PM','03:00 PM','04:30 PM'];

// ── VALIDATION ───────────────────────────────────────────────────────────────
$errors = [];

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// Guest identity required only when no session and no inquiry pre-fill
if (!$user_id) {
    if (empty($guest_name))                                     $errors[] = 'Full name is required.';
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL))       $errors[] = 'A valid email address is required.';
}

if (!in_array($service, $allowed_services, true))               $errors[] = 'Invalid service selection.';
if (!in_array($duration, $allowed_durations, true))             $errors[] = 'Invalid session duration.';
if (!in_array($format, $allowed_formats, true))                 $errors[] = 'Invalid meeting format.';
if (!in_array($time_raw, $allowed_times, true))                 $errors[] = 'Invalid time slot.';

// Date must be a real future date in YYYY-MM-DD format
$booking_date = null;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw)) {
    $errors[] = 'Invalid date format.';
} else {
    $booking_date = new DateTime($date_raw);
    $today        = new DateTime('today');
    if ($booking_date < $today) {
        $errors[] = 'Booking date must be today or in the future.';
    }
}

// Sanity-check price (server-side; client cannot set arbitrary amounts)
$price_map = [
    'UI Design'               => ['30' => 25000, '60' => 45000],
    'UX Research'             => ['30' => 30000, '60' => 50000],
    'Wireframing'             => ['30' => 20000, '60' => 35000],
    'Interactive Prototyping' => ['30' => 40000, '60' => 70000],
    'Responsive Web Design'   => ['30' => 50000, '60' => 90000],
    'Design Systems'          => ['30' => 60000, '60' => 100000],
];
$server_price = $price_map[$service][(string)$duration] ?? null;
if ($server_price === null) {
    $errors[] = 'Could not determine price for the selected service and duration.';
} else {
    $price = $server_price;   // Always use server-side price, never trust POST
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

// ── Resolve guest identity ────────────────────────────────────────────────────
// If a logged-in client is booking, pull their name and email from session
if ($user_id) {
    $guest_name  = $_SESSION['user_name'];
    $guest_email = $_SESSION['email'];
}

// If linked to an inquiry, pull from there (fallback for authenticated guests)
if (empty($guest_name) && $inquiry_id) {
    $stmt = $pdo->prepare('SELECT name, email FROM inquiries WHERE id = ? LIMIT 1');
    $stmt->execute([$inquiry_id]);
    $inq = $stmt->fetch();
    if ($inq) { $guest_name = $inq['name']; $guest_email = $inq['email']; }
}

// ── INSERT INTO BOOKINGS ─────────────────────────────────────────────────────
$stmt = $pdo->prepare(
    'INSERT INTO bookings
        (user_id, inquiry_id, guest_name, guest_email,
         service, duration_min, price_php, booking_date, booking_time,
         meeting_format, status, created_at)
     VALUES
        (?, ?, ?, ?,
         ?, ?, ?, ?, ?,
         ?, \'pending\', NOW())'
);
$stmt->execute([
    $user_id,
    $inquiry_id ?: null,
    $guest_name,
    $guest_email,
    $service,
    $duration,
    $price,
    $booking_date->format('Y-m-d'),
    $time_raw,
    $format,
]);

$new_booking_id = (int) $pdo->lastInsertId();

// ── STORE TRUSTED-SESSION TOKEN ──────────────────────────────────────────────
// SECURITY: Stored by row ID, not email — register.php links ONLY this
// specific booking to the new account (trusted-session linking pattern).
if (!$user_id) {
    $_SESSION['pending_link_booking_id'] = $new_booking_id;
}

// ── SUCCESS RESPONSE ─────────────────────────────────────────────────────────
echo json_encode([
    'success'    => true,
    'booking_id' => 'BKG-' . str_pad($new_booking_id, 4, '0', STR_PAD_LEFT),
    'raw_id'     => $new_booking_id,
    'service'    => $service,
    'date'       => $booking_date->format('M j, Y'),
    'time'       => $time_raw,
    'price'      => '₱' . number_format($price),
    'format'     => $format,
]);
exit;
