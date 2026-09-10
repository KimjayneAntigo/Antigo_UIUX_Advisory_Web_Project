<?php
/**
 * Client Portal — Project Detail Workspace.
 * Strictly verifies that the requested project belongs to $_SESSION['user_id'] (Anti-IDOR).
 */

require_once __DIR__ . '/../includes/auth-check-client.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$activePage = 'projects';
$userId     = (int) ($_SESSION['user_id'] ?? 0);
$projectId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($userId <= 0 || $projectId <= 0) {
    set_flash('error', 'Invalid project requested.');
    safe_redirect('dashboard.php');
}

//  IDOR Ownership Check 
try {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$projectId, $userId]);
    $project = $stmt->fetch();
} catch (\PDOException $e) {
    error_log('client/project-detail.php fetch project error: ' . $e->getMessage());
    set_flash('error', 'Database error loading project workspace.');
    safe_redirect('dashboard.php');
}

if (!$project) {
    // If project not owned by this client, redirect safely without exposing existence
    set_flash('error', 'Project not found or you do not have permission to view it.');
    safe_redirect('dashboard.php');
}

$pageTitle      = htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') . ' — Workspace';
$pageHeading    = 'Project Workspace';
$pageSubheading = htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8');

//  POST Handler Client Message Submission 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid security token. Please try again.');
        safe_redirect("project-detail.php?id={$projectId}");
    }

    $action = trim($_POST['action'] ?? '');

    if ($action === 'send_message') {
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            set_flash('error', 'Message cannot be empty.');
        } elseif (mb_strlen($message) > 2000) {
            set_flash('error', 'Message exceeds the 2,000 character limit.');
        } else {
            $sender = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Client';
            try {
                $stmt = $pdo->prepare(
                    "INSERT INTO project_messages (project_id, sender, role, message, created_at)
                     VALUES (?, ?, 'client', ?, NOW())"
                );
                $stmt->execute([$projectId, $sender, $message]);
                set_flash('success', 'Message sent to the design studio.');
            } catch (\PDOException $e) {
                error_log('client/project-detail send_message error: ' . $e->getMessage());
                set_flash('error', 'Could not send message. Please try again.');
            }
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }
}

// Fetch Files & Messages
$files    = [];
$messages = [];
$designer = null;

try {
    $stmt = $pdo->prepare(
        'SELECT pf.*, u.name AS uploader_name
         FROM project_files pf
         LEFT JOIN users u ON pf.uploaded_by = u.id
         WHERE pf.project_id = ?
         ORDER BY pf.uploaded_at DESC'
    );
    $stmt->execute([$projectId]);
    $files = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log('client/project-detail fetch files error: ' . $e->getMessage());
}

try {
    $stmt = $pdo->prepare('SELECT * FROM project_messages WHERE project_id = ? ORDER BY created_at ASC');
    $stmt->execute([$projectId]);
    $messages = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log('client/project-detail fetch messages error: ' . $e->getMessage());
}

try {
    $stmt = $pdo->prepare("SELECT name, email, company, role FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $designer = $stmt->fetch();
} catch (\PDOException $e) {
    error_log('client/project-detail fetch designer error: ' . $e->getMessage());
}

// Helper File icon
function get_client_file_icon(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return match($ext) {
        'pdf'                => 'lucide:file-text',
        'fig'                => 'lucide:figma',
        'png', 'jpg', 'jpeg' => 'lucide:image',
        'docx'               => 'lucide:file-type',
        'zip'                => 'lucide:archive',
        default              => 'lucide:file',
    };
}

// Discover, 2. Define, 3. Design, 4. Deliver
$currentPhase = (int) ($project['current_phase'] ?? 1);
$stages = [
    1 => ['name' => 'Discover', 'desc' => 'User research & audit'],
    2 => ['name' => 'Define',   'desc' => 'Information architecture & wireframes'],
    3 => ['name' => 'Design',   'desc' => 'Hi-fi UI & design systems'],
    4 => ['name' => 'Deliver',  'desc' => 'Testing, prototyping & handoff'],
];

// Map 5 phases into the 4 stage stepper
$stageIndex = match($currentPhase) {
    1       => 1, // Discover
    2       => 2, // Define
    3, 4    => 3, // Design
    default => 4, // Deliver
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
    <style>
        .chat-scroll {
            max-height: 400px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-thumb {
            background: rgba(19,34,75,0.15);
            border-radius: 4px;
        }
        /* Client's own messages: right aligned, gradient */
        .bubble-client-own {
            background: linear-gradient(135deg, #4C6CCB, #6C5BB5);
            color: #ffffff;
            border-radius: 18px 18px 4px 18px;
        }
        /* Designer's messages: left aligned, surface-alt */
        .bubble-designer {
            background: #F4F6F8;
            color: #13224B;
            border: 1px solid rgba(19,34,75,0.08);
            border-radius: 18px 18px 18px 4px;
        }
        .stepper-line {
            height: 3px;
            background: rgba(19,34,75,0.08);
            flex: 1;
        }
        .stepper-line.active {
            background: linear-gradient(90deg, #4C6CCB, #6C5BB5);
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

      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs text-[#8890AA] mb-1 font-semibold">
            <a href="dashboard.php" class="hover:underline text-[#4C6CCB]">Dashboard</a>
            <iconify-icon icon="lucide:chevron-right" class="text-xs"></iconify-icon>
            <a href="dashboard.php#projects" class="hover:underline">Projects</a>
            <iconify-icon icon="lucide:chevron-right" class="text-xs"></iconify-icon>
            <span class="text-[#13224B] truncate max-w-xs"><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <div class="flex items-center gap-3">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#13224B] tracking-tight">
              <?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?>
            </h2>
            <span class="px-2.5 py-0.5 rounded-full bg-gray-100 text-[#8890AA] font-mono text-xs font-bold">
              <?= htmlspecialchars($project['project_code'], ENT_QUOTES, 'UTF-8') ?>
            </span>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <?= status_badge($project['status']) ?>
        </div>
      </div>

      <!-- Flash Notification -->
      <?= render_flash() ?>

      <!-- ── MILESTONE STEPPER (Discover / Define / Design / Deliver) ───── -->
      <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]">
        <div class="flex items-center justify-between mb-6">
          <div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-[#6C5BB5]">Milestone Progression</span>
            <h3 class="text-base font-extrabold text-[#13224B] mt-0.5">
              Current Stage: <?= htmlspecialchars($project['phase_name'] ?? 'In Progress', ENT_QUOTES, 'UTF-8') ?>
            </h3>
          </div>
          <span class="text-sm font-extrabold text-[#4C6CCB]"><?= (int)$project['progress'] ?>% Complete</span>
        </div>

        <!-- Stepper Visual -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 pt-2">
          <?php foreach ($stages as $num => $stg): ?>
            <?php
              $isCompleted = ($num < $stageIndex);
              $isCurrent   = ($num === $stageIndex);
            ?>
            <div class="relative p-4 rounded-2xl border transition-all <?= $isCurrent ? 'border-[#4C6CCB] bg-[#DDEBFF]/30 shadow-sm' : ($isCompleted ? 'border-emerald-200 bg-emerald-50/40' : 'border-gray-100 bg-white') ?>">
              <div class="flex items-center justify-between mb-2">
                <span class="w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center <?= $isCompleted ? 'bg-emerald-600 text-white' : ($isCurrent ? 'bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white shadow-sm' : 'bg-gray-100 text-gray-400') ?>">
                  <?php if ($isCompleted): ?>
                    <iconify-icon icon="lucide:check" class="text-sm"></iconify-icon>
                  <?php else: ?>
                    <?= $num ?>
                  <?php endif; ?>
                </span>

                <?php if ($isCurrent): ?>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider bg-[#4C6CCB] text-white animate-pulse">
                    Active
                  </span>
                <?php elseif ($isCompleted): ?>
                  <span class="text-[10px] font-bold text-emerald-700 flex items-center gap-0.5">
                    <iconify-icon icon="lucide:check-circle-2" class="text-xs"></iconify-icon>
                    Done
                  </span>
                <?php else: ?>
                  <span class="text-[10px] text-gray-400">Upcoming</span>
                <?php endif; ?>
              </div>

              <div class="font-extrabold text-sm <?= $isCurrent ? 'text-[#13224B]' : ($isCompleted ? 'text-[#13224B]' : 'text-gray-400') ?>">
                <?= $stg['name'] ?>
              </div>
              <div class="text-[11px] text-[#8890AA] mt-0.5 leading-snug">
                <?= $stg['desc'] ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Left Files + Messages/ Right Info + Designer  -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">

        <!-- Left 2 Cols: Deliverables & Messages -->
        <div class="lg:col-span-2 space-y-8">

          <!-- Files Panel (Read + Secure Download) -->
          <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]" id="files">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h3 class="text-base font-bold text-[#13224B]">Deliverables &amp; Design Assets</h3>
                <p class="text-xs text-[#8890AA] mt-0.5">
                  Download wireframes, UI kits, specs, and reports shared by your advisory team.
                </p>
              </div>
              <span class="px-2.5 py-1 rounded-lg bg-[#DDEBFF] text-[#4C6CCB] text-xs font-bold">
                <?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?>
              </span>
            </div>

            <?php if (empty($files)): ?>
              <div class="py-8 text-center bg-[#F4F6F8]/60 rounded-2xl">
                <iconify-icon icon="lucide:file-question" class="text-3xl text-[#8890AA] mb-2 block mx-auto"></iconify-icon>
                <p class="text-xs font-bold text-[#13224B]">No deliverables uploaded yet</p>
                <p class="text-[11px] text-[#8890AA] mt-0.5">Your designer will attach deliverables as sprint milestones complete.</p>
              </div>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($files as $f): ?>
                  <div class="flex items-center justify-between p-4 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] gap-3 hover:bg-[#DDEBFF]/30 transition-colors">
                    <div class="flex items-center gap-3.5 min-w-0">
                      <div class="w-10 h-10 rounded-xl bg-white border border-[rgba(19,34,75,0.08)] flex items-center justify-center text-[#6C5BB5] flex-shrink-0 shadow-sm">
                        <iconify-icon icon="<?= get_client_file_icon($f['name']) ?>" class="text-lg"></iconify-icon>
                      </div>
                      <div class="min-w-0">
                        <p class="text-xs font-bold text-[#13224B] truncate">
                          <?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p class="text-[11px] text-[#8890AA] mt-0.5">
                          <?= htmlspecialchars($f['size'], ENT_QUOTES, 'UTF-8') ?>
                          &middot; <?= time_ago($f['uploaded_at']) ?>
                          <?php if (!empty($f['uploader_name'])): ?>
                            &middot; by <?= htmlspecialchars($f['uploader_name'], ENT_QUOTES, 'UTF-8') ?>
                          <?php endif; ?>
                        </p>
                      </div>
                    </div>

                    <!-- Secure Streaming Download Button -->
                    <a href="download.php?file_id=<?= (int)$f['id'] ?>"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-[#4C6CCB] bg-white border border-[rgba(76,108,203,0.2)] hover:bg-[#4C6CCB] hover:text-white transition-all shadow-sm flex-shrink-0">
                      <iconify-icon icon="lucide:download" class="text-sm"></iconify-icon>
                      <span>Download</span>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Messages Panel (Live Thread) -->
          <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]" id="messages">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h3 class="text-base font-bold text-[#13224B]">Studio Discussion Thread</h3>
                <p class="text-xs text-[#8890AA] mt-0.5">Direct channel with Kimberly and the Antigo design team.</p>
              </div>
              <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Active Thread
              </span>
            </div>

            <!-- Chat Scroll Box -->
            <div class="chat-scroll space-y-4 mb-6 pr-2" id="chatContainer">
              <?php if (empty($messages)): ?>
                <div class="py-8 text-center bg-[#F4F6F8]/60 rounded-2xl">
                  <iconify-icon icon="lucide:messages-square" class="text-3xl text-[#8890AA] mb-2 block mx-auto"></iconify-icon>
                  <p class="text-xs font-bold text-[#13224B]">No messages in this workspace yet</p>
                  <p class="text-[11px] text-[#8890AA] mt-0.5">Send a note below to start the conversation.</p>
                </div>
              <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                  <?php $isClientOwn = ($msg['role'] === 'client'); ?>
                  <div class="flex flex-col <?= $isClientOwn ? 'items-end' : 'items-start' ?>">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="text-[10px] font-bold <?= $isClientOwn ? 'text-[#4C6CCB]' : 'text-[#6C5BB5]' ?>">
                        <?= htmlspecialchars($msg['sender'], ENT_QUOTES, 'UTF-8') ?>
                        <?= $isClientOwn ? '(You)' : '(Studio Designer)' ?>
                      </span>
                      <span class="text-[9px] text-[#8890AA]"><?= time_ago($msg['created_at']) ?></span>
                    </div>
                    <div class="max-w-md px-4 py-3 text-xs leading-relaxed <?= $isClientOwn ? 'bubble-client-own' : 'bubble-designer' ?>">
                      <?= nl2br(htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

            <!-- Message Form -->
            <form method="POST" action="project-detail.php?id=<?= $projectId ?>" class="space-y-3">
              <input type="hidden" name="action" value="send_message">
              <?= csrf_input() ?>
              <div>
                <textarea name="message" rows="3" required placeholder="Type your message, feedback, or question for the designer…"
                          class="input-field w-full px-4 py-3 rounded-2xl text-xs font-medium resize-none"></textarea>
              </div>
              <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-xs font-bold text-white shadow-md transition-all hover:opacity-90"
                        style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                  <iconify-icon icon="lucide:send" class="text-sm"></iconify-icon>
                  <span>Send Message</span>
                </button>
              </div>
            </form>
          </div>

        </div>

        <!--Project Info Card & Designer Card -->
        <div class="space-y-6">

          <!-- Project Details Card (Read-only) -->
          <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#8890AA] mb-4">Project Overview</h3>

            <div class="space-y-3.5 text-xs">
              <div>
                <span class="text-[#8890AA] block text-[11px]">Primary Service</span>
                <span class="font-bold text-[#13224B]"><?= htmlspecialchars($project['category'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <div class="h-[1px] bg-[rgba(19,34,75,0.05)]"></div>

              <div>
                <span class="text-[#8890AA] block text-[11px]">Start Date</span>
                <span class="font-bold text-[#13224B]"><?= date('M j, Y', strtotime($project['created_at'])) ?></span>
              </div>
              <div class="h-[1px] bg-[rgba(19,34,75,0.05)]"></div>

              <div>
                <span class="text-[#8890AA] block text-[11px]">Expected Delivery</span>
                <span class="font-bold text-[#13224B]">
                  <?= $project['due_date'] ? date('M j, Y', strtotime($project['due_date'])) : 'TBD' ?>
                </span>
              </div>
              <div class="h-[1px] bg-[rgba(19,34,75,0.05)]"></div>

              <div>
                <span class="text-[#8890AA] block text-[11px]">Project Status</span>
                <div class="mt-1">
                  <?= status_badge($project['status']) ?>
                </div>
              </div>
              <div class="h-[1px] bg-[rgba(19,34,75,0.05)]"></div>

              <div>
                <span class="text-[#8890AA] block text-[11px]">Agreed Budget</span>
                <span class="font-bold text-emerald-700"><?= htmlspecialchars($project['budget'], ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          </div>

          <!-- Designer Profile Card -->
          <div class="card p-6 border border-[rgba(19,34,75,0.08)]">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#8890AA] mb-4">Assigned Lead Designer</h3>

            <div class="flex items-center gap-3 mb-4">
              <div class="w-12 h-12 rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white flex items-center justify-center font-bold text-sm shadow-md">
                KA
              </div>
              <div>
                <h4 class="text-sm font-extrabold text-[#13224B]">
                  <?= htmlspecialchars($designer['name'] ?? 'Kimberly Jayne Antigo', ENT_QUOTES, 'UTF-8') ?>
                </h4>
                <p class="text-[11px] text-[#6C5BB5] font-semibold">Lead UI/UX Consultant &amp; Founder</p>
                <p class="text-[10px] text-[#8890AA]"><?= htmlspecialchars($designer['email'] ?? 'admin@antigo.com', ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            </div>

            <p class="text-xs text-[#4b4b4b] leading-relaxed mb-4">
              Direct point of contact overseeing research, wireframing, and high-fidelity prototype handoff for your workspace.
            </p>

            <a href="mailto:<?= htmlspecialchars($designer['email'] ?? 'admin@antigo.com', ENT_QUOTES, 'UTF-8') ?>"
               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-[#F4F6F8] transition-colors">
              <iconify-icon icon="lucide:mail"></iconify-icon>
              <span>Email Designer</span>
            </a>
          </div>

        </div>

      </div>

    </main>
  </div>

  <script>
    // Auto-scroll chat to latest message
    const chatContainer = document.getElementById('chatContainer');
    if (chatContainer) {
      chatContainer.scrollTop = chatContainer.scrollHeight;
    }
  </script>
</body>
</html>
