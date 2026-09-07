<?php
require_once 'config/session.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard | Antigo UI/UX Advisory</title>
    
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

        .dashboard-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
            transition: all 0.25s ease;
        }

        .dashboard-card:hover {
            box-shadow: 0 14px 30px -8px rgba(19, 34, 75, 0.1);
        }

        .badge-pending { background: #FFF1D6; color: #946200; }
        .badge-active { background: #DDEBFF; color: #13224B; }
        .badge-design { background: rgba(108,91,181,0.12); color: #6C5BB5; }
        .badge-complete { background: #DFF6E8; color: #127A45; }

        .progress-bar-fill {
            background: var(--grad-soft);
            transition: width 0.6s ease;
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
<body class="min-h-screen flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="w-full bg-white border-b border-[rgba(19,34,75,0.08)] sticky top-0 z-50">
        <div class="max-w-[1400px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="home.php" class="logo">
                <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="logo-mark">
                <div class="logo-text">
                    <div class="word">ANTIGO</div>
                    <div class="sub">CLIENT WORKSPACE</div>
                </div>
            </a>

            <!-- User Profile & Action Menu -->
            <div class="flex items-center gap-4 sm:gap-6">
                <a href="inquiry.php" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold text-white shadow-md" style="background:var(--grad);">
                    <iconify-icon icon="lucide:plus"></iconify-icon>
                    <span>New Project</span>
                </a>
                <a href="book-consultation.php" class="hidden sm:inline-flex items-center gap-2 px-4 py-2.5 rounded-full text-xs font-bold border border-[rgba(19,34,75,0.12)] text-[#13224B] hover:bg-[#F4F6F8]">
                    <iconify-icon icon="lucide:calendar"></iconify-icon>
                    <span>Book Session</span>
                </a>

                <div class="h-6 w-[1px] bg-[rgba(19,34,75,0.1)] hidden sm:block"></div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white flex items-center justify-center font-bold text-sm shadow-sm" id="userAvatar">
                        DC
                    </div>
                    <div class="hidden md:flex flex-col">
                        <span class="text-xs font-bold text-[#13224B]" id="userName">Demo Client</span>
                        <span class="text-[10px] text-[#8890AA]" id="userCompany">Visayas Health Care</span>
                    </div>
                    <button onclick="handleLogout()" title="Log Out" class="w-9 h-9 rounded-full border border-[rgba(19,34,75,0.1)] flex items-center justify-center text-[#8890AA] hover:text-red-500 hover:border-red-200 transition-colors">
                        <iconify-icon icon="lucide:log-out" class="text-base"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Dashboard Content Layout -->
    <main class="flex-1 max-w-[1400px] w-full mx-auto px-6 lg:px-10 py-8">
        
        <!-- Welcome Hero Banner -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#13224B] tracking-tight">Client Project Hub</h1>
                <p class="text-sm text-[#4b4b4b] mt-1">Track sprint deliverables, schedule advisory sessions, and review design prototypes.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-[#127A45] text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#10b981] animate-pulse"></span>
                    Studio Active (Dumaguete, PH)
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Projects</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:folder-kanban"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="metricActiveProjects">2</div>
                <div class="text-xs text-[#6C5BB5] font-semibold mt-1">In design &amp; review</div>
            </div>

            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Consultations</span>
                    <div class="w-9 h-9 rounded-xl bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:calendar"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="metricBookings">1</div>
                <div class="text-xs text-[#8890AA] mt-1">Upcoming session</div>
            </div>

            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Files &amp; Assets</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:file-text"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="metricFiles">5</div>
                <div class="text-xs text-[#8890AA] mt-1">Figma &amp; Specs</div>
            </div>

            <div class="dashboard-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Overall Budget</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:credit-card"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="metricBudget">₱430,000</div>
                <div class="text-xs text-[#127A45] font-semibold mt-1">Committed (₱ PHP)</div>
            </div>
        </div>

        <!-- Main Dashboard Split Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left 2 Cols: Active Projects List -->
            <div class="lg:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-bold text-[#13224B]">Your Projects</h2>
                    <a href="inquiry.php" class="text-xs font-bold text-[#6C5BB5] hover:underline flex items-center gap-1">
                        <span>Start Another Project</span>
                        <iconify-icon icon="lucide:arrow-right"></iconify-icon>
                    </a>
                </div>

                <div id="projectsList" class="space-y-4">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>

            <!-- Right 1 Col: Upcoming Consultations & Activity -->
            <div class="space-y-6">
                
                <!-- Upcoming Consultations Card -->
                <div class="dashboard-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-[#13224B]">Upcoming Sessions</h3>
                        <a href="book-consultation.php" class="text-xs font-bold text-[#4C6CCB] hover:underline">Book Slot</a>
                    </div>
                    
                    <div id="consultationsList" class="space-y-3">
                        <!-- Populated dynamically -->
                    </div>
                </div>

                <!-- Assigned Lead Consultant -->
                <div class="dashboard-card p-6 bg-gradient-to-br from-white to-[#F4F6F8]">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-4">Principal Consultant</div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-white shadow-md flex-shrink-0">
                            <img src="images/profile.png" alt="Kimberly Jayne Antigo" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-[#13224B]">Kimberly Jayne Antigo</h4>
                            <p class="text-xs text-[#6C5BB5] font-semibold">Founder &amp; UI/UX Consultant</p>
                            <p class="text-[10px] text-[#8890AA]">Dumaguete City, PH</p>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-[rgba(19,34,75,0.06)] flex gap-2">
                        <a href="mailto:antigokimberlyjayne@gmail.com" class="flex-1 py-2 text-center rounded-xl bg-white border border-[rgba(19,34,75,0.1)] text-xs font-semibold text-[#13224B] hover:bg-[#F4F6F8] transition-colors">
                            Send Email
                        </a>
                        <a href="book-consultation.php" class="flex-1 py-2 text-center rounded-xl text-xs font-bold text-white shadow-sm" style="background:var(--grad);">
                            Schedule Call
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white mt-12">
        &copy; 2026 Antigo UI/UX Advisory &middot; Client Portal
    </footer>

    <script src="js/app-data.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const user = AntigoData.getCurrentUser();
            if (user) {
                document.getElementById('userName').innerText = user.name;
                document.getElementById('userCompany').innerText = user.company || 'Client Workspace';
                const initials = user.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                document.getElementById('userAvatar').innerText = initials || 'DC';
            }

            renderProjects();
            renderBookings();
        });

        function renderProjects() {
            const projects = AntigoData.getProjects();
            const container = document.getElementById('projectsList');
            container.innerHTML = '';

            let totalBudget = 0;
            let activeCount = 0;
            let totalFiles = 0;

            projects.forEach(p => {
                const numericBudget = parseInt(p.budget.replace(/[^0-9]/g, '')) || 0;
                totalBudget += numericBudget;
                if (p.statusType !== 'completed') activeCount++;
                if (p.files) totalFiles += p.files.length;

                let badgeClass = 'badge-active';
                if (p.statusType === 'in_design') badgeClass = 'badge-design';
                if (p.statusType === 'completed') badgeClass = 'badge-complete';
                if (p.statusType === 'pending') badgeClass = 'badge-pending';

                const cardHtml = `
                    <div onclick="window.location.href='client-project.php?id=${p.id}'" class="dashboard-card p-6 cursor-pointer group">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-widest text-[#6C5BB5]">${p.category}</span>
                                <h3 class="text-lg font-bold text-[#13224B] group-hover:text-[#4C6CCB] transition-colors">${p.title}</h3>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-3 py-1 rounded-full text-xs font-bold ${badgeClass}">${p.status}</span>
                                <span class="text-sm font-extrabold text-[#13224B]">${p.budget}</span>
                            </div>
                        </div>

                        <!-- Progress Bar & Current Phase -->
                        <div class="mb-4">
                            <div class="flex justify-between text-xs mb-1.5">
                                <span class="text-[#4b4b4b] font-medium">Phase: <strong class="text-[#13224B]">${p.phaseName}</strong></span>
                                <span class="font-bold text-[#6C5BB5]">${p.progress}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-[#F4F6F8] overflow-hidden border border-[rgba(19,34,75,0.06)]">
                                <div class="progress-bar-fill h-full rounded-full" style="width: ${p.progress}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-3 border-t border-[rgba(19,34,75,0.06)] text-xs text-[#8890AA]">
                            <div class="flex items-center gap-4">
                                <span class="flex items-center gap-1.5"><iconify-icon icon="lucide:calendar"></iconify-icon> Due: ${p.dueDate}</span>
                                <span class="flex items-center gap-1.5"><iconify-icon icon="lucide:paperclip"></iconify-icon> ${p.files ? p.files.length : 0} Files</span>
                            </div>
                            <span class="font-bold text-[#4C6CCB] flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                Open Workspace <iconify-icon icon="lucide:chevron-right"></iconify-icon>
                            </span>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHtml);
            });

            document.getElementById('metricActiveProjects').innerText = activeCount;
            document.getElementById('metricFiles').innerText = totalFiles;
            document.getElementById('metricBudget').innerText = `₱${totalBudget.toLocaleString()}`;
        }

        function renderBookings() {
            const bookings = AntigoData.getBookings();
            const container = document.getElementById('consultationsList');
            container.innerHTML = '';
            document.getElementById('metricBookings').innerText = bookings.length;

            if (bookings.length === 0) {
                container.innerHTML = '<p class="text-xs text-[#8890AA] py-3">No upcoming consultation slots booked.</p>';
                return;
            }

            bookings.forEach(b => {
                const itemHtml = `
                    <div class="p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)]">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-[#13224B]">${b.service}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#DDEBFF] text-[#13224B] font-bold">${b.duration}</span>
                        </div>
                        <div class="text-xs text-[#6C5BB5] font-semibold flex items-center gap-1.5">
                            <iconify-icon icon="lucide:clock"></iconify-icon>
                            <span>${b.date} · ${b.time}</span>
                        </div>
                        <div class="text-[11px] text-[#8890AA] mt-1">${b.format}</div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', itemHtml);
            });
        }

        function handleLogout() {
            AntigoData.logout();
            window.location.href = 'login.php';
        }
    </script>
</body>
</html>
