<?php
/**
 * Scheduled Consultations Management Hub.
 * Dedicated standalone page for managing client consultation bookings.
 */

require_once __DIR__ . '/../includes/auth-check-admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'bookings';
$pageTitle      = 'Scheduled Consultations — Admin';
$pageHeading    = 'Scheduled Consultations';
$pageSubheading = 'Manage client consultation sessions, review calendar slots, and update meeting confirmations.';

$statusWhitelist = ['pending', 'confirmed', 'completed', 'cancelled'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid or expired security token. Please try again.');
        safe_redirect('bookings.php');
    }

    $action    = trim($_POST['action'] ?? '');
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($bookingId <= 0) {
        set_flash('error', 'Invalid booking reference.');
        safe_redirect('bookings.php');
    }

    // Update Status Action
    if ($action === 'update_booking_status') {
        $newStatus = trim($_POST['status'] ?? '');
        if (!in_array($newStatus, $statusWhitelist, true)) {
            set_flash('error', 'Invalid status selected.');
            safe_redirect('bookings.php');
        }

        try {
            $stmt = $pdo->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $bookingId]);
            $label = ucfirst($newStatus);
            set_flash('success', "Booking marked as {$label}.");
        } catch (\PDOException $e) {
            error_log('admin/bookings.php update status error: ' . $e->getMessage());
            set_flash('error', 'Database error updating booking.');
        }
        safe_redirect('bookings.php');
    }

    // Delete Booking Action
    if ($action === 'delete_booking') {
        try {
            $delStmt = $pdo->prepare('DELETE FROM bookings WHERE id = ?');
            $delStmt->execute([$bookingId]);
            set_flash('success', 'Consultation booking deleted successfully.');
        } catch (\PDOException $e) {
            error_log('admin/bookings.php delete error: ' . $e->getMessage());
            set_flash('error', 'Database error deleting booking.');
        }
        safe_redirect('bookings.php');
    }

    set_flash('error', 'Unknown action.');
    safe_redirect('bookings.php');
}

// Filter & Pagination
$filterStatus = trim($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$bookings  = [];
$totalRows = 0;

// Count queries for filter tabs
$counts = ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
try {
    $counts['all']       = (int) $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
    $counts['pending']   = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
    $counts['confirmed'] = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
    $counts['completed'] = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
    $counts['cancelled'] = (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();

    if (!empty($filterStatus) && in_array($filterStatus, $statusWhitelist, true)) {
        $cStmt = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE status = ?');
        $cStmt->execute([$filterStatus]);
        $totalRows = (int) $cStmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE status = ? ORDER BY date DESC, time DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $filterStatus, PDO::PARAM_STR);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll();
    } else {
        $totalRows = $counts['all'];

        $stmt = $pdo->prepare('SELECT * FROM bookings ORDER BY date DESC, time DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $bookings = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    error_log('admin/bookings.php fetch error: ' . $e->getMessage());
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
    $activePage = 'bookings';
    require_once __DIR__ . '/../includes/header-admin.php'; 
  ?>

  <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Filter Tabs & Stats Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="bookings.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterStatus) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All (<?= $counts['all'] ?>)
          </a>
          <a href="bookings.php?status=pending"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'pending' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Pending (<?= $counts['pending'] ?>)
          </a>
          <a href="bookings.php?status=confirmed"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'confirmed' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Confirmed (<?= $counts['confirmed'] ?>)
          </a>
          <a href="bookings.php?status=completed"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'completed' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Completed (<?= $counts['completed'] ?>)
          </a>
          <a href="bookings.php?status=cancelled"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'cancelled' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Cancelled (<?= $counts['cancelled'] ?>)
          </a>
        </div>

        <div class="text-xs text-[#8890AA]">
          Showing <strong><?= count($bookings) ?></strong> of <strong><?= $totalRows ?></strong> bookings
        </div>
      </div>

      <!-- Bookings Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)] bg-white rounded-2xl shadow-sm">
        <?php if (empty($bookings)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:calendar-x"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No consultations found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterStatus) ? "No bookings with status '{$filterStatus}'." : 'Scheduled client consultations will appear here automatically when booked from the advisory calendar.' ?>
            </p>
            <?php if (!empty($filterStatus)): ?>
              <a href="bookings.php" class="inline-block mt-4 text-xs font-bold text-[#4C6CCB] hover:underline">
                Clear filter
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                  <th class="py-3.5 px-4">Booking Ref</th>
                  <th class="py-3.5 px-4">Client</th>
                  <th class="py-3.5 px-4">Service &amp; Duration</th>
                  <th class="py-3.5 px-4">Scheduled Date</th>
                  <th class="py-3.5 px-4">Rate</th>
                  <th class="py-3.5 px-4">Meeting Format</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($bookings as $b): 
                    $st = strtolower($b['status'] ?? 'pending');
                    $badgeClass = 'badge-pending';
                    if ($st === 'confirmed') $badgeClass = 'badge-confirmed';
                    if ($st === 'completed') $badgeClass = 'badge-completed';
                    if ($st === 'cancelled') $badgeClass = 'badge-cancelled';
                    $clientDisplay = $b['client_name'] ?: ($b['guest_name'] ?: 'Guest Client');
                    $emailDisplay  = $b['client_email'] ?: ($b['guest_email'] ?: 'No email');
                    $bkgCode       = $b['booking_code'] ?: ('BKG-' . str_pad((string)$b['id'], 4, '0', STR_PAD_LEFT));
                    $priceUsd      = format_usd($b['price'] ?: ($b['price_php'] ?? 0));
                ?>
                  <tr class="booking-row transition-colors">
                    <td class="py-4 px-4 font-mono font-bold text-[#13224B]">
                      <?= htmlspecialchars($bkgCode, ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B] text-sm leading-tight">
                        <?= htmlspecialchars($clientDisplay, ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#8890AA] mt-0.5">
                        <?= htmlspecialchars($emailDisplay, ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </td>

                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B]">
                        <?= htmlspecialchars($b['service'] ?: 'Advisory Consultation', ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#6C5BB5] font-semibold mt-0.5">
                        <?= htmlspecialchars($b['duration'] ?: ($b['duration_min'] ? $b['duration_min'] . ' min' : '45 min'), ENT_QUOTES, 'UTF-8') ?>
                      </div>
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

                    <td class="py-4 px-4 text-right whitespace-nowrap space-x-1">
                      <!-- Review Modal Trigger -->
                      <button type="button"
                              onclick='openBookingModal(<?= json_encode($b, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                              class="px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-gray-100 transition-colors">
                        Details
                      </button>

                      <!-- Quick Confirm Button -->
                      <?php if ($st === 'pending'): ?>
                        <form method="POST" action="bookings.php" class="inline">
                          <?= csrf_input() ?>
                          <input type="hidden" name="action" value="update_booking_status">
                          <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                          <input type="hidden" name="status" value="confirmed">
                          <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#DDEBFF] text-[#13224B] text-xs font-bold hover:bg-[#c9ddff] transition-all" title="Confirm Booking">
                            Confirm
                          </button>
                        </form>
                      <?php endif; ?>

                      <!-- Quick Complete Button -->
                      <?php if ($st === 'confirmed'): ?>
                        <form method="POST" action="bookings.php" class="inline">
                          <?= csrf_input() ?>
                          <input type="hidden" name="action" value="update_booking_status">
                          <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                          <input type="hidden" name="status" value="completed">
                          <button type="submit" class="px-3 py-1.5 rounded-lg bg-[#DFF6E8] text-[#127A45] text-xs font-bold hover:bg-[#c7f3d8] transition-all" title="Mark Completed">
                            Complete
                          </button>
                        </form>
                      <?php endif; ?>

                      <!-- Delete Action -->
                      <form method="POST" action="bookings.php" onsubmit="return confirm('Are you sure you want to cancel and delete this booking?');" class="inline">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="delete_booking">
                        <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
                        <button type="submit" class="p-1.5 rounded-lg border border-red-200 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors" title="Delete Booking">
                          <iconify-icon icon="lucide:trash-2" class="text-sm"></iconify-icon>
                        </button>
                      </form>
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
                  <a href="bookings.php?page=<?= $i ?><?= !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : '' ?>"
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

  <!-- Booking Details Review Modal -->
  <div id="bookingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-xl w-full p-8 border border-[rgba(19,34,75,0.08)] shadow-2xl relative">
      <button onclick="closeBookingModal()" class="absolute right-6 top-6 text-[#8890AA] hover:text-[#13224B]">
        <iconify-icon icon="lucide:x" class="text-2xl"></iconify-icon>
      </button>

      <div class="flex items-center gap-3 mb-4">
        <span id="modalBkgStatusBadge" class="px-3 py-1 rounded-full text-xs font-bold badge-pending">Pending</span>
        <span class="text-xs text-[#8890AA] font-mono" id="modalBkgCode">BKG-0001</span>
      </div>

      <h3 class="text-2xl font-extrabold text-[#13224B] mb-1" id="modalBkgClient">Client Name</h3>
      <p class="text-xs text-[#6C5BB5] font-semibold mb-6" id="modalBkgEmail">client@example.com</p>

      <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs bg-[#F4F6F8] p-4 rounded-2xl mb-6">
        <div>
          <span class="text-[#8890AA] block mb-1">Service</span>
          <strong class="text-[#13224B]" id="modalBkgService">UI Design</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-1">Duration</span>
          <strong class="text-[#13224B]" id="modalBkgDuration">45 min</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-1">Rate</span>
          <strong class="text-[#13224B]" id="modalBkgPrice">$150</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-1">Scheduled Date</span>
          <strong class="text-[#13224B]" id="modalBkgDate">2026-09-10</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-1">Time Slot</span>
          <strong class="text-[#13224B]" id="modalBkgTime">10:00 AM</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-1">Format</span>
          <strong class="text-[#13224B]" id="modalBkgFormat">Google Meet</strong>
        </div>
      </div>

      <div class="flex items-center justify-between gap-3 pt-4 border-t border-[rgba(19,34,75,0.06)]">
        <form method="POST" action="bookings.php" id="modalStatusForm" class="flex gap-2">
          <?= csrf_input() ?>
          <input type="hidden" name="action" value="update_booking_status">
          <input type="hidden" name="booking_id" id="modalBookingIdInput" value="">
          <button type="submit" name="status" value="confirmed" class="px-4 py-2 rounded-xl bg-[#DDEBFF] text-[#13224B] text-xs font-bold hover:bg-[#c9ddff] transition-all">
            Confirm Booking
          </button>
          <button type="submit" name="status" value="completed" class="px-4 py-2 rounded-xl bg-[#DFF6E8] text-[#127A45] text-xs font-bold hover:bg-[#c7f3d8] transition-all">
            Mark Completed
          </button>
          <button type="submit" name="status" value="cancelled" class="px-4 py-2 rounded-xl bg-red-50 text-red-700 text-xs font-bold hover:bg-red-100 transition-all">
            Cancel
          </button>
        </form>

        <button type="button" onclick="closeBookingModal()" class="px-4 py-2 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4b4b4b] hover:bg-[#F4F6F8]">
          Close
        </button>
      </div>
    </div>
  </div>

  <script>
    function openBookingModal(data) {
      const modal = document.getElementById('bookingModal');
      const bkgCode = data.booking_code || ('BKG-' + String(data.id).padStart(4, '0'));
      const client = data.client_name || data.guest_name || 'Guest Client';
      const email = data.client_email || data.guest_email || 'No email provided';
      const service = data.service || 'Advisory Consultation';
      const duration = data.duration || (data.duration_min ? data.duration_min + ' min' : '45 min');
      const price = (data.price && data.price.startsWith('$')) ? data.price : ('$' + (data.price_php || data.price || '150'));
      const date = data.date || data.booking_date || 'TBD';
      const time = data.time || data.booking_time || '';
      const format = data.format || data.meeting_format || 'Google Meet';
      const status = (data.status || 'pending').toLowerCase();

      document.getElementById('modalBkgCode').textContent = bkgCode;
      document.getElementById('modalBkgClient').textContent = client;
      document.getElementById('modalBkgEmail').textContent = email;
      document.getElementById('modalBkgService').textContent = service;
      document.getElementById('modalBkgDuration').textContent = duration;
      document.getElementById('modalBkgPrice').textContent = price;
      document.getElementById('modalBkgDate').textContent = date;
      document.getElementById('modalBkgTime').textContent = time;
      document.getElementById('modalBkgFormat').textContent = format;
      document.getElementById('modalBookingIdInput').value = data.id;

      const badge = document.getElementById('modalBkgStatusBadge');
      badge.className = 'px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider badge-' + status;
      badge.textContent = status;

      modal.classList.remove('hidden');
    }

    function closeBookingModal() {
      document.getElementById('bookingModal').classList.add('hidden');
    }

    document.getElementById('bookingModal').addEventListener('click', function(e) {
      if (e.target === this) closeBookingModal();
    });
  </script>

</body>
</html>
