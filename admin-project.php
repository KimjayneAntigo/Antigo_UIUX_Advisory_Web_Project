<?php
require_once 'config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/routing.php';

const STAGE_MAP = [
    1 => ['phase_name' => 'Discovery & Research', 'progress' => 20,  'status' => 'Discovery',   'status_type' => 'pending'],
    2 => ['phase_name' => 'Wireframing',           'progress' => 40,  'status' => 'Wireframing', 'status_type' => 'in_design'],
    3 => ['phase_name' => 'UI/UX Design',          'progress' => 60,  'status' => 'UI Design',   'status_type' => 'in_design'],
    4 => ['phase_name' => 'Prototyping',            'progress' => 80,  'status' => 'Prototyping', 'status_type' => 'in_design'],
    5 => ['phase_name' => 'Delivered',              'progress' => 100, 'status' => 'Delivered',   'status_type' => 'completed'],
];

// Validate ?id param (supports integer id or project_code like PRJ-3001)
$rawId = trim($_GET['id'] ?? '');
if ($rawId === '') {
    set_flash('error', 'No project specified.');
    safe_redirect('admin-dashboard.php');
}

try {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? OR project_code = ? LIMIT 1');
    $stmt->execute([is_numeric($rawId) ? (int) $rawId : 0, $rawId]);
    $project = $stmt->fetch();
} catch (\PDOException $e) {
    error_log('admin-project error: ' . $e->getMessage());
    set_flash('error', 'Database error loading project.');
    safe_redirect('admin-dashboard.php');
}

if (!$project) {
    set_flash('error', 'Project not found.');
    safe_redirect('admin-dashboard.php');
}

$projectId = (int) $project['id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    // Update stage/phase
    if ($action === 'update_stage') {
        $stage = (int) ($_POST['stage'] ?? 0);
        if ($stage >= 1 && $stage <= 5) {
            $map = STAGE_MAP[$stage];
            $uStmt = $pdo->prepare(
                'UPDATE projects
                 SET current_phase = ?, phase_name = ?, progress = ?, status = ?, status_type = ?, updated_at = NOW()
                 WHERE id = ?'
            );
            $uStmt->execute([$stage, $map['phase_name'], $map['progress'], $map['status'], $map['status_type'], $projectId]);
            set_flash('success', "Stage updated to Phase {$stage}: {$map['phase_name']}.");
        }
        safe_redirect("admin-project.php?id={$projectId}");
    }

    // Update notes
    if ($action === 'update_notes') {
        $notes = trim($_POST['notes'] ?? '');
        $uStmt = $pdo->prepare('UPDATE projects SET internal_notes = ?, updated_at = NOW() WHERE id = ?');
        $uStmt->execute([$notes, $projectId]);
        set_flash('success', 'Internal notes saved.');
        safe_redirect("admin-project.php?id={$projectId}");
    }

    // Post message
    if ($action === 'send_message') {
        $msg = trim($_POST['message'] ?? '');
        if ($msg !== '') {
            $sender = $_SESSION['user_name'] ?? 'Kimberly Jayne Antigo';
            $mStmt = $pdo->prepare('INSERT INTO project_messages (project_id, sender, role, message, created_at) VALUES (?, ?, \'designer\', ?, NOW())');
            $mStmt->execute([$projectId, $sender, $msg]);
            set_flash('success', 'Message sent to client.');
        }
        safe_redirect("admin-project.php?id={$projectId}");
    }

    // Upload deliverable file
    if ($action === 'upload_file' && isset($_FILES['file'])) {
        $file = $_FILES['file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['pdf', 'png', 'jpg', 'jpeg', 'zip', 'fig', 'svg'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExts, true)) {
                $uploadDir = __DIR__ . '/uploads/projects/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $safeName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file['name']));
                $fileName = time() . '_' . $safeName;
                $destPath = $uploadDir . $fileName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $sizeFormatted = round($file['size'] / (1024 * 1024), 1) . ' MB';
                    if ($file['size'] < 1024 * 1024) {
                        $sizeFormatted = round($file['size'] / 1024, 1) . ' KB';
                    }
                    $fStmt = $pdo->prepare('INSERT INTO project_files (project_id, uploaded_by, name, size, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())');
                    $fStmt->execute([$projectId, (int) $_SESSION['user_id'], $file['name'], $sizeFormatted, 'uploads/projects/' . $fileName]);
                    set_flash('success', "File \"{$file['name']}\" uploaded successfully.");
                } else {
                    set_flash('error', 'Failed to save uploaded file.');
                }
            } else {
                set_flash('error', 'Invalid file format. Allowed: pdf, png, jpg, zip, fig, svg.');
            }
        }
        safe_redirect("admin-project.php?id={$projectId}");
    }
}

// Fetch files
$filesStmt = $pdo->prepare('SELECT * FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC');
$filesStmt->execute([$projectId]);
$files = $filesStmt->fetchAll();

// Fetch messages
$messagesStmt = $pdo->prepare('SELECT * FROM project_messages WHERE project_id = ? ORDER BY created_at ASC');
$messagesStmt->execute([$projectId]);
$messages = $messagesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($project['title']) ?> | Admin Project Control</title>
    
    <!-- Google Fonts -->
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
                        navy: '#13224B',
                        violet: '#6C5BB5',
                        brandBlue: '#4C6CCB',
                        'light-blue': '#DDEBFF',
                        'light-gray': '#F4F6F8',
                        'dark-gray': '#4b4b4b',
                        'text-primary': '#13224B',
                        'text-soft': '#4b4b4b',
                        'text-faint': '#8890AA',
                        'surface-alt': '#F4F6F8',
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
            --navy: #13224B;
            --violet: #6C5BB5;
            --blue: #4C6CCB;
            --white: #FFFFFF;
            --light-blue: #DDEBFF;
            --light-gray: #F4F6F8;
            --dark-gray: #4b4b4b;
            --text: #13224B;
            --text-soft: #4b4b4b;
            --text-faint: #8890AA;
            --surface: #FFFFFF;
            --surface-alt: #F4F6F8;
            --border: rgba(19, 34, 75, 0.09);
            --grad: linear-gradient(100deg, #13224B 0%, #6C5BB5 55%, #4C6CCB 100%);
            --grad-soft: linear-gradient(135deg, #4C6CCB, #6C5BB5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-alt);
            color: var(--text);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .admin-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }

        .internal-notes-block {
            background-color: #FFF8E8;
            border-left: 4px solid #F59E0B;
            font-style: italic;
        }

        .chat-bubble-admin {
            background: var(--surface-alt);
            color: var(--navy);
            border: 1px solid var(--border);
            border-radius: 18px 18px 4px 18px;
        }

        .chat-bubble-client {
            background: var(--grad);
            color: #FFFFFF;
            border-radius: 18px 18px 18px 4px;
        }

        .select-input {
            background: #F4F6F8;
            border: 1px solid var(--border);
            color: var(--navy);
            transition: all 0.2s ease;
        }
        .select-input:focus {
            outline: none;
            border-color: var(--violet);
            background: #FFFFFF;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Admin Header -->
    <header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="admin-dashboard.php" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white/80 hover:text-white transition-colors" title="Back to Dashboard">
                    <iconify-icon icon="lucide:arrow-left" class="text-lg"></iconify-icon>
                </a>
                <div>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-[#DDEBFF]"><?= htmlspecialchars($project['category'] ?? 'UI/UX Design') ?> &middot; <?= htmlspecialchars($project['project_code']) ?></span>
                    <h1 class="text-base font-bold text-white"><?= htmlspecialchars($project['title']) ?></h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="admin-dashboard.php" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition-colors">
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-[1440px] mx-auto px-6 lg:px-10 pt-6 w-full">
        <?= render_flash() ?>
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-[1440px] w-full mx-auto px-6 lg:px-10 py-4 space-y-8">
        
        <!-- Status & Phase Management Control Bar -->
        <div class="admin-card p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#6C5BB5]">Milestone Stepper Control</span>
                    <h2 class="text-xl font-extrabold text-[#13224B] mt-0.5">Project Phase &amp; Delivery Status</h2>
                    <p class="text-xs text-[#8890AA]">Updating this phase controls what the client sees in their portal stepper in real time.</p>
                </div>

                <!-- Phase Dropdown Form -->
                <form method="POST" action="admin-project.php?id=<?= $projectId ?>" class="flex items-center gap-3">
                    <input type="hidden" name="action" value="update_stage">
                    <label class="text-xs font-bold text-[#8890AA] whitespace-nowrap">Current Phase:</label>
                    <select name="stage" onchange="this.form.submit()" class="select-input px-4 py-2.5 rounded-xl text-xs font-bold cursor-pointer">
                        <?php foreach (STAGE_MAP as $sNum => $sInfo): ?>
                            <option value="<?= $sNum ?>" <?= ((int)$project['current_phase'] === $sNum) ? 'selected' : '' ?>>
                                Phase <?= $sNum ?>: <?= htmlspecialchars($sInfo['phase_name']) ?> (<?= $sInfo['progress'] ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Visual Stepper Preview -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                <?php 
                $curPhase = (int) $project['current_phase'];
                foreach (STAGE_MAP as $step => $sInfo): 
                    $isPast = $step < $curPhase;
                    $isCurrent = $step === $curPhase;
                ?>
                    <div class="p-3 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 <?= $isPast ? 'bg-[#10b981] text-white' : ($isCurrent ? 'bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white shadow-md ring-2 ring-[#6C5BB5]/30' : 'bg-white border border-[rgba(19,34,75,0.15)] text-[#8890AA]') ?>">
                            <?php if ($isPast): ?>
                                <iconify-icon icon="lucide:check" class="text-white text-sm"></iconify-icon>
                            <?php elseif ($isCurrent): ?>
                                <span class="text-white font-bold text-xs"><?= $step ?></span>
                            <?php else: ?>
                                <span class="text-[#8890AA] text-xs font-semibold"><?= $step ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="text-[9px] uppercase font-bold text-[#8890AA]">Phase <?= $step ?></div>
                            <div class="text-xs font-bold text-[#13224B] truncate"><?= htmlspecialchars($sInfo['phase_name']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Split Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Side (7 Cols): Client Info, Deliverables & Admin Internal Notes -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Client Info Card -->
                <div class="admin-card p-6 sm:p-8">
                    <h3 class="text-base font-bold text-[#13224B] mb-4">Client Information &amp; Commercial Scope</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Client Name</span>
                            <strong class="text-sm font-bold text-[#13224B]"><?= htmlspecialchars($project['client_name'] ?? 'Direct Client') ?></strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Company</span>
                            <strong class="text-sm font-bold text-[#13224B]"><?= htmlspecialchars($project['company'] ?: 'Direct Client') ?></strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Contract Value</span>
                            <strong class="text-sm font-extrabold text-[#4C6CCB]"><?= htmlspecialchars($project['budget'] ?: '$150,000') ?></strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Due Date</span>
                            <strong class="text-sm font-bold text-[#13224B]"><?= !empty($project['due_date']) ? date('M j, Y', strtotime($project['due_date'])) : 'TBD' ?></strong>
                        </div>
                    </div>
                </div>

                <!-- ADMIN INTERNAL NOTES BLOCK -->
                <div class="admin-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-[#F59E0B]/20 text-[#946200] text-[10px] font-bold uppercase tracking-wider">
                                Studio Confidential
                            </span>
                            <h3 class="text-base font-bold text-[#13224B]">Admin Internal Notes</h3>
                        </div>
                        <span class="text-[11px] text-[#8890AA] italic">Not visible to client</span>
                    </div>
                    
                    <form method="POST" action="admin-project.php?id=<?= $projectId ?>">
                        <input type="hidden" name="action" value="update_notes">
                        <div class="internal-notes-block p-4 rounded-2xl mb-4 text-xs text-[#946200] leading-relaxed">
                            <textarea name="notes" rows="4" class="w-full bg-transparent border-none outline-none resize-none font-medium text-xs text-[#946200] italic leading-relaxed" placeholder="Add confidential studio notes, billing milestones, design strategy..."><?= htmlspecialchars($project['internal_notes'] ?? '') ?></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-white border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-[#F4F6F8] transition-colors">
                                Update Notes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Deliverables Management -->
                <div class="admin-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-[#13224B]">Deliverables Repository</h3>
                        <span class="text-xs text-[#6C5BB5] font-semibold"><?= count($files) ?> Files</span>
                    </div>

                    <div class="space-y-3 mb-5">
                        <?php if (empty($files)): ?>
                            <p class="text-xs text-[#8890AA] py-4 text-center">No deliverable files uploaded yet.</p>
                        <?php else: ?>
                            <?php foreach ($files as $f): ?>
                                <div class="p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-white border border-[rgba(19,34,75,0.08)] flex items-center justify-center text-[#6C5BB5]">
                                            <iconify-icon icon="lucide:file-text"></iconify-icon>
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-[#13224B]"><?= htmlspecialchars($f['name']) ?></div>
                                            <div class="text-[10px] text-[#8890AA]"><?= htmlspecialchars($f['size'] ?? '') ?> &middot; Uploaded <?= date('M j, Y', strtotime($f['uploaded_at'])) ?></div>
                                        </div>
                                    </div>
                                    <a href="download.php?file_id=<?= (int)$f['id'] ?>" class="text-xs text-[#4C6CCB] font-bold hover:underline">
                                        Download
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="admin-project.php?id=<?= $projectId ?>" enctype="multipart/form-data" id="adminUploadForm">
                        <input type="hidden" name="action" value="upload_file">
                        <input type="file" name="file" onchange="this.form.submit()" class="hidden" id="adminUploadInput">
                        <div class="p-4 bg-[#F4F6F8] rounded-2xl border border-dashed border-[rgba(19,34,75,0.14)] text-center cursor-pointer hover:border-[#6C5BB5] transition-colors" onclick="document.getElementById('adminUploadInput').click()">
                            <iconify-icon icon="lucide:upload" class="text-xl text-[#6C5BB5] mb-1"></iconify-icon>
                            <div class="text-xs font-bold text-[#13224B]">Upload New Deliverable Asset for Client</div>
                            <div class="text-[10px] text-[#8890AA]">Figma file, PDF report, or ZIP package</div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Side (5 Cols): Live Messaging with Client -->
            <div class="lg:col-span-5 space-y-6">
                <div class="admin-card p-6 flex flex-col h-[620px]">
                    <div class="flex items-center justify-between pb-4 border-b border-[rgba(19,34,75,0.08)] mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-[#13224B]">Client Conversation</h3>
                            <p class="text-[10px] text-[#8890AA]">Posting as <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> (Designer)</p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#DDEBFF] text-[#13224B] text-[10px] font-bold">
                            Live Thread
                        </span>
                    </div>

                    <!-- Messages View -->
                    <div id="adminMessagesContainer" class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
                        <?php if (empty($messages)): ?>
                            <p class="text-xs text-[#8890AA] text-center my-auto">No messages in conversation yet.</p>
                        <?php else: ?>
                            <?php foreach ($messages as $m): 
                                $isDesigner = ($m['role'] ?? '') === 'designer';
                            ?>
                                <div class="flex flex-col <?= $isDesigner ? 'items-end' : 'items-start' ?>">
                                    <div class="text-[10px] text-[#8890AA] mb-1 px-1"><?= htmlspecialchars($m['sender']) ?> &middot; <?= date('M j, g:i a', strtotime($m['created_at'])) ?></div>
                                    <div class="max-w-[85%] p-3 text-xs leading-relaxed <?= $isDesigner ? 'chat-bubble-admin' : 'chat-bubble-client' ?>">
                                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Post Reply Form -->
                    <form method="POST" action="admin-project.php?id=<?= $projectId ?>" class="mt-4 pt-3 border-t border-[rgba(19,34,75,0.08)] flex gap-2">
                        <input type="hidden" name="action" value="send_message">
                        <input type="text" name="message" placeholder="Reply to client as Kimberly..." required class="flex-1 px-4 py-3 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] text-xs text-[#13224B] focus:outline-none focus:border-[#6C5BB5] focus:bg-white">
                        <button type="submit" class="w-11 h-11 rounded-xl text-white flex items-center justify-center shadow-md hover:scale-105 transition-transform" style="background:var(--grad);">
                            <iconify-icon icon="lucide:send" class="text-base"></iconify-icon>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white mt-12">
        &copy; 2026 Antigo UI/UX Advisory &middot; Admin Control Panel
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.getElementById('adminMessagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
</body>
</html>
