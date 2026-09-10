<?php
/**
 * Client Portal — Dashboard Overview.
 * Scoped strictly to the logged-in client's user_id to prevent IDOR access.
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/routing.php';

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

// POST Handlers: Settings (Profile & Password updates)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid security token. Please refresh and try again.');
        safe_redirect('dashboard.php#settings');
    }

    $action = trim($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $newName    = trim($_POST['name'] ?? '');
        $newCompany = trim($_POST['company'] ?? '');

        if (empty($newName)) {
            set_flash('error', 'Full name cannot be empty.');
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE users SET name = ?, company = ? WHERE id = ?');
                $stmt->execute([$newName, $newCompany ?: null, $userId]);

                $_SESSION['user_name'] = $newName;
                $_SESSION['name']      = $newName;
                set_flash('success', 'Profile updated successfully.');
            } catch (\PDOException $e) {
                error_log('client/dashboard update_profile error: ' . $e->getMessage());
                set_flash('error', 'Database error updating profile.');
            }
        }
        safe_redirect('dashboard.php#settings');
    }

    if ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            set_flash('error', 'All password fields are required.');
        } elseif (strlen($newPass) < 6) {
            set_flash('error', 'New password must be at least 6 characters long.');
        } elseif ($newPass !== $confirmPass) {
            set_flash('error', 'New password and confirmation do not match.');
        } else {
            try {
                $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $userRow = $stmt->fetch();

                if (!$userRow || !password_verify($currentPass, $userRow['password_hash'])) {
                    set_flash('error', 'Current password is incorrect.');
                } else {
                    $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                    $updStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $updStmt->execute([$newHash, $userId]);
                    set_flash('success', 'Password updated successfully.');
                }
            } catch (\PDOException $e) {
                error_log('client/dashboard change_password error: ' . $e->getMessage());
                set_flash('error', 'Database error updating password.');
            }
        }
        safe_redirect('dashboard.php#settings');
    }
}

// Queries (Strictly scoped to $userId to prevent IDOR)
$activeProjectsCount = 0;
$totalFilesCount     = 0;
$messagesCount       = 0;
$projects            = [];
$pendingInquiries    = [];
$clientFiles         = [];
$clientMessages      = [];
$recentActivities    = [];
$currentUserProfile  = ['name' => $rawName, 'email' => $_SESSION['email'] ?? '', 'company' => ''];

try {
    // Current User Details for Settings
    $stmtUser = $pdo->prepare('SELECT name, email, company, created_at FROM users WHERE id = ? LIMIT 1');
    $stmtUser->execute([$userId]);
    $uData = $stmtUser->fetch();
    if ($uData) {
        $currentUserProfile = $uData;
    }

    // Active Projects Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = :uid AND status_type != 'completed'");
    $stmt->execute(['uid' => $userId]);
    $activeProjectsCount = (int) $stmt->fetchColumn();

    // Total Files Shared Count
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM project_files pf
         JOIN projects p ON pf.project_id = p.id
         WHERE p.user_id = :uid'
    );
    $stmt->execute(['uid' => $userId]);
    $totalFilesCount = (int) $stmt->fetchColumn();

    // Designer Messages Count
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM project_messages pm
         JOIN projects p ON pm.project_id = p.id
         WHERE p.user_id = :uid AND pm.role = 'designer'"
    );
    $stmt->execute(['uid' => $userId]);
    $messagesCount = (int) $stmt->fetchColumn();

    // Active Projects List
    $stmt = $pdo->prepare(
        'SELECT * FROM projects
         WHERE user_id = :uid
         ORDER BY updated_at DESC'
    );
    $stmt->execute(['uid' => $userId]);
    $projects = $stmt->fetchAll();

    // Pending Review Inquiries (Live intake list for this client)
    $stmtInq = $pdo->prepare(
        "SELECT * FROM inquiries
         WHERE user_id = :uid AND status != 'converted'
         ORDER BY created_at DESC"
    );
    $stmtInq->execute(['uid' => $userId]);
    $pendingInquiries = $stmtInq->fetchAll();

    // All Client Deliverable Files (Download-only, scoped strictly to $userId)
    $stmtFiles = $pdo->prepare(
        'SELECT pf.*, p.title AS project_title, p.project_code
         FROM project_files pf
         JOIN projects p ON pf.project_id = p.id
         WHERE p.user_id = :uid
         ORDER BY pf.uploaded_at DESC'
    );
    $stmtFiles->execute(['uid' => $userId]);
    $clientFiles = $stmtFiles->fetchAll();

    // All Client Messages (Scoped strictly to $userId)
    $stmtMsgs = $pdo->prepare(
        'SELECT pm.*, p.title AS project_title, p.project_code
         FROM project_messages pm
         JOIN projects p ON pm.project_id = p.id
         WHERE p.user_id = :uid
         ORDER BY pm.created_at DESC
         LIMIT 15'
    );
    $stmtMsgs->execute(['uid' => $userId]);
    $clientMessages = $stmtMsgs->fetchAll();

    // Recent Activity Feed (Merged Files + Messages)
    $fileActs = array_map(function ($f) {
        return [
            'act_type'      => 'file',
            'act_text'      => $f['name'],
            'act_time'      => $f['uploaded_at'],
            'project_id'    => $f['project_id'],
            'project_title' => $f['project_title'],
            'act_sub'       => 'New deliverable uploaded',
        ];
    }, array_slice($clientFiles, 0, 6));

    $msgActs = array_map(function ($m) {
        return [
            'act_type'      => 'message',
            'act_text'      => $m['message'],
            'act_time'      => $m['created_at'],
            'project_id'    => $m['project_id'],
            'project_title' => $m['project_title'],
            'act_sub'       => 'Message from ' . $m['sender'],
        ];
    }, array_slice($clientMessages, 0, 6));

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

    <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

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
              Track live design deliverables, milestone progress, and consult directly with your assigned Antigo advisory team.
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

        <!-- Left: Projects and Pending Inquiries (2 Cols) -->
        <div class="lg:col-span-2 space-y-6" id="projects">
          
          <!-- Section Heading -->
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold text-[#13224B]">My Projects</h3>
              <p class="text-xs text-[#8890AA] mt-0.5">Live overview of your design deliverables and timeline milestones.</p>
            </div>
            <a href="../inquiry.php" class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1">
              <span>+ New Project</span>
            </a>
          </div>

          <!-- Pending Review Inquiries (Live intake created by this client) -->
          <?php if (!empty($pendingInquiries)): ?>
            <div class="space-y-3">
              <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-[#6C5BB5]">
                <iconify-icon icon="lucide:clock" class="text-sm"></iconify-icon>
                <span>Pending Review Inquiries (<?= count($pendingInquiries) ?>)</span>
              </div>
              <?php foreach ($pendingInquiries as $inq): ?>
                <div class="card p-5 border border-purple-200/80 bg-purple-50/30 rounded-2xl">
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                      <span class="px-2 py-0.5 rounded bg-[#13224B] font-mono text-[10px] font-bold text-white">
                        <?= htmlspecialchars($inq['ref_code'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                      <h4 class="text-sm font-bold text-[#13224B]">
                        <?= htmlspecialchars($inq['service'] ?: $inq['project_type'] ?: 'Project Inquiry', ENT_QUOTES, 'UTF-8') ?>
                      </h4>
                    </div>
                    <div>
                      <?= status_badge($inq['status']) ?>
                    </div>
                  </div>
                  <p class="text-xs text-[#4b4b4b] line-clamp-2 mb-3 leading-relaxed">
                    <?= htmlspecialchars($inq['description'], ENT_QUOTES, 'UTF-8') ?>
                  </p>
                  <div class="flex flex-wrap items-center justify-between gap-3 text-[11px] text-[#8890AA] pt-2 border-t border-[rgba(19,34,75,0.06)]">
                    <div class="flex items-center gap-4">
                      <span>Budget: <strong class="text-[#13224B]"><?= htmlspecialchars($inq['budget'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                      <span>Timeline: <strong class="text-[#13224B]"><?= htmlspecialchars($inq['timeline'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                    </div>
                    <span>Submitted <?= time_ago($inq['created_at']) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- Active Projects List -->
          <?php if (empty($projects)): ?>
            <?php if (empty($pendingInquiries)): ?>
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
            <?php endif; ?>
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

        <!-- Recent Activity Feed (Right Column) -->
        <div class="space-y-5">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-bold text-[#13224B]">Recent Activity</h3>
              <p class="text-xs text-[#8890AA] mt-0.5">Latest updates across your workspaces.</p>
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

      <!-- ── Messages Section (#messages) ─────────────────────────────────── -->
      <div class="space-y-5 pt-4" id="messages">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-[#13224B]">Studio Messages</h3>
            <p class="text-xs text-[#8890AA] mt-0.5">Real-time collaboration and feedback from your Antigo design team.</p>
          </div>
        </div>

        <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
          <?php if (empty($clientMessages)): ?>
            <div class="py-10 text-center">
              <div class="w-12 h-12 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-2xl mx-auto mb-3">
                <iconify-icon icon="lucide:message-square"></iconify-icon>
              </div>
              <h4 class="text-sm font-bold text-[#13224B]">No messages yet</h4>
              <p class="text-xs text-[#8890AA] mt-1">Open an active project workspace to send a message to your lead designer.</p>
            </div>
          <?php else: ?>
            <div class="divide-y divide-[rgba(19,34,75,0.06)]">
              <?php foreach ($clientMessages as $msg): ?>
                <div class="py-4 flex flex-col sm:flex-row sm:items-start justify-between gap-4 first:pt-0 last:pb-0">
                  <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 <?= $msg['role'] === 'designer' ? 'bg-[#13224B] text-white' : 'bg-[#DDEBFF] text-[#4C6CCB]' ?>">
                      <?= strtoupper(substr($msg['sender'], 0, 2)) ?>
                    </div>
                    <div>
                      <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-bold text-[#13224B]"><?= htmlspecialchars($msg['sender'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?= $msg['role'] === 'designer' ? 'bg-purple-100 text-[#6C5BB5]' : 'bg-blue-50 text-[#4C6CCB]' ?>">
                          <?= $msg['role'] === 'designer' ? 'Studio Lead' : 'You' ?>
                        </span>
                        <span class="text-[11px] text-[#8890AA]"><?= time_ago($msg['created_at']) ?></span>
                      </div>
                      <p class="text-xs text-[#4b4b4b] leading-relaxed max-w-2xl">
                        <?= nl2br(htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8')) ?>
                      </p>
                      <span class="inline-block mt-1.5 text-[11px] text-[#8890AA] font-mono">
                        Project: <?= htmlspecialchars($msg['project_code'] . ' — ' . $msg['project_title'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </div>
                  </div>
                  <a href="project-detail.php?id=<?= (int)$msg['project_id'] ?>#messages"
                     class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1 flex-shrink-0 self-start sm:self-auto">
                    <span>Reply in Project</span>
                    <iconify-icon icon="lucide:arrow-right"></iconify-icon>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Files Section (#files) ───────────────────────────────────────── -->
      <div class="space-y-5 pt-4" id="files">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-bold text-[#13224B]">Deliverables &amp; Files</h3>
            <p class="text-xs text-[#8890AA] mt-0.5">Secure client deliverables, specs, Figma files, and research audits.</p>
          </div>
        </div>

        <div class="card border border-[rgba(19,34,75,0.08)] overflow-hidden">
          <?php if (empty($clientFiles)): ?>
            <div class="p-10 text-center">
              <div class="w-12 h-12 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mx-auto mb-3">
                <iconify-icon icon="lucide:file-text"></iconify-icon>
              </div>
              <h4 class="text-sm font-bold text-[#13224B]">No deliverables shared yet</h4>
              <p class="text-xs text-[#8890AA] mt-1">Design assets and specification documents uploaded by Kimberly will appear here.</p>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-[rgba(19,34,75,0.08)] text-[#8890AA] uppercase tracking-wider font-bold bg-[#F4F6F8]/50">
                    <th class="py-3.5 px-6">File Name</th>
                    <th class="py-3.5 px-4">Associated Project</th>
                    <th class="py-3.5 px-4">File Size</th>
                    <th class="py-3.5 px-4">Uploaded</th>
                    <th class="py-3.5 px-6 text-right">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(19,34,75,0.06)]">
                  <?php foreach ($clientFiles as $file): ?>
                    <tr class="hover:bg-[#F4F6F8]/40 transition-colors">
                      <td class="py-4 px-6 font-bold text-[#13224B]">
                        <div class="flex items-center gap-2.5">
                          <iconify-icon icon="lucide:file" class="text-base text-[#4C6CCB]"></iconify-icon>
                          <span><?= htmlspecialchars($file['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                      </td>
                      <td class="py-4 px-4 text-[#6C5BB5] font-semibold">
                        <?= htmlspecialchars($file['project_code'] . ' · ' . $file['project_title'], ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td class="py-4 px-4 text-[#8890AA] font-mono text-[11px]">
                        <?= htmlspecialchars($file['size'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td class="py-4 px-4 text-[#8890AA]">
                        <?= time_ago($file['uploaded_at']) ?>
                      </td>
                      <td class="py-4 px-6 text-right">
                        <a href="../download.php?file_id=<?= (int)$file['id'] ?>"
                           class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-xs font-bold text-[#4C6CCB] bg-[#DDEBFF] hover:bg-[#4C6CCB] hover:text-white transition-all shadow-sm">
                          <iconify-icon icon="lucide:download"></iconify-icon>
                          <span>Download</span>
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Settings Section (#settings) ─────────────────────────────────── -->
      <div class="space-y-5 pt-4" id="settings">
        <div>
          <h3 class="text-lg font-bold text-[#13224B]">Account Settings</h3>
          <p class="text-xs text-[#8890AA] mt-0.5">Manage your client profile details and security credentials.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          <!-- Profile Information Form -->
          <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg">
                <iconify-icon icon="lucide:user-check"></iconify-icon>
              </div>
              <div>
                <h4 class="text-sm font-bold text-[#13224B]">Profile Information</h4>
                <p class="text-[11px] text-[#8890AA]">Update your contact name and company details.</p>
              </div>
            </div>

            <form method="POST" action="dashboard.php" class="space-y-4">
              <input type="hidden" name="action" value="update_profile">
              <?= csrf_input() ?>

              <div>
                <label for="prof_name" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Full Name</label>
                <input type="text" id="prof_name" name="name" required
                       value="<?= htmlspecialchars($currentUserProfile['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
              </div>

              <div>
                <label for="prof_email" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Email Address</label>
                <input type="email" id="prof_email" disabled
                       value="<?= htmlspecialchars($currentUserProfile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.08)] bg-gray-100 text-xs font-semibold text-gray-500 cursor-not-allowed">
                <p class="text-[10px] text-[#8890AA] mt-1">Contact studio support if you need to transfer this account to a new email.</p>
              </div>

              <div>
                <label for="prof_company" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Company / Organization</label>
                <input type="text" id="prof_company" name="company"
                       value="<?= htmlspecialchars($currentUserProfile['company'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
              </div>

              <div class="pt-2">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-md hover:opacity-95 transition-all"
                        style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                  Save Profile Changes
                </button>
              </div>
            </form>
          </div>

          <!-- Change Password Form -->
          <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg">
                <iconify-icon icon="lucide:lock"></iconify-icon>
              </div>
              <div>
                <h4 class="text-sm font-bold text-[#13224B]">Change Password</h4>
                <p class="text-[11px] text-[#8890AA]">Ensure your client portal account remains securely protected.</p>
              </div>
            </div>

            <form method="POST" action="dashboard.php" class="space-y-4">
              <input type="hidden" name="action" value="change_password">
              <?= csrf_input() ?>

              <div>
                <label for="cur_pass" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Current Password</label>
                <input type="password" id="cur_pass" name="current_password" required placeholder="••••••••"
                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
              </div>

              <div>
                <label for="new_pass" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">New Password</label>
                <input type="password" id="new_pass" name="new_password" required minlength="6" placeholder="At least 6 characters"
                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
              </div>

              <div>
                <label for="conf_pass" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Confirm New Password</label>
                <input type="password" id="conf_pass" name="confirm_password" required minlength="6" placeholder="Repeat new password"
                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
              </div>

              <div class="pt-2">
                <button type="submit"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold text-[#13224B] bg-white border border-[rgba(19,34,75,0.15)] shadow-sm hover:bg-[#F4F6F8] transition-all">
                  Update Password
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>

    </main>
  </div>

</body>
</html>
