<?php
require_once 'config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/routing.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid or expired security token. Please try again.');
        safe_redirect('admin-dashboard.php');
    }

    $action = trim($_POST['action'] ?? '');

    // Convert Inquiry to Project
    if ($action === 'convert_to_project') {
        $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
        $res = convert_inquiry_to_project($pdo, $inquiryId);
        if ($res['success']) {
            set_flash('success', "Inquiry successfully converted to Project {$res['project_code']}!");
            safe_redirect("admin/project-detail.php?id={$res['project_id']}");
        } else {
            set_flash('error', $res['error'] ?? 'Database error converting inquiry to project.');
            safe_redirect('admin-dashboard.php');
        }
    }

    // Update Inquiry Status Action
    if ($action === 'update_status') {
        $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
        $status    = trim($_POST['status'] ?? '');
        $statusWhitelist = ['new', 'reviewed', 'contacted', 'converted', 'lost'];

        if ($inquiryId > 0 && in_array($status, $statusWhitelist, true)) {
            try {
                $stmt = $pdo->prepare('UPDATE inquiries SET status = ? WHERE id = ?');
                $stmt->execute([$status, $inquiryId]);
                set_flash('success', "Inquiry marked as " . ucfirst($status) . ".");
            } catch (\PDOException $e) {
                error_log('admin-dashboard update status error: ' . $e->getMessage());
                set_flash('error', 'Could not update status.');
            }
        }
        safe_redirect('admin-dashboard.php');
    }

    // Delete Inquiry Action
    if ($action === 'delete_inquiry') {
        $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);
        if ($inquiryId > 0) {
            try {
                $fStmt = $pdo->prepare('SELECT file_name FROM inquiries WHERE id = ? LIMIT 1');
                $fStmt->execute([$inquiryId]);
                $inqRow = $fStmt->fetch();
                if ($inqRow && !empty($inqRow['file_name'])) {
                    $filePath = __DIR__ . '/uploads/inquiries/' . $inqRow['file_name'];
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
                $delStmt = $pdo->prepare('DELETE FROM inquiries WHERE id = ?');
                $delStmt->execute([$inquiryId]);
                set_flash('success', 'Inquiry lead deleted successfully.');
            } catch (\PDOException $e) {
                error_log('admin-dashboard delete_inquiry error: ' . $e->getMessage());
                set_flash('error', 'Database error deleting inquiry.');
            }
        }
        safe_redirect('admin-dashboard.php');
    }

    // Update Booking Status Action
    if ($action === 'update_booking_status') {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $bookingWhitelist = ['pending', 'confirmed', 'completed', 'cancelled'];
        if ($bookingId > 0 && in_array($newStatus, $bookingWhitelist, true)) {
            try {
                $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $bookingId]);
                set_flash('success', 'Booking marked as ' . ucfirst($newStatus) . '.');
            } catch (\PDOException $e) {
                error_log('admin-dashboard update_booking_status error: ' . $e->getMessage());
                set_flash('error', 'Database error updating booking.');
            }
        }
        safe_redirect('admin-dashboard.php#bookings');
    }

    // Delete / Cancel Booking Action
    if ($action === 'delete_booking') {
        $bookingId = (int) ($_POST['booking_id'] ?? 0);
        if ($bookingId > 0) {
            try {
                $delStmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
                $delStmt->execute([$bookingId]);
                set_flash('success', 'Consultation booking deleted successfully.');
            } catch (\PDOException $e) {
                error_log('admin-dashboard delete_booking error: ' . $e->getMessage());
                set_flash('error', 'Database error deleting booking.');
            }
        }
        safe_redirect('admin-dashboard.php#bookings');
    }
}

// Fetch live data from MySQL
$inquiries = [];
$bookings  = [];
$projects  = [];
$clients   = [];
$totalPipeline = 0;

try {
    $inquiries = $pdo->query('SELECT * FROM inquiries ORDER BY created_at DESC')->fetchAll();
    $bookings  = $pdo->query('SELECT * FROM bookings ORDER BY date DESC, time DESC')->fetchAll();
    $projects  = $pdo->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll();
    $clients   = $pdo->query("SELECT u.*, COUNT(p.id) AS project_count FROM users u LEFT JOIN projects p ON u.id = p.user_id WHERE u.role = 'client' GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();

    foreach ($projects as $p) {
        $num = (int) preg_replace('/[^0-9]/', '', $p['budget'] ?? '');
        $totalPipeline += $num;
    }
} catch (\PDOException $e) {
    error_log('admin-dashboard fetch error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Studio Command Center | Antigo Advisory</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#13224B',
                        violet: '#6C5BB5',
                        brandBlue: '#4C6CCB',
                        'light-blue': '#DDEBFF',
                        'light-gray': '#F4F6F8',
                        'dark-gray': '#4b4b4b',
                        'text-primary': '#13224B',
                        'text-soft': '#4b4b4b',
                        'text-faint': '#8890AA',
                        'surface-alt': '#F4F6F8',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <style>
        :root {
            --navy: #13224B;
            --violet: #6C5BB5;
            --blue: #4C6CCB;
            --white: #FFFFFF;
            --light-blue: #DDEBFF;
            --light-gray: #F4F6F8;
            --dark-gray: #4b4b4b;
            --text: #13224B;
            --text-soft: #4b4b4b;
            --text-faint: #8890AA;
            --surface: #FFFFFF;
            --surface-alt: #F4F6F8;
            --border: rgba(19, 34, 75, 0.09);
            --grad: linear-gradient(100deg, #13224B 0%, #6C5BB5 55%, #4C6CCB 100%);
            --grad-soft: linear-gradient(135deg, #4C6CCB, #6C5BB5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-alt);
            color: var(--text);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .admin-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }

        .badge-new { background: #FFF1D6; color: #946200; }
        .badge-contacted { background: #DDEBFF; color: #13224B; }
        .badge-reviewed { background: rgba(108,91,181,0.12); color: #6C5BB5; }
        .badge-converted { background: #DFF6E8; color: #127A45; }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo-text {
            line-height: 1.15;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .logo-text .word {
            font-weight: 800;
            font-size: 18px;
            letter-spacing: 0.05em;
            color: #FFFFFF;
            line-height: 1.1;
        }
        .logo-text .sub {
            font-size: 9px;
            letter-spacing: 0.22em;
            color: #DDEBFF;
            text-transform: uppercase;
            font-weight: 700;
            line-height: 1.1;
            margin-top: 2px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Admin Top Nav -->
    <header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="<?= home_url() ?>" class="logo">
                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center p-1.5 shadow-sm">
                    <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="w-full h-full object-contain">
                </div>
                <div class="logo-text">
                    <div class="word">ANTIGO</div>
                    <div class="sub">STUDIO COMMAND CENTER</div>
                </div>
            </a>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white flex items-center justify-center font-bold text-sm">
                        KA
                    </div>
                    <div class="hidden sm:flex flex-col">
                        <span class="text-xs font-bold text-white"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Kimberly Jayne Antigo') ?></span>
                        <span class="text-[10px] text-[#DDEBFF]">Admin &amp; Lead Consultant</span>
                    </div>
                    <a href="logout.php" title="Log Out" class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white/70 hover:text-white transition-colors">
                        <iconify-icon icon="lucide:log-out" class="text-base"></iconify-icon>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-[1440px] mx-auto px-6 lg:px-10 pt-6 w-full">
        <?= render_flash() ?>
    </div>

    <!-- Admin Content -->
    <main class="flex-1 max-w-[1440px] w-full mx-auto px-6 lg:px-10 py-4 space-y-8">
        
        <!-- Header Introduction -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#13224B] tracking-tight">Studio Pipeline Overview</h1>
                <p class="text-sm text-[#4b4b4b] mt-1">Review incoming project leads, manage scheduled consultations, and oversee active design delivery.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3.5 py-1.5 rounded-full bg-white border border-[rgba(19,34,75,0.08)] text-xs font-bold text-[#13224B] shadow-sm flex items-center gap-2">
                    <iconify-icon icon="lucide:database" class="text-[#6C5BB5]"></iconify-icon>
                    <span>Connected to MySQL Database</span>
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Inquiries Captured</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:inbox"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]"><?= count($inquiries) ?></div>
                <div class="text-xs text-[#6C5BB5] font-semibold mt-1">Canonical intake queue</div>
            </div>

            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Scheduled Sessions</span>
                    <div class="w-9 h-9 rounded-xl bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:calendar-clock"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]"><?= count($bookings) ?></div>
                <div class="text-xs text-[#8890AA] mt-1">Live calendar slots</div>
            </div>

            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Projects</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:kanban"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]"><?= count($projects) ?></div>
                <div class="text-xs text-[#8890AA] mt-1">In design / review</div>
            </div>

            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Pipeline Value</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:circle-dollar-sign"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]">$<?= number_format($totalPipeline) ?></div>
                <div class="text-xs text-[#127A45] font-semibold mt-1">Total Pipeline ($ USD)</div>
            </div>
        </div>

        <!-- Section 1: Inquiries Lead Qualification Queue -->
        <div class="admin-card p-6 sm:p-8" id="inquiries">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Inquiry Leads Queue (Canonical Intake)</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Admin reviews and qualifies incoming project leads from the Inquiry page.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[#8890AA]"><?= count($inquiries) ?> Total Leads</span>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[rgba(19,34,75,0.08)] text-[#8890AA] uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4">Ref ID</th>
                            <th class="py-3.5 px-4">Lead Name &amp; Company</th>
                            <th class="py-3.5 px-4">Service</th>
                            <th class="py-3.5 px-4">Budget Range</th>
                            <th class="py-3.5 px-4">Timeline</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                        <?php if (empty($inquiries)): ?>
                            <tr>
                                <td colspan="7" class="py-8 text-center text-xs text-[#8890AA]">No inquiries received yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inquiries as $inq): 
                                $status = strtolower($inq['status'] ?? 'new');
                                $badgeClass = 'badge-new';
                                if ($status === 'reviewed') $badgeClass = 'badge-reviewed';
                                if ($status === 'contacted') $badgeClass = 'badge-contacted';
                                if ($status === 'converted') $badgeClass = 'badge-converted';
                            ?>
                                <tr class="hover:bg-[#F4F6F8] transition-colors">
                                    <td class="py-4 px-4 font-mono font-bold text-[#13224B]"><?= htmlspecialchars($inq['ref_code'] ?? 'INQ-' . $inq['id']) ?></td>
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-[#13224B]"><?= htmlspecialchars($inq['name']) ?></div>
                                        <div class="text-[10px] text-[#8890AA]"><?= htmlspecialchars($inq['company'] ?: $inq['email']) ?></div>
                                    </td>
                                    <td class="py-4 px-4 font-semibold text-[#13224B]"><?= htmlspecialchars($inq['service'] ?? 'UI/UX Design') ?></td>
                                    <td class="py-4 px-4 font-bold text-[#4C6CCB]"><?= htmlspecialchars($inq['budget'] ?? 'TBD') ?></td>
                                    <td class="py-4 px-4 text-[#4b4b4b]"><?= htmlspecialchars($inq['timeline'] ?? 'Flexible') ?></td>
                                    <td class="py-4 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $badgeClass ?> uppercase tracking-wider"><?= htmlspecialchars($inq['status']) ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap space-x-1">
                                        <button type="button" onclick="openInquiryModal(<?= (int)$inq['id'] ?>)" class="px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-white transition-colors">
                                            Review
                                        </button>
                                        <form method="POST" action="admin-dashboard.php" onsubmit="return confirm('Are you sure you want to delete this inquiry?');" class="inline">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete_inquiry">
                                            <input type="hidden" name="inquiry_id" value="<?= (int)$inq['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg border border-red-200 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors" title="Delete inquiry">
                                                <iconify-icon icon="lucide:trash-2" class="text-sm"></iconify-icon>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Booked Consultations Calendar Grid -->
        <div class="admin-card p-6 sm:p-8" id="bookings">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Scheduled Consultations</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Bookings created directly by clients from the Book Consultation page.</p>
                </div>
                <span class="text-xs text-[#8890AA]"><?= count($bookings) ?> Bookings</span>
            </div>

            <!-- Bookings Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[rgba(19,34,75,0.08)] text-[#8890AA] uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4">Booking ID</th>
                            <th class="py-3.5 px-4">Client</th>
                            <th class="py-3.5 px-4">Service &amp; Duration</th>
                            <th class="py-3.5 px-4">Rate</th>
                            <th class="py-3.5 px-4">Date &amp; Time</th>
                            <th class="py-3.5 px-4">Meeting Format</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="8" class="py-8 text-center text-xs text-[#8890AA]">No consultations booked yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $b): ?>
                                <tr class="hover:bg-[#F4F6F8] transition-colors">
                                    <td class="py-4 px-4 font-mono font-bold text-[#13224B]"><?= htmlspecialchars($b['booking_code'] ?? 'BKG-' . $b['id']) ?></td>
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-[#13224B]"><?= htmlspecialchars($b['client_name']) ?></div>
                                        <div class="text-[10px] text-[#8890AA]"><?= htmlspecialchars($b['client_email']) ?></div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <span class="font-bold text-[#13224B]"><?= htmlspecialchars($b['service']) ?></span>
                                        <span class="text-[10px] text-[#6C5BB5] font-semibold block"><?= htmlspecialchars($b['duration'] ?? '45 Mins') ?></span>
                                    </td>
                                    <td class="py-4 px-4 font-bold text-[#4C6CCB]"><?= htmlspecialchars($b['price'] ?? '$150') ?></td>
                                    <td class="py-4 px-4 font-semibold text-[#13224B]"><?= htmlspecialchars($b['date']) ?> &middot; <?= htmlspecialchars($b['time']) ?></td>
                                    <td class="py-4 px-4 text-[#4b4b4b]"><?= htmlspecialchars($b['format'] ?? 'Google Meet') ?></td>
                                    <td class="py-4 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#DFF6E8] text-[#127A45] uppercase tracking-wider"><?= htmlspecialchars($b['status'] ?? 'confirmed') ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-right whitespace-nowrap space-x-1">
                                        <?php if (($b['status'] ?? '') !== 'confirmed'): ?>
                                            <form method="POST" action="admin-dashboard.php" class="inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="update_booking_status">
                                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" class="px-2 py-1 rounded bg-[#DDEBFF] text-[#4C6CCB] text-[10px] font-bold hover:bg-[#c9ddff]" title="Confirm Booking">
                                                    Confirm
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if (($b['status'] ?? '') !== 'completed'): ?>
                                            <form method="POST" action="admin-dashboard.php" class="inline">
                                                <?= csrf_input() ?>
                                                <input type="hidden" name="action" value="update_booking_status">
                                                <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="px-2 py-1 rounded bg-[#DFF6E8] text-[#127A45] text-[10px] font-bold hover:bg-[#c7f3d8]" title="Mark Completed">
                                                    Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="admin-dashboard.php" onsubmit="return confirm('Are you sure you want to cancel and delete this booking?');" class="inline">
                                            <?= csrf_input() ?>
                                            <input type="hidden" name="action" value="delete_booking">
                                            <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                                            <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50" title="Delete Booking">
                                                <iconify-icon icon="lucide:trash-2" class="text-sm"></iconify-icon>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Active Studio Projects -->
        <div class="admin-card p-6 sm:p-8" id="projects">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Active Client Projects</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Direct link into full admin project control and phase stepper management.</p>
                </div>
                <span class="text-xs text-[#8890AA]"><?= count($projects) ?> Projects</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if (empty($projects)): ?>
                    <p class="text-xs text-[#8890AA] col-span-3 py-8 text-center">No projects in pipeline yet.</p>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <div class="p-6 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-3">
                                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#6C5BB5]"><?= htmlspecialchars($p['category'] ?? 'UI/UX Design') ?></span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-[#13224B] border border-[rgba(19,34,75,0.08)]"><?= htmlspecialchars($p['status'] ?? 'Discovery') ?></span>
                                </div>
                                <h4 class="text-base font-bold text-[#13224B] mb-1"><?= htmlspecialchars($p['title']) ?></h4>
                                <p class="text-xs text-[#8890AA] mb-4"><?= htmlspecialchars($p['client_name']) ?> (<?= htmlspecialchars($p['company'] ?: 'Client') ?>)</p>

                                <div class="mb-4">
                                    <div class="flex justify-between text-xs mb-1">
                                        <span class="text-[#4b4b4b]">Phase: <strong><?= htmlspecialchars($p['phase_name'] ?? 'Discovery & Research') ?></strong></span>
                                        <span class="font-bold text-[#6C5BB5]"><?= (int)($p['progress'] ?? 20) ?>%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-white overflow-hidden border border-[rgba(19,34,75,0.06)]">
                                        <div class="h-full rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5]" style="width: <?= (int)($p['progress'] ?? 20) ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-[rgba(19,34,75,0.06)] flex justify-between items-center">
                                <span class="text-sm font-extrabold text-[#13224B]"><?= htmlspecialchars($p['budget'] ?: '$150,000') ?></span>
                                <a href="admin/project-detail.php?id=<?= (int)$p['id'] ?>" class="px-3.5 py-1.5 rounded-lg bg-white border border-[rgba(19,34,75,0.1)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF] transition-colors">
                                    Control Panel &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Section 4: Registered Clients Directory -->
        <div class="admin-card p-6 sm:p-8" id="clients">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Registered Client Accounts</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Directory of client accounts registered in the client portal and their projects.</p>
                </div>
                <span class="text-xs text-[#8890AA]"><?= count($clients) ?> Clients</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[rgba(19,34,75,0.08)] text-[#8890AA] uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4">Client Name</th>
                            <th class="py-3.5 px-4">Email</th>
                            <th class="py-3.5 px-4">Company</th>
                            <th class="py-3.5 px-4">Active Projects</th>
                            <th class="py-3.5 px-4">Member Since</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="5" class="py-8 text-center text-xs text-[#8890AA]">No client accounts registered yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $cl): ?>
                                <tr class="hover:bg-[#F4F6F8] transition-colors">
                                    <td class="py-4 px-4 font-bold text-[#13224B]">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white font-bold flex items-center justify-center text-[10px]">
                                                <?= strtoupper(substr($cl['name'], 0, 1)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($cl['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4 text-[#4b4b4b]"><?= htmlspecialchars($cl['email']) ?></td>
                                    <td class="py-4 px-4 text-[#6C5BB5] font-semibold"><?= htmlspecialchars($cl['company'] ?: '—') ?></td>
                                    <td class="py-4 px-4 font-bold text-[#4C6CCB]"><?= (int)($cl['project_count'] ?? 0) ?></td>
                                    <td class="py-4 px-4 text-[#8890AA]"><?= date('M j, Y', strtotime($cl['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Inquiry Review Modal -->
    <div id="inquiryDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-8 border border-[rgba(19,34,75,0.08)] shadow-2xl relative">
            <button onclick="closeInquiryModal()" class="absolute right-6 top-6 text-[#8890AA] hover:text-[#13224B]">
                <iconify-icon icon="lucide:x" class="text-2xl"></iconify-icon>
            </button>

            <div class="flex items-center gap-3 mb-6">
                <span id="modalInqStatusBadge" class="px-3 py-1 rounded-full text-xs font-bold badge-new">New Lead</span>
                <span class="text-xs text-[#8890AA]" id="modalInqId">INQ-1001</span>
            </div>

            <h3 class="text-2xl font-extrabold text-[#13224B] mb-1" id="modalInqName">Maria Santos</h3>
            <p class="text-xs text-[#6C5BB5] font-semibold mb-6" id="modalInqCompany">Pesolink Financial Services &middot; maria@pesolink.com</p>

            <div class="grid grid-cols-3 gap-4 text-xs bg-[#F4F6F8] p-4 rounded-2xl mb-6">
                <div>
                    <span class="text-[#8890AA] block mb-1">Service Required</span>
                    <strong class="text-[#13224B]" id="modalInqService">UI Design</strong>
                </div>
                <div>
                    <span class="text-[#8890AA] block mb-1">Budget Band</span>
                    <strong class="text-[#13224B]" id="modalInqBudget">$150,000 – $300,000</strong>
                </div>
                <div>
                    <span class="text-[#8890AA] block mb-1">Ideal Timeline</span>
                    <strong class="text-[#13224B]" id="modalInqTimeline">1 Month</strong>
                </div>
            </div>

            <div class="space-y-4 mb-8 text-xs">
                <div>
                    <span class="font-bold text-[#8890AA] uppercase tracking-wider block mb-1.5">Project Scope &amp; Description</span>
                    <p class="p-4 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.08)] text-[#4b4b4b] leading-relaxed text-sm whitespace-pre-wrap" id="modalInqDescription">
                        Redesigning mobile banking dashboard for smoother digital transactions...
                    </p>
                </div>
                <div id="modalInqFileBlock" class="hidden">
                    <span class="font-bold text-[#8890AA] uppercase tracking-wider block mb-1.5">Attached Reference File</span>
                    <a id="modalInqFileLink" href="#" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#DDEBFF] text-[#13224B] font-semibold hover:underline">
                        <iconify-icon icon="lucide:file-text" class="text-[#4C6CCB]"></iconify-icon>
                        <span id="modalInqFileName">brief.pdf</span>
                    </a>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                <div class="flex gap-2">
                    <button type="button" onclick="setInquiryStatusAction('reviewed')" class="px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-[#F4F6F8]">
                        Mark Reviewed
                    </button>
                    <button type="button" onclick="setInquiryStatusAction('contacted')" class="px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF]">
                        Mark Contacted
                    </button>
                </div>
                
                <div class="flex items-center gap-2">
                    <form id="convertProjectForm" method="POST" action="admin-dashboard.php">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="convert_to_project">
                        <input type="hidden" name="inquiry_id" id="convertInquiryIdInput" value="">
                        <button type="submit" id="convertSubmitBtn" class="px-6 py-2.5 rounded-xl text-white text-xs font-bold shadow-md hover:scale-105 transition-transform" style="background:var(--grad);">
                            Convert to Active Project &rarr;
                        </button>
                    </form>

                    <form id="modalDeleteInquiryForm" method="POST" action="admin-dashboard.php" onsubmit="return confirm('Are you sure you want to permanently delete this inquiry?');">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="delete_inquiry">
                        <input type="hidden" name="inquiry_id" id="modalDeleteInquiryIdInput" value="">
                        <button type="submit" class="px-4 py-2.5 rounded-xl border border-red-200 text-xs font-bold text-red-600 hover:bg-red-50 inline-flex items-center gap-1.5 transition-colors">
                            <iconify-icon icon="lucide:trash-2"></iconify-icon>
                            <span>Delete Lead</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden Form for Status Updates -->
    <form id="statusUpdateForm" method="POST" action="admin-dashboard.php" class="hidden">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="inquiry_id" id="statusInquiryIdInput" value="">
        <input type="hidden" name="status" id="statusValueInput" value="">
    </form>

    <!-- Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white mt-12">
        &copy; 2026 Antigo UI/UX Advisory &middot; Studio Administration
    </footer>

    <script>
        const allInquiries = <?= json_encode($inquiries, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        let selectedInquiryId = null;

        function openInquiryModal(id) {
            selectedInquiryId = id;
            const inq = allInquiries.find(i => parseInt(i.id) === parseInt(id));
            if (!inq) return;

            document.getElementById('convertInquiryIdInput').value = inq.id;
            const delInp = document.getElementById('modalDeleteInquiryIdInput');
            if (delInp) delInp.value = inq.id;
            document.getElementById('modalInqId').innerText = inq.ref_code || ('INQ-' + inq.id);
            document.getElementById('modalInqName').innerText = inq.name;
            document.getElementById('modalInqCompany').innerText = `${inq.company || 'Direct Client'} · ${inq.email}`;
            document.getElementById('modalInqService').innerText = inq.service || 'UI/UX Design';
            document.getElementById('modalInqBudget').innerText = inq.budget || 'TBD';
            document.getElementById('modalInqTimeline').innerText = inq.timeline || 'Flexible';
            document.getElementById('modalInqDescription').innerText = inq.description || 'No description provided.';
            
            const badge = document.getElementById('modalInqStatusBadge');
            badge.className = 'px-3 py-1 rounded-full text-xs font-bold';
            const status = (inq.status || 'new').toLowerCase();
            if (status === 'reviewed') {
                badge.classList.add('badge-reviewed');
                badge.innerText = 'Reviewed';
            } else if (status === 'contacted') {
                badge.classList.add('badge-contacted');
                badge.innerText = 'Contacted';
            } else if (status === 'converted') {
                badge.classList.add('badge-converted');
                badge.innerText = 'Converted';
            } else {
                badge.classList.add('badge-new');
                badge.innerText = 'New Lead';
            }

            const fileBlock = document.getElementById('modalInqFileBlock');
            if (inq.attached_file) {
                document.getElementById('modalInqFileName').innerText = inq.attached_file;
                document.getElementById('modalInqFileLink').href = 'uploads/inquiries/' + inq.attached_file;
                fileBlock.classList.remove('hidden');
            } else {
                fileBlock.classList.add('hidden');
            }

            const convertBtn = document.getElementById('convertSubmitBtn');
            if (status === 'converted') {
                convertBtn.disabled = true;
                convertBtn.innerText = 'Already Converted';
                convertBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                convertBtn.disabled = false;
                convertBtn.innerText = 'Convert to Active Project →';
                convertBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            document.getElementById('inquiryDetailModal').classList.remove('hidden');
        }

        function closeInquiryModal() {
            document.getElementById('inquiryDetailModal').classList.add('hidden');
            selectedInquiryId = null;
        }

        function setInquiryStatusAction(status) {
            if (!selectedInquiryId) return;
            document.getElementById('statusInquiryIdInput').value = selectedInquiryId;
            document.getElementById('statusValueInput').value = status;
            document.getElementById('statusUpdateForm').submit();
        }

        async function handleConvertSubmit(e) {
            e.preventDefault();
            if (!selectedInquiryId) return;

            const btn = document.getElementById('convertSubmitBtn');
            btn.disabled = true;
            btn.innerText = 'Converting...';

            const formData = new FormData();
            formData.append('action', 'convert_to_project');
            formData.append('inquiry_id', selectedInquiryId);

            try {
                const response = await fetch('admin-dashboard.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    alert(data.error || 'Failed to convert inquiry.');
                    btn.disabled = false;
                    btn.innerText = 'Convert to Active Project →';
                }
            } catch (err) {
                // Fallback to standard form submission
                document.getElementById('convertProjectForm').submit();
            }
        }
    </script>
</body>
</html>
