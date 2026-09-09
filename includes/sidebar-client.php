<?php

// pages inside /client/ must step up one level
$base = str_contains($_SERVER['SCRIPT_NAME'], '/client/') ? '../' : '';

// Current user info from session 
$rawName   = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Demo Client';
$userName  = htmlspecialchars($rawName, ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($_SESSION['email'] ?? 'demo@client.com', ENT_QUOTES, 'UTF-8');
// Build initials (up to 2 chars)
$nameParts = explode(' ', trim($rawName));
$initials  = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

// Nav items
$navItems = [
    ['Dashboard',   'lucide:layout-dashboard', $base . 'client/dashboard.php', 'dashboard'],
    ['My Projects', 'lucide:folder-kanban',    $base . 'client/dashboard.php#projects', 'projects'],
    ['Messages',    'lucide:message-circle',   $base . 'client/dashboard.php#messages', 'messages'],
    ['Files',       'lucide:file-archive',     $base . 'client/dashboard.php#files',    'files'],
    ['Settings',    'lucide:settings-2',       $base . 'client/dashboard.php#settings', 'settings'],
];
?>
<aside class="sidebar">

  <!-- Logo  -->
  <div class="flex items-center gap-3 px-5 pt-6 pb-4">
    <div class="logo-mark">A</div>
    <div>
      <div class="logo-text-word">Antigo</div>
      <div class="logo-text-sub">UI/UX Advisory</div>
    </div>
  </div>

  <!-- Client Portal badge  -->
  <div class="mx-4 mb-4">
    <span
      class="block text-center text-xs font-semibold rounded-md py-1 px-2"
      style="background:rgba(76,108,203,0.25);color:#93aee8;letter-spacing:0.06em;"
    >
      CLIENT PORTAL
    </span>
  </div>

  <hr style="border-color:rgba(255,255,255,0.08);margin:0 1rem 0.75rem;" />

  <!-- Navigation -->
  <nav class="flex-1 px-3 space-y-0.5">
    <?php foreach ($navItems as [$label, $icon, $href, $key]): ?>
      <a
        href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
        class="sidebar-link<?= (($activePage ?? '') === $key ? ' active' : '') ?>"
      >
        <iconify-icon icon="<?= $icon ?>" width="18" height="18"></iconify-icon>
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- User footer -->
  <div class="px-4 pb-5 pt-4 mt-auto" style="border-top:1px solid rgba(255,255,255,0.08);">
    <div class="flex items-center gap-3 mb-3">
      <!-- Avatar with initials -->
      <div
        class="flex items-center justify-center rounded-full text-xs font-bold text-white flex-shrink-0"
        style="width:34px;height:34px;background:linear-gradient(135deg,#4C6CCB,#6C5BB5);"
      >
        <?= $initials ?>
      </div>
      <div class="overflow-hidden">
        <p class="text-sm font-semibold text-white truncate leading-tight"><?= $userName ?></p>
        <p class="text-xs truncate" style="color:rgba(255,255,255,0.45);"><?= $userEmail ?></p>
      </div>
    </div>
    <a
      href="<?= $base ?>logout.php"
      class="sidebar-link w-full text-xs"
      style="color:rgba(255,100,100,0.75);"
      onmouseover="this.style.color='#f87171';this.style.background='rgba(239,68,68,0.1)'"
      onmouseout="this.style.color='rgba(255,100,100,0.75)';this.style.background=''"
    >
      <iconify-icon icon="lucide:log-out" width="16" height="16"></iconify-icon>
      Log out
    </a>
  </div>

</aside>
