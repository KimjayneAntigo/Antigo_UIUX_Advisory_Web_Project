<?php
/**
 * Registered Clients Directory Hub.
 * Dedicated standalone page for managing client accounts, inspecting active projects, and communication.
 */

require_once __DIR__ . '/../includes/auth-check-admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'clients';
$pageTitle      = 'Clients Directory — Admin';
$pageHeading    = 'Registered Clients Directory';
$pageSubheading = 'Directory of verified client accounts, associated design projects, and collaboration history.';

// Filter & Pagination
$filterStatus = trim($_GET['filter'] ?? '');
$search       = trim($_GET['q'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

$clients   = [];
$totalRows = 0;

// Count queries for filter tabs
$counts = ['all' => 0, 'with_projects' => 0, 'no_projects' => 0];
try {
    $counts['all'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

    $counts['with_projects'] = (int) $pdo->query(
        "SELECT COUNT(DISTINCT u.id) 
         FROM users u 
         JOIN projects p ON u.id = p.user_id 
         WHERE u.role = 'client' AND (p.status_type != 'cancelled' OR p.status_type IS NULL)"
    )->fetchColumn();

    $counts['no_projects'] = max(0, $counts['all'] - $counts['with_projects']);

    $havingClauses = [];
    $whereClauses  = ["u.role = 'client'"];
    $params        = [];

    if (!empty($search)) {
        $whereClauses[] = '(u.name LIKE ? OR u.email LIKE ? OR u.company LIKE ?)';
        $searchParam    = '%' . $search . '%';
        $params[]       = $searchParam;
        $params[]       = $searchParam;
        $params[]       = $searchParam;
    }

    if ($filterStatus === 'with_projects') {
        $havingClauses[] = 'COUNT(p.id) > 0';
    } elseif ($filterStatus === 'no_projects') {
        $havingClauses[] = 'COUNT(p.id) = 0';
    }

    $whereSql  = 'WHERE ' . implode(' AND ', $whereClauses);
    $havingSql = !empty($havingClauses) ? 'HAVING ' . implode(' AND ', $havingClauses) : '';

    // Total rows for pagination
    $cSql = "SELECT u.id, COUNT(p.id) AS project_count 
             FROM users u 
             LEFT JOIN projects p ON u.id = p.user_id 
             {$whereSql} 
             GROUP BY u.id 
             {$havingSql}";
    $cStmt = $pdo->prepare($cSql);
    $cStmt->execute($params);
    $totalRows = count($cStmt->fetchAll());

    // Fetch clients
    $sql = "SELECT u.*, 
                   COUNT(p.id) AS project_count,
                   COUNT(CASE WHEN p.status_type NOT IN ('completed', 'cancelled') THEN 1 END) AS active_project_count
            FROM users u 
            LEFT JOIN projects p ON u.id = p.user_id 
            {$whereSql} 
            GROUP BY u.id 
            {$havingSql}
            ORDER BY u.created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $paramIndex = 1;
    foreach ($params as $p) {
        $stmt->bindValue($paramIndex++, $p, PDO::PARAM_STR);
    }
    $stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
    $stmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clients = $stmt->fetchAll();

} catch (\PDOException $e) {
    error_log('admin/clients.php fetch error: ' . $e->getMessage());
    set_flash('error', 'Error loading clients directory.');
}

$totalPages = max(1, (int) ceil($totalRows / $perPage));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .client-row:hover { background-color: rgba(76,108,203,0.03); }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8]">

  <!-- Top Horizontal Navbar -->
  <?php 
    $activePage = 'clients';
    require_once __DIR__ . '/../includes/header-admin.php'; 
  ?>

  <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Filter Tabs & Stats Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
          <a href="clients.php"
             class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition-all <?= empty($filterStatus) ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            All Clients (<?= $counts['all'] ?>)
          </a>
          <a href="clients.php?filter=with_projects"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'with_projects' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Active Clients (<?= $counts['with_projects'] ?>)
          </a>
          <a href="clients.php?filter=no_projects"
             class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all <?= $filterStatus === 'no_projects' ? 'bg-[#13224B] text-white shadow-sm' : 'bg-white text-[#4b4b4b] border border-[rgba(19,34,75,0.08)] hover:bg-[#F4F6F8]' ?>">
            Inquiry Leads (<?= $counts['no_projects'] ?>)
          </a>
        </div>

        <div class="flex items-center gap-3">
          <form method="GET" action="clients.php" class="relative">
            <?php if (!empty($filterStatus)): ?>
              <input type="hidden" name="filter" value="<?= htmlspecialchars($filterStatus, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search clients..."
                   class="pl-8 pr-3 py-1.5 text-xs rounded-lg border border-[rgba(19,34,75,0.12)] bg-white text-[#13224B] focus:outline-none focus:border-[#4C6CCB] w-48 sm:w-60">
            <iconify-icon icon="lucide:search" class="absolute left-2.5 top-2 text-[#8890AA] text-xs"></iconify-icon>
          </form>
          <div class="text-xs text-[#8890AA] whitespace-nowrap">
            Showing <strong><?= count($clients) ?></strong> of <strong><?= $totalRows ?></strong> clients
          </div>
        </div>
      </div>

      <!-- Clients Table Card -->
      <div class="card p-0 overflow-hidden border border-[rgba(19,34,75,0.08)] bg-white rounded-2xl shadow-sm">
        <?php if (empty($clients)): ?>
          <div class="p-12 text-center">
            <div class="w-14 h-14 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
              <iconify-icon icon="lucide:users"></iconify-icon>
            </div>
            <h3 class="text-base font-bold text-[#13224B]">No clients found</h3>
            <p class="text-xs text-[#8890AA] mt-1 max-w-sm mx-auto">
              <?= !empty($filterStatus) || !empty($search) ? "No client accounts match your current filters." : 'Registered client accounts will appear here automatically upon client portal signup.' ?>
            </p>
            <?php if (!empty($filterStatus) || !empty($search)): ?>
              <a href="clients.php" class="inline-block mt-4 text-xs font-bold text-[#4C6CCB] hover:underline">
                Reset filters
              </a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-[rgba(19,34,75,0.08)] bg-[#F4F6F8]/70 text-[#8890AA] uppercase tracking-wider font-bold">
                  <th class="py-3.5 px-4">Client Name</th>
                  <th class="py-3.5 px-4">Contact Email</th>
                  <th class="py-3.5 px-4">Company / Organization</th>
                  <th class="py-3.5 px-4">Projects</th>
                  <th class="py-3.5 px-4">Member Since</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($clients as $cl): 
                    $initial = strtoupper(substr($cl['name'] ?? 'C', 0, 1));
                    $projCount = (int)($cl['project_count'] ?? 0);
                    $actCount = (int)($cl['active_project_count'] ?? 0);
                ?>
                  <tr class="client-row transition-colors">
                    <td class="py-4 px-4 font-bold text-[#13224B]">
                      <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-[#13224B] to-[#4C6CCB] text-white font-bold flex items-center justify-center text-xs shadow-sm">
                          <?= $initial ?>
                        </div>
                        <div>
                          <div class="font-bold text-[#13224B] text-sm"><?= htmlspecialchars($cl['name'], ENT_QUOTES, 'UTF-8') ?></div>
                          <span class="text-[10px] text-[#8890AA] font-mono">UID-<?= str_pad((string)$cl['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                      </div>
                    </td>

                    <td class="py-4 px-4 text-[#13224B] font-medium">
                      <?= htmlspecialchars($cl['email'], ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td class="py-4 px-4 text-[#6C5BB5] font-semibold">
                      <?= htmlspecialchars($cl['company'] ?: 'Independent / Personal', ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td class="py-4 px-4">
                      <?php if ($projCount > 0): ?>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#DDEBFF] text-[#13224B]">
                          <iconify-icon icon="lucide:folder-check" class="text-[#4C6CCB]"></iconify-icon>
                          <span><?= $projCount ?> <?= $projCount === 1 ? 'Project' : 'Projects' ?></span>
                        </span>
                        <?php if ($actCount > 0): ?>
                          <span class="text-[10px] text-emerald-600 font-bold block mt-0.5"><?= $actCount ?> active sprint</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-[11px] text-[#8890AA] italic">No active projects</span>
                      <?php endif; ?>
                    </td>

                    <td class="py-4 px-4 text-[#8890AA]">
                      <?= date('M j, Y', strtotime($cl['created_at'])) ?>
                    </td>

                    <td class="py-4 px-4 text-right whitespace-nowrap space-x-1">
                      <a href="mailto:<?= htmlspecialchars($cl['email'], ENT_QUOTES, 'UTF-8') ?>"
                         class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-gray-100 transition-colors"
                         title="Email client directly">
                        <iconify-icon icon="lucide:mail" class="text-xs text-[#4C6CCB]"></iconify-icon>
                        <span>Email</span>
                      </a>
                      <?php if ($projCount > 0): ?>
                        <a href="projects.php?q=<?= urlencode($cl['name']) ?>"
                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-[#DDEBFF] text-[#13224B] text-xs font-bold hover:bg-[#c9ddff] transition-all"
                           title="View client projects">
                          <span>Projects &rarr;</span>
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
                  <a href="clients.php?page=<?= $i ?><?= !empty($filterStatus) ? '&filter=' . urlencode($filterStatus) : '' ?><?= !empty($search) ? '&q=' . urlencode($search) : '' ?>"
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
