<?php
/**
 * Admin Profile & Studio Account Settings
 */

require_once __DIR__ . '/../includes/auth-check-admin.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];

// Fetch current admin user record
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$adminUser = $stmt->fetch();

if (!$adminUser) {
    set_flash('error', 'User account not found.');
    safe_redirect('../login.php');
}

// Handle POST submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update Profile Info
    if ($action === 'update_profile') {
        if (!verify_csrf()) {
            set_flash('error', 'Security token expired. Please try again.');
            safe_redirect('profile.php');
        }

        $name    = trim($_POST['name'] ?? '');
        $company = trim($_POST['company'] ?? '');

        if (empty($name)) {
            set_flash('error', 'Full name cannot be empty.');
            safe_redirect('profile.php');
        }

        try {
            $upStmt = $pdo->prepare('UPDATE users SET name = ?, company = ? WHERE id = ?');
            $upStmt->execute([$name, $company ?: null, $userId]);

            $_SESSION['user_name'] = $name;
            set_flash('success', 'Profile information updated successfully.');
        } catch (\PDOException $e) {
            error_log('admin/profile.php update_profile error: ' . $e->getMessage());
            set_flash('error', 'Database error updating profile.');
        }
        safe_redirect('profile.php');
    }

    // Change Password
    if ($action === 'change_password') {
        if (!verify_csrf()) {
            set_flash('error', 'Security token expired. Please try again.');
            safe_redirect('profile.php');
        }

        $curPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $cfmPass = $_POST['confirm_password'] ?? '';

        if (!password_verify($curPass, $adminUser['password_hash'])) {
            set_flash('error', 'Current password is incorrect.');
            safe_redirect('profile.php');
        }

        if (strlen($newPass) < 8) {
            set_flash('error', 'New password must be at least 8 characters long.');
            safe_redirect('profile.php');
        }

        if ($newPass !== $cfmPass) {
            set_flash('error', 'New password and confirmation do not match.');
            safe_redirect('profile.php');
        }

        try {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $passStmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $passStmt->execute([$newHash, $userId]);

            set_flash('success', 'Password updated successfully.');
        } catch (\PDOException $e) {
            error_log('admin/profile.php change_password error: ' . $e->getMessage());
            set_flash('error', 'Database error updating password.');
        }
        safe_redirect('profile.php');
    }
}

// Quick stats for profile overview
$totalProjectsCount = 0;
$totalInquiriesCount = 0;
try {
    $totalProjectsCount  = (int) $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $totalInquiriesCount = (int) $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
} catch (\Throwable $e) {
    // Silently ignore
}

$pageTitle = 'Admin Profile & Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/head-common.php'; ?>
</head>
<body class="min-h-screen bg-[#F4F6F8] text-[#13224B]">

    <!-- Horizontal Top Navigation Bar -->
    <?php 
      $activePage = 'settings';
      require_once __DIR__ . '/../includes/header-admin.php'; 
    ?>

    <main class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Flash Messages -->
        <?= render_flash() ?>

        <!-- Page Header/redirection to dashboard -->
        <!-- <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[rgba(19,34,75,0.08)] pb-5">
            <div>
                <h1 class="text-2xl font-extrabold text-[#13224B]">Studio Admin Profile &amp; Settings</h1>
                <p class="text-xs text-[#8890AA] mt-1">Manage your administrator account credentials, studio identity, and security preferences.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="../admin-dashboard.php" class="px-4 py-2 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4b4b4b] bg-white hover:bg-gray-50 transition-colors flex items-center gap-1.5 shadow-sm">
                    <iconify-icon icon="lucide:arrow-left" class="text-sm"></iconify-icon>
                    <span>Back to Dashboard</span>
                </a>
            </div> -->
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            
            <!-- Left: Identity Card -->
            <div class="card p-6 sm:p-8 space-y-6 border border-[rgba(19,34,75,0.08)]">
                <div class="flex flex-col items-center text-center">
                    <div class="relative mb-4">
                        <img src="../images/profile.png"
                             alt="<?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8') ?>"
                             class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-lg ring-2 ring-[#4C6CCB]/20">
                        <span class="absolute bottom-1 right-1 w-4 h-4 rounded-full bg-emerald-500 ring-2 ring-white" title="Active"></span>
                    </div>

                    <h2 class="text-lg font-bold text-[#13224B]"><?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="text-xs text-[#8890AA] mt-0.5"><?= htmlspecialchars($adminUser['email'], ENT_QUOTES, 'UTF-8') ?></p>
                    
                    <div class="mt-3">
                        <span class="px-3 py-1 rounded-full text-[11px] font-extrabold bg-[#DDEBFF] text-[#13224B]">
                            Studio Admin &amp; Lead Consultant
                        </span>
                    </div>
                </div>

                <hr class="border-[rgba(19,34,75,0.08)]">

                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-1">
                        <span class="text-[#8890AA] font-medium">Company / Studio</span>
                        <strong class="text-[#13224B]"><?= htmlspecialchars($adminUser['company'] ?? 'Antigo UI/UX Advisory', ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#8890AA] font-medium">Account Role</span>
                        <strong class="text-emerald-700 font-bold uppercase tracking-wider text-[10px]">Super Administrator</strong>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#8890AA] font-medium">Total Client Projects</span>
                        <strong class="text-[#13224B]"><?= $totalProjectsCount ?></strong>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-[#8890AA] font-medium">Total Inquiries</span>
                        <strong class="text-[#13224B]"><?= $totalInquiriesCount ?></strong>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-[#DDEBFF]/40 border border-[#4C6CCB]/20 flex items-start gap-3 text-xs text-[#13224B]">
                    <iconify-icon icon="lucide:shield-check" class="text-lg text-[#4C6CCB] flex-shrink-0 mt-0.5"></iconify-icon>
                    <p class="leading-relaxed">Your administrator session has full access to lead pipelines, client project workspaces, deliverables, and payment verifications.</p>
                </div>
            </div>

            <!-- Right: Settings Forms -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Profile Info Form -->
                <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg">
                            <iconify-icon icon="lucide:user-check"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#13224B]">Studio Profile Information</h3>
                            <p class="text-[11px] text-[#8890AA]">Update your public administrator display name and studio affiliation.</p>
                        </div>
                    </div>

                    <form method="POST" action="profile.php" class="space-y-4">
                        <input type="hidden" name="action" value="update_profile">
                        <?= csrf_input() ?>

                        <div>
                            <label for="admin_name" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Full Name *</label>
                            <input type="text" id="admin_name" name="name" required
                                   value="<?= htmlspecialchars($adminUser['name'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
                        </div>

                        <div>
                            <label for="admin_company" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Studio / Company Name</label>
                            <input type="text" id="admin_company" name="company"
                                   value="<?= htmlspecialchars($adminUser['company'] ?? 'Antigo UI/UX Advisory', ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
                        </div>

                        <div>
                            <label for="admin_email" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Primary Email Address</label>
                            <input type="email" id="admin_email" disabled
                                   value="<?= htmlspecialchars($adminUser['email'], ENT_QUOTES, 'UTF-8') ?>"
                                   class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.08)] bg-gray-100 text-xs font-semibold text-gray-500 cursor-not-allowed">
                            <p class="text-[10px] text-[#8890AA] mt-1">Administrator email is linked to database root credentials.</p>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-md hover:opacity-95 transition-all"
                                    style="background: linear-gradient(135deg, #4C6CCB, #6C5BB5);">
                                Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Update Form -->
                <div class="card p-6 sm:p-8 border border-[rgba(19,34,75,0.08)]">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg">
                            <iconify-icon icon="lucide:lock"></iconify-icon>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[#13224B]">Change Admin Password</h3>
                            <p class="text-[11px] text-[#8890AA]">Ensure your studio command center is secured with a strong password.</p>
                        </div>
                    </div>

                    <form method="POST" action="profile.php" class="space-y-4">
                        <input type="hidden" name="action" value="change_password">
                        <?= csrf_input() ?>

                        <div>
                            <label for="admin_cur_pass" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Current Password *</label>
                            <input type="password" id="admin_cur_pass" name="current_password" required
                                   placeholder="••••••••"
                                   class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="admin_new_pass" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">New Password *</label>
                                <input type="password" id="admin_new_pass" name="new_password" required minlength="8"
                                       placeholder="Min. 8 characters"
                                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
                            </div>
                            <div>
                                <label for="admin_cfm_pass" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-1.5">Confirm New Password *</label>
                                <input type="password" id="admin_cfm_pass" name="confirm_password" required minlength="8"
                                       placeholder="Repeat new password"
                                       class="w-full px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-semibold text-[#13224B] focus:outline-none focus:border-[#4C6CCB]">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit"
                                    class="px-6 py-2.5 rounded-xl text-xs font-bold text-white shadow-md hover:opacity-95 transition-all bg-[#13224B] hover:bg-[#1a2f68]">
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