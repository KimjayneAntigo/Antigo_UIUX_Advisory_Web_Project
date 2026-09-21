<?php
/**
 * Client Consultation & Scheduling Hub.
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'scheduling';
$pageTitle      = 'Consultations & Scheduling — Client Portal';
$pageHeading    = 'Consultations & Scheduling';
$pageSubheading = 'Manage scheduled video advisory sessions, reserve strategy workshops, and access meeting links.';

$userId      = (int) ($_SESSION['user_id'] ?? 0);
$clientEmail = trim($_SESSION['email'] ?? '');
if ($userId <= 0) {
    safe_redirect('../login.php');
}

// Filter & Pagination
$filterStatus = trim($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$bookings  = [];
$totalRows = 0;

// Count queries for filter tabs
$counts = ['all' => 0, 'upcoming' => 0, 'completed' => 0, 'cancelled' => 0];
try {
    $baseWhere = '(user_id = ? OR client_email = ? OR guest_email = ?)';
    $baseParams = [$userId, $clientEmail, $clientEmail];

    $cStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE {$baseWhere}");
    $cStmt->execute($baseParams);
    $counts['all'] = (int) $cStmt->fetchColumn();

    $cUpStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE {$baseWhere} AND status IN ('pending', 'confirmed')");
    $cUpStmt->execute($baseParams);
    $counts['upcoming'] = (int) $cUpStmt->fetchColumn();

    $cCompStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE {$baseWhere} AND status = 'completed'");
    $cCompStmt->execute($baseParams);
    $counts['completed'] = (int) $cCompStmt->fetchColumn();

    $cCancStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE {$baseWhere} AND status = 'cancelled'");
    $cCancStmt->execute($baseParams);
    $counts['cancelled'] = (int) $cCancStmt->fetchColumn();

    $statusClause = '';
    if (!empty($filterStatus)) {
        if ($filterStatus === 'upcoming') {
            $statusClause = "AND status IN ('pending', 'confirmed')";
        } elseif ($filterStatus === 'completed') {
            $statusClause = "AND status = 'completed'";
        } elseif ($filterStatus === 'cancelled') {
            $statusClause = "AND status = 'cancelled'";
        }
    }

    $countSql = "SELECT COUNT(*) FROM bookings WHERE {$baseWhere} {$statusClause}";
    $cStmt = $pdo->prepare($countSql);
    $cStmt->execute($baseParams);
    $totalRows = (int) $cStmt->fetchColumn();

    $sql = "SELECT * FROM bookings 
            WHERE {$baseWhere} {$statusClause} 
            ORDER BY date DESC, time DESC 
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $paramIdx = 1;
    foreach ($baseParams as $bp) {
        $stmt->bindValue($paramIdx++, $bp);
    }
    $stmt->bindValue($paramIdx++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIdx++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $bookings = $stmt->fetchAll();

} catch (\PDOException $e) {
    error_log('client/scheduling.php error: ' . $e->getMessage());
    set_flash('error', 'Error loading consultations.');
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .booking-row:hover { background-color: rgba(76,108,203,0.03); }
        .badge-pending   { background: #FFF1D6; color: #946200; }
        .badge-confirmed { background: #DDEBFF; color: #13224B; }
        .badge-completed { background: #DFF6E8; color: #127A45; }
        .badge-cancelled { background: #FEE2E2; color: #991B1B; }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8]">

  <!-- Top Horizontal Navbar -->
  <?php 
    $activePage = 'scheduling';
    require_once __DIR__ . '/../includes/header-client.php'; 
  ?>

  <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Filter Tabs & Stats Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="scheduling.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterStatus) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All (<?= $counts['all'] ?>)
          </a>
          <a href="scheduling.php?status=upcoming"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'upcoming' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Upcoming (<?= $counts['upcoming'] ?>)
          </a>
          <a href="scheduling.php?status=completed"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'completed' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Completed (<?= $counts['completed'] ?>)
          </a>
          <a href="scheduling.php?status=cancelled"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'cancelled' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Cancelled (<?= $counts['cancelled'] ?>)
          </a>
        </div>

        <div class="flex items-center gap-3">
          <a href="../book-consultation.php"
             class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-[#13224B] hover:bg-[#21356f] text-white text-xs font-bold shadow-sm transition-all">
            <iconify-icon icon="lucide:calendar-plus" class="text-sm"></iconify-icon>
            <span>+ Book New Consultation</span>
          </a>
          <div class="text-xs text-[#8890AA] whitespace-nowrap">
            Showing <strong><?= count($bookings) ?></strong> of <strong><?= $totalRows ?></strong> sessions
          </div>
        </div>
      </div>

      <!-- Bookings Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)] bg-white rounded-2xl shadow-sm">
        <?php if (empty($bookings)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:calendar-clock"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No consultations found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterStatus) ? "No sessions match the selected status." : 'Schedule your first advisory strategy consultation or design review session with Kimberly.' ?>
            </p>
            <div class="mt-4">
              <a href="../book-consultation.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#4C6CCB] text-white text-xs font-bold shadow hover:bg-[#3d59af]">
                <span>Book a Consultation</span>
                <iconify-icon icon="lucide:arrow-right" class="text-xs"></iconify-icon>
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                  <th class="py-3.5 px-4">Booking Ref</th>
                  <th class="py-3.5 px-4">Consultation Topic</th>
                  <th class="py-3.5 px-4">Duration</th>
                  <th class="py-3.5 px-4">Scheduled Date &amp; Time</th>
                  <th class="py-3.5 px-4">Rate</th>
                  <th class="py-3.5 px-4">Meeting Format</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4 text-right">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($bookings as $b): 
                    $st = strtolower($b['status'] ?? 'pending');
                    $badgeClass = 'badge-pending';
                    if ($st === 'confirmed') $badgeClass = 'badge-confirmed';
                    if ($st === 'completed') $badgeClass = 'badge-completed';
                    if ($st === 'cancelled') $badgeClass = 'badge-cancelled';
                    $bkgCode  = $b['booking_code'] ?: ('BKG-' . str_pad((string)$b['id'], 4, '0', STR_PAD_LEFT));
                    $priceUsd = format_usd($b['price'] ?: ($b['price_php'] ?? 0));
                ?>
                  <tr class="booking-row transition-colors">
                    <td class="py-4 px-4 font-mono font-bold text-[#13224B]">
                      <?= htmlspecialchars($bkgCode, ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B] text-sm">
                        <?= htmlspecialchars($b['service'] ?: 'Advisory Consultation', ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#6C5BB5] font-semibold mt-0.5">
                        Lead: Kimberly Jayne Antigo
                      </div>
                    </td>

                    <td class="py-4 px-4 font-semibold text-[#13224B]">
                      <?= htmlspecialchars($b['duration'] ?: ($b['duration_min'] ? $b['duration_min'] . ' min' : '45 min'), ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B]">
                        <?= htmlspecialchars($b['date'] ?: ($b['booking_date'] ?? 'TBD'), ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#8890AA] mt-0.5">
                        <?= htmlspecialchars($b['time'] ?: ($b['booking_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </td>

                    <td class="py-4 px-4 font-extrabold text-[#13224B]">
                      <?= $priceUsd ?>
                    </td>

                    <td class="py-4 px-4 text-[#4b4b4b]">
                      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#F4F6F8] text-[11px] font-semibold">
                        <iconify-icon icon="lucide:video" class="text-[#4C6CCB]"></iconify-icon>
                        <?= htmlspecialchars($b['format'] ?: ($b['meeting_format'] ?? 'Google Meet'), ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <td class="py-4 px-4">
                      <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $badgeClass ?> uppercase tracking-wider">
                        <?= htmlspecialchars($st, ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <td class="py-4 px-4 text-right whitespace-nowrap">
                      <?php if ($st === 'confirmed' || $st === 'pending'): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#DDEBFF] text-[#13224B] font-bold text-xs">
                          <iconify-icon icon="lucide:video" class="text-xs text-[#4C6CCB]"></iconify-icon>
                          <span>Meet Ready</span>
                        </span>
                      <?php else: ?>
                        <a href="../book-consultation.php"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4b4b4b] hover:bg-gray-100 transition-colors">
                          <span>Book Again</span>
                        </a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination Bar -->
          <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/50 flex items-center justify-between text-xs">
              <div class="text-[#8890AA]">
                Page <?= $page ?> of <?= $totalPages ?>
              </div>
              <div class="flex items-center gap-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <a href="scheduling.php?page=<?= $i ?><?= !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : '' ?>"
                     class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-colors <?= $page === $i ? 'bg-[#13224B] text-white' : 'bg-white text-[#13224B] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

  </main>

</body>
</html>
