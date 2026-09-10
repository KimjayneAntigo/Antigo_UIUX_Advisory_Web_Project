<?php
/**
 * admin control panel for a single project.
 */

require_once __DIR__ . '/../includes/auth-check-admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Server-side (NEVER trust client-submitted progress) 
const STAGE_MAP = [
    1 => ['phase_name' => 'Discovery & Research', 'progress' => 20,  'status' => 'Discovery'],
    2 => ['phase_name' => 'Wireframing',           'progress' => 40,  'status' => 'Wireframing'],
    3 => ['phase_name' => 'UI/UX Design',          'progress' => 60,  'status' => 'UI Design'],
    4 => ['phase_name' => 'Prototyping',            'progress' => 80,  'status' => 'Prototyping'],
    5 => ['phase_name' => 'Delivered',              'progress' => 100, 'status' => 'Delivered'],
];

// Validate ?id param (supports integer id or project_code like PRJ-3001)
$rawId = trim($_GET['id'] ?? '');
if ($rawId === '') {
    set_flash('error', 'No project specified.');
    safe_redirect('../admin-dashboard.php');
}

try {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? OR project_code = ? LIMIT 1');
    $stmt->execute([is_numeric($rawId) ? (int) $rawId : 0, $rawId]);
    $project = $stmt->fetch();
} catch (\PDOException $e) {
    error_log('project-detail fetch project error: ' . $e->getMessage());
    set_flash('error', 'Database error loading project.');
    safe_redirect('../admin-dashboard.php');
}

if (!$project) {
    set_flash('error', 'Project not found.');
    safe_redirect('../admin-dashboard.php');
}

$projectId = (int) $project['id'];

//  POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        set_flash('error', 'Invalid or expired security token. Please try again.');
        safe_redirect("project-detail.php?id={$projectId}");
    }

    $action = trim($_POST['action'] ?? '');

    // Update Stage
    if ($action === 'update_stage') {
        $stage = isset($_POST['stage']) ? (int) $_POST['stage'] : 0;
        if ($stage < 1 || $stage > 5) {
            set_flash('error', 'Invalid stage selected. Must be 1–5.');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        $map = STAGE_MAP[$stage];
        try {
            $stmt = $pdo->prepare(
                'UPDATE projects
                 SET current_phase = ?, phase_name = ?, progress = ?, status = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([
                $stage,
                $map['phase_name'],
                $map['progress'],
                $map['status'],
                $projectId,
            ]);
            set_flash('success', "Stage updated to Phase {$stage}: {$map['phase_name']} ({$map['progress']}%).");
        } catch (\PDOException $e) {
            error_log('project-detail update_stage error: ' . $e->getMessage());
            set_flash('error', 'Could not update project stage. Please try again.');
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }

    // Upload File 
    if ($action === 'upload_file') {
        $allowedExts = ['pdf', 'fig', 'png', 'jpg', 'jpeg', 'docx', 'zip'];
        $maxBytes    = 10 * 1024 * 1024; // 10 MB

        if (!isset($_FILES['project_file']) || $_FILES['project_file']['error'] !== UPLOAD_ERR_OK) {
            $uploadError = $_FILES['project_file']['error'] ?? -1;
            set_flash('error', 'File upload failed (code ' . $uploadError . '). Please try again.');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        $file     = $_FILES['project_file'];
        $origName = basename($file['name']);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $size     = (int) $file['size'];

        if (!in_array($ext, $allowedExts, true)) {
            set_flash('error', 'Invalid file type. Allowed: ' . implode(', ', $allowedExts) . '.');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        // MIME type inspection for upload security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'image/png',
            'image/jpeg',
            'image/webp',
            'application/zip',
            'application/x-zip-compressed',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/octet-stream',
            'text/plain',
        ];

        if (!in_array($mime, $allowedMimes, true)) {
            set_flash('error', 'Security check failed: File content type not permitted.');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        if ($size > $maxBytes) {
            set_flash('error', 'File exceeds the 10 MB limit (' . format_filesize($size) . ' uploaded).');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        // Build upload dir
        $uploadDir = __DIR__ . '/../uploads/projects/' . $projectId . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log('project-detail: cannot create upload dir ' . $uploadDir);
                set_flash('error', 'Server error creating upload directory.');
                safe_redirect("project-detail.php?id={$projectId}");
            }
        }

        // Unique filename to avoid collisions
        $safeBase     = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $origName);
        $destFilename = time() . '_' . $safeBase;
        $destPath     = $uploadDir . $destFilename;
        $relPath      = 'uploads/projects/' . $projectId . '/' . $destFilename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            error_log('project-detail: move_uploaded_file failed for ' . $origName);
            set_flash('error', 'Could not save uploaded file. Please try again.');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        $displaySize = format_filesize($size);
        $uploadedBy  = (int) ($_SESSION['user_id'] ?? 0);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO project_files (project_id, uploaded_by, name, size, file_path, uploaded_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$projectId, $uploadedBy, $origName, $displaySize, $relPath]);
            set_flash('success', 'File "' . $origName . '" uploaded successfully (' . $displaySize . ').');
        } catch (\PDOException $e) {
            error_log('project-detail upload_file DB error: ' . $e->getMessage());
            // Remove orphan file
            @unlink($destPath);
            set_flash('error', 'File saved on disk but database entry failed. Contact support.');
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }

    // Delete File
    if ($action === 'delete_file') {
        $fileId = (int) ($_POST['file_id'] ?? 0);
        if ($fileId <= 0) {
            set_flash('error', 'Invalid file specified.');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        try {
            $fStmt = $pdo->prepare('SELECT * FROM project_files WHERE id = ? AND project_id = ? LIMIT 1');
            $fStmt->execute([$fileId, $projectId]);
            $fileRow = $fStmt->fetch();

            if (!$fileRow) {
                set_flash('error', 'Deliverable file not found.');
                safe_redirect("project-detail.php?id={$projectId}");
            }

            // Remove physical file from disk
            if (!empty($fileRow['file_path'])) {
                $fullPath = __DIR__ . '/../' . ltrim($fileRow['file_path'], '/\\');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Delete database record
            $delStmt = $pdo->prepare('DELETE FROM project_files WHERE id = ? AND project_id = ?');
            $delStmt->execute([$fileId, $projectId]);

            set_flash('success', 'Deliverable file deleted successfully.');
        } catch (\PDOException $e) {
            error_log('project-detail delete_file error: ' . $e->getMessage());
            set_flash('error', 'Database error deleting file.');
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }

    // Send Message
    if ($action === 'send_message') {
        $message = trim($_POST['message'] ?? '');
        if ($message === '') {
            set_flash('error', 'Message cannot be empty.');
            safe_redirect("project-detail.php?id={$projectId}");
        }
        if (mb_strlen($message) > 2000) {
            set_flash('error', 'Message is too long (max 2,000 characters).');
            safe_redirect("project-detail.php?id={$projectId}");
        }

        $senderName = $_SESSION['user_name'] ?? 'Admin';
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO project_messages (project_id, sender, role, message, created_at)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$projectId, $senderName, 'designer', $message]);
            set_flash('success', 'Message sent.');
        } catch (\PDOException $e) {
            error_log('project-detail send_message error: ' . $e->getMessage());
            set_flash('error', 'Could not send message. Please try again.');
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }

    // Archive 
    if ($action === 'archive') {
        try {
            $stmt = $pdo->prepare(
                "UPDATE projects SET status_type = 'completed', updated_at = NOW() WHERE id = ?"
            );
            $stmt->execute([$projectId]);
            set_flash('success', 'Project archived.');
        } catch (\PDOException $e) {
            error_log('project-detail archive error: ' . $e->getMessage());
            set_flash('error', 'Could not archive project. Please try again.');
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }

    // Update Notes
    if ($action === 'update_notes') {
        $notes = trim($_POST['internal_notes'] ?? '');
        try {
            $stmt = $pdo->prepare(
                'UPDATE projects SET internal_notes = ?, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$notes ?: null, $projectId]);
            set_flash('success', 'Internal notes saved.');
        } catch (\PDOException $e) {
            error_log('project-detail update_notes error: ' . $e->getMessage());
            set_flash('error', 'Could not save notes. Please try again.');
        }
        safe_redirect("project-detail.php?id={$projectId}");
    }

    // Unknown action
    set_flash('error', 'Unknown action.');
    safe_redirect("project-detail.php?id={$projectId}");
}


// Fetch related data 
$files    = [];
$messages = [];
$client   = null;

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
    error_log('project-detail fetch files error: ' . $e->getMessage());
}

try {
    $stmt = $pdo->prepare('SELECT * FROM project_messages WHERE project_id = ? ORDER BY created_at ASC');
    $stmt->execute([$projectId]);
    $messages = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log('project-detail fetch messages error: ' . $e->getMessage());
}

if (!empty($project['client_email'])) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$project['client_email']]);
        $client = $stmt->fetch() ?: null;
    } catch (\PDOException $e) {
        error_log('project-detail fetch client error: ' . $e->getMessage());
    }
}

// Display helpers
$pageTitle  = htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') . ' — Admin';
$dueDateFmt = $project['due_date'] ? date('M j, Y', strtotime($project['due_date'])) : 'TBD';
$createdFmt = $project['created_at'] ? date('M j, Y', strtotime($project['created_at'])) : '—';

// Status type badge helper
function status_type_badge(string $type): string
{
    return match($type) {
        'in_design' => '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#DDEBFF] text-[#13224B]">In Design</span>',
        'completed' => '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#DFF6E8] text-[#127A45]">Completed</span>',
        default     => '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#FFF1D6] text-[#946200]">Pending</span>',
    };
}

// File icon by extension
function file_icon(string $name): string
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | Antigo Advisory</title>

    <!-- Google Fonts – Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:         '#13224B',
                        violet:       '#6C5BB5',
                        brandBlue:    '#4C6CCB',
                        'light-blue': '#DDEBFF',
                        'light-gray': '#F4F6F8',
                        'dark-gray':  '#4b4b4b',
                    },
                    fontFamily: {
                        poppins: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --navy:       #13224B;
            --violet:     #6C5BB5;
            --blue:       #4C6CCB;
            --white:      #FFFFFF;
            --light-blue: #DDEBFF;
            --light-gray: #F4F6F8;
            --dark-gray:  #4b4b4b;
            --text:       #13224B;
            --text-soft:  #4b4b4b;
            --text-faint: #8890AA;
            --surface:    #FFFFFF;
            --surface-alt:#F4F6F8;
            --border:     rgba(19,34,75,0.09);
            --grad:       linear-gradient(100deg,#13224B 0%,#6C5BB5 55%,#4C6CCB 100%);
            --grad-soft:  linear-gradient(135deg,#4C6CCB,#6C5BB5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-alt);
            color: var(--text);
            margin: 0; padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .admin-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 4px 18px -4px rgba(19,34,75,0.06);
        }

        .progress-track {
            background: rgba(19,34,75,0.08);
            border-radius: 99px;
            height: 8px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--grad-soft);
            transition: width 0.5s ease;
        }

        /* Chat bubbles */
        .bubble-designer {
            background: var(--grad-soft);
            color: #FFFFFF;
            border-radius: 18px 18px 4px 18px;
        }
        .bubble-client {
            background: var(--surface-alt);
            color: var(--navy);
            border: 1px solid var(--border);
            border-radius: 18px 18px 18px 4px;
        }

        /* Internal notes warm block */
        .notes-confidential {
            background: #FFFBEB;
            border-left: 4px solid #F59E0B;
            border-radius: 0 12px 12px 0;
        }

        /* Textarea */
        .antigo-textarea {
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface-alt);
            color: var(--text);
            font-family: 'Poppins', sans-serif;
            font-size: 0.8125rem;
            padding: 0.75rem 1rem;
            width: 100%;
            resize: vertical;
            transition: border-color 0.2s;
        }
        .antigo-textarea:focus {
            outline: none;
            border-color: var(--violet);
            background: #fff;
        }

        /* Select */
        .antigo-select {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--navy);
            font-family: 'Poppins', sans-serif;
            font-size: 0.8125rem;
            font-weight: 700;
            padding: 0.55rem 2.5rem 0.55rem 0.875rem;
            cursor: pointer;
            transition: border-color 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24'%3E%3Cpath fill='%238890AA' d='m7 10 5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
        }
        .antigo-select:focus {
            outline: none;
            border-color: var(--violet);
            background-color: #fff;
        }

        /* Dropzone */
        .dropzone {
            border: 2px dashed rgba(108,91,181,0.35);
            border-radius: 14px;
            background: rgba(76,108,203,0.03);
            transition: background 0.2s, border-color 0.2s;
        }
        .dropzone:hover {
            border-color: var(--violet);
            background: rgba(108,91,181,0.06);
        }

        /* Chat scroll */
        .chat-scroll {
            max-height: 360px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        .chat-scroll::-webkit-scrollbar { width: 4px; }
        .chat-scroll::-webkit-scrollbar-thumb {
            background: rgba(19,34,75,0.15);
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-h-screen">

<!-- ══════════════════════ TOP NAV ═══════════════════════════════════════════ -->
<header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md">
    <div class="max-w-[1440px] mx-auto px-6 lg:px-10 h-[68px] flex items-center justify-between gap-4">

        <!-- Left: back + breadcrumb -->
        <div class="flex items-center gap-3 min-w-0">
            <a href="../admin-dashboard.php"
               class="flex-shrink-0 w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white/70 hover:text-white hover:bg-white/20 transition-all"
               title="Back to Dashboard">
                <iconify-icon icon="lucide:arrow-left" class="text-base"></iconify-icon>
            </a>
            <div class="min-w-0">
                <p class="text-[9px] font-bold uppercase tracking-widest text-[#DDEBFF] flex items-center gap-1 flex-wrap">
                    <a href="../admin-dashboard.php" class="hover:underline">Projects</a>
                    <iconify-icon icon="lucide:chevron-right" class="text-[10px]"></iconify-icon>
                    <span><?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>
                <div class="flex items-center gap-2 mt-0.5">
                    <h1 class="text-sm font-extrabold text-white truncate max-w-xs sm:max-w-md">
                        <?= htmlspecialchars($project['title'], ENT_QUOTES, 'UTF-8') ?>
                    </h1>
                    <span class="flex-shrink-0 px-2 py-0.5 rounded bg-[#4C6CCB]/40 text-[10px] font-bold text-[#DDEBFF] tracking-widest font-mono">
                        <?= htmlspecialchars($project['project_code'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- action buttons -->
        <div class="flex items-center gap-2 flex-shrink-0">
            <!-- Mark Complete -->
            <form method="POST" action="project-detail.php?id=<?= $projectId ?>"
                  onsubmit="return confirm('Archive this project? This marks it as Completed.');">
                <input type="hidden" name="action" value="archive">
                <button type="submit"
                        class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-[#DFF6E8]/20 border border-[#DFF6E8]/30 text-[#DFF6E8] text-xs font-bold hover:bg-[#DFF6E8]/30 transition-all">
                    <iconify-icon icon="lucide:check-circle-2" class="text-base"></iconify-icon>
                    Mark Complete
                </button>
            </form>

            <!-- Send Invoice stub -->
            <button type="button"
                    onclick="alert('Invoice module coming soon.')"
                    class="hidden sm:flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white/10 border border-white/20 text-white text-xs font-bold hover:bg-white/20 transition-all">
                <iconify-icon icon="lucide:receipt" class="text-base"></iconify-icon>
                Send Invoice
            </button>

            <!-- Mobile: archive icon only -->
            <form method="POST" action="project-detail.php?id=<?= $projectId ?>"
                  class="sm:hidden"
                  onsubmit="return confirm('Archive this project?');">
                <input type="hidden" name="action" value="archive">
                <button type="submit"
                        class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-[#DFF6E8] hover:bg-white/20 transition-all"
                        title="Mark Complete">
                    <iconify-icon icon="lucide:check-circle-2" class="text-base"></iconify-icon>
                </button>
            </form>
        </div>
    </div>
</header>

<!-- ═════════════════════ MAIN CONTENT ══════════════════════════════════════ -->
<main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-10 py-8 space-y-7">

    <!-- Flash messages -->
    <?= render_flash() ?>

    <!--  STAGE BAR -->
    <div class="admin-card p-6 sm:p-8">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-5 mb-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-widest text-[#6C5BB5]">Milestone Stepper Control</span>
                <h2 class="text-xl font-extrabold text-[#13224B] mt-0.5">Project Phase &amp; Delivery Status</h2>
                <p class="text-xs text-[#8890AA] mt-0.5">Updating this phase controls what the client sees in their portal stepper in real time.</p>
            </div>

            <!-- Stage update form -->
            <form method="POST" action="project-detail.php?id=<?= $projectId ?>"
                  class="flex items-center gap-2 flex-shrink-0">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update_stage">
                <label class="text-xs font-bold text-[#8890AA] whitespace-nowrap hidden sm:block">Phase:</label>
                <select name="stage" class="antigo-select">
                    <?php foreach (STAGE_MAP as $num => $info): ?>
                        <option value="<?= $num ?>" <?= (int)$project['current_phase'] === $num ? 'selected' : '' ?>>
                            Phase <?= $num ?>: <?= htmlspecialchars($info['phase_name'], ENT_QUOTES, 'UTF-8') ?> (<?= $info['progress'] ?>%)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit"
                        class="flex-shrink-0 px-4 py-2 rounded-xl text-white text-xs font-bold shadow hover:opacity-90 transition-all whitespace-nowrap"
                        style="background:var(--grad-soft);">
                    Update
                </button>
            </form>
        </div>

        <!-- Progress bar -->
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-[#4b4b4b]">
                    Phase <?= (int)$project['current_phase'] ?>:
                    <strong class="text-[#13224B]"><?= htmlspecialchars($project['phase_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </span>
                <span class="text-xs font-extrabold text-[#6C5BB5]"><?= (int)$project['progress'] ?>%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width:<?= (int)$project['progress'] ?>%"></div>
            </div>
        </div>

        <!-- Step pills -->
        <div class="grid grid-cols-5 gap-2 mt-5">
            <?php foreach (STAGE_MAP as $num => $info):
                $current = (int)$project['current_phase'];
                $state   = ($num < $current) ? 'done' : (($num === $current) ? 'active' : 'future');
            ?>
            <div class="flex flex-col items-center gap-1.5 text-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                    <?= $state === 'done'   ? 'bg-[#10b981] text-white' : '' ?>
                    <?= $state === 'active' ? 'text-white ring-2 ring-[#6C5BB5]/40 shadow-md' : '' ?>
                    <?= $state === 'future' ? 'bg-white border border-[rgba(19,34,75,0.15)] text-[#8890AA]' : '' ?>"
                    <?= $state === 'active' ? 'style="background:var(--grad-soft);"' : '' ?>>
                    <?php if ($state === 'done'): ?>
                        <iconify-icon icon="lucide:check" class="text-sm"></iconify-icon>
                    <?php else: ?>
                        <?= $num ?>
                    <?php endif; ?>
                </div>
                <span class="text-[9px] font-semibold leading-tight
                    <?= $state === 'active' ? 'text-[#6C5BB5] font-bold' : 'text-[#8890AA]' ?>">
                    <?= htmlspecialchars($info['phase_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Due date -->
        <p class="text-xs text-[#8890AA] mt-5 flex items-center gap-1.5">
            <iconify-icon icon="lucide:calendar" class="text-[#6C5BB5]"></iconify-icon>
            Expected delivery: <strong class="text-[#13224B]"><?= $dueDateFmt ?></strong>
        </p>
    </div><!-- /STAGE BAR -->

    <!--  TWO-COLUMN LAYOUT -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-7">

        <!-- LEFT COL (60%) -->
        <div class="xl:col-span-3 space-y-6">

            <!-- FILES PANEL -->
            <div class="admin-card p-6 sm:p-8">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-bold text-[#13224B]">Deliverables &amp; Files</h3>
                        <p class="text-xs text-[#8890AA] mt-0.5">
                            <?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?> attached
                        </p>
                    </div>
                    <div class="w-9 h-9 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center">
                        <iconify-icon icon="lucide:folder-open" class="text-base"></iconify-icon>
                    </div>
                </div>

                <!-- File list -->
                <?php if (empty($files)): ?>
                    <div class="py-8 text-center">
                        <iconify-icon icon="lucide:folder" class="text-4xl text-[#8890AA] mb-2 block mx-auto"></iconify-icon>
                        <p class="text-sm text-[#8890AA] font-medium">No files uploaded yet.</p>
                        <p class="text-xs text-[#8890AA]">Upload a deliverable below to share with the client.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-2.5 mb-6">
                        <?php foreach ($files as $f): ?>
                            <div class="flex items-center justify-between p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] gap-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex-shrink-0 w-9 h-9 rounded-lg bg-white border border-[rgba(19,34,75,0.08)] flex items-center justify-center text-[#6C5BB5]">
                                        <iconify-icon icon="<?= file_icon($f['name']) ?>"></iconify-icon>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-[#13224B] truncate">
                                            <?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>
                                        </p>
                                        <p class="text-[10px] text-[#8890AA]">
                                            <?= htmlspecialchars($f['size'], ENT_QUOTES, 'UTF-8') ?>
                                            &middot;
                                            <?= time_ago($f['uploaded_at']) ?>
                                            <?php if (!empty($f['uploader_name'])): ?>
                                                &middot; by <?= htmlspecialchars($f['uploader_name'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="download.php?file_id=<?= (int)$f['id'] ?>"
                                       class="flex-shrink-0 flex items-center gap-1 text-xs font-bold text-[#4C6CCB] hover:text-[#6C5BB5] hover:underline transition-colors"
                                       title="Download <?= htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8') ?>">
                                        <iconify-icon icon="lucide:download" class="text-sm"></iconify-icon>
                                        Download
                                    </a>
                                    <form method="POST" action="project-detail.php?id=<?= $projectId ?>" onsubmit="return confirm('Are you sure you want to delete this deliverable file?');" class="inline">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete_file">
                                        <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                                        <button type="submit" class="p-1 rounded text-red-500 hover:text-red-700 hover:bg-red-50 transition-colors" title="Delete deliverable">
                                            <iconify-icon icon="lucide:trash-2" class="text-sm"></iconify-icon>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Upload form -->
                <form method="POST" action="project-detail.php?id=<?= $projectId ?>"
                      enctype="multipart/form-data" id="uploadForm">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="upload_file">
                    <input type="file" name="project_file" id="fileInput" class="hidden"
                           accept=".pdf,.fig,.png,.jpg,.jpeg,.docx,.zip"
                           onchange="updateDropzoneLabel(this)">

                    <div class="dropzone p-6 text-center cursor-pointer"
                         onclick="document.getElementById('fileInput').click()">
                        <iconify-icon icon="lucide:upload-cloud" class="text-3xl text-[#6C5BB5] mb-2 block mx-auto"></iconify-icon>
                        <p class="text-xs font-bold text-[#13224B]" id="dropzoneLabel">
                            Click to upload a deliverable
                        </p>
                        <p class="text-[10px] text-[#8890AA] mt-0.5">PDF, Figma, PNG, JPG, DOCX, ZIP — max 10 MB</p>
                    </div>

                    <div class="flex justify-end mt-3">
                        <button type="submit" id="uploadBtn"
                                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-xs font-bold shadow transition-all hover:opacity-90"
                                style="background:var(--grad-soft); opacity:0.5; cursor:not-allowed;"
                                disabled>
                            <iconify-icon icon="lucide:upload"></iconify-icon>
                            Upload File
                        </button>
                    </div>
                </form>
            </div><!-- /FILES -->

            <!-- MESSAGES PANEL -->
            <div class="admin-card p-6 sm:p-8">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-bold text-[#13224B]">Client Conversation</h3>
                        <p class="text-xs text-[#8890AA] mt-0.5">
                            Posting as
                            <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong>
                            (Designer)
                        </p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-[#DDEBFF] text-[#13224B] text-[10px] font-bold">
                        Live Thread
                    </span>
                </div>

                <!-- Thread -->
                <div class="chat-scroll space-y-4 mb-5 pr-1" id="chatThread">
                    <?php if (empty($messages)): ?>
                        <div class="py-6 text-center">
                            <iconify-icon icon="lucide:message-circle" class="text-3xl text-[#8890AA] mb-2 block mx-auto"></iconify-icon>
                            <p class="text-xs text-[#8890AA]">No messages yet. Start the conversation below.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg):
                            $isDesigner = ($msg['role'] === 'designer');
                        ?>
                            <div class="flex flex-col <?= $isDesigner ? 'items-end' : 'items-start' ?>">
                                <span class="text-[10px] text-[#8890AA] mb-1 px-1">
                                    <?= htmlspecialchars($msg['sender'], ENT_QUOTES, 'UTF-8') ?>
                                    &middot;
                                    <?= time_ago($msg['created_at']) ?>
                                </span>
                                <div class="max-w-[85%] px-4 py-3 text-xs leading-relaxed
                                    <?= $isDesigner ? 'bubble-designer' : 'bubble-client' ?>">
                                    <?= nl2br(htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Send message form -->
                <form method="POST" action="project-detail.php?id=<?= $projectId ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="send_message">
                    <div class="flex gap-2">
                        <textarea name="message" rows="2" maxlength="2000" required
                                  class="antigo-textarea"
                                  placeholder="Reply to client as designer…"
                                  id="msgTextarea"></textarea>
                        <button type="submit"
                                class="flex-shrink-0 w-11 h-11 self-end rounded-xl text-white flex items-center justify-center shadow hover:scale-105 transition-transform"
                                style="background:var(--grad);">
                            <iconify-icon icon="lucide:send" class="text-base"></iconify-icon>
                        </button>
                    </div>
                    <p class="text-[10px] text-[#8890AA] mt-1 text-right" id="charCount">0 / 2000</p>
                </form>
            </div><!-- /MESSAGES -->

        </div><!-- /LEFT COL -->

        <!-- RIGHT COL  -->
        <div class="xl:col-span-2 space-y-6">

            <!-- CLIENT INFO -->
            <div class="admin-card p-6">
                <div class="flex items-center gap-3 mb-5">
                    <!-- Avatar initials -->
                    <?php
                        $displayName = $client['name'] ?? $project['client_name'];
                        $nameParts   = explode(' ', trim($displayName));
                        $initials    = strtoupper(($nameParts[0][0] ?? '') . (isset($nameParts[1]) ? $nameParts[1][0] : ''));
                    ?>
                    <div class="w-12 h-12 rounded-full text-white font-extrabold text-base flex items-center justify-center flex-shrink-0 shadow"
                         style="background:var(--grad-soft);">
                        <?= htmlspecialchars($initials ?: '?', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-[#13224B] truncate">
                            <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <p class="text-[10px] text-[#8890AA] truncate">
                            <?= htmlspecialchars($client['company'] ?? $project['company'] ?? 'No company on file', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center gap-2 text-[#4b4b4b]">
                        <iconify-icon icon="lucide:mail" class="text-[#6C5BB5] flex-shrink-0 text-sm"></iconify-icon>
                        <span class="truncate"><?= htmlspecialchars($project['client_email'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="flex items-center gap-2 text-[#4b4b4b]">
                        <iconify-icon icon="lucide:building-2" class="text-[#6C5BB5] flex-shrink-0 text-sm"></iconify-icon>
                        <span class="truncate"><?= htmlspecialchars($project['company'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if ($client): ?>
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:user-check" class="text-[#10b981] flex-shrink-0 text-sm"></iconify-icon>
                            <span class="text-[#127A45] font-semibold">Registered portal user</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:user-x" class="text-[#8890AA] flex-shrink-0 text-sm"></iconify-icon>
                            <span class="text-[#8890AA]">No portal account linked</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div><!-- /CLIENT INFO -->

            <!-- PROJECT DETAILS -->
            <div class="admin-card p-6">
                <h3 class="text-sm font-bold text-[#13224B] mb-4">Project Details</h3>

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[#8890AA] flex-shrink-0">Service / Category</span>
                        <span class="font-bold text-[#13224B] text-right">
                            <?= htmlspecialchars($project['category'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[#8890AA] flex-shrink-0">Project Started</span>
                        <span class="font-bold text-[#13224B]"><?= $createdFmt ?></span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[#8890AA] flex-shrink-0">Contract Value</span>
                        <span class="font-extrabold text-[#4C6CCB]">
                            <?= htmlspecialchars($project['budget'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[#8890AA] flex-shrink-0">Due Date</span>
                        <span class="font-bold text-[#13224B]"><?= $dueDateFmt ?></span>
                    </div>
                    <div class="flex justify-between items-center gap-2">
                        <span class="text-[#8890AA] flex-shrink-0">Status</span>
                        <?= status_type_badge($project['status_type']) ?>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-[#8890AA] flex-shrink-0">Current Phase</span>
                        <span class="font-bold text-[#6C5BB5]">
                            <?= htmlspecialchars($project['phase_name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>
            </div><!-- /PROJECT DETAILS -->

            <!-- INTERNAL NOTES -->
            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-wider">
                            Studio Confidential
                        </span>
                        <h3 class="text-sm font-bold text-[#13224B]">Internal Notes</h3>
                    </div>
                    <span class="text-[10px] text-[#8890AA] italic flex-shrink-0">Not visible to client</span>
                </div>

                <div class="notes-confidential p-4 mb-4 rounded-r-2xl">
                    <form method="POST" action="project-detail.php?id=<?= $projectId ?>" id="notesForm">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="update_notes">
                        <textarea name="internal_notes" rows="5"
                                  class="w-full bg-transparent border-none outline-none resize-none font-medium text-xs text-amber-800 italic leading-relaxed placeholder-amber-400"
                                  placeholder="Add confidential studio notes, billing milestones, design strategy…"
                                  ><?= htmlspecialchars($project['internal_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </form>
                </div>

                <div class="flex justify-end">
                    <button type="submit" form="notesForm"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold hover:bg-amber-100 transition-colors">
                        <iconify-icon icon="lucide:save" class="text-sm"></iconify-icon>
                        Save Notes
                    </button>
                </div>
            </div><!-- /INTERNAL NOTES -->

        </div><!-- /RIGHT COL -->

    </div><!-- /TWO-COL -->

</main>

<!--  FOOTER  -->
<footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white mt-10">
    &copy; 2026 Antigo UI/UX Advisory &middot; Studio Administration
</footer>

<script>
//  Scroll chat to bottom on load
(function () {
    const thread = document.getElementById('chatThread');
    if (thread) thread.scrollTop = thread.scrollHeight;
})();

//  Dropzone label + enable upload button
function updateDropzoneLabel(input) {
    const label = document.getElementById('dropzoneLabel');
    const btn   = document.getElementById('uploadBtn');
    if (input.files && input.files[0]) {
        const f     = input.files[0];
        const sizeMB = (f.size / 1048576).toFixed(2);
        label.textContent = 'Selected: ' + f.name + ' (' + sizeMB + ' MB)';
        label.classList.add('text-[#6C5BB5]');
        if (btn) {
            btn.disabled = false;
            btn.style.opacity  = '1';
            btn.style.cursor   = 'pointer';
        }
    } else {
        label.textContent = 'Click to upload a deliverable';
        label.classList.remove('text-[#6C5BB5]');
        if (btn) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.style.cursor  = 'not-allowed';
        }
    }
}

// Char counter for message textarea
(function () {
    const ta      = document.getElementById('msgTextarea');
    const counter = document.getElementById('charCount');
    if (!ta || !counter) return;
    ta.addEventListener('input', function () {
        const len = this.value.length;
        counter.textContent = len + ' / 2000';
        counter.style.color = len > 1900 ? '#EF4444' : '';
    });
})();
</script>

</body>
</html>
