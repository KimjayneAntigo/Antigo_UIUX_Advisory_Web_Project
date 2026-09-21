<?php
/**
 * Client Deliverables & Design Files Hub.
 * Dedicated standalone page for client to browse and download Figma files, specs, and design audits.
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'files';
$pageTitle      = 'Deliverables & Files — Client Portal';
$pageHeading    = 'Deliverables & Files';
$pageSubheading = 'Access production design assets, interactive Figma files, design tokens, and research reports.';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    safe_redirect('../login.php');
}

// Filter & Pagination
$filterType = trim($_GET['type'] ?? '');
$search     = trim($_GET['q'] ?? '');
$page       = max(1, (int) ($_GET['page'] ?? 1));
$perPage    = 10;
$offset     = ($page - 1) * $perPage;

$clientFiles = [];
$totalRows   = 0;

// Count queries for filter tabs
$counts = ['all' => 0, 'figma' => 0, 'documents' => 0, 'images' => 0];
try {
    $cAllStmt = $pdo->prepare('SELECT COUNT(*) FROM project_files pf JOIN projects p ON pf.project_id = p.id WHERE p.user_id = ?');
    $cAllStmt->execute([$userId]);
    $counts['all'] = (int) $cAllStmt->fetchColumn();

    $cFigStmt = $pdo->prepare("SELECT COUNT(*) FROM project_files pf JOIN projects p ON pf.project_id = p.id WHERE p.user_id = ? AND (pf.name LIKE '%.fig%' OR pf.name LIKE '%.sketch%')");
    $cFigStmt->execute([$userId]);
    $counts['figma'] = (int) $cFigStmt->fetchColumn();

    $cDocStmt = $pdo->prepare("SELECT COUNT(*) FROM project_files pf JOIN projects p ON pf.project_id = p.id WHERE p.user_id = ? AND (pf.name LIKE '%.pdf%' OR pf.name LIKE '%.doc%' OR pf.name LIKE '%.txt%')");
    $cDocStmt->execute([$userId]);
    $counts['documents'] = (int) $cDocStmt->fetchColumn();

    $cImgStmt = $pdo->prepare("SELECT COUNT(*) FROM project_files pf JOIN projects p ON pf.project_id = p.id WHERE p.user_id = ? AND (pf.name LIKE '%.png%' OR pf.name LIKE '%.jpg%' OR pf.name LIKE '%.jpeg%' OR pf.name LIKE '%.svg%')");
    $cImgStmt->execute([$userId]);
    $counts['images'] = (int) $cImgStmt->fetchColumn();

    $whereClauses = ['p.user_id = ?'];
    $params       = [$userId];

    if (!empty($filterType)) {
        if ($filterType === 'figma') {
            $whereClauses[] = "(pf.name LIKE '%.fig%' OR pf.name LIKE '%.sketch%')";
        } elseif ($filterType === 'documents') {
            $whereClauses[] = "(pf.name LIKE '%.pdf%' OR pf.name LIKE '%.doc%' OR pf.name LIKE '%.txt%')";
        } elseif ($filterType === 'images') {
            $whereClauses[] = "(pf.name LIKE '%.png%' OR pf.name LIKE '%.jpg%' OR pf.name LIKE '%.jpeg%' OR pf.name LIKE '%.svg%')";
        }
    }

    if (!empty($search)) {
        $whereClauses[] = '(pf.name LIKE ? OR p.title LIKE ? OR p.project_code LIKE ?)';
        $searchParam    = '%' . $search . '%';
        $params[]       = $searchParam;
        $params[]       = $searchParam;
        $params[]       = $searchParam;
    }

    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM project_files pf JOIN projects p ON pf.project_id = p.id {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int) $countStmt->fetchColumn();

    $sql = "SELECT pf.*, p.title AS project_title, p.project_code 
            FROM project_files pf 
            JOIN projects p ON pf.project_id = p.id 
            {$whereSql} 
            ORDER BY pf.uploaded_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $paramIndex = 1;
    foreach ($params as $p) {
        $stmt->bindValue($paramIndex++, $p, PDO::PARAM_STR);
    }
    $stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clientFiles = $stmt->fetchAll();

} catch (\PDOException $e) {
    error_log('client/files.php error: ' . $e->getMessage());
    set_flash('error', 'Error loading deliverables.');
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .file-row:hover { background-color: rgba(76,108,203,0.03); }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8]">

  <!-- Top Horizontal Navbar -->
  <?php 
    $activePage = 'files';
    require_once __DIR__ . '/../includes/header-client.php'; 
  ?>

  <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Filter Tabs & Stats Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="files.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterType) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All Files (<?= $counts['all'] ?>)
          </a>
          <a href="files.php?type=figma"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterType === 'figma' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Figma &amp; Designs (<?= $counts['figma'] ?>)
          </a>
          <a href="files.php?type=documents"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterType === 'documents' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Documents &amp; PDF (<?= $counts['documents'] ?>)
          </a>
          <a href="files.php?type=images"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterType === 'images' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Assets &amp; Images (<?= $counts['images'] ?>)
          </a>
        </div>

        <div class="flex items-center gap-3">
          <form method="GET" action="files.php" class="relative">
            <?php if (!empty($filterType)): ?>
              <input type="hidden" name="type" value="<?= htmlspecialchars($filterType, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search file name or project..."
                   class="pl-8 pr-3 py-1.5 text-xs rounded-lg border border-[rgba(19,34,75,0.12)] bg-white text-[#13224B] focus:outline-none focus:border-[#4C6CCB] w-52 sm:w-64">
            <iconify-icon icon="lucide:search" class="absolute left-2.5 top-2 text-[#8890AA] text-xs"></iconify-icon>
          </form>
          <div class="text-xs text-[#8890AA] whitespace-nowrap">
            Showing <strong><?= count($clientFiles) ?></strong> of <strong><?= $totalRows ?></strong> files
          </div>
        </div>
      </div>

      <!-- Deliverables Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)] bg-white rounded-2xl shadow-sm">
        <?php if (empty($clientFiles)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:file-text"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No deliverables found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterType) || !empty($search) ? "No files match your current filter." : 'Project deliverables, wireframes, and design specs shared by Kimberly will appear here.' ?>
            </p>
            <?php if (!empty($filterType) || !empty($search)): ?>
              <a href="files.php" class="inline-block mt-4 text-xs font-bold text-[#4C6CCB] hover:underline">
                Reset filters
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                  <th class="py-3.5 px-6">File Name</th>
                  <th class="py-3.5 px-4">Associated Project</th>
                  <th class="py-3.5 px-4">Size</th>
                  <th class="py-3.5 px-4">Uploaded</th>
                  <th class="py-3.5 px-6 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($clientFiles as $f): 
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $icon = 'lucide:file';
                    $iconColor = 'text-[#4C6CCB]';
                    if (in_array($ext, ['fig', 'sketch'], true)) {
                        $icon = 'lucide:figma';
                        $iconColor = 'text-purple-600';
                    } elseif ($ext === 'pdf') {
                        $icon = 'lucide:file-text';
                        $iconColor = 'text-rose-500';
                    } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'svg'], true)) {
                        $icon = 'lucide:image';
                        $iconColor = 'text-emerald-500';
                    } elseif (in_array($ext, ['zip', 'rar'], true)) {
                        $icon = 'lucide:archive';
                        $iconColor = 'text-amber-500';
                    }
                ?>
                  <tr class="file-row transition-colors">
                    <td class="py-4 px-6 font-bold text-[#13224B]">
                      <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-[#F4F6F8] flex items-center justify-center text-lg <?= $iconColor ?> shadow-sm">
                          <iconify-icon icon="<?= $icon ?>"></iconify-icon>
                        </div>
                        <div>
                          <div class="font-bold text-[#13224B] text-sm"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></div>
                          <span class="text-[10px] text-[#8890AA] uppercase font-mono tracking-wider"><?= htmlspecialchars($ext, ENT_QUOTES, 'UTF-8') ?> file</span>
                        </div>
                      </div>
                    </td>

                    <td class="py-4 px-4">
                      <a href="project-detail.php?id=<?= (int)$f['project_id'] ?>" class="font-semibold text-[#6C5BB5] hover:text-[#4C6CCB] transition-colors">
                        <?= htmlspecialchars($f['project_code'] . ' · ' . $f['project_title'], ENT_QUOTES, 'UTF-8') ?>
                      </a>
                    </td>

                    <td class="py-4 px-4 text-[#8890AA] font-mono text-[11px]">
                      <?= htmlspecialchars($f['size'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td class="py-4 px-4 text-[#8890AA]">
                      <?= time_ago($f['uploaded_at']) ?>
                    </td>

                    <td class="py-4 px-6 text-right whitespace-nowrap space-x-2">
                      <a href="../download.php?file_id=<?= (int)$f['id'] ?>"
                         class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-[#13224B] bg-[#DDEBFF] hover:bg-[#4C6CCB] hover:text-white transition-all shadow-sm">
                        <iconify-icon icon="lucide:download"></iconify-icon>
                        <span>Download</span>
                      </a>
                      <a href="project-detail.php?id=<?= (int)$f['project_id'] ?>#files"
                         class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4b4b4b] hover:bg-gray-100 transition-colors">
                        <span>Workspace &rarr;</span>
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
                  <a href="files.php?page=<?= $i ?><?= !empty($filterType) ? '&type=' . urlencode($filterType) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
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
