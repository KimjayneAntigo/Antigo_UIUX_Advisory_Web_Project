<?php
/**
 * admin/payments.php
 * Payment Recording & Verification Management Hub.
 * Allows studio admins to review offline payment declarations, verify, or reject with notes.
 */

require_once __DIR__ . '/../includes/auth-check-admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'payments';
$pageTitle      = 'Payments & Verification — Admin';
$pageHeading    = 'Payments & Verification';
$pageSubheading = 'Verify manual client payment declarations, review receipts, and reconcile project settlements.';

$statusWhitelist = ['pending', 'verified', 'rejected'];
$actionWhitelist = ['verify', 'reject'];

// POST Handler: Verify or Reject payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid or expired security token. Please try again.');
        safe_redirect('payments.php');
    }

    $action    = trim($_POST['action'] ?? '');
    $paymentId = (int) ($_POST['payment_id'] ?? 0);

    if ($paymentId <= 0) {
        set_flash('error', 'Invalid payment reference.');
        safe_redirect('payments.php');
    }

    if (!in_array($action, $actionWhitelist, true)) {
        set_flash('error', 'Invalid payment action requested.');
        safe_redirect('payments.php');
    }

    $refStr = format_payment_ref($paymentId);

    // Verify Action
    if ($action === 'verify') {
        try {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'verified', verified_at = NOW() WHERE id = ?");
            $stmt->execute([$paymentId]);
            set_flash('success', "Payment {$refStr} marked as verified.");
        } catch (\PDOException $e) {
            error_log('admin/payments.php verify error: ' . $e->getMessage());
            set_flash('error', 'Database error updating payment verification.');
        }
        safe_redirect('payments.php');
    }

    // Reject Action
    if ($action === 'reject') {
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        if (empty($adminNotes)) {
            set_flash('error', 'Please provide a reason or note explaining why the payment was rejected.');
            safe_redirect('payments.php');
        }

        try {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected', admin_notes = ? WHERE id = ?");
            $stmt->execute([$adminNotes, $paymentId]);
            set_flash('success', "Payment {$refStr} marked as rejected.");
        } catch (\PDOException $e) {
            error_log('admin/payments.php reject error: ' . $e->getMessage());
            set_flash('error', 'Database error updating payment rejection.');
        }
        safe_redirect('payments.php');
    }

    safe_redirect('payments.php');
}

// Filter & Pagination
$filterStatus = trim($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$payments  = [];
$totalRows = 0;

// Count by status for filter tabs
$counts = ['all' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0];
try {
    $counts['all']      = (int) $pdo->query('SELECT COUNT(*) FROM payments')->fetchColumn();
    $counts['pending']  = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
    $counts['verified'] = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'verified'")->fetchColumn();
    $counts['rejected'] = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'rejected'")->fetchColumn();

    if (!empty($filterStatus) && in_array($filterStatus, $statusWhitelist, true)) {
        $cStmt = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE status = ?');
        $cStmt->execute([$filterStatus]);
        $totalRows = (int) $cStmt->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT pay.*, p.project_code, p.title AS project_title, p.budget AS project_budget,
                    u.name AS client_name, u.email AS client_email, u.company AS client_company
             FROM payments pay
             JOIN projects p ON pay.project_id = p.id
             JOIN users u ON pay.user_id = u.id
             WHERE pay.status = ?
             ORDER BY pay.submitted_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $filterStatus, PDO::PARAM_STR);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $payments = $stmt->fetchAll();
    } else {
        $totalRows = $counts['all'];

        $stmt = $pdo->prepare(
            'SELECT pay.*, p.project_code, p.title AS project_title, p.budget AS project_budget,
                    u.name AS client_name, u.email AS client_email, u.company AS client_company
             FROM payments pay
             JOIN projects p ON pay.project_id = p.id
             JOIN users u ON pay.user_id = u.id
             ORDER BY pay.submitted_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $payments = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    error_log('admin/payments.php fetch error: ' . $e->getMessage());
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .payment-row:hover { background-color: rgba(76,108,203,0.03); }
        .badge-pending   { background: #FFF1D6; color: #946200; }
        .badge-verified  { background: #DFF6E8; color: #127A45; }
        .badge-rejected  { background: #FDE8E8; color: #9B1C1C; }
    </style>
</head>
<body class="min-h-screen">

  <!-- Admin Sidebar Shell -->
  <?php require_once __DIR__ . '/../includes/sidebar-admin.php'; ?>

  <!-- Main Content Wrapper -->
  <div class="main-content">
    <?php require_once __DIR__ . '/../includes/header-admin.php'; ?>

    <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Filter Tabs & Stats Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="payments.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterStatus) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All (<?= $counts['all'] ?>)
          </a>
          <a href="payments.php?status=pending"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all inline-flex items-center gap-1.5 <?= $filterStatus === 'pending' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            <span>Pending</span>
            <?php if ($counts['pending'] > 0): ?>
              <span class="px-1.5 py-0.2 rounded-full text-[10px] <?= $filterStatus === 'pending' ? 'bg-[#4C6CCB] text-white' : 'bg-amber-100 text-amber-900 font-extrabold' ?>">
                <?= $counts['pending'] ?>
              </span>
            <?php endif; ?>
          </a>
          <a href="payments.php?status=verified"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'verified' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Verified (<?= $counts['verified'] ?>)
          </a>
          <a href="payments.php?status=rejected"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'rejected' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Rejected (<?= $counts['rejected'] ?>)
          </a>
        </div>

        <div class="text-xs text-[#8890AA]">
          Showing <strong><?= count($payments) ?></strong> of <strong><?= $totalRows ?></strong> payment records
        </div>
      </div>

      <!-- Payments Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)]">
        <?php if (empty($payments)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:credit-card"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No payment records found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterStatus) ? "There are currently no {$filterStatus} payments." : "When clients complete projects and report their payments, they will appear here for verification." ?>
            </p>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/50 text-[#8890AA] uppercase font-bold tracking-wider text-[10px]">
                  <th class="py-3.5 px-4">Ref Code</th>
                  <th class="py-3.5 px-4">Client</th>
                  <th class="py-3.5 px-4">Project</th>
                  <th class="py-3.5 px-4">Amount</th>
                  <th class="py-3.5 px-4">Method</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4">Submitted</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.05)]">
                <?php foreach ($payments as $pay): ?>
                  <?php
                    $ref = format_payment_ref((int)$pay['id']);
                    $st  = strtolower($pay['status']);
                    $badgeClass = match($st) {
                        'verified' => 'badge-verified',
                        'rejected' => 'badge-rejected',
                        default    => 'badge-pending',
                    };
                  ?>
                  <tr class="payment-row transition-colors">
                    <!-- Reference -->
                    <td class="py-4 px-4 font-mono font-bold text-[#13224B]">
                      <?= $ref ?>
                    </td>

                    <!-- Client -->
                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B]"><?= htmlspecialchars($pay['client_name'], ENT_QUOTES, 'UTF-8') ?></div>
                      <div class="text-[11px] text-[#8890AA]"><?= htmlspecialchars($pay['client_email'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>

                    <!-- Project -->
                    <td class="py-4 px-4">
                      <a href="project-detail.php?id=<?= (int)$pay['project_id'] ?>" class="font-bold text-[#4C6CCB] hover:underline flex items-center gap-1">
                        <span><?= htmlspecialchars($pay['project_title'], ENT_QUOTES, 'UTF-8') ?></span>
                        <iconify-icon icon="lucide:external-link" class="text-[10px]"></iconify-icon>
                      </a>
                      <span class="text-[10px] font-mono text-[#8890AA]"><?= htmlspecialchars($pay['project_code'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>

                    <!-- Amount -->
                    <td class="py-4 px-4 font-bold text-[#13224B] text-sm">
                      $<?= number_format((float)$pay['amount'], 2) ?>
                    </td>

                    <!-- Method -->
                    <td class="py-4 px-4">
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#F4F6F8] font-semibold text-[#13224B]">
                        <iconify-icon icon="<?= match($pay['payment_method']) {
                            'GCash'         => 'lucide:smartphone',
                            'Bank Transfer' => 'lucide:landmark',
                            'Cash'          => 'lucide:banknote',
                            default         => 'lucide:credit-card'
                        } ?>" class="text-xs text-[#4C6CCB]"></iconify-icon>
                        <?= htmlspecialchars($pay['payment_method'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <!-- Status -->
                    <td class="py-4 px-4">
                      <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider <?= $badgeClass ?>">
                        <?= htmlspecialchars($pay['status'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <!-- Submitted Date -->
                    <td class="py-4 px-4 text-[#8890AA]">
                      <?= date('M j, Y', strtotime($pay['submitted_at'])) ?>
                      <div class="text-[10px]"><?= date('g:i A', strtotime($pay['submitted_at'])) ?></div>
                    </td>

                    <!-- Actions -->
                    <td class="py-4 px-4 text-right whitespace-nowrap">
                      <button type="button"
                              onclick="openPaymentModal(<?= (int)$pay['id'] ?>)"
                              class="px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-white transition-colors">
                        <?= $pay['status'] === 'pending' ? 'Review & Verify' : 'View Details' ?>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer -->
          <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-[#F4F6F8]/30 border-t border-[rgba(19,34,75,0.08)] flex items-center justify-between">
              <div class="text-xs text-[#8890AA]">
                Page <?= $page ?> of <?= $totalPages ?>
              </div>
              <div class="flex items-center gap-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <a href="payments.php?page=<?= $i ?><?= !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : '' ?>"
                     class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all <?= $page === $i ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#13224B] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

    </main>
  </div>

  <!-- Detail & Verification Modal -->
  <div id="paymentDetailModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl border border-[rgba(19,34,75,0.12)] overflow-hidden animate-in fade-in zoom-in-95 duration-150">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-[rgba(19,34,75,0.08)] flex items-center justify-between bg-[#F4F6F8]/60">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg shadow-sm">
            <iconify-icon icon="lucide:receipt"></iconify-icon>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-mono text-sm font-extrabold text-[#13224B]" id="modalRefCode">PAY-00000</span>
              <span id="modalStatusBadge" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">Pending</span>
            </div>
            <p class="text-[11px] text-[#8890AA]">Manual Offline Payment Declaration</p>
          </div>
        </div>
        <button type="button" onclick="closePaymentModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-[#8890AA] hover:text-[#13224B] hover:bg-white transition-colors">
          <iconify-icon icon="lucide:x" class="text-base"></iconify-icon>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-5 text-xs">

        <!-- Payment Details Summary Grid -->
        <div class="grid grid-cols-2 gap-3 p-4 rounded-xl bg-[#F4F6F8]/70 border border-[rgba(19,34,75,0.06)]">
          <div>
            <span class="text-[#8890AA] text-[10px] uppercase font-bold tracking-wider block">Payable Amount</span>
            <strong class="text-base font-extrabold text-[#13224B]" id="modalAmount">$0.00</strong>
          </div>
          <div>
            <span class="text-[#8890AA] text-[10px] uppercase font-bold tracking-wider block">Payment Method</span>
            <span class="font-bold text-[#13224B] text-sm" id="modalMethod">GCash</span>
          </div>
          <div class="pt-2 border-t border-[rgba(19,34,75,0.06)]">
            <span class="text-[#8890AA] text-[10px] uppercase font-bold tracking-wider block">Submitted At</span>
            <span class="font-medium text-[#4b4b4b]" id="modalSubmitted">Date</span>
          </div>
          <div class="pt-2 border-t border-[rgba(19,34,75,0.06)]">
            <span class="text-[#8890AA] text-[10px] uppercase font-bold tracking-wider block">Verified At</span>
            <span class="font-medium text-[#4b4b4b]" id="modalVerified">—</span>
          </div>
        </div>

        <!-- Project & Client Context -->
        <div class="space-y-2 p-4 rounded-xl bg-white border border-[rgba(19,34,75,0.08)]">
          <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Project &amp; Client</span>
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-bold text-[#13224B] text-sm" id="modalProjectTitle">Project Title</p>
              <p class="text-[11px] text-[#8890AA]" id="modalClientInfo">Client Name (client@email.com)</p>
            </div>
            <a href="#" id="modalProjectLink" class="px-2.5 py-1 rounded-lg bg-[#DDEBFF] text-[#4C6CCB] font-bold text-[11px] hover:bg-[#4C6CCB] hover:text-white transition-colors flex items-center gap-1">
              <span>View</span>
              <iconify-icon icon="lucide:arrow-right" class="text-xs"></iconify-icon>
            </a>
          </div>
        </div>

        <!-- Admin Notes / Rejection Reason (if any) -->
        <div id="modalNotesBlock" class="hidden p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 space-y-1">
          <strong class="block text-[11px] font-bold text-rose-800">Admin Rejection Note:</strong>
          <p class="text-[11px] italic text-rose-700" id="modalAdminNotes"></p>
        </div>

        <!-- Action Box for Pending Payments -->
        <div id="modalPendingActions" class="space-y-4 pt-2">
          <div class="p-3 rounded-xl bg-amber-50 border border-amber-200/60 text-amber-900 text-[11px] flex items-start gap-2">
            <iconify-icon icon="lucide:alert-triangle" class="text-amber-600 text-sm flex-shrink-0 mt-0.5"></iconify-icon>
            <span>Please check your physical bank statement or GCash app to confirm that the amount actually arrived before marking as verified.</span>
          </div>

          <div class="flex items-center gap-2">
            <!-- Verify Form -->
            <form method="POST" action="payments.php" class="flex-1"
                  onsubmit="return confirm('Verify that this payment actually arrived in your bank/GCash account?');">
              <?= csrf_input() ?>
              <input type="hidden" name="action" value="verify">
              <input type="hidden" name="payment_id" id="verifyPaymentId" value="">
              <button type="submit"
                      class="w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white shadow-sm flex items-center justify-center gap-1.5 transition-all hover:opacity-95"
                      style="background: linear-gradient(135deg, #127A45, #10B981);">
                <iconify-icon icon="lucide:check-circle-2" class="text-sm"></iconify-icon>
                <span>Verify Payment</span>
              </button>
            </form>

            <!-- Toggle Reject Form Button -->
            <button type="button"
                    onclick="toggleRejectBox()"
                    class="py-2.5 px-4 rounded-xl border border-red-200 text-xs font-bold text-red-600 hover:bg-red-50 transition-colors flex items-center gap-1">
              <iconify-icon icon="lucide:x-circle" class="text-sm"></iconify-icon>
              <span>Reject...</span>
            </button>
          </div>

          <!-- Collapsible Rejection Form -->
          <div id="rejectFormBox" class="hidden p-4 rounded-xl bg-red-50/50 border border-red-200 space-y-3">
            <form method="POST" action="payments.php" onsubmit="return confirm('Reject this payment declaration? The client will see your note.');">
              <?= csrf_input() ?>
              <input type="hidden" name="action" value="reject">
              <input type="hidden" name="payment_id" id="rejectPaymentId" value="">

              <div>
                <label class="block text-[11px] font-bold text-red-900 mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                <textarea name="admin_notes" required rows="2"
                          placeholder="e.g. Reference not found on bank statement, wrong account, or amount mismatch..."
                          class="w-full p-2.5 rounded-lg border border-red-200 text-xs text-[#13224B] focus:outline-none focus:border-red-400 bg-white"></textarea>
              </div>

              <div class="flex justify-end gap-2">
                <button type="button" onclick="toggleRejectBox()" class="px-3 py-1.5 rounded-lg text-xs font-bold text-[#8890AA] hover:bg-white">
                  Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-red-600 text-xs font-bold text-white hover:bg-red-700 shadow-sm">
                  Confirm Rejection
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Settled State Message (if already verified) -->
        <div id="modalSettledMessage" class="hidden p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] flex items-center gap-2 font-medium">
          <iconify-icon icon="lucide:check-circle" class="text-emerald-600 text-base"></iconify-icon>
          <span>This payment declaration is verified and settled. No further actions needed.</span>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/50 flex justify-end">
        <button type="button" onclick="closePaymentModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-[#8890AA] hover:text-[#13224B] hover:bg-white transition-colors">
          Close
        </button>
      </div>

    </div>
  </div>

  <script>
    const allPayments = <?= json_encode($payments, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

    function openPaymentModal(id) {
      const pay = allPayments.find(p => parseInt(p.id) === parseInt(id));
      if (!pay) return;

      const ref = 'PAY-' + String(pay.id).padStart(5, '0');
      document.getElementById('modalRefCode').innerText = ref;
      document.getElementById('modalAmount').innerText  = '$' + parseFloat(pay.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      document.getElementById('modalMethod').innerText  = pay.payment_method;
      document.getElementById('modalSubmitted').innerText = pay.submitted_at;
      document.getElementById('modalVerified').innerText  = pay.verified_at || '—';

      document.getElementById('modalProjectTitle').innerText = pay.project_title;
      document.getElementById('modalClientInfo').innerText   = `${pay.client_name} (${pay.client_email})`;
      document.getElementById('modalProjectLink').href       = `project-detail.php?id=${pay.project_id}`;

      document.getElementById('verifyPaymentId').value = pay.id;
      document.getElementById('rejectPaymentId').value = pay.id;

      const badge = document.getElementById('modalStatusBadge');
      badge.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider';
      const status = pay.status.toLowerCase();
      if (status === 'verified') {
        badge.classList.add('badge-verified');
        badge.innerText = 'Verified';
      } else if (status === 'rejected') {
        badge.classList.add('badge-rejected');
        badge.innerText = 'Rejected';
      } else {
        badge.classList.add('badge-pending');
        badge.innerText = 'Pending';
      }

      // Notes block
      const notesBlock = document.getElementById('modalNotesBlock');
      if (pay.admin_notes && pay.admin_notes.trim() !== '') {
        document.getElementById('modalAdminNotes').innerText = pay.admin_notes;
        notesBlock.classList.remove('hidden');
      } else {
        notesBlock.classList.add('hidden');
      }

      // Action boxes
      const pendingActions = document.getElementById('modalPendingActions');
      const settledMessage = document.getElementById('modalSettledMessage');
      const rejectBox      = document.getElementById('rejectFormBox');
      if (rejectBox) rejectBox.classList.add('hidden');

      if (status === 'pending') {
        pendingActions.classList.remove('hidden');
        settledMessage.classList.add('hidden');
      } else if (status === 'verified') {
        pendingActions.classList.add('hidden');
        settledMessage.classList.remove('hidden');
      } else {
        pendingActions.classList.add('hidden');
        settledMessage.classList.add('hidden');
      }

      document.getElementById('paymentDetailModal').classList.remove('hidden');
    }

    function closePaymentModal() {
      document.getElementById('paymentDetailModal').classList.add('hidden');
    }

    function toggleRejectBox() {
      const box = document.getElementById('rejectFormBox');
      if (box) {
        box.classList.toggle('hidden');
      }
    }

    // Close on Escape or click outside
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closePaymentModal();
    });
    document.getElementById('paymentDetailModal')?.addEventListener('click', function(e) {
      if (e.target === this) closePaymentModal();
    });
  </script>
</body>
</html>
