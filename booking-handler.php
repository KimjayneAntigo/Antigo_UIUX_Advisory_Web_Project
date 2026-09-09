<?php
/**
 * Consultation booking submission endpoint.
 * Accepts POST only always returns JSON.
 */

require_once __DIR__ . '/includes/routing.php';
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json');

//  Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
    exit;
}

//  Whitelists 
$allowed_services  = [
    'UI Design',
    'UX Research',
    'Wireframing',
    'Interactive Prototyping',
    'Responsive Web Design',
    'Design Systems',
];
$allowed_durations = [30, 60];     // accepted as integers from POST
$allowed_formats   = [
    'Video Call (Google Meet)',
    'Zoom Meet (Zoom)',
    'Phone Call (+63)',
    'In-Person Studio (Cebu City)',
];
$allowed_times     = [
    '09:00 AM',
    '10:00 AM',
    '11:30 AM',
    '01:30 PM',
    '03:00 PM',
    '04:30 PM',
];

//  Server-side price map (USD) 
// Key: service name → [duration_int => raw_int_amount]
// The stored string is formatted as '$XX0' matching USD.
$price_map = [
    'UI Design'               => [30 => 250, 60 =>  450],
    'UX Research'             => [30 => 300, 60 =>  500],
    'Wireframing'             => [30 => 200, 60 =>  350],
    'Interactive Prototyping' => [30 => 400, 60 =>  700],
    'Responsive Web Design'   => [30 => 500, 60 =>  900],
    'Design Systems'          => [30 => 600, 60 => 1000],
];

//  Input collection & sanitisation 
$guest_name  = trim($_POST['guest_name']  ?? '');
$guest_email = trim($_POST['guest_email'] ?? '');
$service     = trim($_POST['service']     ?? '');
$duration    = (int) ($_POST['duration'] ?? 60);
$date_raw    = trim($_POST['date']        ?? '');   // expected YYYY-MM-DD
$time_raw    = trim($_POST['time']        ?? '');
$format      = trim($_POST['format']      ?? '');
$inquiry_id  = isset($_POST['inquiry_id']) ? (int) $_POST['inquiry_id'] : null;

//  Validation 
$errors  = [];
$user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

// Guest identity required only when no active session
if (!$user_id) {
    if (empty($guest_name)) {
        $errors[] = 'Full name is required.';
    }
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }
}

if (!in_array($service, $allowed_services, true)) {
    $errors[] = 'Invalid service selection.';
}
if (!in_array($duration, $allowed_durations, true)) {
    $errors[] = 'Invalid session duration.';
}
if (!in_array($format, $allowed_formats, true)) {
    $errors[] = 'Invalid meeting format.';
}
if (!in_array($time_raw, $allowed_times, true)) {
    $errors[] = 'Invalid time slot.';
}

// Date must be a real, present-or-future date in YYYY-MM-DD format
$bookingDate = null;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_raw)) {
    $errors[] = 'Invalid date format.';
} else {
    $bookingDate = new DateTime($date_raw);
    $today       = new DateTime('today');
    if ($bookingDate < $today) {
        $errors[] = 'Booking date must be today or in the future.';
    }
}

// Resolve server-side price never trust the client-supplied price
$rawPrice     = null;
$priceFormatted = null;
if (in_array($service, $allowed_services, true) && in_array($duration, $allowed_durations, true)) {
    $rawPrice       = $price_map[$service][$duration] ?? null;
    $priceFormatted = $rawPrice !== null
        ? '$' . number_format($rawPrice)
        : null;
}
if ($rawPrice === null) {
    $errors[] = 'Could not determine price for the selected service and duration.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['error' => implode(' ', $errors)]);
    exit;
}

//  Resolve client identity 
// Logged-in session takes precedence fall back to inquiry lookup, then POST.
if ($user_id) {
    $guest_name  = $_SESSION['user_name']  ?? $guest_name;
    $guest_email = $_SESSION['email']      ?? $guest_email;
}

if (empty($guest_name) && $inquiry_id) {
    $stmtInq = $pdo->prepare('SELECT name, email FROM inquiries WHERE id = ? LIMIT 1');
    $stmtInq->execute([$inquiry_id]);
    $inq = $stmtInq->fetch();
    if ($inq) {
        $guest_name  = $inq['name'];
        $guest_email = $inq['email'];
    }
}

// Format stored duration string matching schema: '30 min' / '60 min'
$durationStr = $duration . ' min';

// Database insert — two-step transaction for booking_code (UNIQUE NOT NULL) ─
// Step 1: INSERT with 'PENDING' placeholder; Step 2: UPDATE to BKG-XXXX.
try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO bookings
             (user_id, booking_code, inquiry_id, client_name, client_email,
              guest_name, guest_email, service, duration, duration_min,
              price, price_php, date, booking_date, time, booking_time, format, meeting_format, status)
         VALUES
             (?, \'PENDING\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\')'
    );
    $stmt->execute([
        $user_id,
        $inquiry_id ?: null,
        $guest_name,
        $guest_email,
        $guest_name,
        $guest_email,
        $service,
        $durationStr,
        $duration,
        $priceFormatted,
        $rawPrice,
        $bookingDate->format('Y-m-d'),
        $bookingDate->format('Y-m-d'),
        $time_raw,
        $time_raw,
        $format,
        $format,
    ]);

    $newId       = (int) $pdo->lastInsertId();
    $bookingCode = 'BKG-' . str_pad($newId, 4, '0', STR_PAD_LEFT);

    $stmtUpd = $pdo->prepare('UPDATE bookings SET booking_code = ? WHERE id = ?');
    $stmtUpd->execute([$bookingCode, $newId]);

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('booking-handler PDOException: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Something went wrong saving your booking. Please try again.']);
    exit;
}

//  Trusted-session linking (guest submissions only) 
if (!$user_id) {
    $_SESSION['pending_link_booking_id'] = $newId;
}

//  Success response 
echo json_encode([
    'success'      => true,
    'is_logged_in' => (bool)$user_id,
    'redirect'     => $user_id ? 'client-dashboard.php' : null,
    'booking_id'   => $bookingCode,
    'raw_id'       => $newId,
    'service'      => $service,
    'date'         => $bookingDate->format('M j, Y'),
    'time'         => $time_raw,
    'price'        => $priceFormatted,
    'format'       => $format,
]);
exit;

