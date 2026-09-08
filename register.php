<?php
session_start();

// Already authenticated
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin-dashboard.php' : 'client-dashboard.php'));
    exit;
}

require_once 'config/db.php';
// If already logged in, skip login and redirect to the proper dashboard.
// Only attach guest records created in this current browser session.
$pending_inquiry_id = $_SESSION['pending_link_inquiry_id'] ?? null;
$pending_booking_id = $_SESSION['pending_link_booking_id'] ?? null;

// Pre-fill name/email from the pending inquiry/booking for UX convenience
$prefill_name  = '';
$prefill_email = '';

if ($pending_inquiry_id) {
    $stmt = $pdo->prepare('SELECT name, email FROM inquiries WHERE id = ? LIMIT 1');
    $stmt->execute([$pending_inquiry_id]);
    $row = $stmt->fetch();
    if ($row) { $prefill_name = $row['name']; $prefill_email = $row['email']; }
} elseif ($pending_booking_id) {
    $stmt = $pdo->prepare('SELECT b.guest_name AS name, b.guest_email AS email FROM bookings b WHERE b.id = ? LIMIT 1');
    $stmt->execute([$pending_booking_id]);
    $row = $stmt->fetch();
    if ($row) { $prefill_name = $row['name']; $prefill_email = $row['email']; }
}

$error = '';

// POST HANDLER
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    // VALIDATION
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // DUPLICATE EMAIL CHECK 
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Redirect to login with a flash message rather than exposing the
            // error inline
            header('Location: login.php?msg=account_exists');
            exit;
        }

        // INSERT NEW USER
        $hash = password_hash($password, PASSWORD_DEFAULT);

       // Force 'client' role on the server to prevent attackers from injecting role='admin' to gain unauthorized privileges.
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_at)
             VALUES (?, ?, ?, \'client\', NOW())'
        );
        $stmt->execute([$name, $email, $hash]);
        $new_user_id = (int) $pdo->lastInsertId();

        // TRUSTED-SESSION LINKING
        // Link ONLY the session-stored IDs — NOT any historical records
        // belonging to this email address
        if ($pending_inquiry_id) {
            $stmt = $pdo->prepare('UPDATE inquiries SET user_id = ? WHERE id = ? AND user_id IS NULL');
            $stmt->execute([$new_user_id, $pending_inquiry_id]);
        }
        if ($pending_booking_id) {
            $stmt = $pdo->prepare('UPDATE bookings SET user_id = ? WHERE id = ? AND user_id IS NULL');
            $stmt->execute([$new_user_id, $pending_booking_id]);
        }

        // Clear pending-link values immediately after linking
        unset($_SESSION['pending_link_inquiry_id'], $_SESSION['pending_link_booking_id']);

      // Issue a brand-new session ID and delete the old one to prevent session hijacking.
        session_regenerate_id(true);

        $_SESSION['user_id']   = $new_user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['name']      = $name;
        $_SESSION['email']     = $email;
        $_SESSION['role']      = 'client';

       // Future task: Add CAPTCHA or request limits to block bot attacks and automated spam signups.

        header('Location: client/dashboard.php?welcome=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Antigo UI/UX Advisory</title>

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
                        navy:         '#13224B',
                        violet:       '#6C5BB5',
                        brandBlue:    '#4C6CCB',
                        'light-blue': '#DDEBFF',
                        'light-gray': '#F4F6F8',
                        'dark-gray':  '#4b4b4b',
                        'text-primary':'#13224B',
                        'text-soft':  '#4b4b4b',
                        'text-faint': '#8890AA',
                        'surface-alt':'#F4F6F8',
                    },
                    fontFamily: { poppins: ['Poppins', 'sans-serif'] }
                }
            }
        }
    </script>

    <style>
        :root {
            --navy:        #13224B;
            --violet:      #6C5BB5;
            --blue:        #4C6CCB;
            --white:       #FFFFFF;
            --light-blue:  #DDEBFF;
            --light-gray:  #F4F6F8;
            --dark-gray:   #4b4b4b;
            --text:        #13224B;
            --text-soft:   #4b4b4b;
            --text-faint:  #8890AA;
            --surface:     #FFFFFF;
            --surface-alt: #F4F6F8;
            --border:      rgba(19, 34, 75, 0.09);
            --grad:        linear-gradient(100deg, #13224B 0%, #6C5BB5 55%, #4C6CCB 100%);
            --grad-soft:   linear-gradient(135deg, #4C6CCB, #6C5BB5);
            --success:     #10b981;
            --error:       #ef4444;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-alt);
            color: var(--text);
            margin: 0; padding: 0;
        }

        .brand-gradient-panel {
            background: linear-gradient(135deg, #13224B 0%, #1c2c5e 40%, #6C5BB5 75%, #4C6CCB 100%);
        }

        .input-field {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            color: var(--navy);
            transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--violet);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(108,91,181,.12);
        }

        .btn-brand-primary {
            background: var(--grad);
            color: #FFFFFF;
            box-shadow: 0 14px 28px -10px rgba(76,108,203,.45);
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .btn-brand-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px -8px rgba(76,108,203,.6);
        }

        .logo { display:flex; align-items:center; gap:12px; text-decoration:none; }
        .logo-mark { height:38px; width:auto; max-width:54px; flex-shrink:0; object-fit:contain; }
        .logo-text { line-height:1.15; display:flex; flex-direction:column; justify-content:center; }
        .logo-text .word { font-weight:800; font-size:18px; letter-spacing:.05em; color:var(--navy); line-height:1.1; }
        .logo-text .sub  { font-size:9px; letter-spacing:.22em; color:var(--violet); text-transform:uppercase; font-weight:700; line-height:1.1; margin-top:2px; }
    </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

    <!-- Minimal Header -->
    <header class="w-full bg-white/80 backdrop-blur-md border-b border-[rgba(19,34,75,0.08)] py-4 px-6 sm:px-12 sticky top-0 z-50">
        <div class="max-w-[1360px] mx-auto flex items-center justify-between">
            <a href="home.php" class="logo">
                <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="logo-mark">
                <div class="logo-text">
                    <div class="word">ANTIGO</div>
                    <div class="sub">UI/UX ADVISORY</div>
                </div>
            </a>
            <a href="login.php" class="text-xs sm:text-sm font-semibold text-[#4b4b4b] hover:text-[#4C6CCB] transition-colors flex items-center gap-1.5">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon>
                <span>Back to Login</span>
            </a>
        </div>
    </header>

    <!-- Main Auth Section -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8 lg:p-12">
        <div class="w-full max-w-[1060px] bg-white rounded-3xl overflow-hidden border border-[rgba(19,34,75,0.08)] shadow-2xl flex flex-col lg:flex-row">

            <!-- Left: Brand Panel -->
            <div class="lg:w-5/12 brand-gradient-panel p-8 sm:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-[#6C5BB5]/30 blur-2xl pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white text-xs font-semibold backdrop-blur-sm mb-8">
                        <iconify-icon icon="lucide:user-plus" class="text-sm text-[#DDEBFF]"></iconify-icon>
                        <span>New Client Account</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-snug mb-4">
                        Track your project from day one.
                    </h2>
                    <p class="text-sm text-white/80 leading-relaxed">
                        Register to claim your submitted inquiry, access your project dashboard, and collaborate directly with Kimberly at every stage of your design journey.
                    </p>

                    <?php if ($pending_inquiry_id || $pending_booking_id): ?>
                    <!-- Pending-link notice — shown only when a session token is present -->
                    <div class="mt-8 p-4 rounded-xl bg-white/10 border border-white/20">
                        <div class="flex items-center gap-2 text-xs font-bold text-[#DDEBFF] mb-2">
                            <iconify-icon icon="lucide:link"></iconify-icon>
                            Your submission is ready to link
                        </div>
                        <p class="text-xs text-white/70 leading-relaxed">
                            After you register, your
                            <?= $pending_inquiry_id ? 'inquiry' : '' ?>
                            <?= ($pending_inquiry_id && $pending_booking_id) ? 'and ' : '' ?>
                            <?= $pending_booking_id ? 'consultation booking' : '' ?>
                            will automatically be attached to your new account.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="relative z-10 pt-8 mt-8 border-t border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center font-bold text-xs">KA</div>
                        <div>
                            <div class="text-sm font-bold">Kimberly Jayne Antigo</div>
                            <div class="text-xs text-white/70">Founder &amp; Principal Consultant</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Registration Form -->
            <div class="lg:w-7/12 p-8 sm:p-12 flex flex-col justify-between">
                <div>
                    <div class="mb-8">
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#13224B]">Create Your Account</h1>
                        <p class="text-xs sm:text-sm text-[#4b4b4b] mt-1">Client portal access — takes less than a minute</p>
                    </div>

                    <!-- PHP error banner -->
                    <?php if ($error): ?>
                    <div class="mb-5 p-3 rounded-xl bg-red-50 border border-red-200 text-red-600 text-xs font-medium flex items-center gap-2">
                        <iconify-icon icon="lucide:alert-circle"></iconify-icon>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="register.php" class="space-y-5" novalidate>

                        <!-- Full Name -->
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Full Name</label>
                            <div class="relative">
                                <input type="text" id="name" name="name" required
                                       placeholder="e.g. Maria Santos"
                                       value="<?= htmlspecialchars($_POST['name'] ?? $prefill_name) ?>"
                                       class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                                <iconify-icon icon="lucide:user" class="absolute right-4 top-4 text-[#8890AA] text-lg pointer-events-none"></iconify-icon>
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Email Address</label>
                            <div class="relative">
                                <input type="email" id="email" name="email" required
                                       placeholder="you@company.com"
                                       value="<?= htmlspecialchars($_POST['email'] ?? $prefill_email) ?>"
                                       class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                                <iconify-icon icon="lucide:mail" class="absolute right-4 top-4 text-[#8890AA] text-lg pointer-events-none"></iconify-icon>
                            </div>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required
                                       placeholder="Min. 6 characters"
                                       class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                                <iconify-icon icon="lucide:lock" class="absolute right-4 top-4 text-[#8890AA] text-lg pointer-events-none"></iconify-icon>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="confirm" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="confirm" name="confirm" required
                                       placeholder="Re-enter password"
                                       class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                                <iconify-icon icon="lucide:shield-check" class="absolute right-4 top-4 text-[#8890AA] text-lg pointer-events-none"></iconify-icon>
                            </div>
                        </div>

                        <button type="submit" class="btn-brand-primary w-full py-4 rounded-xl font-bold uppercase text-xs tracking-wider flex items-center justify-center gap-2">
                            <iconify-icon icon="lucide:user-check" class="text-base"></iconify-icon>
                            <span>Create Client Account</span>
                        </button>
                    </form>

                    <!-- Login CTA -->
                    <div class="mt-6 pt-5 border-t border-[rgba(19,34,75,0.07)] text-center">
                        <p class="text-xs text-[#8890AA]">
                            Already have an account?
                            <a href="login.php" class="text-[#6C5BB5] font-semibold hover:underline ml-1">Log in instead →</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white">
        &copy; 2026 Antigo UI/UX Advisory. All rights reserved.
    </footer>

</body>
</html>
