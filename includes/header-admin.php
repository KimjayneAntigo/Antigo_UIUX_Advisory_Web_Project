<?php
/**
 * Top header bar for Admin portal.
 * Full-width horizontal dark navy navigation layout.
 */

$base = str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? '../' : '';

$rawAdminName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Kimberly Jayne Antigo';
$adminName    = htmlspecialchars($rawAdminName, ENT_QUOTES, 'UTF-8');
$adminEmail   = htmlspecialchars($_SESSION['email'] ?? 'admin@antigo.com', ENT_QUOTES, 'UTF-8');
$adminRole    = htmlspecialchars($_SESSION['role'] ?? 'admin', ENT_QUOTES, 'UTF-8');

// Dynamic pending badge counts
$pendingPaymentsCount  = 0;
$pendingInquiriesCount = 0;
if (isset($pdo)) {
    try {
        $pendingPaymentsCount  = (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
        $pendingInquiriesCount = (int) $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")->fetchColumn();
    } catch (\Throwable $e) {
        // Silently skip if query fails
    }
}

// Current active nav item
$currentActive = $activePage ?? '';
if (empty($currentActive)) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (str_contains($script, 'inquiries.php')) {
        $currentActive = 'inquiries';
    } elseif (str_contains($script, 'payments.php')) {
        $currentActive = 'payments';
    } elseif (str_contains($script, 'bookings.php')) {
        $currentActive = 'bookings';
    } elseif (str_contains($script, 'projects.php') || str_contains($script, 'project-detail.php') || str_contains($script, 'admin-project.php')) {
        $currentActive = 'projects';
    } elseif (str_contains($script, 'clients.php')) {
        $currentActive = 'clients';
    } elseif (str_contains($script, 'profile.php')) {
        $currentActive = 'settings';
    } else {
        $currentActive = 'overview';
    }
}

$navItems = [
    ['Overview',  $base . 'admin-dashboard.php', 'overview'],
    ['Inquiries', $base . 'admin/inquiries.php', 'inquiries'],
    ['Payments',  $base . 'admin/payments.php',  'payments'],
    ['Bookings',  $base . 'admin/bookings.php',  'bookings'],
    ['Projects',  $base . 'admin/projects.php',  'projects'],
    ['Clients',   $base . 'admin/clients.php',   'clients'],
];
?>
<header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md border-b border-white/10">
    <div class="max-w-[1536px] mx-auto px-4 sm:px-6 lg:px-8 h-[70px] flex items-center justify-between gap-4">
        
        <!-- Left: Logo & Branding -->
        <a href="<?= $base ?>admin-dashboard.php" class="flex items-center gap-3 group flex-shrink-0 text-decoration-none">
            <div class="w-9 h-9 rounded-xl bg-white flex items-center justify-center p-1.5 shadow-sm transition-transform group-hover:scale-105">
                <img src="<?= $base ?>images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="w-full h-full object-contain">
            </div>
            <div class="flex flex-col justify-center leading-none">
                <span class="font-extrabold text-[16px] tracking-wide text-white uppercase font-poppins">ANTIGO</span>
                <span class="text-[9px] font-semibold tracking-widest text-[#DDEBFF] uppercase mt-0.5 font-poppins">UI/UX ADVISORY</span>
            </div>
        </a>

        <!-- Center: Horizontal Navigation Links -->
        <nav class="hidden lg:flex items-center gap-7 h-full">
            <?php foreach ($navItems as [$label, $href, $key]): ?>
                <?php
                    $isActive = ($currentActive === $key) || ($key === 'overview' && $currentActive === 'dashboard');
                    $badgeCount = 0;
                    if ($key === 'inquiries') $badgeCount = $pendingInquiriesCount;
                    if ($key === 'payments')  $badgeCount = $pendingPaymentsCount;
                ?>
                <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                   data-nav="<?= $key ?>"
                   class="nav-link-top group relative h-full flex items-center text-xs font-semibold tracking-wide transition-colors <?= $isActive ? 'active text-white font-bold' : 'text-white/70 hover:text-white' ?>">
                    <span><?= $label ?></span>
                    <?php if ($badgeCount > 0): ?>
                        <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-amber-400 text-amber-950 leading-none">
                            <?= $badgeCount ?>
                        </span>
                    <?php endif; ?>
                    <span class="nav-indicator absolute bottom-0 left-0 right-0 h-[2.5px] bg-[#4C6CCB] rounded-t-sm transition-all duration-200 pointer-events-none <?= $isActive ? 'opacity-100 scale-x-100' : 'opacity-0 scale-x-0 group-hover:opacity-100 group-hover:scale-x-100' ?>"></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Right: Admin Profile Block + Logout -->
        <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
            
            <div class="h-6 w-[1px] bg-white/15 hidden sm:block"></div>

            <!-- Profile & Settings Access -->
            <a href="<?= $base ?>admin/profile.php"
               title="Admin Profile & Settings"
               class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-white/10 transition-all text-left group <?= $currentActive === 'settings' ? 'ring-1 ring-[#4C6CCB] bg-white/5' : '' ?>">
                <div class="hidden sm:flex flex-col text-right leading-tight">
                    <span class="text-xs font-bold text-white group-hover:text-[#DDEBFF] transition-colors"><?= $adminName ?></span>
                    <span class="text-[10px] text-white/60 font-semibold mt-0.5">Studio Admin</span>
                </div>
                <div class="relative">
                    <img src="<?= $base ?>images/profile.png"
                         alt="<?= $adminName ?>"
                         class="w-9 h-9 rounded-full object-cover border-2 border-white/20 shadow-sm group-hover:border-[#4C6CCB] transition-all">
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-[#13224B]"></span>
                </div>
            </a>

            <!-- Logout Icon Button -->
            <a href="<?= $base ?>logout.php"
               title="Log Out"
               class="w-9 h-9 rounded-xl flex items-center justify-center text-white/70 hover:text-white hover:bg-red-500/20 hover:border-red-400/30 transition-all border border-transparent">
                <iconify-icon icon="lucide:log-out" class="text-lg"></iconify-icon>
            </a>

            <!-- Mobile Hamburger Toggle -->
            <button type="button"
                    onclick="document.getElementById('admin-mobile-nav').classList.toggle('hidden');"
                    class="lg:hidden w-9 h-9 rounded-xl flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-colors">
                <iconify-icon icon="lucide:menu" class="text-xl"></iconify-icon>
            </button>
        </div>
    </div>

    <!-- Mobile Nav Dropdown -->
    <div id="admin-mobile-nav" class="hidden lg:hidden border-t border-white/10 bg-[#0e1938] px-4 py-3 space-y-1">
        <?php foreach ($navItems as [$label, $href, $key]): ?>
            <?php
                $isActive = ($currentActive === $key) || ($key === 'overview' && $currentActive === 'dashboard');
                $badgeCount = 0;
                if ($key === 'inquiries') $badgeCount = $pendingInquiriesCount;
                if ($key === 'payments')  $badgeCount = $pendingPaymentsCount;
            ?>
            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
               class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold transition-colors <?= $isActive ? 'bg-[#4C6CCB] text-white' : 'text-white/70 hover:text-white hover:bg-white/5' ?>">
                <span><?= $label ?></span>
                <?php if ($badgeCount > 0): ?>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-amber-950">
                        <?= $badgeCount ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        <a href="<?= $base ?>admin/profile.php"
           class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-white/70 hover:text-white hover:bg-white/5 <?= $currentActive === 'settings' ? 'bg-[#4C6CCB] text-white' : '' ?>">
            <span>Settings &amp; Profile</span>
            <iconify-icon icon="lucide:settings" class="text-sm"></iconify-icon>
        </a>
    </div>
</header>

<script>
(function() {
    function syncAdminHashNav() {
        const hash = window.location.hash;
        if (!hash) return;
        const navLinks = document.querySelectorAll('header nav a[data-nav]');
        let matched = false;
        navLinks.forEach(link => {
            const href = link.getAttribute('href') || '';
            const linkHash = href.includes('#') ? '#' + href.split('#')[1] : '';
            const ind = link.querySelector('.nav-indicator');
            if (linkHash && linkHash === hash) {
                matched = true;
                link.classList.add('active', 'text-white', 'font-bold');
                link.classList.remove('text-white/70');
                if (ind) {
                    ind.classList.add('opacity-100', 'scale-x-100');
                    ind.classList.remove('opacity-0', 'scale-x-0');
                }
            } else if (linkHash) {
                link.classList.remove('active', 'font-bold');
                link.classList.add('text-white/70');
                if (ind) {
                    ind.classList.remove('opacity-100', 'scale-x-100');
                    ind.classList.add('opacity-0', 'scale-x-0');
                }
            }
        });
        if (matched) {
            // Unset overview link active state if another hash link matched
            const overviewLink = document.querySelector('header nav a[data-nav="overview"]');
            if (overviewLink) {
                overviewLink.classList.remove('active', 'font-bold');
                overviewLink.classList.add('text-white/70');
                const ind = overviewLink.querySelector('.nav-indicator');
                if (ind) {
                    ind.classList.remove('opacity-100', 'scale-x-100');
                    ind.classList.add('opacity-0', 'scale-x-0');
                }
            }
        }
    }
    window.addEventListener('hashchange', syncAdminHashNav);
    window.addEventListener('DOMContentLoaded', syncAdminHashNav);
    if (document.readyState !== 'loading') syncAdminHashNav();
})();
</script>