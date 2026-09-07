<?php

require_once 'config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Studio Command Center | Antigo Advisory</title>
    
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
            border-radius: 20px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }

        .badge-new { background: #FFF1D6; color: #946200; }
        .badge-contacted { background: #DDEBFF; color: #13224B; }
        .badge-reviewed { background: rgba(108,91,181,0.12); color: #6C5BB5; }
        .badge-converted { background: #DFF6E8; color: #127A45; }

        .tab-btn.active {
            background: var(--navy);
            color: #FFFFFF;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
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
            color: #FFFFFF;
            line-height: 1.1;
        }
        .logo-text .sub {
            font-size: 9px;
            letter-spacing: 0.22em;
            color: #DDEBFF;
            text-transform: uppercase;
            font-weight: 700;
            line-height: 1.1;
            margin-top: 2px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Admin Top Nav -->
    <header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="home.php" class="logo">
                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center p-1.5 shadow-sm">
                    <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="w-full h-full object-contain">
                </div>
                <div class="logo-text">
                    <div class="word">ANTIGO</div>
                    <div class="sub">STUDIO COMMAND CENTER</div>
                </div>
            </a>

            <div class="flex items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white flex items-center justify-center font-bold text-sm">
                        KA
                    </div>
                    <div class="hidden sm:flex flex-col">
                        <span class="text-xs font-bold text-white">Kimberly Jayne Antigo</span>
                        <span class="text-[10px] text-[#DDEBFF]">Admin &amp; Lead Consultant</span>
                    </div>
                    <button onclick="handleLogout()" title="Log Out" class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center text-white/70 hover:text-white transition-colors">
                        <iconify-icon icon="lucide:log-out" class="text-base"></iconify-icon>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Admin Content -->
    <main class="flex-1 max-w-[1440px] w-full mx-auto px-6 lg:px-10 py-8 space-y-8">
        
        <!-- Header Introduction -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#13224B] tracking-tight">Studio Pipeline Overview</h1>
                <p class="text-sm text-[#4b4b4b] mt-1">Review incoming project leads, manage scheduled consultations, and oversee active design delivery.</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3.5 py-1.5 rounded-full bg-white border border-[rgba(19,34,75,0.08)] text-xs font-bold text-[#13224B] shadow-sm flex items-center gap-2">
                    <iconify-icon icon="lucide:database" class="text-[#6C5BB5]"></iconify-icon>
                    <span>Phase 1 LocalStorage Prototype</span>
                </span>
            </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Inquiries Captured</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DDEBFF] text-[#4C6CCB] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:inbox"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="statInquiriesCount">3</div>
                <div class="text-xs text-[#6C5BB5] font-semibold mt-1">Canonical intake queue</div>
            </div>

            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Scheduled Sessions</span>
                    <div class="w-9 h-9 rounded-xl bg-[#FFF1D6] text-[#946200] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:calendar-clock"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="statBookingsCount">2</div>
                <div class="text-xs text-[#8890AA] mt-1">Live calendar slots</div>
            </div>

            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Active Projects</span>
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-[#6C5BB5] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:kanban"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="statProjectsCount">3</div>
                <div class="text-xs text-[#8890AA] mt-1">In design / review</div>
            </div>

            <div class="admin-card p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-[#8890AA]">Pipeline Value</span>
                    <div class="w-9 h-9 rounded-xl bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-lg">
                        <iconify-icon icon="lucide:circle-dollar-sign"></iconify-icon>
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-[#13224B]" id="statPipelineValue">₱550,000</div>
                <div class="text-xs text-[#127A45] font-semibold mt-1">Total Pipeline (₱ PHP)</div>
            </div>
        </div>

        <!-- Section 1: Inquiries Lead Qualification Queue -->
        <div class="admin-card p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Inquiry Leads Queue (Canonical Intake)</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Admin reviews and qualifies incoming project leads from the Inquiry page.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-[#8890AA]">Auto-synced with localStorage</span>
                </div>
            </div>

            <!-- Inquiries Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[rgba(19,34,75,0.08)] text-[#8890AA] uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4">Ref ID</th>
                            <th class="py-3.5 px-4">Lead Name &amp; Company</th>
                            <th class="py-3.5 px-4">Service</th>
                            <th class="py-3.5 px-4">Budget Range (₱)</th>
                            <th class="py-3.5 px-4">Timeline</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inquiriesTableBody" class="divide-y divide-[rgba(19,34,75,0.06)]">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 2: Booked Consultations Calendar Grid -->
        <div class="admin-card p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Scheduled Consultations</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Bookings created directly by clients from the Book Consultation page.</p>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[rgba(19,34,75,0.08)] text-[#8890AA] uppercase tracking-wider font-bold">
                            <th class="py-3.5 px-4">Booking ID</th>
                            <th class="py-3.5 px-4">Client</th>
                            <th class="py-3.5 px-4">Service &amp; Duration</th>
                            <th class="py-3.5 px-4">Rate (₱)</th>
                            <th class="py-3.5 px-4">Date &amp; Time</th>
                            <th class="py-3.5 px-4">Meeting Format</th>
                            <th class="py-3.5 px-4">Status</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTableBody" class="divide-y divide-[rgba(19,34,75,0.06)]">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Active Studio Projects -->
        <div class="admin-card p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Active Client Projects</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Direct link into full admin project control and phase stepper management.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="adminProjectsList">
                <!-- Populated by JS -->
            </div>
        </div>
    </main>

    <!-- Inquiry Review Modal -->
    <div id="inquiryDetailModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-8 border border-[rgba(19,34,75,0.08)] shadow-2xl relative">
            <button onclick="closeInquiryModal()" class="absolute right-6 top-6 text-[#8890AA] hover:text-[#13224B]">
                <iconify-icon icon="lucide:x" class="text-2xl"></iconify-icon>
            </button>

            <div class="flex items-center gap-3 mb-6">
                <span id="modalInqStatusBadge" class="px-3 py-1 rounded-full text-xs font-bold badge-new">New Lead</span>
                <span class="text-xs text-[#8890AA]" id="modalInqId">INQ-1001</span>
            </div>

            <h3 class="text-2xl font-extrabold text-[#13224B] mb-1" id="modalInqName">Maria Santos</h3>
            <p class="text-xs text-[#6C5BB5] font-semibold mb-6" id="modalInqCompany">Pesolink Financial Services · maria@pesolink.com</p>

            <div class="grid grid-cols-3 gap-4 text-xs bg-[#F4F6F8] p-4 rounded-2xl mb-6">
                <div>
                    <span class="text-[#8890AA] block mb-1">Service Required</span>
                    <strong class="text-[#13224B]" id="modalInqService">UI Design</strong>
                </div>
                <div>
                    <span class="text-[#8890AA] block mb-1">Budget Band (₱)</span>
                    <strong class="text-[#13224B]" id="modalInqBudget">₱150,000 – ₱300,000</strong>
                </div>
                <div>
                    <span class="text-[#8890AA] block mb-1">Ideal Timeline</span>
                    <strong class="text-[#13224B]" id="modalInqTimeline">1 Month</strong>
                </div>
            </div>

            <div class="space-y-4 mb-8 text-xs">
                <div>
                    <span class="font-bold text-[#8890AA] uppercase tracking-wider block mb-1.5">Project Scope &amp; Description</span>
                    <p class="p-4 rounded-2xl bg-white border border-[rgba(19,34,75,0.08)] text-[#4b4b4b] leading-relaxed text-sm" id="modalInqDescription">
                        Redesigning mobile banking dashboard for smoother digital transactions...
                    </p>
                </div>
                <div id="modalInqFileBlock">
                    <span class="font-bold text-[#8890AA] uppercase tracking-wider block mb-1.5">Attached Reference File</span>
                    <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#DDEBFF] text-[#13224B] font-semibold">
                        <iconify-icon icon="lucide:file-text" class="text-[#4C6CCB]"></iconify-icon>
                        <span id="modalInqFileName">brief.pdf</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                <div class="flex gap-2">
                    <button type="button" onclick="setInquiryStatusAction('reviewed')" class="px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-[#F4F6F8]">
                        Mark Reviewed
                    </button>
                    <button type="button" onclick="setInquiryStatusAction('contacted')" class="px-4 py-2.5 rounded-xl border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF]">
                        Mark Contacted
                    </button>
                </div>
                <button type="button" onclick="convertToProject()" class="px-6 py-2.5 rounded-xl text-white text-xs font-bold shadow-md hover:scale-105 transition-transform" style="background:var(--grad);">
                    Convert to Active Project →
                </button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white mt-12">
        &copy; 2026 Antigo UI/UX Advisory &middot; Studio Administration
    </footer>

    <script src="js/app-data.js"></script>
    <script>
        let selectedInquiryId = null;

        document.addEventListener('DOMContentLoaded', () => {
            renderInquiries();
            renderBookings();
            renderProjects();
        });

        function renderInquiries() {
            const inquiries = AntigoData.getInquiries();
            const tbody = document.getElementById('inquiriesTableBody');
            tbody.innerHTML = '';
            document.getElementById('statInquiriesCount').innerText = inquiries.length;

            inquiries.forEach(inq => {
                let badgeClass = 'badge-new';
                if (inq.status === 'reviewed') badgeClass = 'badge-reviewed';
                if (inq.status === 'contacted') badgeClass = 'badge-contacted';
                if (inq.status === 'converted') badgeClass = 'badge-converted';

                const rowHtml = `
                    <tr class="hover:bg-[#F4F6F8] transition-colors">
                        <td class="py-4 px-4 font-mono font-bold text-[#13224B]">${inq.id}</td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-[#13224B]">${inq.name}</div>
                            <div class="text-[10px] text-[#8890AA]">${inq.company || inq.email}</div>
                        </td>
                        <td class="py-4 px-4 font-semibold text-[#13224B]">${inq.projectType}</td>
                        <td class="py-4 px-4 font-bold text-[#4C6CCB]">${inq.budget}</td>
                        <td class="py-4 px-4 text-[#4b4b4b]">${inq.timeline}</td>
                        <td class="py-4 px-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold ${badgeClass} uppercase tracking-wider">${inq.status}</span>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button onclick="openInquiryModal('${inq.id}')" class="px-3 py-1.5 rounded-lg border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-white transition-colors">
                                Review &amp; Qualify
                            </button>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', rowHtml);
            });
        }

        function renderBookings() {
            const bookings = AntigoData.getBookings();
            const tbody = document.getElementById('bookingsTableBody');
            tbody.innerHTML = '';
            document.getElementById('statBookingsCount').innerText = bookings.length;

            bookings.forEach(b => {
                const rowHtml = `
                    <tr class="hover:bg-[#F4F6F8] transition-colors">
                        <td class="py-4 px-4 font-mono font-bold text-[#13224B]">${b.id}</td>
                        <td class="py-4 px-4">
                            <div class="font-bold text-[#13224B]">${b.clientName}</div>
                            <div class="text-[10px] text-[#8890AA]">${b.clientEmail}</div>
                        </td>
                        <td class="py-4 px-4">
                            <span class="font-bold text-[#13224B]">${b.service}</span>
                            <span class="text-[10px] text-[#6C5BB5] font-semibold block">${b.duration}</span>
                        </td>
                        <td class="py-4 px-4 font-bold text-[#4C6CCB]">${b.price}</td>
                        <td class="py-4 px-4 font-semibold text-[#13224B]">${b.date} · ${b.time}</td>
                        <td class="py-4 px-4 text-[#4b4b4b]">${b.format}</td>
                        <td class="py-4 px-4">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#DFF6E8] text-[#127A45] uppercase tracking-wider">${b.status}</span>
                        </td>
                    </tr>
                `;
                tbody.insertAdjacentHTML('beforeend', rowHtml);
            });
        }

        function renderProjects() {
            const projects = AntigoData.getProjects();
            const container = document.getElementById('adminProjectsList');
            container.innerHTML = '';
            document.getElementById('statProjectsCount').innerText = projects.length;

            let totalPipeline = 0;
            projects.forEach(p => {
                const num = parseInt(p.budget.replace(/[^0-9]/g, '')) || 0;
                totalPipeline += num;

                const cardHtml = `
                    <div class="p-6 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-[10px] font-bold uppercase tracking-widest text-[#6C5BB5]">${p.category}</span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white text-[#13224B] border border-[rgba(19,34,75,0.08)]">${p.status}</span>
                            </div>
                            <h4 class="text-base font-bold text-[#13224B] mb-1">${p.title}</h4>
                            <p class="text-xs text-[#8890AA] mb-4">${p.clientName} (${p.company || 'Client'})</p>

                            <div class="mb-4">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-[#4b4b4b]">Phase: <strong>${p.phaseName}</strong></span>
                                    <span class="font-bold text-[#6C5BB5]">${p.progress}%</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-white overflow-hidden border border-[rgba(19,34,75,0.06)]">
                                    <div class="h-full rounded-full bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5]" style="width: ${p.progress}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-[rgba(19,34,75,0.06)] flex justify-between items-center">
                            <span class="text-sm font-extrabold text-[#13224B]">${p.budget}</span>
                            <a href="admin-project.php?id=${p.id}" class="px-3.5 py-1.5 rounded-lg bg-white border border-[rgba(19,34,75,0.1)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF] transition-colors">
                                Control Panel →
                            </a>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHtml);
            });

            document.getElementById('statPipelineValue').innerText = `₱${totalPipeline.toLocaleString()}`;
        }

        function openInquiryModal(id) {
            selectedInquiryId = id;
            const inq = AntigoData.getInquiry(id);
            if (!inq) return;

            document.getElementById('modalInqId').innerText = inq.id;
            document.getElementById('modalInqName').innerText = inq.name;
            document.getElementById('modalInqCompany').innerText = `${inq.company || 'No Company'} · ${inq.email}`;
            document.getElementById('modalInqService').innerText = inq.projectType;
            document.getElementById('modalInqBudget').innerText = inq.budget;
            document.getElementById('modalInqTimeline').innerText = inq.timeline;
            document.getElementById('modalInqDescription').innerText = inq.description;
            
            const fileBlock = document.getElementById('modalInqFileBlock');
            if (inq.fileName) {
                document.getElementById('modalInqFileName').innerText = inq.fileName;
                fileBlock.classList.remove('hidden');
            } else {
                fileBlock.classList.add('hidden');
            }

            document.getElementById('inquiryDetailModal').classList.remove('hidden');
        }

        function closeInquiryModal() {
            document.getElementById('inquiryDetailModal').classList.add('hidden');
            selectedInquiryId = null;
        }

        function setInquiryStatusAction(status) {
            if (!selectedInquiryId) return;
            AntigoData.updateInquiryStatus(selectedInquiryId, status);
            renderInquiries();
            closeInquiryModal();
            alert(`Inquiry ${selectedInquiryId} marked as ${status}.`);
        }

        function convertToProject() {
            if (!selectedInquiryId) return;
            const inq = AntigoData.getInquiry(selectedInquiryId);
            if (!inq) return;

            AntigoData.updateInquiryStatus(selectedInquiryId, 'converted');
            renderInquiries();
            closeInquiryModal();
            alert(`Inquiry ${selectedInquiryId} converted to Active Project! Navigating to project workspace...`);
            window.location.href = 'admin-project.php?id=PRJ-3001';
        }

        function handleLogout() {
            AntigoData.logout();
            window.location.href = 'logout.php';
        }
    </script>
</body>
</html>
