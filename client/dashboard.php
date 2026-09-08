<?php
/**
 * Client Portal — Dashboard Overview.
 * Scoped strictly to the logged-in client's user_id to prevent IDOR access.
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage     = 'dashboard';
$pageTitle      = 'Client Dashboard — Workspace';
$pageHeading    = 'Client Workspace';
$pageSubheading = 'Track your active projects, deliverables, milestones, and studio messages.';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    safe_redirect('../login.php');
}

// Client name
$rawName   = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Client';
$nameParts = explode(' ', trim($rawName));
$firstName = htmlspecialchars($nameParts[0], ENT_QUOTES, 'UTF-8');

// Stat Cards Queries (Strictly scoped to $userId)
$activeProjectsCount = 0;
$totalFilesCount     = 0;
$messagesCount       = 0;
$projects            = [];
$recentActivities    = [];

try {
    // Active Projects
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = :uid AND status_type != 'completed'");
    $stmt->execute(['uid' => $userId]);
    $activeProjectsCount = (int) $stmt->fetchColumn();

    // Total Files Shared
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM project_files pf
         JOIN projects p ON pf.project_id = p.id
         WHERE p.user_id = :uid'
    );
    $stmt->execute(['uid' => $userId]);
    $totalFilesCount = (int) $stmt->fetchColumn();

    // Designer Messages
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM project_messages pm
         JOIN projects p ON pm.project_id = p.id
         WHERE p.user_id = :uid AND pm.role = 'designer'"
    );
    $stmt->execute(['uid' => $userId]);
    $messagesCount = (int) $stmt->fetchColumn();

    // Your Projects List (Strictly scoped to $userId)
    $stmt = $pdo->prepare(
        'SELECT * FROM projects
         WHERE user_id = :uid
         ORDER BY updated_at DESC'
    );
    $stmt->execute(['uid' => $userId]);
    $projects = $stmt->fetchAll();

    // Recent Activity (Merged Files + Messages)
    $stmtFiles = $pdo->prepare(
        "SELECT 'file' AS act_type, pf.name AS act_text, pf.uploaded_at AS act_time,
                p.id AS project_id, p.title AS project_title, 'New deliverable uploaded' AS act_sub
         FROM project_files pf
         JOIN projects p ON pf.project_id = p.id
         WHERE p.user_id = :uid
         ORDER BY pf.uploaded_at DESC
         LIMIT 6"
    );
    $stmtFiles->execute(['uid' => $userId]);
    $fileActs = $stmtFiles->fetchAll();

    $stmtMsgs = $pdo->prepare(
        "SELECT 'message' AS act_type, pm.message AS act_text, pm.created_at AS act_time,
                p.id AS project_id, p.title AS project_title, CONCAT('Message from ', pm.sender) AS act_sub
         FROM project_messages pm
         JOIN projects p ON pm.project_id = p.id
         WHERE p.user_id = :uid
         ORDER BY pm.created_at DESC
         LIMIT 6"
    );
    $stmtMsgs->execute(['uid' => $userId]);
    $msgActs = $stmtMsgs->fetchAll();

    // Merge and sort newest first
    $recentActivities = array_merge($fileActs, $msgActs);
    usort($recentActivities, function ($a, $b) {
        return strtotime($b['act_time']) <=> strtotime($a['act_time']);
    });
    $recentActivities = array_slice($recentActivities, 0, 6);

} catch (\PDOException $e) {
    error_log('client/dashboard.php error: ' . $e->getMessage());
    set_flash('error', 'Error loading dashboard details.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px -6px rgba(19,34,75,0.09);
        }
        .progress-track {
            background: rgba(19,34,75,0.07);
            border-radius: 99px;
            height: 7px;
            overflow: hidden;
        }
        .progress-bar-fill {
            background: linear-gradient(135deg, #4C6CCB, #6C5BB5);
            height: 100%;
            border-radius: 99px;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body class="min-h-screen">

  <!-- Client Sidebar Shell -->
  <?php require_once __DIR__ . '/../includes/sidebar-client.php'; ?>

  <!-- Main Content Wrapper -->
  <div class="main-content">
    <?php require_once __DIR__ . '/../includes/header-client.php'; ?>

    <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Welcome Banner -->
      <div class="card p-6 sm:p-8 bg-gradient-to-r from-[#13224B] via-[#21356f] to-[#6C5BB5] text-white border-0 shadow-lg relative overflow-hidden">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/15 text-[#DDEBFF]">
              <iconify-icon icon="lucide:sparkles" class="text-amber-300"></iconify-icon>
              Client Advisory Portal
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              Welcome back, <?= $firstName ?>!
            </h2>
            <p class="text-xs sm:text-sm text-[#DDEBFF] max-w-xl leading-relaxed">
              Track the live design deliverables, milestone progress, and consult directly with your assigned Antigo advisory team.
            </p>
          </div>

          <div class="flex items-center gap-3 flex-shrink-0">
            <a href="../inquiry.php"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-full text-xs font-bold text-[#13224B] bg-white hover:bg-[#DDEBFF] shadow-md transition-all">
              <iconify-icon icon="lucide:plus-circle" class="text-base text-[#4C6CCB]"></iconify-icon>
              <span>Start New Project</span>
            </a>
            <a href="../book-consultation.php"
               class="inline-flex items-center gap-2 px-5 py-3 rounded-full text-xs font-bold text-white bg-white/10 hover:bg-white/20 border border-white/20 transition-all">
              <iconify-icon icon="lucide:calendar"></iconify-icon>
              <span>Book Session</span>
            </a>
          </div>
        </div>
      </div>

      <!-- ── Three Stat Cards (Scoped to $userId) ────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Active Projects -->
        <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Projects</span>
            <div class="w-10 h-10 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg shadow-sm">
              <iconify-icon icon="lucide:folder-kanban"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $activeProjectsCount ?></div>
          <p class="text-xs text-[#6C5BB5] font-semibold mt-1">Ongoing studio sprints</p>
        </div>

        <!-- Total Files Shared -->
        <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Files Shared</span>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg shadow-sm">
              <iconify-icon icon="lucide:file-archive"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $totalFilesCount ?></div>
          <p class="text-xs text-[#8890AA] mt-1">Deliverables &amp; Figma assets</p>
        </div>

        <!-- Designer Messages -->
        <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Messages from Studio</span>
            <div class="w-10 h-10 rounded-xl bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-lg shadow-sm">
              <iconify-icon icon="lucide:message-circle"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $messagesCount ?></div>
          <p class="text-xs text-[#127A45] font-semibold mt-1">Direct designer updates</p>
        </div>
      </div>

      <!-- Main Grid: Projects List + Recent Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Left: Your Projects (2 Cols) -->
        <div class="lg:col-span-2 space-y-5" id="projects">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold text-[#13224B]">Your Projects</h3>
              <p class="text-xs text-[#8890AA] mt-0.5">Live overview of your design deliverables and timeline milestones.</p>
            </div>
            <a href="../inquiry.php" class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1">
              <span>+ New Project</span>
            </a>
          </div>

          <?php if (empty($projects)): ?>
            <div class="card p-12 text-center border border-[rgba(19,34,75,0.08)]">
              <div class="w-16 h-16 rounded-2xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-3xl mx-auto mb-4">
                <iconify-icon icon="lucide:folder-plus"></iconify-icon>
              </div>
              <h4 class="text-base font-bold text-[#13224B]">No Active Projects Yet</h4>
              <p class="text-xs text-[#8890AA] mt-1.5 max-w-md mx-auto leading-relaxed">
                Ready to elevate your digital product with expert UI/UX advisory? Submit your project requirements to start collaborating.
              </p>
              <a href="../inquiry.php"
                 class="inline-flex items-center gap-2 mt-5 px-6 py-3 rounded-full text-xs font-bold text-white shadow-md hover:opacity-90 transition-all"
                 style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                <iconify-icon icon="lucide:plus"></iconify-icon>
                <span>Start a Project</span>
              </a>
            </div>
          <?php else: ?>
            <div class="space-y-4">
              <?php foreach ($projects as $p): ?>
                <a href="project-detail.php?id=<?= (int)$p['id'] ?>"
                   class="card p-6 block border border-[rgba(19,34,75,0.08)] project-card transition-all group">
                  <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                    <div>
                      <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 rounded bg-gray-100 font-mono text-[10px] font-bold text-[#8890AA]">
                          <?= htmlspecialchars($p['project_code'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="text-xs text-[#6C5BB5] font-semibold">
                          <?= htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      </div>
                      <h4 class="text-base font-extrabold text-[#13224B] group-hover:text-[#4C6CCB] transition-colors">
                        <?= htmlspecialchars($p['title'], ENT_QUOTES, 'UTF-8') ?>
                      </h4>
                    </div>

                    <div class="flex-shrink-0">
                      <?= status_badge($p['status']) ?>
                    </div>
                  </div>

                  <!-- Milestone Progress Bar -->
                  <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                      <span class="font-bold text-[#13224B] flex items-center gap-1.5">
                        <iconify-icon icon="lucide:milestone" class="text-[#6C5BB5]"></iconify-icon>
                        <?= htmlspecialchars($p['phase_name'] ?? 'In Progress', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                      <span class="font-extrabold text-[#4C6CCB]"><?= (int)$p['progress'] ?>%</span>
                    </div>
                    <div class="progress-track">
                      <div class="progress-bar-fill" style="width: <?= (int)$p['progress'] ?>%;"></div>
                    </div>
                  </div>

                  <!-- Footer Meta -->
                  <div class="flex items-center justify-between text-[11px] text-[#8890AA] pt-4 mt-4 border-t border-[rgba(19,34,75,0.05)]">
                    <div class="flex items-center gap-1">
                      <iconify-icon icon="lucide:calendar" class="text-xs"></iconify-icon>
                      <span>Started <?= time_ago($p['created_at']) ?></span>
                    </div>
                    <div class="flex items-center gap-1 text-[#4C6CCB] font-bold group-hover:underline">
                      <span>View Workspace</span>
                      <iconify-icon icon="lucide:arrow-right" class="text-xs"></iconify-icon>
                    </div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Recent Activity Panel -->
        <div class="space-y-5" id="messages">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold text-[#13224B]">Recent Activity</h3>
              <p class="text-xs text-[#8890AA] mt-0.5">Latest updates across your design projects.</p>
            </div>
          </div>

          <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
            <?php if (empty($recentActivities)): ?>
              <div class="py-8 text-center">
                <iconify-icon icon="lucide:clock" class="text-3xl text-[#8890AA] mb-2 block mx-auto"></iconify-icon>
                <p class="text-xs text-[#8890AA]">No activity recorded yet.</p>
              </div>
            <?php else: ?>
              <div class="space-y-4">
                <?php foreach ($recentActivities as $act): ?>
                  <div class="flex items-start gap-3 text-xs pb-3.5 border-b border-[rgba(19,34,75,0.05)] last:border-0 last:pb-0">
                    <div class="w-8 h-8 rounded-lg flex-shrink-0 flex items-center justify-center <?= $act['act_type'] === 'file' ? 'bg-[#DDEBFF] text-[#4C6CCB]' : 'bg-purple-50 text-[#6C5BB5]' ?>">
                      <iconify-icon icon="<?= $act['act_type'] === 'file' ? 'lucide:file-text' : 'lucide:message-square' ?>"></iconify-icon>
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="text-[10px] text-[#8890AA] font-medium"><?= htmlspecialchars($act['act_sub'], ENT_QUOTES, 'UTF-8') ?></p>
                      <a href="project-detail.php?id=<?= (int)$act['project_id'] ?>"
                         class="font-bold text-[#13224B] hover:text-[#4C6CCB] truncate block">
                        <?= htmlspecialchars($act['act_text'], ENT_QUOTES, 'UTF-8') ?>
                      </a>
                      <p class="text-[10px] text-gray-400 mt-0.5"><?= time_ago($act['act_time']) ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </main>
  </div>

</body>
</html>
