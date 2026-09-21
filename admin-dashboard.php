<?php
/**
 * Studio Command Center & Executive Overview.
 * High-level performance KPIs, lead qualification preview, active project sprints, and scheduling highlights.
 */

require_once __DIR__ . '/config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/routing.php';

// Handle POST actions (e.g. quick conversion from overview)
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
                $fStmt = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
                $fStmt->execute([$inquiryId]);
                $inqRow = $fStmt->fetch();
                if ($inqRow) {
                    $attachedFile = $inqRow['attached_file'] ?? ($inqRow['file_name'] ?? null);
                    if (!empty($attachedFile)) {
                        $filePath = __DIR__ . '/uploads/inquiries/' . $attachedFile;
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
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
}

// Fetch live data from MySQL for executive overview
$inquiries        = [];
$bookings         = [];
$projects         = [];
$activeProjects   = [];
$recentPayments   = [];
$totalPipeline    = 0;
$totalInquiries   = 0;
$pendingInquiries = 0;
$upcomingBookings = 0;

try {
    // Total counts
    $totalInquiries   = (int) $pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
    $pendingInquiries = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")->fetchColumn();
    $upcomingBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('pending', 'confirmed')")->fetchColumn();

    // Latest 5 Inquiries
    $inquiries = $pdo->query('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5')->fetchAll();

    // Upcoming 4 Bookings
    $bookings  = $pdo->query("SELECT * FROM bookings WHERE status != 'cancelled' ORDER BY date ASC, time ASC LIMIT 4")->fetchAll();

    // Active Projects
    $allProjects = $pdo->query('SELECT * FROM projects ORDER BY updated_at DESC, created_at DESC')->fetchAll();
    foreach ($allProjects as $p) {
        if (($p['status_type'] ?? '') !== 'cancelled') {
            $totalPipeline += parse_budget_amount($p['budget'] ?? '');
            if (($p['status_type'] ?? '') !== 'completed' && ($p['status'] ?? '') !== 'Delivered') {
                $activeProjects[] = $p;
            }
        }
    }

    // Recent Payments
    $recentPayments = $pdo->query(
        "SELECT pay.*, p.title AS project_title, p.project_code, u.name AS client_name 
         FROM payments pay 
         JOIN projects p ON pay.project_id = p.id 
         JOIN users u ON pay.user_id = u.id 
         ORDER BY pay.submitted_at DESC 
         LIMIT 3"
    )->fetchAll();

} catch (\PDOException $e) {
    error_log('admin-dashboard fetch error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/includes/head-common.php'; ?>
    <title>Admin Overview — Studio Command Center | Antigo Advisory</title>
    <style>
        .admin-card {
            background: #FFFFFF;
            border: 1px solid rgba(19, 34, 75, 0.08);
            border-radius: 20px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }
        .badge-new        { background: #FFF1D6; color: #946200; }
        .badge-contacted  { background: #DDEBFF; color: #13224B; }
        .badge-reviewed   { background: rgba(108,91,181,0.12); color: #6C5BB5; }
        .badge-converted  { background: #DFF6E8; color: #127A45; }
        .progress-track {
            background: rgba(19,34,75,0.07);
            border-radius: 99px;
            height: 6px;
            overflow: hidden;
        }
        .progress-fill {
            background: linear-gradient(135deg, #4C6CCB, #6C5BB5);
            height: 100%;
            border-radius: 99px;
        }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8]">

    <!-- Admin Top Horizontal Navbar -->
    <?php $activePage = 'overview'; require_once __DIR__ . '/includes/header-admin.php'; ?>

    <!-- Flash Messages -->
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pt-6 w-full">
        <?= render_flash() ?>
    </div>

    <!-- Admin Content -->
    <main class="max-w-[1440px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 space-y-8">
        
        <!-- Header Introduction Banner -->
        <div class="card p-6 sm:p-8 bg-gradient-to-r from-[#13224B] via-[#21356f] to-[#4C6CCB] text-white border-0 shadow-lg relative overflow-hidden rounded-3xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/15 text-[#DDEBFF]">
                        <iconify-icon icon="lucide:shield-check" class="text-emerald-300"></iconify-icon>
                        Studio Command Center
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Welcome back, Kimberly!
                    </h1>
                    <p class="text-xs sm:text-sm text-[#DDEBFF] max-w-xl leading-relaxed">
                        Track live client lead qualification, monitor active design delivery sprints, and oversee scheduled advisory sessions.
                    </p>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <a href="admin/inquiries.php"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-[#13224B] bg-white hover:bg-[#DDEBFF] shadow transition-all">
                        <iconify-icon icon="lucide:inbox" class="text-sm text-[#4C6CCB]"></iconify-icon>
                        <span>Review Leads (<?= $pendingInquiries ?> new)</span>
                    </a>
                    <a href="admin/bookings.php"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition-all">
                        <iconify-icon icon="lucide:calendar"></iconify-icon>
                        <span>Calendar</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Pipeline Value -->
            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Pipeline Value</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-lg shadow-sm">
                        <iconify-icon icon="lucide:circle-dollar-sign"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]">$<?= number_format($totalPipeline) ?></div>
                <div class="text-xs text-[#127A45] font-semibold mt-1">Total active studio budget ($ USD)</div>
            </div>

            <!-- Active Projects -->
            <a href="admin/projects.php" class="admin-card p-6 block hover:border-[#4C6CCB] transition-all group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Projects</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition-transform">
                        <iconify-icon icon="lucide:kanban"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]"><?= count($activeProjects) ?></div>
                <div class="text-xs text-[#6C5BB5] font-semibold mt-1 flex items-center gap-1">
                    <span>Manage sprints &rarr;</span>
                </div>
            </a>

            <!-- Inquiries Captured -->
            <a href="admin/inquiries.php" class="admin-card p-6 block hover:border-[#4C6CCB] transition-all group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Inquiries Queue</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition-transform">
                        <iconify-icon icon="lucide:inbox"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]"><?= $totalInquiries ?></div>
                <div class="text-xs text-[#4C6CCB] font-semibold mt-1 flex items-center gap-1">
                    <span><?= $pendingInquiries ?> new awaiting review &rarr;</span>
                </div>
            </a>

            <!-- Scheduled Sessions -->
            <a href="admin/bookings.php" class="admin-card p-6 block hover:border-[#4C6CCB] transition-all group">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Scheduled Sessions</span>
                    <div class="w-9 h-9 rounded-xl bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition-transform">
                        <iconify-icon icon="lucide:calendar-clock"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]"><?= $upcomingBookings ?></div>
                <div class="text-xs text-[#946200] font-semibold mt-1 flex items-center gap-1">
                    <span>Upcoming consultations &rarr;</span>
                </div>
            </a>
        </div>

        <!-- Main Grid: Active Sprints (Left 2 Cols) + Quick Feeds (Right 1 Col) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left: Active Sprints & Recent Leads (2 Cols) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Section: Active Project Sprints Spotlight -->
                <div class="admin-card p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-bold text-[#13224B]">Active Project Sprints</h2>
                            <p class="text-xs text-[#8890AA] mt-0.5">Live status and milestone progress for currently ongoing studio projects.</p>
                        </div>
                        <a href="admin/projects.php" class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1">
                            <span>View All Projects &rarr;</span>
                        </a>
                    </div>

                    <?php if (empty($activeProjects)): ?>
                        <div class="py-10 text-center text-xs text-[#8890AA]">
                            No active project sprints right now. Convert an incoming lead to start a project.
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach (array_slice($activeProjects, 0, 4) as $p): ?>
                                <div class="p-5 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex flex-col justify-between hover:shadow-md transition-all">
                                    <div>
                                        <div class="flex justify-between items-start mb-2">
                                            <span class="text-[10px] font-mono font-bold text-[#8890AA]"><?= htmlspecialchars($p['project_code'] ?? 'PRJ-' . $p['id']) ?></span>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white text-[#13224B] border border-[rgba(19,34,75,0.08)]">
                                                <?= htmlspecialchars($p['status'] ?? 'Active') ?>
                                            </span>
                                        </div>
                                        <h4 class="text-sm font-bold text-[#13224B] mb-1 line-clamp-1"><?= htmlspecialchars($p['title']) ?></h4>
                                        <p class="text-xs text-[#6C5BB5] font-semibold mb-3"><?= htmlspecialchars($p['client_name']) ?> (<?= htmlspecialchars($p['company'] ?: 'Client') ?>)</p>

                                        <div class="mb-4">
                                            <div class="flex justify-between text-xs mb-1">
                                                <span class="text-[#4b4b4b] text-[11px]"><?= htmlspecialchars($p['phase_name'] ?? 'In Progress') ?></span>
                                                <span class="font-extrabold text-[#4C6CCB] text-[11px]"><?= (int)($p['progress'] ?? 0) ?>%</span>
                                            </div>
                                            <div class="progress-track">
                                                <div class="progress-fill" style="width: <?= min(100, max(0, (int)($p['progress'] ?? 0))) ?>%"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-[rgba(19,34,75,0.06)] flex justify-between items-center">
                                        <span class="text-xs font-extrabold text-[#13224B]"><?= format_usd($p['budget'] ?? '$0') ?></span>
                                        <a href="admin/project-detail.php?id=<?= (int)$p['id'] ?>" class="px-3 py-1 rounded-lg bg-white border border-[rgba(19,34,75,0.1)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF] transition-colors">
                                            Control Panel &rarr;
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section: Recent Inquiries Queue -->
                <div class="admin-card p-6 sm:p-7">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-lg font-bold text-[#13224B]">Recent Inquiry Leads</h2>
                            <p class="text-xs text-[#8890AA] mt-0.5">Incoming leads submitted via the advisory inquiry form.</p>
                        </div>
                        <a href="admin/inquiries.php" class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1">
                            <span>View All (<?= $totalInquiries ?>) &rarr;</span>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                                    <th class="py-3 px-3">Lead</th>
                                    <th class="py-3 px-3">Service &amp; Budget</th>
                                    <th class="py-3 px-3">Submitted</th>
                                    <th class="py-3 px-3">Status</th>
                                    <th class="py-3 px-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                                <?php if (empty($inquiries)): ?>
                                    <tr>
                                        <td colspan="5" class="py-6 text-center text-xs text-[#8890AA]">No inquiries received yet.</td>
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
                                            <td class="py-3 px-3">
                                                <div class="font-bold text-[#13224B]"><?= htmlspecialchars($inq['name']) ?></div>
                                                <div class="text-[10px] text-[#6C5BB5]"><?= htmlspecialchars($inq['company'] ?: 'Individual') ?></div>
                                            </td>
                                            <td class="py-3 px-3">
                                                <div class="font-semibold text-[#13224B]"><?= htmlspecialchars($inq['service'] ?? 'UI/UX Design') ?></div>
                                                <div class="text-[10px] text-[#8890AA]"><?= htmlspecialchars($inq['budget'] ?? 'TBD') ?></div>
                                            </td>
                                            <td class="py-3 px-3 text-[#8890AA] whitespace-nowrap">
                                                <?= time_ago($inq['created_at']) ?>
                                            </td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $badgeClass ?> uppercase tracking-wider">
                                                    <?= htmlspecialchars($inq['status']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-right whitespace-nowrap space-x-1">
                                                <button type="button" onclick="openInquiryModal(<?= (int)$inq['id'] ?>)" class="px-2.5 py-1 rounded-lg border border-[rgba(19,34,75,0.12)] text-[11px] font-bold text-[#13224B] hover:bg-white">
                                                    Review
                                                </button>
                                                <a href="admin/inquiries.php" class="px-2.5 py-1 rounded-lg bg-[#DDEBFF] text-[11px] font-bold text-[#13224B] hover:bg-[#c9ddff]">
                                                    Pipeline &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Right: Upcoming Agenda & Payment Verification (1 Col) -->
            <div class="space-y-6">
                
                <!-- Upcoming Consultations Card -->
                <div class="admin-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-sm">
                                <iconify-icon icon="lucide:calendar"></iconify-icon>
                            </div>
                            <h3 class="text-sm font-bold text-[#13224B]">Upcoming Agenda</h3>
                        </div>
                        <a href="admin/bookings.php" class="text-[11px] font-bold text-[#4C6CCB] hover:underline">
                            View All &rarr;
                        </a>
                    </div>

                    <?php if (empty($bookings)): ?>
                        <p class="text-xs text-[#8890AA] py-4 text-center">No upcoming consultations booked.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($bookings as $b): ?>
                                <div class="p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] text-xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-[#13224B]"><?= htmlspecialchars($b['client_name'] ?: ($b['guest_name'] ?? 'Client')) ?></span>
                                        <span class="font-mono text-[10px] text-[#4C6CCB] font-bold"><?= htmlspecialchars($b['time'] ?: ($b['booking_time'] ?? '')) ?></span>
                                    </div>
                                    <div class="text-[11px] text-[#6C5BB5] font-semibold mb-2">
                                        <?= htmlspecialchars($b['service'] ?: 'Advisory Consultation') ?> &middot; <?= htmlspecialchars($b['date'] ?: ($b['booking_date'] ?? 'TBD')) ?>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] text-[#8890AA] pt-2 border-t border-[rgba(19,34,75,0.06)]">
                                        <span><?= htmlspecialchars($b['format'] ?: ($b['meeting_format'] ?? 'Google Meet')) ?></span>
                                        <a href="admin/bookings.php" class="font-bold text-[#4C6CCB] hover:underline">Details &rarr;</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Payments Preview Card -->
                <div class="admin-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-sm">
                                <iconify-icon icon="lucide:credit-card"></iconify-icon>
                            </div>
                            <h3 class="text-sm font-bold text-[#13224B]">Payment Ledger</h3>
                        </div>
                        <a href="admin/payments.php" class="text-[11px] font-bold text-[#4C6CCB] hover:underline">
                            Verify &rarr;
                        </a>
                    </div>

                    <?php if (empty($recentPayments)): ?>
                        <p class="text-xs text-[#8890AA] py-4 text-center">No payment declarations recorded yet.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($recentPayments as $pay): 
                                $paySt = strtolower($pay['status'] ?? 'pending');
                                $stBadge = 'bg-[#FFF1D6] text-[#946200]';
                                if ($paySt === 'verified') $stBadge = 'bg-[#DFF6E8] text-[#127A45]';
                                elseif ($paySt === 'rejected') $stBadge = 'bg-rose-100 text-rose-800';
                            ?>
                                <div class="p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] text-xs">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-[#13224B]"><?= format_payment_ref($pay['id']) ?></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $stBadge ?> uppercase">
                                            <?= htmlspecialchars($paySt) ?>
                                        </span>
                                    </div>
                                    <div class="font-extrabold text-sm text-[#13224B] my-1">
                                        <?= format_usd($pay['amount']) ?>
                                    </div>
                                    <div class="text-[10px] text-[#8890AA]">
                                        <?= htmlspecialchars($pay['client_name']) ?> &middot; <?= htmlspecialchars($pay['project_title']) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

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
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                <div class="flex gap-2">
                    <button type="button" onclick="setInquiryStatusAction('reviewed')" class="px-4 py-2 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-[#F4F6F8]">
                        Mark Reviewed
                    </button>
                    <button type="button" onclick="setInquiryStatusAction('contacted')" class="px-4 py-2 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF]">
                        Mark Contacted
                    </button>
                </div>
                
                <div class="flex items-center gap-2">
                    <form id="convertProjectForm" method="POST" action="admin-dashboard.php">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="convert_to_project">
                        <input type="hidden" name="inquiry_id" id="convertInquiryIdInput" value="">
                        <button type="submit" id="convertSubmitBtn" class="px-6 py-2.5 rounded-xl text-white text-xs font-bold shadow-md hover:opacity-90 transition-all" style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                            Convert to Active Project &rarr;
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for status update -->
    <form id="statusUpdateForm" method="POST" action="admin-dashboard.php" class="hidden">
        <?= csrf_input() ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="inquiry_id" id="statusInquiryIdInput" value="">
        <input type="hidden" name="status" id="statusValueInput" value="">
    </form>

    <script>
        const inquiriesData = <?= json_encode(array_column($inquiries, null, 'id'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function openInquiryModal(id) {
            const inq = inquiriesData[id];
            if (!inq) return;

            document.getElementById('modalInqId').textContent = inq.ref_code || ('INQ-' + String(inq.id).padStart(5, '0'));
            document.getElementById('modalInqName').textContent = inq.name;
            document.getElementById('modalInqCompany').textContent = (inq.company ? inq.company + ' · ' : '') + inq.email;
            document.getElementById('modalInqService').textContent = inq.service || 'UI/UX Design';
            document.getElementById('modalInqBudget').textContent = inq.budget || 'TBD';
            document.getElementById('modalInqTimeline').textContent = inq.timeline || 'Flexible';
            document.getElementById('modalInqDescription').textContent = inq.description || 'No detailed scope provided.';
            
            document.getElementById('convertInquiryIdInput').value = inq.id;
            document.getElementById('statusInquiryIdInput').value = inq.id;

            const badge = document.getElementById('modalInqStatusBadge');
            badge.className = 'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider badge-' + inq.status.toLowerCase();
            badge.textContent = inq.status;

            const convertBtn = document.getElementById('convertSubmitBtn');
            if (inq.status === 'converted') {
                convertBtn.disabled = true;
                convertBtn.classList.add('opacity-50', 'cursor-not-allowed');
                convertBtn.textContent = 'Already Converted to Project';
            } else {
                convertBtn.disabled = false;
                convertBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                convertBtn.textContent = 'Convert to Active Project →';
            }

            document.getElementById('inquiryDetailModal').classList.remove('hidden');
        }

        function closeInquiryModal() {
            document.getElementById('inquiryDetailModal').classList.add('hidden');
        }

        function setInquiryStatusAction(status) {
            document.getElementById('statusValueInput').value = status;
            document.getElementById('statusUpdateForm').submit();
        }

        document.getElementById('inquiryDetailModal').addEventListener('click', function(e) {
            if (e.target === this) closeInquiryModal();
        });
    </script>

</body>
</html>