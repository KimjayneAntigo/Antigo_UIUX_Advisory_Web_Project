<?php

require_once __DIR__ . '/../includes/auth-check-admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage    = 'inquiries';
$pageTitle     = 'Inquiry Leads Pipeline — Admin';
$pageHeading   = 'Inquiry Leads Pipeline';
$pageSubheading = 'Qualify incoming leads, track client status, and convert inquiries to active projects.';

$statusWhitelist = ['new', 'reviewed', 'contacted', 'converted', 'lost'];

// POST Handler: Status Update or Convert to Project
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = trim($_POST['action'] ?? '');
    $inquiryId = (int) ($_POST['inquiry_id'] ?? 0);

    if ($inquiryId <= 0) {
        set_flash('error', 'Invalid inquiry reference.');
        safe_redirect('inquiries.php');
    }

    // Update Status Action
    if ($action === 'update_status') {
        $newStatus = trim($_POST['status'] ?? '');
        if (!in_array($newStatus, $statusWhitelist, true)) {
            set_flash('error', 'Invalid status selected.');
            safe_redirect('inquiries.php');
        }

        try {
            $stmt = $pdo->prepare('UPDATE inquiries SET status = :status WHERE id = :id');
            $stmt->execute(['status' => $newStatus, 'id' => $inquiryId]);
            $label = ucfirst($newStatus);
            set_flash('success', "Inquiry marked as {$label}.");
        } catch (\PDOException $e) {
            error_log('inquiries.php update_status error: ' . $e->getMessage());
            set_flash('error', 'Unable to update inquiry status.');
        }
        safe_redirect('inquiries.php');
    }

    // Convert to Project Action
    if ($action === 'convert_to_project') {
        try {
            // Fetch inquiry row
            $stmt = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
            $stmt->execute([$inquiryId]);
            $inq = $stmt->fetch();

            if (!$inq) {
                set_flash('error', 'Inquiry not found.');
                safe_redirect('inquiries.php');
            }

            // Find matching user_id if inquiry doesn't have one
            $userId = $inq['user_id'];
            if (!$userId && !empty($inq['email'])) {
                $uStmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
                $uStmt->execute([$inq['email']]);
                $uRow = $uStmt->fetch();
                if ($uRow) {
                    $userId = (int) $uRow['id'];
                }
            }

            // Generate unique project_code
            $maxCodeStmt = $pdo->query('SELECT MAX(id) AS max_id FROM projects');
            $nextNum = ((int) ($maxCodeStmt->fetch()['max_id'] ?? 0)) + 3001;
            $projectCode = 'PRJ-' . $nextNum;

            $clientDisplayName = !empty($inq['company']) ? $inq['company'] : $inq['name'];
            $serviceName       = !empty($inq['service']) ? $inq['service'] : 'UI/UX Design';
            $projectTitle      = $clientDisplayName . ' – ' . $serviceName;
            $budgetVal         = !empty($inq['budget']) ? $inq['budget'] : '$150,000 – $300,000';
            $budgetVal         = str_replace('₱', '$', $budgetVal);
            $dueDate           = date('Y-m-d', strtotime('+30 days'));

            $pdo->beginTransaction();

            $pStmt = $pdo->prepare(
                'INSERT INTO projects
                    (user_id, project_code, title, category, client_name, client_email, company, budget, due_date,
                     current_phase, phase_name, progress, status, status_type, internal_notes, created_at, updated_at)
                 VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, \'Discovery & Research\', 20, \'Discovery\', \'in_design\', ?, NOW(), NOW())'
            );

            $notes = "Converted from Inquiry ref: {$inq['ref_code']}.\nDescription: {$inq['description']}";
            $pStmt->execute([
                $userId,
                $projectCode,
                $projectTitle,
                $serviceName,
                $inq['name'],
                $inq['email'],
                $inq['company'] ?: null,
                $budgetVal,
                $dueDate,
                $notes,
            ]);

            $newProjectId = (int) $pdo->lastInsertId();

            // Mark inquiry as converted
            $updInq = $pdo->prepare("UPDATE inquiries SET status = 'converted' WHERE id = ?");
            $updInq->execute([$inquiryId]);

            // If an attached file exists in inquiry, link it to project_files
            if (!empty($inq['attached_file'])) {
                $fStmt = $pdo->prepare(
                    'INSERT INTO project_files (project_id, uploaded_by, name, size, file_path, uploaded_at)
                     VALUES (?, ?, ?, ?, ?, NOW())'
                );
                $fStmt->execute([
                    $newProjectId,
                    (int)($_SESSION['user_id'] ?? 0),
                    basename($inq['attached_file']),
                    'Reference Brief',
                    'uploads/inquiries/' . $inq['attached_file'],
                ]);
            }

            // Insert initial system message into thread
            $mStmt = $pdo->prepare(
                'INSERT INTO project_messages (project_id, sender, role, message, created_at)
                 VALUES (?, ?, \'designer\', ?, NOW())'
            );
            $mStmt->execute([
                $newProjectId,
                $_SESSION['user_name'] ?? 'Admin',
                "Project workspace initialized from inquiry {$inq['ref_code']}. Discovery phase is now active.",
            ]);

            $pdo->commit();

            set_flash('success', "Inquiry successfully converted to Project {$projectCode}!");
            safe_redirect("project-detail.php?id={$newProjectId}");

        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('inquiries.php convert_to_project error: ' . $e->getMessage());
            set_flash('error', 'Database error converting inquiry to project.');
            safe_redirect('inquiries.php');
        }
    }

    set_flash('error', 'Unknown action.');
    safe_redirect('inquiries.php');
}

// Filter & Pagination
$filterStatus = trim($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$inquiries = [];
$totalRows = 0;

try {
    if (!empty($filterStatus) && in_array($filterStatus, $statusWhitelist, true)) {
        $cStmt = $pdo->prepare('SELECT COUNT(*) FROM inquiries WHERE status = ?');
        $cStmt->execute([$filterStatus]);
        $totalRows = (int) $cStmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT * FROM inquiries WHERE status = ? ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $filterStatus, PDO::PARAM_STR);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $inquiries = $stmt->fetchAll();
    } else {
        $totalRows = (int) $pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();

        $stmt = $pdo->prepare('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $inquiries = $stmt->fetchAll();
    }
} catch (\PDOException $e) {
    error_log('inquiries.php list query error: ' . $e->getMessage());
    set_flash('error', 'Error loading inquiries.');
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .inquiry-row:hover { background-color: rgba(76,108,203,0.03); }
        .badge-new        { background: #FFF1D6; color: #946200; }
        .badge-contacted  { background: #DDEBFF; color: #13224B; }
        .badge-reviewed   { background: rgba(108,91,181,0.14); color: #6C5BB5; }
        .badge-converted  { background: #DFF6E8; color: #127A45; }
        .badge-lost       { background: #F4F6F8; color: #6b7280; border: 1px solid #e5e7eb; }
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
          <a href="inquiries.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterStatus) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All (<?= $totalRows ?>)
          </a>
          <?php foreach ($statusWhitelist as $st): ?>
            <a href="inquiries.php?status=<?= $st ?>"
               class="px-3 py-1.5 rounded-lg text-xs font-bold capitalize transition-all <?= $filterStatus === $st ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
              <?= $st ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div class="text-xs text-[#8890AA]">
          Showing <strong><?= count($inquiries) ?></strong> of <strong><?= $totalRows ?></strong> leads
        </div>
      </div>

      <!-- Inquiries Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)]">
        <?php if (empty($inquiries)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:inbox"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No inquiries found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterStatus) ? "No inquiries with status '{$filterStatus}'." : 'Incoming client project leads will appear here automatically when submitted from the public inquiry form.' ?>
            </p>
            <?php if (!empty($filterStatus)): ?>
              <a href="inquiries.php" class="inline-block mt-4 text-xs font-bold text-[#4C6CCB] hover:underline">
                Clear filter
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                  <th class="py-3.5 px-4">Lead / Company</th>
                  <th class="py-3.5 px-4">Contact Info</th>
                  <th class="py-3.5 px-4">Service &amp; Budget</th>
                  <th class="py-3.5 px-4">Submitted</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($inquiries as $inq): ?>
                  <tr class="inquiry-row transition-colors">
                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B] text-sm leading-tight">
                        <?= htmlspecialchars($inq['name'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#6C5BB5] font-semibold mt-0.5">
                        <?= htmlspecialchars($inq['company'] ?: 'Individual / Independent', ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <span class="inline-block mt-1 font-mono text-[10px] text-[#8890AA] bg-gray-100 px-1.5 py-0.5 rounded">
                        <?= htmlspecialchars($inq['ref_code'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <td class="py-4 px-4">
                      <div class="text-xs text-[#13224B] font-medium">
                        <?= htmlspecialchars($inq['email'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#8890AA] mt-0.5 flex items-center gap-1">
                        <iconify-icon icon="lucide:phone" class="text-xs"></iconify-icon>
                        <?= htmlspecialchars($inq['phone'] ?: 'No phone provided', ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </td>

                    <td class="py-4 px-4">
                      <div class="font-bold text-[#13224B]">
                        <?= htmlspecialchars($inq['service'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <div class="text-[11px] text-[#8890AA] mt-0.5">
                        <?= htmlspecialchars($inq['budget'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars($inq['timeline'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </td>

                    <td class="py-4 px-4 text-[#8890AA] text-[11px] whitespace-nowrap">
                      <?= time_ago($inq['created_at']) ?>
                      <div class="text-[10px] text-gray-400">
                        <?= date('M j, Y', strtotime($inq['created_at'])) ?>
                      </div>
                    </td>

                    <td class="py-4 px-4">
                      <!-- Quick Status Dropdown Form -->
                      <form method="POST" action="inquiries.php" class="inline-block">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="inquiry_id" value="<?= (int) $inq['id'] ?>">
                        <select name="status"
                                onchange="this.form.submit()"
                                class="text-xs font-semibold px-2.5 py-1 rounded-full border border-transparent cursor-pointer shadow-sm focus:outline-none focus:ring-2 focus:ring-[#4C6CCB] badge-<?= $inq['status'] ?>">
                          <?php foreach ($statusWhitelist as $st): ?>
                            <option value="<?= $st ?>" <?= $inq['status'] === $st ? 'selected' : '' ?>>
                              <?= ucfirst($st) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </form>
                    </td>

                    <td class="py-4 px-4 text-right whitespace-nowrap space-x-2">
                      <button type="button"
                              onclick='openInquiryModal(<?= json_encode($inq, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                              class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-gray-100 transition-colors">
                        <iconify-icon icon="lucide:eye" class="text-sm"></iconify-icon>
                        <span>Details</span>
                      </button>

                      <?php if ($inq['status'] !== 'converted'): ?>
                        <form method="POST" action="inquiries.php" class="inline-block"
                              onsubmit="return confirm('Convert this inquiry into an Active Project? This will create a project workspace.');">
                          <input type="hidden" name="action" value="convert_to_project">
                          <input type="hidden" name="inquiry_id" value="<?= (int) $inq['id'] ?>">
                          <button type="submit"
                                  class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-white transition-all hover:opacity-90"
                                  style="background: linear-gradient(135deg, #127A45, #10b981);">
                            <iconify-icon icon="lucide:sparkles" class="text-sm"></iconify-icon>
                            <span>Convert</span>
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-[#127A45]">
                          <iconify-icon icon="lucide:check-check"></iconify-icon> Converted
                        </span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination Footer -->
          <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/50 flex items-center justify-between text-xs">
              <span class="text-[#8890AA]">Page <?= $page ?> of <?= $totalPages ?></span>
              <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                  <a href="inquiries.php?page=<?= $page - 1 ?><?= !empty($filterStatus) ? '&status=' . $filterStatus : '' ?>"
                     class="px-3 py-1 rounded bg-white border border-[rgba(19,34,75,0.1)] text-[#13224B] hover:bg-gray-100 font-bold">
                    Previous
                  </a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <a href="inquiries.php?page=<?= $i ?><?= !empty($filterStatus) ? '&status=' . $filterStatus : '' ?>"
                     class="px-2.5 py-1 rounded font-bold <?= $i === $page ? 'bg-[#13224B] text-white' : 'bg-white border border-[rgba(19,34,75,0.1)] text-[#13224B] hover:bg-gray-100' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                  <a href="inquiries.php?page=<?= $page + 1 ?><?= !empty($filterStatus) ? '&status=' . $filterStatus : '' ?>"
                     class="px-3 py-1 rounded bg-white border border-[rgba(19,34,75,0.1)] text-[#13224B] hover:bg-gray-100 font-bold">
                    Next
                  </a>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

        <?php endif; ?>
      </div>

    </main>
  </div>

  <!-- Detail Modal -->
  <div id="inquiryDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 border border-[rgba(19,34,75,0.08)] shadow-2xl relative">
      <button onclick="closeInquiryModal()" class="absolute right-5 top-5 text-[#8890AA] hover:text-[#13224B] p-1">
        <iconify-icon icon="lucide:x" class="text-2xl"></iconify-icon>
      </button>

      <div class="flex items-center gap-3 mb-4">
        <span id="modalRefCode" class="font-mono text-xs font-bold text-[#6C5BB5] bg-[#DDEBFF] px-2.5 py-1 rounded-md">INQ-XXXX</span>
        <span id="modalStatusBadge" class="text-xs font-bold px-2.5 py-0.5 rounded-full badge-new">New</span>
      </div>

      <h3 class="text-xl sm:text-2xl font-extrabold text-[#13224B] mb-1" id="modalName">Lead Name</h3>
      <p class="text-xs text-[#6C5BB5] font-semibold mb-5" id="modalCompany">Company · email@example.com</p>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-[#F4F6F8] p-4 rounded-2xl mb-5">
        <div>
          <span class="text-[#8890AA] block mb-0.5">Phone</span>
          <strong class="text-[#13224B]" id="modalPhone">—</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-0.5">Service</span>
          <strong class="text-[#13224B]" id="modalService">—</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-0.5">Budget</span>
          <strong class="text-[#13224B]" id="modalBudget">—</strong>
        </div>
        <div>
          <span class="text-[#8890AA] block mb-0.5">Timeline</span>
          <strong class="text-[#13224B]" id="modalTimeline">—</strong>
        </div>
      </div>

      <div class="space-y-4 mb-6 text-xs">
        <div>
          <span class="font-bold text-[#8890AA] uppercase tracking-wider block mb-1.5">Project Scope &amp; Message</span>
          <p class="p-4 rounded-2xl bg-white border border-[rgba(19,34,75,0.08)] text-[#4b4b4b] leading-relaxed text-sm max-h-48 overflow-y-auto" id="modalDescription">
            Description text...
          </p>
        </div>

        <div id="modalFileBlock" class="hidden">
          <span class="font-bold text-[#8890AA] uppercase tracking-wider block mb-1.5">Attached Reference File</span>
          <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#DDEBFF] text-[#13224B] font-semibold">
            <iconify-icon icon="lucide:file-text" class="text-[#4C6CCB]"></iconify-icon>
            <span id="modalFileName">brief.pdf</span>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-[rgba(19,34,75,0.08)]">
        <form method="POST" action="inquiries.php" id="modalConvertForm"
              onsubmit="return confirm('Convert this inquiry into an active project?');">
          <input type="hidden" name="action" value="convert_to_project">
          <input type="hidden" name="inquiry_id" id="modalConvertInquiryId" value="">
          <button type="submit" id="modalConvertBtn"
                  class="btn-primary py-2.5 px-5 text-xs font-bold inline-flex items-center gap-2">
            <iconify-icon icon="lucide:sparkles"></iconify-icon>
            <span>Convert to Project</span>
          </button>
        </form>

        <button type="button" onclick="closeInquiryModal()"
                class="px-5 py-2.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4b4b4b] hover:bg-[#F4F6F8]">
          Close
        </button>
      </div>
    </div>
  </div>

  <script>
    function openInquiryModal(inq) {
      document.getElementById('modalRefCode').innerText       = inq.ref_code;
      document.getElementById('modalStatusBadge').innerText   = inq.status.toUpperCase();
      document.getElementById('modalStatusBadge').className   = 'text-xs font-bold px-2.5 py-0.5 rounded-full badge-' + inq.status;
      document.getElementById('modalName').innerText          = inq.name;
      document.getElementById('modalCompany').innerText       = (inq.company ? inq.company + ' · ' : '') + inq.email;
      document.getElementById('modalPhone').innerText         = inq.phone || 'None provided';
      document.getElementById('modalService').innerText       = inq.service;
      document.getElementById('modalBudget').innerText        = inq.budget;
      document.getElementById('modalTimeline').innerText      = inq.timeline;
      document.getElementById('modalDescription').innerText   = inq.description;
      document.getElementById('modalConvertInquiryId').value  = inq.id;

      const fileBlock = document.getElementById('modalFileBlock');
      if (inq.attached_file) {
        document.getElementById('modalFileName').innerText = inq.attached_file;
        fileBlock.classList.remove('hidden');
      } else {
        fileBlock.classList.add('hidden');
      }

      const convertBtn = document.getElementById('modalConvertBtn');
      if (inq.status === 'converted') {
        convertBtn.disabled = true;
        convertBtn.classList.add('opacity-50', 'cursor-not-allowed');
        convertBtn.querySelector('span').innerText = 'Already Converted';
      } else {
        convertBtn.disabled = false;
        convertBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        convertBtn.querySelector('span').innerText = 'Convert to Project';
      }

      document.getElementById('inquiryDetailModal').classList.remove('hidden');
    }

    function closeInquiryModal() {
      document.getElementById('inquiryDetailModal').classList.add('hidden');
    }
  </script>
</body>
</html>
