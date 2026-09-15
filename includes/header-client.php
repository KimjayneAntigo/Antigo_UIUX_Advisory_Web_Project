<?php
/**
 * Top header bar for Client Portal.
 * Full-width horizontal dark navy navigation layout (matches Reference Image 3).
 */

$base = str_contains($_SERVER['SCRIPT_NAME'], '/client/') ? '../' : '';

$rawClientName   = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Client';
$clientName      = htmlspecialchars($rawClientName, ENT_QUOTES, 'UTF-8');
$clientEmail     = htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8');
$nameParts       = explode(' ', trim($rawClientName));
$clientFirstName = htmlspecialchars($nameParts[0] ?? 'Client', ENT_QUOTES, 'UTF-8');
$clientInitial   = strtoupper(substr($nameParts[0] ?? 'C', 0, 1));

// Current active nav item
$currentActive = $activePage ?? '';
if (empty($currentActive)) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($script, 'book-consultation.php')) {
        $currentActive = 'scheduling';
    } elseif (str_contains($script, 'project-detail.php')) {
        $currentActive = 'projects';
    } else {
        $currentActive = 'dashboard';
    }
}

$navItems = [
    ['Dashboard',  $base . 'client/dashboard.php',          'dashboard'],
    ['Projects',   $base . 'client/dashboard.php#projects', 'projects'],
    ['Scheduling', $base . 'book-consultation.php',         'scheduling'],
    ['Files',      $base . 'client/dashboard.php#files',    'files'],
];
?>
<header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md border-b border-white/10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 h-[70px] flex items-center justify-between gap-4">
        
        <!-- Left: Logo & Branding -->
        <a href="<?= $base ?>client/dashboard.php" class="flex items-center gap-3 group flex-shrink-0 text-decoration-none">
            <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center p-1.5 shadow-sm transition-transform group-hover:scale-105">
                <img src="<?= $base ?>images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex flex-col justify-center leading-none">
                <span class="font-extrabold text-[16px] tracking-wide text-white uppercase font-poppins">ANTIGO</span>
                <span class="text-[9px] font-semibold tracking-widest text-[#DDEBFF] uppercase mt-0.5 font-poppins">UI/UX ADVISORY</span>
            </div>
        </a>

        <!-- Center: Horizontal Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 h-full">
            <?php foreach ($navItems as [$label, $href, $key]): ?>
                <?php $isActive = ($currentActive === $key); ?>
                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                   class="relative h-full flex items-center text-xs tracking-wide transition-colors <?= $isActive ? 'text-white font-bold' : 'text-white/70 hover:text-white font-medium' ?>">
                    <span><?= $label ?></span>
                    <?php if ($isActive): ?>
                        <span class="absolute bottom-0 left-0 right-0 h-[2.5px] bg-[#4C6CCB] rounded-t-sm"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Right: User Pill Button with Dropdown + Standalone Logout -->
        <div class="flex items-center gap-2.5 sm:gap-3 flex-shrink-0">

            <!-- User Pill Button (Image 3) -->
            <div class="relative">
                <button type="button"
                        id="client-user-pill"
                        onclick="toggleClientDropdown(event)"
                        class="inline-flex items-center gap-2.5 px-3 py-1.5 rounded-full bg-white/10 hover:bg-white/15 border border-white/15 transition-all text-white cursor-pointer shadow-sm group">
                    <div class="w-7 h-7 rounded-full bg-[#4C6CCB] text-white font-bold text-xs flex items-center justify-center shadow-sm">
                        <?= $clientInitial ?>
                    </div>
                    <span class="text-xs font-semibold text-white tracking-wide"><?= $clientFirstName ?></span>
                    <iconify-icon icon="lucide:chevron-down" class="text-xs text-white/70 transition-transform group-hover:text-white"></iconify-icon>
                </button>

                <!-- Dropdown Menu -->
                <div id="client-user-dropdown"
                     class="hidden absolute right-0 mt-2 w-60 rounded-2xl bg-[#13224B] border border-white/15 shadow-2xl py-2 text-xs text-white z-50 animate-in fade-in zoom-in-95 duration-150">
                    <div class="px-4 py-2.5 border-b border-white/10">
                        <p class="font-bold text-white truncate"><?= $clientName ?></p>
                        <?php if (!empty($clientEmail)): ?>
                            <p class="text-[11px] text-[#DDEBFF]/80 truncate mt-0.5"><?= $clientEmail ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="py-1">
                        <!-- Settings / Profile (User Request: Settings locates/accesses profile of client) -->
                        <a href="<?= $base ?>client/dashboard.php#settings"
                           onclick="document.getElementById('client-user-dropdown').classList.add('hidden')"
                           class="flex items-center gap-2.5 px-4 py-2 hover:bg-white/10 text-white/90 hover:text-white transition-colors">
                            <iconify-icon icon="lucide:settings" class="text-sm text-[#4C6CCB]"></iconify-icon>
                            <span>Account Settings &amp; Profile</span>
                        </a>

                        <a href="<?= $base ?>client/dashboard.php#projects"
                           onclick="document.getElementById('client-user-dropdown').classList.add('hidden')"
                           class="flex items-center gap-2.5 px-4 py-2 hover:bg-white/10 text-white/90 hover:text-white transition-colors">
                            <iconify-icon icon="lucide:folder-kanban" class="text-sm text-[#6C5BB5]"></iconify-icon>
                            <span>My Projects</span>
                        </a>

                        <a href="<?= $base ?>book-consultation.php"
                           class="flex items-center gap-2.5 px-4 py-2 hover:bg-white/10 text-white/90 hover:text-white transition-colors">
                            <iconify-icon icon="lucide:calendar" class="text-sm text-emerald-400"></iconify-icon>
                            <span>Schedule Consultation</span>
                        </a>

                        <a href="<?= $base ?>client/dashboard.php#files"
                           onclick="document.getElementById('client-user-dropdown').classList.add('hidden')"
                           class="flex items-center gap-2.5 px-4 py-2 hover:bg-white/10 text-white/90 hover:text-white transition-colors">
                            <iconify-icon icon="lucide:file-text" class="text-sm text-amber-400"></iconify-icon>
                            <span>Deliverables &amp; Files</span>
                        </a>
                    </div>

                    <div class="border-t border-white/10 pt-1 mt-1">
                        <a href="<?= $base ?>logout.php"
                           class="flex items-center gap-2.5 px-4 py-2 hover:bg-red-500/20 text-red-300 hover:text-red-200 transition-colors">
                            <iconify-icon icon="lucide:log-out" class="text-sm"></iconify-icon>
                            <span>Sign Out</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Standalone Logout Button (Image 3) -->
            <a href="<?= $base ?>logout.php"
               title="Sign Out"
               class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 flex items-center justify-center text-white/80 hover:text-white transition-all">
                <iconify-icon icon="lucide:log-out" class="text-base"></iconify-icon>
            </a>

            <!-- Mobile Hamburger Toggle -->
            <button type="button"
                    onclick="document.getElementById('client-mobile-nav').classList.toggle('hidden');"
                    class="md:hidden w-9 h-9 rounded-xl flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                <iconify-icon icon="lucide:menu" class="text-xl"></iconify-icon>
            </button>
        </div>
    </div>

    <!-- Mobile Nav Dropdown -->
    <div id="client-mobile-nav" class="hidden md:hidden border-t border-white/10 bg-[#0e1938] px-4 py-3 space-y-1">
        <?php foreach ($navItems as [$label, $href, $key]): ?>
            <?php $isActive = ($currentActive === $key); ?>
            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-colors <?= $isActive ? 'bg-[#4C6CCB] text-white' : 'text-white/70 hover:text-white hover:bg-white/5' ?>">
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
        <a href="<?= $base ?>client/dashboard.php#settings"
           class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5">
            <span>Account Settings &amp; Profile</span>
            <iconify-icon icon="lucide:settings" class="text-sm text-[#4C6CCB]"></iconify-icon>
        </a>
    </div>
</header>

<script>
function toggleClientDropdown(e) {
    e.stopPropagation();
    const dd = document.getElementById('client-user-dropdown');
    if (dd) dd.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    const dd = document.getElementById('client-user-dropdown');
    const pill = document.getElementById('client-user-pill');
    if (dd && !dd.contains(e.target) && !pill?.contains(e.target)) {
        dd.classList.add('hidden');
    }
});
</script>
