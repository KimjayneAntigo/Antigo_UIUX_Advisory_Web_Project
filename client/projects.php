<?php
/**
 * Client Projects Workspace Hub.
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'projects';
$pageTitle      = 'My Projects — Client Portal';
$pageHeading    = 'My Projects';
$pageSubheading = 'Track your active design deliverables, milestone progress, and project workspaces.';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    safe_redirect('../login.php');
}

// Filter & Pagination
$filterStatus = trim($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$projects         = [];
$pendingInquiries = [];
$totalRows        = 0;

// Count queries for filter tabs
$counts = ['all' => 0, 'active' => 0, 'review' => 0, 'completed' => 0, 'cancelled' => 0];
try {
    // Counts for this client's projects
    $cAllStmt = $pdo->prepare('SELECT COUNT(*) FROM projects WHERE user_id = ?');
    $cAllStmt->execute([$userId]);
    $counts['all'] = (int) $cAllStmt->fetchColumn();

    $cActStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ? AND (status_type = 'active' OR status IN ('Discovery', 'Wireframing', 'In Progress', 'Active'))");
    $cActStmt->execute([$userId]);
    $counts['active'] = (int) $cActStmt->fetchColumn();

    $cRevStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ? AND (status_type = 'review' OR status LIKE '%Review%')");
    $cRevStmt->execute([$userId]);
    $counts['review'] = (int) $cRevStmt->fetchColumn();

    $cCompStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ? AND (status_type = 'completed' OR status IN ('Delivered', 'Completed'))");
    $cCompStmt->execute([$userId]);
    $counts['completed'] = (int) $cCompStmt->fetchColumn();

    $cCancStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = ? AND (status_type = 'cancelled' OR status = 'Cancelled')");
    $cCancStmt->execute([$userId]);
    $counts['cancelled'] = (int) $cCancStmt->fetchColumn();

    $whereClauses = ['user_id = ?'];
    $params       = [$userId];

    if (!empty($filterStatus)) {
        if ($filterStatus === 'active') {
            $whereClauses[] = "(status_type = 'active' OR status IN ('Discovery', 'Wireframing', 'In Progress', 'Active'))";
        } elseif ($filterStatus === 'review') {
            $whereClauses[] = "(status_type = 'review' OR status LIKE '%Review%')";
        } elseif ($filterStatus === 'completed') {
            $whereClauses[] = "(status_type = 'completed' OR status IN ('Delivered', 'Completed'))";
        } elseif ($filterStatus === 'cancelled') {
            $whereClauses[] = "(status_type = 'cancelled' OR status = 'Cancelled')";
        }
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM projects {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int) $countStmt->fetchColumn();

    $sql = "SELECT * FROM projects {$whereSql} ORDER BY updated_at DESC, created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $paramIndex = 1;
    foreach ($params as $p) {
        $stmt->bindValue($paramIndex++, $p, PDO::PARAM_INT);
    }
    $stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projects = $stmt->fetchAll();

    // Fetch pending inquiries created by this client
    $stmtInq = $pdo->prepare("SELECT * FROM inquiries WHERE user_id = ? AND status != 'converted' ORDER BY created_at DESC");
    $stmtInq->execute([$userId]);
    $pendingInquiries = $stmtInq->fetchAll();

} catch (\PDOException $e) {
    error_log('client/projects.php error: ' . $e->getMessage());
    set_flash('error', 'Error loading projects.');
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .project-row:hover { background-color: rgba(76,108,203,0.03); }
        .progress-track {
            background: rgba(19,34,75,0.07);
            border-radius: 99px;
            height: 6px;
            overflow: hidden;
            width: 100px;
        }
        .progress-fill {
            background: linear-gradient(135deg, #4C6CCB, #6C5BB5);
            height: 100%;
            border-radius: 99px;
        }
        .badge-active    { background: #DDEBFF; color: #13224B; }
        .badge-review    { background: rgba(108,91,181,0.12); color: #6C5BB5; }
        .badge-completed { background: #DFF6E8; color: #127A45; }
        .badge-cancelled { background: #FEE2E2; color: #991B1B; }
        .badge-new       { background: #FFF1D6; color: #946200; }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8]">

  <!-- Top Horizontal Navbar -->
  <?php 
    $activePage = 'projects';
    require_once __DIR__ . '/../includes/header-client.php'; 
  ?>

  <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Filter Tabs & Stats Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="projects.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterStatus) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All (<?= $counts['all'] ?>)
          </a>
          <a href="projects.php?status=active"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'active' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Active (<?= $counts['active'] ?>)
          </a>
          <a href="projects.php?status=review"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'review' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Review (<?= $counts['review'] ?>)
          </a>
          <a href="projects.php?status=completed"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'completed' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Completed (<?= $counts['completed'] ?>)
          </a>
          <a href="projects.php?status=cancelled"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'cancelled' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Cancelled (<?= $counts['cancelled'] ?>)
          </a>
        </div>

        <div class="flex items-center gap-3">
          <a href="../inquiry.php"
             class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg bg-[#13224B] hover:bg-[#21356f] text-white text-xs font-bold shadow-sm transition-all">
            <iconify-icon icon="lucide:plus" class="text-sm"></iconify-icon>
            <span>Start New Project</span>
          </a>
          <div class="text-xs text-[#8890AA] whitespace-nowrap">
            Showing <strong><?= count($projects) ?></strong> of <strong><?= $totalRows ?></strong> projects
          </div>
        </div>
      </div>

      <!-- Projects Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)] bg-white rounded-2xl shadow-sm">
        <?php if (empty($projects)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:folder-open"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No projects found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterStatus) ? "No projects match the selected status." : 'Your active design projects will appear here once converted and initialized by Kimberly.' ?>
            </p>
            <div class="mt-4">
              <a href="../inquiry.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#4C6CCB] text-white text-xs font-bold shadow hover:bg-[#3d59af]">
                <span>Submit a Project Scope</span>
                <iconify-icon icon="lucide:arrow-right" class="text-xs"></iconify-icon>
              </a>
            </div>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                  <th class="py-3.5 px-4">Project Code &amp; Title</th>
                  <th class="py-3.5 px-4">Category</th>
                  <th class="py-3.5 px-4">Current Phase &amp; Progress</th>
                  <th class="py-3.5 px-4">Budget ($ USD)</th>
                  <th class="py-3.5 px-4">Started</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($projects as $p): 
                    $isCancelled = (($p['status_type'] ?? '') === 'cancelled' || ($p['status'] ?? '') === 'Cancelled');
                    $isDelivered = (($p['status_type'] ?? '') === 'completed' || in_array($p['status'] ?? '', ['Delivered', 'Completed'], true));
                    $isReview    = (($p['status_type'] ?? '') === 'review' || str_contains($p['status'] ?? '', 'Review'));
                    $badgeClass  = 'badge-active';
                    if ($isCancelled) $badgeClass = 'badge-cancelled';
                    elseif ($isDelivered) $badgeClass = 'badge-completed';
                    elseif ($isReview) $badgeClass = 'badge-review';
                    $budgetFormatted = format_usd($p['budget'] ?? '$0');
                ?>
                  <tr class="project-row transition-colors <?= $isCancelled ? 'opacity-70 hover:opacity-100' : '' ?>">
                    <td class="py-4 px-4">
                      <div class="font-mono font-bold text-[11px] text-[#8890AA] mb-0.5">
                        <?= htmlspecialchars($p['project_code'] ?? 'PRJ-' . $p['id'], ENT_QUOTES, 'UTF-8') ?>
                      </div>
                      <a href="project-detail.php?id=<?= (int)$p['id'] ?>" class="font-bold text-[#13224B] text-sm hover:text-[#4C6CCB] transition-colors line-clamp-1">
                        <?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>
                      </a>
                    </td>

                    <td class="py-4 px-4">
                      <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-[#13224B]">
                        <?= htmlspecialchars($p['category'] ?? 'UI/UX Design', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <td class="py-4 px-4">
                      <div class="flex items-center gap-3">
                        <div class="progress-track">
                          <div class="progress-fill" style="width: <?= min(100, max(0, (int)($p['progress'] ?? 0))) ?>%;"></div>
                        </div>
                        <span class="font-extrabold text-[#4C6CCB] font-mono text-xs"><?= (int)($p['progress'] ?? 0) ?>%</span>
                      </div>
                      <div class="text-[10px] text-[#8890AA] mt-1 font-medium">
                        <?= htmlspecialchars($p['phase_name'] ?? 'In Progress', ENT_QUOTES, 'UTF-8') ?>
                      </div>
                    </td>

                    <td class="py-4 px-4 font-extrabold text-[#13224B] whitespace-nowrap">
                      <?= $budgetFormatted ?>
                    </td>

                    <td class="py-4 px-4 text-[#8890AA] whitespace-nowrap">
                      <?= date('M j, Y', strtotime($p['created_at'])) ?>
                    </td>

                    <td class="py-4 px-4">
                      <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $badgeClass ?> uppercase tracking-wider">
                        <?= htmlspecialchars($p['status'] ?? 'Active', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </td>

                    <td class="py-4 px-4 text-right whitespace-nowrap">
                      <a href="project-detail.php?id=<?= (int)$p['id'] ?>"
                         class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[#DDEBFF] hover:bg-[#4C6CCB] text-[#13224B] hover:text-white font-bold text-xs transition-all shadow-sm">
                        <span>Workspace</span>
                        <iconify-icon icon="lucide:arrow-right" class="text-xs"></iconify-icon>
                      </a>
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
                  <a href="projects.php?page=<?= $i ?><?= !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : '' ?>"
                     class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-colors <?= $page === $i ? 'bg-[#13224B] text-white' : 'bg-white text-[#13224B] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
                    <?= $i ?>
                  </a>
                <?php endfor; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Pending Intake Inquiries Section -->
      <?php if (!empty($pendingInquiries)): ?>
        <div class="space-y-4 pt-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-[#13224B] flex items-center gap-2">
                <iconify-icon icon="lucide:clock" class="text-[#6C5BB5]"></iconify-icon>
                <span>Submitted Project Inquiries Awaiting Studio Review (<?= count($pendingInquiries) ?>)</span>
              </h3>
              <p class="text-xs text-[#8890AA] mt-0.5">Requirements you submitted that are currently being qualified by Kimberly.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($pendingInquiries as $inq): ?>
              <div class="card p-5 border border-purple-200/80 bg-purple-50/20 rounded-2xl shadow-sm">
                <div class="flex items-center justify-between gap-2 mb-2">
                  <span class="px-2 py-0.5 rounded bg-[#13224B] font-mono text-[10px] font-bold text-white">
                    <?= htmlspecialchars($inq['ref_code'] ?: ('INQ-' . str_pad((string)$inq['id'], 5, '0', STR_PAD_LEFT))) ?>
                  </span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#FFF1D6] text-[#946200] uppercase tracking-wider">
                    <?= htmlspecialchars($inq['status'] ?? 'New') ?>
                  </span>
                </div>
                <h4 class="text-sm font-bold text-[#13224B] mb-1">
                  <?= htmlspecialchars($inq['service'] ?: 'Custom UI/UX Advisory') ?>
                </h4>
                <p class="text-xs text-[#4b4b4b] line-clamp-2 mb-3 leading-relaxed">
                  <?= htmlspecialchars($inq['description'] ?? 'No scope provided.') ?>
                </p>
                <div class="flex items-center justify-between text-[11px] text-[#8890AA] pt-2 border-t border-[rgba(19,34,75,0.06)]">
                  <span>Budget: <strong class="text-[#13224B]"><?= htmlspecialchars($inq['budget'] ?? 'TBD') ?></strong></span>
                  <span>Submitted <?= time_ago($inq['created_at']) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

  </main>

</body>
</html>
