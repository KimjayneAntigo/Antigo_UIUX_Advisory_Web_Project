<?php
/**
 * includes/functions.php
 * Shared utility functions for the Antigo UI/UX Advisory Web App.
 */

require_once __DIR__ . '/routing.php';

/**
 * Trim and HTML-encode a string to prevent XSS.
 */
function sanitize_input(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Store a flash message in the session.
 *
 * @param string $msg  The message text.
 * @param string $type One of: success | error | warning | info
 */
function flash_message(string $msg, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $msg, 'type' => $type];
}

/**
 * Retrieve and clear the flash message from the session.
 *
 * @return array{message:string,type:string}|null
 */
function get_flash_message(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Render a Tailwind-styled flash banner HTML string, or empty string if none.
 */
function render_flash(): string
{
    $flash = get_flash_message();
    if ($flash === null) {
        return '';
    }

    $type    = $flash['type'];
    $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    $configs = [
        'success' => [
            'bg'     => 'bg-emerald-50',
            'border' => 'border-emerald-200',
            'text'   => 'text-emerald-700',
            'icon'   => 'lucide:check-circle-2',
        ],
        'error'   => [
            'bg'     => 'bg-red-50',
            'border' => 'border-red-200',
            'text'   => 'text-red-600',
            'icon'   => 'lucide:alert-circle',
        ],
        'warning' => [
            'bg'     => 'bg-amber-50',
            'border' => 'border-amber-200',
            'text'   => 'text-amber-700',
            'icon'   => 'lucide:alert-triangle',
        ],
        'info'    => [
            'bg'     => '',   // custom inline
            'border' => '',
            'text'   => '',
            'icon'   => 'lucide:info',
        ],
    ];

    // Fallback to info
    if (!array_key_exists($type, $configs)) {
        $type = 'info';
    }

    $c = $configs[$type];

    if ($type === 'info') {
        // Use brand colours via inline style
        $styleAttr  = ' style="background:#DDEBFF;border-color:#4C6CCB;color:#13224B;"';
        $classAttr  = 'flex items-center gap-3 border rounded-lg px-4 py-3 mb-4 text-sm font-medium';
        $iconStyle  = ' style="color:#4C6CCB;"';
    } else {
        $styleAttr  = '';
        $iconStyle  = '';
        $classAttr  = "flex items-center gap-3 border {$c['bg']} {$c['border']} {$c['text']} rounded-lg px-4 py-3 mb-4 text-sm font-medium";
    }

    return <<<HTML
<div class="{$classAttr}"{$styleAttr} role="alert">
  <span class="iconify text-lg flex-shrink-0" data-icon="{$c['icon']}"{$iconStyle}></span>
  <span>{$message}</span>
</div>
HTML;
}

/**
 * Format a number as USD currency.
 *
 * @param int|float $amount
 * @return string  e.g. "$250,000"
 */
function format_currency(int|float $amount): string
{
    return '$' . number_format((float)$amount, 0);
}

/**
 * Return a human-readable "time ago" string.
 *
 * @param string $datetime MySQL DATETIME string or any strtotime-compatible value.
 */
function time_ago(string $datetime): string
{
    $time  = strtotime($datetime);
    if ($time === false) {
        return $datetime;
    }
    $diff  = time() - $time;

    if ($diff < 60) {
        return 'just now';
    }
    if ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        return $mins . ' min ago';
    }
    if ($diff < 86400) {
        $hrs = (int)floor($diff / 3600);
        return $hrs . ' hr ago';
    }
    if ($diff < 604800) {   // 7 days
        $days = (int)floor($diff / 86400);
        return $days . ' days ago';
    }
    return date('M j, Y', $time);
}

/**
 * Return a styled <span> badge for a given status string.
 *
 * @param string $status  e.g. 'new', 'converted', 'confirmed', 'in_design'
 */
function status_badge(string $status): string
{
    // Map status → [bg, text, border?]
    $map = [
        'new'        => ['bg' => 'bg-amber-100',   'text' => 'text-amber-800'],
        'reviewed'   => ['bg' => '',               'text' => 'text-[#13224B]',  'inline' => 'background:#DDEBFF;'],
        'contacted'  => ['bg' => '',               'text' => 'text-[#4C6CCB]',  'inline' => 'background:#DDEBFF;'],
        'converted'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800'],
        'lost'       => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600'],
        'pending'    => ['bg' => 'bg-amber-100',   'text' => 'text-amber-800'],
        'confirmed'  => ['bg' => '',               'text' => 'text-[#4C6CCB]',  'inline' => 'background:#DDEBFF;'],
        'completed'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800'],
        'cancelled'  => ['bg' => 'bg-red-100',     'text' => 'text-red-600'],
        'in_design'  => ['bg' => 'bg-purple-100',  'text' => 'text-purple-700'],
    ];

    $cfg    = $map[$status] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
    $label  = htmlspecialchars(ucfirst(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8');
    $bgCls  = $cfg['bg'] ?? '';
    $txtCls = $cfg['text'] ?? '';
    $inline = isset($cfg['inline']) ? ' style="' . $cfg['inline'] . '"' : '';

    return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {$bgCls} {$txtCls}\"{$inline}>{$label}</span>";
}

/**
 * Convenience alias for flash_message with ($type, $msg) parameter order.
 */
function set_flash(string $type, string $msg): void
{
    flash_message($msg, $type);
}

/**
 * Safe HTTP header redirect with immediate exit.
 */
function safe_redirect(string $url): void
{
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    }
    exit;
}

/**
 * Format bytes into readable string (B, KB, MB, GB).
 */
function format_filesize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    }
    if ($bytes < 1073741824) {
        return round($bytes / 1048576, 1) . ' MB';
    }
    return round($bytes / 1073741824, 1) . ' GB';
}

/**
 * Generate or retrieve a CSRF token for the current session.
 */
function generate_csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify that a submitted CSRF token matches the session token.
 */
function verify_csrf_token(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Render a hidden HTML input containing the CSRF token.
 */
function csrf_input(): string
{
    $token = htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Convert an existing inquiry into an active project in a database transaction.
 *
 * @param PDO $pdo
 * @param int $inquiryId
 * @return array{success:bool, project_id?:int, project_code?:string, error?:string}
 */
function convert_inquiry_to_project(PDO $pdo, int $inquiryId): array
{
    if ($inquiryId <= 0) {
        return ['success' => false, 'error' => 'Invalid inquiry reference.'];
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM inquiries WHERE id = ? LIMIT 1');
        $stmt->execute([$inquiryId]);
        $inq = $stmt->fetch();

        if (!$inq) {
            return ['success' => false, 'error' => 'Inquiry not found.'];
        }

        // Find matching user_id if inquiry doesn't have one
        $userId = $inq['user_id'] ?? null;
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
        $serviceName       = !empty($inq['service']) ? $inq['service'] : (!empty($inq['project_type']) ? $inq['project_type'] : 'UI/UX Design');
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

        $notes = "Converted from Inquiry ref: " . ($inq['ref_code'] ?? ('INQ-' . $inquiryId)) . ".\nDescription: " . ($inq['description'] ?? '');
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
        $upInqStmt = $pdo->prepare('UPDATE inquiries SET status = \'converted\' WHERE id = ?');
        $upInqStmt->execute([$inquiryId]);

        // Copy inquiry file as initial project file if one was uploaded
        if (!empty($inq['file_name'])) {
            $inqFilePath = __DIR__ . '/../uploads/inquiries/' . $inq['file_name'];
            if (file_exists($inqFilePath)) {
                $projUploadDir = __DIR__ . '/../uploads/projects/' . $newProjectId . '/';
                if (!is_dir($projUploadDir)) {
                    mkdir($projUploadDir, 0755, true);
                }
                $newFileName = 'inquiry_' . $inq['file_name'];
                $projDestPath = $projUploadDir . $newFileName;
                if (@copy($inqFilePath, $projDestPath)) {
                    $relPath = 'uploads/projects/' . $newProjectId . '/' . $newFileName;
                    $fileSizeStr = format_filesize((int) filesize($projDestPath));
                    $fStmt = $pdo->prepare(
                        'INSERT INTO project_files (project_id, uploaded_by, name, size, file_path, uploaded_at)
                         VALUES (?, ?, ?, ?, ?, NOW())'
                    );
                    $fStmt->execute([$newProjectId, $userId, $inq['file_name'], $fileSizeStr, $relPath]);
                }
            }
        }

        // Add initial system message
        $msgStmt = $pdo->prepare(
            'INSERT INTO project_messages (project_id, sender, role, message, created_at)
             VALUES (?, \'Kimberly Jayne Antigo\', \'designer\', ?, NOW())'
        );
        $welcomeMsg = "Welcome to your project workspace! We have set up your project based on your inquiry. Let's build something extraordinary together.";
        $msgStmt->execute([$newProjectId, $welcomeMsg]);

        $pdo->commit();

        return [
            'success'      => true,
            'project_id'   => $newProjectId,
            'project_code' => $projectCode,
        ];
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('convert_inquiry_to_project error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Database error during project conversion: ' . $e->getMessage()];
    }
}


