<?php
/**
 * Top header bar for Client Portal.
 */

$base = str_contains($_SERVER['SCRIPT_NAME'], '/client/') ? '../' : '';
$rawClientName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Demo Client';
$clientName    = htmlspecialchars($rawClientName, ENT_QUOTES, 'UTF-8');
$clientEmail   = htmlspecialchars($_SESSION['email'] ?? 'demo@client.com', ENT_QUOTES, 'UTF-8');
$parts         = explode(' ', trim($rawClientName));
$clientInitials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
?>
<header class="w-full bg-white border-b border-[rgba(19,34,75,0.08)] sticky top-0 z-40 shadow-sm">
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 h-[68px] flex items-center justify-between gap-4">
        <!-- Left: Page Heading -->
        <div class="flex items-center gap-3 min-w-0">
            <div class="min-w-0">
                <h1 class="text-base sm:text-lg font-extrabold text-[#13224B] truncate leading-tight">
                    <?= htmlspecialchars($pageHeading ?? 'Client Workspace', ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <?php if (!empty($pageSubheading)): ?>
                    <p class="text-[11px] text-[#8890AA] truncate hidden sm:block">
                        <?= htmlspecialchars($pageSubheading, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right New Project, Avatar, Logout -->
        <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
            <a href="<?= $base ?>inquiry.php"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full text-xs font-bold text-white shadow-sm transition-all hover:opacity-90"
               style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                <iconify-icon icon="lucide:plus" class="text-sm"></iconify-icon>
                <span>Start New Project</span>
            </a>

            <div class="h-6 w-[1px] bg-[rgba(19,34,75,0.08)] hidden sm:block"></div>

            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white flex items-center justify-center font-bold text-xs shadow-sm">
                    <?= $clientInitials ?>
                </div>
                <div class="hidden lg:flex flex-col text-left">
                    <span class="text-xs font-bold text-[#13224B] leading-none"><?= $clientName ?></span>
                    <span class="text-[10px] text-[#4C6CCB] font-semibold mt-0.5">Client Portal</span>
                </div>
            </div>

            <a href="<?= $base ?>logout.php"
               title="Log Out"
               class="w-9 h-9 rounded-xl border border-[rgba(19,34,75,0.1)] flex items-center justify-center text-[#8890AA] hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-colors">
                <iconify-icon icon="lucide:log-out" class="text-base"></iconify-icon>
            </a>
        </div>
    </div>
</header>
