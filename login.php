<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | Antigo UI/UX Advisory</title>
    
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
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--surface-alt);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

        .brand-gradient-panel {
            background: linear-gradient(135deg, #13224B 0%, #1c2c5e 40%, #6C5BB5 75%, #4C6CCB 100%);
        }

        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: 0 20px 50px -15px rgba(19, 34, 75, 0.12);
        }

        .role-pill {
            transition: all 0.25s ease;
        }

        .role-pill.active {
            background: var(--grad);
            color: #FFFFFF;
            box-shadow: 0 4px 14px rgba(76, 108, 203, 0.35);
        }

        .role-pill:not(.active) {
            color: var(--text-faint);
            background: transparent;
        }

        .input-field {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            color: var(--navy);
            transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--violet);
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(108, 91, 181, 0.12);
        }

        .btn-brand-primary {
            background: var(--grad);
            color: #FFFFFF;
            box-shadow: 0 14px 28px -10px rgba(76, 108, 203, 0.45);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-brand-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px -8px rgba(76, 108, 203, 0.6);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo-mark {
            height: 38px;
            width: auto;
            max-width: 54px;
            flex-shrink: 0;
            object-fit: contain;
        }
        .logo-text {
            line-height: 1.15;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .logo-text .word {
            font-weight: 800;
            font-size: 18px;
            letter-spacing: 0.05em;
            color: var(--navy);
            line-height: 1.1;
        }
        .logo-text .sub {
            font-size: 9px;
            letter-spacing: 0.22em;
            color: var(--violet);
            text-transform: uppercase;
            font-weight: 700;
            line-height: 1.1;
            margin-top: 2px;
        }
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
            <a href="home.php" class="text-xs sm:text-sm font-semibold text-[#4b4b4b] hover:text-[#4C6CCB] transition-colors flex items-center gap-1.5">
                <iconify-icon icon="lucide:arrow-left"></iconify-icon>
                <span>Back to Home</span>
            </a>
        </div>
    </header>

    <!-- Main Auth Section -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8 lg:p-12">
        <div class="w-full max-w-[1060px] min-h-[580px] bg-white rounded-3xl overflow-hidden border border-[rgba(19,34,75,0.08)] shadow-2xl flex flex-col lg:flex-row">
            
            <!-- Left Side: Brand Panel -->
            <div class="lg:w-5/12 brand-gradient-panel p-8 sm:p-12 text-white flex flex-col justify-between relative overflow-hidden">
                <!-- Background ambient circles -->
                <div class="absolute -top-24 -right-24 w-72 h-72 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 rounded-full bg-[#6C5BB5]/30 blur-2xl pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-white text-xs font-semibold backdrop-blur-sm mb-8">
                        <iconify-icon icon="lucide:shield-check" class="text-sm text-[#DDEBFF]"></iconify-icon>
                        <span>Verified Studio Portal</span>
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-snug mb-4">
                        Designing experiences that drive impact.
                    </h2>
                    <p class="text-sm text-white/80 leading-relaxed">
                        Access your active project dashboard, review wireframe iterations, coordinate design system assets, and collaborate directly with Kimberly.
                    </p>
                </div>

                <div class="relative z-10 pt-8 mt-8 border-t border-white/10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center font-bold text-xs">
                            KA
                        </div>
                        <div>
                            <div class="text-sm font-bold">Kimberly Jayne Antigo</div>
                            <div class="text-xs text-white/70">Founder &amp; Principal Consultant</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="lg:w-7/12 p-8 sm:p-12 flex flex-col justify-between">
                <div>
                    <!-- Header & Role Switcher -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#13224B]">Log In</h1>
                            <p class="text-xs sm:text-sm text-[#4b4b4b] mt-1">Select your account role to continue</p>
                        </div>
                        <!-- Role Toggle -->
                        <div class="inline-flex bg-[#F4F6F8] p-1 rounded-full border border-[rgba(19,34,75,0.08)] self-start sm:self-auto">
                            <button type="button" id="role-client" onclick="setRole('client')" class="role-pill active px-5 py-2 rounded-full text-xs font-bold transition-all">
                                Client
                            </button>
                            <button type="button" id="role-admin" onclick="setRole('admin')" class="role-pill px-5 py-2 rounded-full text-xs font-bold transition-all">
                                Admin
                            </button>
                        </div>
                    </div>

                    <!-- Form -->
                    <form id="loginForm" onsubmit="handleLogin(event)" class="space-y-5">
                        <div id="error-banner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-red-600 text-xs font-medium"></div>

                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Email Address</label>
                            <div class="relative">
                                <input type="email" id="email" required placeholder="name@company.com" value="demo@client.com"
                                       class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                                <iconify-icon icon="lucide:mail" class="absolute right-4 top-4 text-[#8890AA] text-lg pointer-events-none"></iconify-icon>
                            </div>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA]">Password</label>
                                <a href="javascript:void(0)" onclick="openForgotModal()" class="text-xs text-[#6C5BB5] font-semibold hover:underline">Forgot password?</a>
                            </div>
                            <div class="relative">
                                <input type="password" id="password" required placeholder="••••••••" value="password"
                                       class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                                <iconify-icon icon="lucide:lock" class="absolute right-4 top-4 text-[#8890AA] text-lg pointer-events-none"></iconify-icon>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <label class="flex items-center gap-2 cursor-pointer select-none text-[#4b4b4b]">
                                <input type="checkbox" id="rememberMe" checked class="rounded text-[#4C6CCB] focus:ring-[#4C6CCB]">
                                <span>Remember my login</span>
                            </label>
                        </div>

                        <button type="submit" class="btn-brand-primary w-full py-4 rounded-xl font-bold uppercase text-xs tracking-wider flex items-center justify-center gap-2">
                            <span id="btn-text">Sign In as Client</span>
                            <iconify-icon icon="lucide:arrow-right" class="text-base"></iconify-icon>
                        </button>
                    </form>
                </div>

                <!-- Phase 1 Demo Accounts Notice -->
                <div class="mt-8 pt-6 border-t border-[rgba(19,34,75,0.08)]">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#8890AA] flex items-center gap-1.5">
                            <iconify-icon icon="lucide:terminal" class="text-[#6C5BB5]"></iconify-icon>
                            Phase 1 Demo Accounts
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                        <button type="button" onclick="fillDemo('client')" class="text-left p-2.5 rounded-lg bg-[#F4F6F8] hover:bg-[#DDEBFF] border border-[rgba(19,34,75,0.06)] transition-all">
                            <div class="font-bold text-[#13224B]">Client Account</div>
                            <div class="text-[11px] text-[#4b4b4b] truncate">demo@client.com (password)</div>
                        </button>
                        <button type="button" onclick="fillDemo('admin')" class="text-left p-2.5 rounded-lg bg-[#F4F6F8] hover:bg-[#DDEBFF] border border-[rgba(19,34,75,0.06)] transition-all">
                            <div class="font-bold text-[#13224B]">Admin Account</div>
                            <div class="text-[11px] text-[#4b4b4b] truncate">admin@antigo.com (password)</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Forgot Password Modal -->
    <div id="forgotModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 sm:p-8 border border-[rgba(19,34,75,0.08)] shadow-2xl relative">
            <button onclick="closeForgotModal()" class="absolute right-4 top-4 text-[#8890AA] hover:text-[#13224B] p-2">
                <iconify-icon icon="lucide:x" class="text-xl"></iconify-icon>
            </button>
            <div class="w-12 h-12 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-2xl mb-4">
                <iconify-icon icon="lucide:key-round"></iconify-icon>
            </div>
            <h3 class="text-xl font-bold text-[#13224B] mb-2">Reset Password</h3>
            <p class="text-xs sm:text-sm text-[#4b4b4b] mb-6">Enter your registered email address and we'll send you a password recovery verification link.</p>
            <div class="space-y-4">
                <input type="email" placeholder="you@company.com" class="input-field w-full px-4 py-3 rounded-xl text-sm font-medium">
                <button type="button" onclick="handleForgotSubmit()" class="btn-brand-primary w-full py-3.5 rounded-xl font-bold uppercase text-xs tracking-wider">
                    Send Recovery Link
                </button>
            </div>
        </div>
    </div>

    <!-- Minimal Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white">
        &copy; 2026 Antigo UI/UX Advisory. All rights reserved.
    </footer>

    <script src="js/app-data.js"></script>
    <script>
        let currentRole = 'client';

        function setRole(role) {
            currentRole = role;
            const clientBtn = document.getElementById('role-client');
            const adminBtn = document.getElementById('role-admin');
            const btnText = document.getElementById('btn-text');
            const emailInput = document.getElementById('email');

            if (role === 'client') {
                clientBtn.classList.add('active');
                adminBtn.classList.remove('active');
                btnText.innerText = 'Sign In as Client';
                emailInput.value = 'demo@client.com';
            } else {
                adminBtn.classList.add('active');
                clientBtn.classList.remove('active');
                btnText.innerText = 'Sign In as Admin';
                emailInput.value = 'admin@antigo.com';
            }
        }

        function fillDemo(role) {
            setRole(role);
            document.getElementById('password').value = 'password';
        }

        function handleLogin(e) {
            e.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const errorBanner = document.getElementById('error-banner');

            if (!email || !password) {
                errorBanner.innerText = 'Please enter both email and password.';
                errorBanner.classList.remove('hidden');
                return;
            }

            if (password.length < 6) {
                errorBanner.innerText = 'Password must be at least 6 characters.';
                errorBanner.classList.remove('hidden');
                return;
            }

            // Authenticate and save session
            AntigoData.login(currentRole, email);

            if (currentRole === 'admin') {
                window.location.href = 'admin-dashboard.php';
            } else {
                window.location.href = 'client-dashboard.php';
            }
        }

        function openForgotModal() {
            document.getElementById('forgotModal').classList.remove('hidden');
        }

        function closeForgotModal() {
            document.getElementById('forgotModal').classList.add('hidden');
        }

        function handleForgotSubmit() {
            alert('Password recovery link sent! Check your inbox.');
            closeForgotModal();
        }
    </script>
</body>
</html>
