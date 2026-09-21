<?php
/**
 * Client Portal Dashboard & Executive Overview.
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/routing.php';

$activePage     = 'dashboard';
$pageTitle      = 'Client Dashboard — Workspace | Antigo Advisory';
$pageHeading    = 'Client Workspace';
$pageSubheading = 'Track your active projects, deliverables, milestones, and studio messages.';

$userId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    safe_redirect('../login.php');
}

// Client name
$rawName     = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Client';
$nameParts   = explode(' ', trim($rawName));
$firstName   = htmlspecialchars($nameParts[0], ENT_QUOTES, 'UTF-8');
$clientEmail = trim($_SESSION['email'] ?? '');

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
$upcomingBookings    = 0;
$activeProject       = null;
$invoicedProjects    = [];
$recentFiles         = [];
$nextBooking         = null;
$currentUserProfile  = ['name' => $rawName, 'email' => $clientEmail, 'company' => ''];

try {
    // Current User Profile for Settings
    $stmtUser = $pdo->prepare('SELECT name, email, company, created_at FROM users WHERE id = ? LIMIT 1');
    $stmtUser->execute([$userId]);
    $uData = $stmtUser->fetch();
    if ($uData) {
        $currentUserProfile = $uData;
    }

    // Active Projects Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE user_id = :uid AND status_type NOT IN ('completed', 'cancelled')");
    $stmt->execute(['uid' => $userId]);
    $activeProjectsCount = (int) $stmt->fetchColumn();

    // Total Files Count
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM project_files pf JOIN projects p ON pf.project_id = p.id WHERE p.user_id = :uid');
    $stmt->execute(['uid' => $userId]);
    $totalFilesCount = (int) $stmt->fetchColumn();

    // Designer Messages Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_messages pm JOIN projects p ON pm.project_id = p.id WHERE p.user_id = :uid AND pm.role = 'designer'");
    $stmt->execute(['uid' => $userId]);
    $messagesCount = (int) $stmt->fetchColumn();

    // Scheduled Consultations Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE (user_id = ? OR client_email = ? OR guest_email = ?) AND status != 'cancelled'");
    $stmt->execute([$userId, $clientEmail, $clientEmail]);
    $upcomingBookings = (int) $stmt->fetchColumn();

    // Featured Active Project (Top active sprint)
    $stmtAct = $pdo->prepare("SELECT * FROM projects WHERE user_id = :uid AND status_type NOT IN ('completed', 'cancelled') ORDER BY updated_at DESC LIMIT 1");
    $stmtAct->execute(['uid' => $userId]);
    $activeProject = $stmtAct->fetch();

    // Invoiced Projects Awaiting Payment
    $stmtInvoiced = $pdo->prepare(
        "SELECT p.id, p.title, p.project_code, p.invoice_sent_at
         FROM projects p
         LEFT JOIN payments pay ON pay.project_id = p.id AND pay.status = 'verified'
         WHERE p.user_id = :uid
           AND p.status_type = 'completed'
           AND p.invoice_sent_at IS NOT NULL
           AND pay.id IS NULL
         ORDER BY p.invoice_sent_at DESC"
    );
    $stmtInvoiced->execute(['uid' => $userId]);
    $invoicedProjects = $stmtInvoiced->fetchAll();

    // Latest 4 Deliverables
    $stmtFiles = $pdo->prepare(
        'SELECT pf.*, p.title AS project_title, p.project_code
         FROM project_files pf
         JOIN projects p ON pf.project_id = p.id
         WHERE p.user_id = :uid
         ORDER BY pf.uploaded_at DESC
         LIMIT 4'
    );
    $stmtFiles->execute(['uid' => $userId]);
    $recentFiles = $stmtFiles->fetchAll();

    // Next Scheduled Consultation
    $stmtBkg = $pdo->prepare(
        "SELECT * FROM bookings 
         WHERE (user_id = ? OR client_email = ? OR guest_email = ?) 
           AND status IN ('pending', 'confirmed') 
         ORDER BY date ASC, time ASC 
         LIMIT 1"
    );
    $stmtBkg->execute([$userId, $clientEmail, $clientEmail]);
    $nextBooking = $stmtBkg->fetch();

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
        .dashboard-card {
            background: #FFFFFF;
            border: 1px solid rgba(19, 34, 75, 0.08);
            border-radius: 20px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }
    </style>
</head>
<body class="min-h-screen bg-[#F4F6F8]">

  <!-- Top Horizontal Navbar -->
  <?php $activePage = 'dashboard'; require_once __DIR__ . '/../includes/header-client.php'; ?>

  <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- Invoiced Projects Alert Banners -->
      <?php if (!empty($invoicedProjects)): ?>
        <div class="space-y-3">
          <?php foreach ($invoicedProjects as $invProj): ?>
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-[#13224B] via-[#21356f] to-[#4C6CCB] border border-blue-400/30 text-white shadow-md flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-amber-400/20 border border-amber-300/30 flex items-center justify-center text-amber-300 flex-shrink-0 text-xl">
                  <iconify-icon icon="lucide:receipt"></iconify-icon>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-amber-400/20 text-amber-300 font-mono">Invoice Ready</span>
                    <span class="text-xs text-blue-200 font-mono"><?= htmlspecialchars($invProj['project_code'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <p class="text-sm font-bold text-white mt-1">
                    Your project '<?= htmlspecialchars($invProj['title'], ENT_QUOTES, 'UTF-8') ?>' is complete — invoice ready.
                  </p>
                </div>
              </div>
              <a href="project-detail.php?id=<?= (int)$invProj['id'] ?>#paymentCardContainer"
                 class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-[#13224B] font-extrabold text-xs shadow transition-all flex-shrink-0">
                <span>Pay Now</span>
                <iconify-icon icon="lucide:arrow-right" class="text-sm"></iconify-icon>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Welcome Hero Banner -->
      <div class="card p-6 sm:p-8 bg-gradient-to-r from-[#13224B] via-[#21356f] to-[#6C5BB5] text-white border-0 shadow-lg relative overflow-hidden rounded-3xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/15 text-[#DDEBFF]">
              <iconify-icon icon="lucide:sparkles" class="text-amber-300"></iconify-icon>
              Client Advisory Portal
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              Welcome back, <?= $firstName ?>!
            </h1>
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

      <!-- Quick KPI Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Active Projects -->
        <a href="projects.php" class="dashboard-card p-6 block hover:border-[#4C6CCB] transition-all group">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Projects</span>
            <div class="w-10 h-10 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition-transform">
              <iconify-icon icon="lucide:folder-kanban"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $activeProjectsCount ?></div>
          <p class="text-xs text-[#6C5BB5] font-semibold mt-1">Ongoing studio sprints &rarr;</p>
        </a>

        <!-- Deliverables Shared -->
        <a href="files.php" class="dashboard-card p-6 block hover:border-[#4C6CCB] transition-all group">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Deliverables &amp; Files</span>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition-transform">
              <iconify-icon icon="lucide:file-text"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $totalFilesCount ?></div>
          <p class="text-xs text-[#8890AA] mt-1">Figma &amp; specs archive &rarr;</p>
        </a>

        <!-- Consultations Scheduled -->
        <a href="scheduling.php" class="dashboard-card p-6 block hover:border-[#4C6CCB] transition-all group">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Consultations</span>
            <div class="w-10 h-10 rounded-xl bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-lg shadow-sm group-hover:scale-105 transition-transform">
              <iconify-icon icon="lucide:calendar-clock"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $upcomingBookings ?></div>
          <p class="text-xs text-[#946200] font-semibold mt-1">Scheduled sessions &rarr;</p>
        </a>

        <!-- Messages from Studio -->
        <div class="dashboard-card p-6">
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Studio Updates</span>
            <div class="w-10 h-10 rounded-xl bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-lg shadow-sm">
              <iconify-icon icon="lucide:message-circle"></iconify-icon>
            </div>
          </div>
          <div class="text-3xl font-extrabold text-[#13224B]"><?= $messagesCount ?></div>
          <p class="text-xs text-[#127A45] font-semibold mt-1">Direct designer feedback</p>
        </div>
      </div>

      <!-- Main Overview Grid: Project Spotlight + Sidebar Feeds -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Left 2 Cols: Active Project Spotlight & Recent Deliverables -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- Active Project Spotlight Card -->
          <div class="dashboard-card p-6 sm:p-7">
            <div class="flex items-center justify-between mb-6">
              <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#6C5BB5]">Current Spotlight</span>
                <h2 class="text-lg font-bold text-[#13224B]">Active Project Sprint</h2>
              </div>
              <a href="projects.php" class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1">
                <span>View All Projects &rarr;</span>
              </a>
            </div>

            <?php if ($activeProject): ?>
              <div class="p-6 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)]">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-4">
                  <div>
                    <div class="flex items-center gap-2 mb-1">
                      <span class="px-2 py-0.5 rounded bg-white font-mono text-[10px] font-bold text-[#8890AA] border border-[rgba(19,34,75,0.06)]">
                        <?= htmlspecialchars($activeProject['project_code'], ENT_QUOTES, 'UTF-8') ?>
                      </span>
                      <span class="text-xs text-[#6C5BB5] font-semibold">
                        <?= htmlspecialchars($activeProject['category'] ?? 'UI/UX Design', ENT_QUOTES, 'UTF-8') ?>
                      </span>
                    </div>
                    <h3 class="text-lg font-bold text-[#13224B]">
                      <?= htmlspecialchars($activeProject['title'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                  </div>
                  <div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#DDEBFF] text-[#13224B] uppercase tracking-wider">
                      <?= htmlspecialchars($activeProject['status'] ?? 'Active', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-2 mb-6">
                  <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-[#13224B] flex items-center gap-1.5">
                      <iconify-icon icon="lucide:milestone" class="text-[#6C5BB5]"></iconify-icon>
                      <?= htmlspecialchars($activeProject['phase_name'] ?? 'In Progress', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="font-extrabold text-[#4C6CCB] font-mono text-sm"><?= (int)$activeProject['progress'] ?>%</span>
                  </div>
                  <div class="progress-track">
                    <div class="progress-bar-fill" style="width: <?= (int)$activeProject['progress'] ?>%;"></div>
                  </div>
                </div>

                <!-- Meta & Action -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                  <div class="text-xs text-[#8890AA]">
                    Budget: <strong class="text-[#13224B]"><?= format_usd($activeProject['budget'] ?? '$0') ?></strong> &middot; Started <?= time_ago($activeProject['created_at']) ?>
                  </div>
                  <a href="project-detail.php?id=<?= (int)$activeProject['id'] ?>"
                     class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-white text-xs font-bold shadow transition-all"
                     style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                    <span>Enter Project Workspace</span>
                    <iconify-icon icon="lucide:arrow-right" class="text-sm"></iconify-icon>
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="py-10 text-center text-xs text-[#8890AA]">
                <p>No active project sprint at this moment.</p>
                <a href="../inquiry.php" class="inline-block mt-3 text-xs font-bold text-[#4C6CCB] hover:underline">
                  + Submit a new project requirement
                </a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Recent Deliverables Quick Access -->
          <div class="dashboard-card p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h3 class="text-lg font-bold text-[#13224B]">Recent Deliverables</h3>
                <p class="text-xs text-[#8890AA] mt-0.5">Production assets, specs, and wireframe files uploaded by Kimberly.</p>
              </div>
              <a href="files.php" class="text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] flex items-center gap-1">
                <span>View All Deliverables &rarr;</span>
              </a>
            </div>

            <?php if (empty($recentFiles)): ?>
              <div class="py-8 text-center text-xs text-[#8890AA]">
                No deliverables shared yet. Once your project enters sprint execution, files will appear here.
              </div>
            <?php else: ?>
              <div class="divide-y divide-[rgba(19,34,75,0.06)]">
                <?php foreach ($recentFiles as $f): 
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
                    }
                ?>
                  <div class="py-3.5 flex items-center justify-between gap-4 first:pt-0 last:pb-0">
                    <div class="flex items-center gap-3 min-w-0">
                      <div class="w-8 h-8 rounded-xl bg-[#F4F6F8] flex items-center justify-center text-lg <?= $iconColor ?> shadow-sm flex-shrink-0">
                        <iconify-icon icon="<?= $icon ?>"></iconify-icon>
                      </div>
                      <div class="min-w-0">
                        <p class="text-xs font-bold text-[#13224B] truncate"><?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-[10px] text-[#8890AA] truncate mt-0.5">
                          <?= htmlspecialchars($f['project_code'] . ' · ' . $f['project_title'], ENT_QUOTES, 'UTF-8') ?> &middot; <?= htmlspecialchars($f['size'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                      </div>
                    </div>

                    <a href="../download.php?file_id=<?= (int)$f['id'] ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-[#13224B] bg-[#DDEBFF] hover:bg-[#4C6CCB] hover:text-white transition-all shadow-sm flex-shrink-0">
                      <iconify-icon icon="lucide:download"></iconify-icon>
                      <span>Download</span>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>

        <!-- Right 1 Col: Next Session & Advisory Lead Card -->
        <div class="space-y-6">

          <!-- Next Session Spotlight -->
          <div class="dashboard-card p-6">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-sm">
                  <iconify-icon icon="lucide:calendar"></iconify-icon>
                </div>
                <h3 class="text-sm font-bold text-[#13224B]">Next Session</h3>
              </div>
              <a href="scheduling.php" class="text-[11px] font-bold text-[#4C6CCB] hover:underline">
                Agenda &rarr;
              </a>
            </div>

            <?php if ($nextBooking): ?>
              <div class="p-4 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] space-y-2 text-xs">
                <div class="flex items-center justify-between">
                  <span class="font-bold text-[#13224B]"><?= htmlspecialchars($nextBooking['service'] ?: 'Advisory Consultation') ?></span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#DDEBFF] text-[#13224B] uppercase">
                    <?= htmlspecialchars($nextBooking['status']) ?>
                  </span>
                </div>
                <div class="text-[11px] text-[#8890AA] flex items-center gap-1">
                  <iconify-icon icon="lucide:calendar-clock" class="text-[#6C5BB5]"></iconify-icon>
                  <span><?= htmlspecialchars($nextBooking['date']) ?> &middot; <?= htmlspecialchars($nextBooking['time']) ?></span>
                </div>
                <div class="text-[10px] text-[#4b4b4b]">
                  Format: <strong><?= htmlspecialchars($nextBooking['format'] ?? 'Google Meet') ?></strong>
                </div>
                <div class="pt-2 border-t border-[rgba(19,34,75,0.06)] flex justify-between items-center text-[11px]">
                  <span class="font-extrabold text-[#13224B]"><?= format_usd($nextBooking['price'] ?? '$150') ?></span>
                  <a href="scheduling.php" class="font-bold text-[#4C6CCB] hover:underline">View Details &rarr;</a>
                </div>
              </div>
            <?php else: ?>
              <div class="text-center py-4 text-xs text-[#8890AA]">
                <p>No upcoming consultation scheduled.</p>
                <a href="../book-consultation.php" class="inline-block mt-2 text-xs font-bold text-[#4C6CCB] hover:underline">
                  + Schedule a Consultation
                </a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Studio Advisory Lead Card -->
          <div class="dashboard-card p-6 bg-gradient-to-br from-white to-[#F4F6F8]">
            <div class="flex items-center gap-3.5 mb-4">
              <img src="../images/profile.png" alt="Kimberly Jayne Antigo" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-md">
              <div>
                <h4 class="text-sm font-bold text-[#13224B]">Kimberly Jayne Antigo</h4>
                <p class="text-[11px] text-[#6C5BB5] font-semibold">Lead UI/UX Advisor</p>
              </div>
            </div>
            <p class="text-xs text-[#4b4b4b] leading-relaxed mb-4">
              Need strategic guidance or custom sprint adjustments? I am available for direct collaboration.
            </p>
            <div class="pt-3 border-t border-[rgba(19,34,75,0.08)] flex items-center justify-between text-xs">
              <span class="text-[11px] text-[#8890AA]">Response SLA: &lt; 24h</span>
              <a href="mailto:admin@antigo.com" class="font-bold text-[#4C6CCB] hover:underline flex items-center gap-1">
                <iconify-icon icon="lucide:mail"></iconify-icon>
                <span>Contact Studio</span>
              </a>
            </div>
          </div>

        </div>

      </div>

      <!-- Account Settings & Profile Section (Anchored via #settings) -->
      <div class="space-y-6 pt-6 border-t border-[rgba(19,34,75,0.08)]" id="settings">
        <div>
          <h2 class="text-lg font-bold text-[#13224B]">Account Settings &amp; Profile</h2>
          <p class="text-xs text-[#8890AA] mt-0.5">Manage your client profile details, company credentials, and security password.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          <!-- Profile Information Form -->
          <div class="dashboard-card p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg shadow-sm">
                <iconify-icon icon="lucide:user-check"></iconify-icon>
              </div>
              <div>
                <h4 class="text-sm font-bold text-[#13224B]">Profile Information</h4>
                <p class="text-[11px] text-[#8890AA]">Update your full name and company representation.</p>
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
                <p class="text-[10px] text-[#8890AA] mt-1">Contact studio support if you need to transfer this account to a new email address.</p>
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
          <div class="dashboard-card p-6 sm:p-8">
            <div class="flex items-center gap-3 mb-6">
              <div class="w-10 h-10 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg shadow-sm">
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

</body>
</html>