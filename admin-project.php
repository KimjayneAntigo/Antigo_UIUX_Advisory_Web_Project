<?php

require_once 'config/session.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header('Location: login.php'); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Project Control | Antigo UI/UX Advisory</title>
    
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
            border-radius: 24px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }

        /* Distinct warm-neutral Admin Internal Notes block */
        .internal-notes-block {
            background-color: #FFF8E8;
            border-left: 4px solid #F59E0B;
            font-style: italic;
        }

        .chat-bubble-admin {
            background: var(--surface-alt);
            color: var(--navy);
            border: 1px solid var(--border);
            border-radius: 18px 18px 4px 18px;
        }

        .chat-bubble-client {
            background: var(--grad);
            color: #FFFFFF;
            border-radius: 18px 18px 18px 4px;
        }

        .select-input {
            background: #F4F6F8;
            border: 1px solid var(--border);
            color: var(--navy);
            transition: all 0.2s ease;
        }
        .select-input:focus {
            outline: none;
            border-color: var(--violet);
            background: #FFFFFF;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Admin Header -->
    <header class="w-full bg-[#13224B] text-white sticky top-0 z-50 shadow-md">
        <div class="max-w-[1440px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="admin-dashboard.php" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-white/80 hover:text-white transition-colors">
                    <iconify-icon icon="lucide:arrow-left" class="text-lg"></iconify-icon>
                </a>
                <div>
                    <span class="text-[9px] font-bold uppercase tracking-widest text-[#DDEBFF]" id="adminCatBadge">Fintech · Mobile App</span>
                    <h1 class="text-base font-bold text-white" id="adminProjectTitle">Pesolink Mobile Banking Redesign</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="saveProjectChanges()" class="px-4 py-2 rounded-xl bg-[#4C6CCB] hover:bg-[#3d5bb8] text-white text-xs font-bold shadow-sm flex items-center gap-2 transition-colors">
                    <iconify-icon icon="lucide:save"></iconify-icon>
                    <span>Save Project State</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-[1440px] w-full mx-auto px-6 lg:px-10 py-8 space-y-8">
        
        <!-- Status & Phase Management Control Bar -->
        <div class="admin-card p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-[#6C5BB5]">Milestone Stepper Control</span>
                    <h2 class="text-xl font-extrabold text-[#13224B] mt-0.5">Project Phase &amp; Delivery Status</h2>
                    <p class="text-xs text-[#8890AA]">Updating this phase controls what the client sees in their portal stepper in real time.</p>
                </div>

                <!-- Phase Dropdown -->
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-[#8890AA] whitespace-nowrap">Current Phase:</label>
                    <select id="phaseSelector" onchange="handlePhaseChange(this.value)" class="select-input px-4 py-2.5 rounded-xl text-xs font-bold cursor-pointer">
                        <option value="1">Phase 1: Discovery &amp; Research (20%)</option>
                        <option value="2">Phase 2: Wireframing &amp; Flows (40%)</option>
                        <option value="3" selected>Phase 3: UI/UX Design &amp; Mockups (60%)</option>
                        <option value="4">Phase 4: Interactive Prototype (80%)</option>
                        <option value="5">Phase 5: Handover &amp; Assets (100%)</option>
                    </select>
                </div>
            </div>

            <!-- Visual Stepper Preview -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3" id="adminStepperContainer">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Split Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Side (7 Cols): Client Info, Deliverables & Admin Internal Notes -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Client Info Card -->
                <div class="admin-card p-6 sm:p-8">
                    <h3 class="text-base font-bold text-[#13224B] mb-4">Client Information &amp; Commercial Scope</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Client Name</span>
                            <strong class="text-sm font-bold text-[#13224B]" id="adminClientName">Maria Santos</strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Company</span>
                            <strong class="text-sm font-bold text-[#13224B]" id="adminClientCompany">Pesolink Financial</strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Contract Value</span>
                            <strong class="text-sm font-extrabold text-[#4C6CCB]" id="adminBudget">₱250,000</strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Due Date</span>
                            <strong class="text-sm font-bold text-[#13224B]" id="adminDueDate">Sep 30, 2026</strong>
                        </div>
                    </div>
                </div>

                <!-- ADMIN INTERNAL NOTES BLOCK -->
                <div class="admin-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full bg-[#F59E0B]/20 text-[#946200] text-[10px] font-bold uppercase tracking-wider">
                                Studio Confidential
                            </span>
                            <h3 class="text-base font-bold text-[#13224B]">Admin Internal Notes</h3>
                        </div>
                        <span class="text-[11px] text-[#8890AA] italic">Not visible to client</span>
                    </div>
                    
                    <div class="internal-notes-block p-4 rounded-2xl mb-4 text-xs text-[#946200] leading-relaxed">
                        <textarea id="adminNotesTextarea" rows="3" class="w-full bg-transparent border-none outline-none resize-none font-medium text-xs text-[#946200] italic leading-relaxed" placeholder="Add confidential studio notes, billing milestones, design strategy..."></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="button" onclick="saveAdminNotes()" class="px-4 py-2 rounded-xl bg-white border border-[rgba(19,34,75,0.12)] text-xs font-bold text-[#13224B] hover:bg-[#F4F6F8] transition-colors">
                            Update Notes
                        </button>
                    </div>
                </div>

                <!-- Deliverables Management -->
                <div class="admin-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-[#13224B]">Deliverables Repository</h3>
                        <span class="text-xs text-[#6C5BB5] font-semibold" id="adminFilesCount">3 Files</span>
                    </div>

                    <div class="space-y-3 mb-5" id="adminFilesList">
                        <!-- Populated by JS -->
                    </div>

                    <div class="p-4 bg-[#F4F6F8] rounded-2xl border border-dashed border-[rgba(19,34,75,0.14)] text-center cursor-pointer" onclick="document.getElementById('adminUploadInput').click()">
                        <input type="file" id="adminUploadInput" onchange="handleAdminUpload(this)" class="hidden">
                        <iconify-icon icon="lucide:upload" class="text-xl text-[#6C5BB5] mb-1"></iconify-icon>
                        <div class="text-xs font-bold text-[#13224B]">Upload New Deliverable Asset for Client</div>
                        <div class="text-[10px] text-[#8890AA]">Figma file, PDF report, or ZIP package</div>
                    </div>
                </div>
            </div>

            <!-- Right Side (5 Cols): Live Messaging with Client -->
            <div class="lg:col-span-5 space-y-6">
                <div class="admin-card p-6 flex flex-col h-[620px]">
                    <div class="flex items-center justify-between pb-4 border-b border-[rgba(19,34,75,0.08)] mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-[#13224B]">Client Conversation</h3>
                            <p class="text-[10px] text-[#8890AA]">Posting as Kimberly Jayne Antigo (Designer)</p>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full bg-[#DDEBFF] text-[#13224B] text-[10px] font-bold">
                            Live Thread
                        </span>
                    </div>

                    <!-- Messages View -->
                    <div id="adminMessagesContainer" class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Post Reply Form -->
                    <form onsubmit="sendAdminMessage(event)" class="mt-4 pt-3 border-t border-[rgba(19,34,75,0.08)] flex gap-2">
                        <input type="text" id="adminChatInput" placeholder="Reply to client as Kimberly..." required class="flex-1 px-4 py-3 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] text-xs text-[#13224B] focus:outline-none focus:border-[#6C5BB5] focus:bg-white">
                        <button type="submit" class="w-11 h-11 rounded-xl text-white flex items-center justify-center shadow-md hover:scale-105 transition-transform" style="background:var(--grad);">
                            <iconify-icon icon="lucide:send" class="text-base"></iconify-icon>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full py-6 text-center text-xs text-[#8890AA] border-t border-[rgba(19,34,75,0.06)] bg-white mt-12">
        &copy; 2026 Antigo UI/UX Advisory &middot; Admin Control Panel
    </footer>

    <script src="js/app-data.js"></script>
    <script>
        let currentProjectId = 'PRJ-3001';
        let projectData = null;

        const PHASES = [
            { step: 1, name: 'Discovery & Research' },
            { step: 2, name: 'Wireframing' },
            { step: 3, name: 'UI/UX Design' },
            { step: 4, name: 'Interactive Prototype' },
            { step: 5, name: 'Handover & Assets' }
        ];

        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const pId = urlParams.get('id');
            if (pId) currentProjectId = pId;

            projectData = AntigoData.getProject(currentProjectId);
            if (!projectData) {
                projectData = AntigoData.getProjects()[0];
                currentProjectId = projectData.id;
            }

            renderAll();
        });

        function renderAll() {
            document.getElementById('adminCatBadge').innerText = projectData.category;
            document.getElementById('adminProjectTitle').innerText = projectData.title;
            document.getElementById('adminClientName').innerText = projectData.clientName;
            document.getElementById('adminClientCompany').innerText = projectData.company || 'Direct Client';
            document.getElementById('adminBudget').innerText = projectData.budget;
            document.getElementById('adminDueDate').innerText = projectData.dueDate;
            document.getElementById('adminNotesTextarea').value = projectData.internalNotes || '';
            document.getElementById('phaseSelector').value = projectData.currentPhase.toString();

            renderStepper();
            renderFiles();
            renderMessages();
        }

        function renderStepper() {
            const container = document.getElementById('adminStepperContainer');
            container.innerHTML = '';

            PHASES.forEach(p => {
                let stateClass = '';
                let iconHtml = '';

                if (p.step < projectData.currentPhase) {
                    iconHtml = '<iconify-icon icon="lucide:check" class="text-white text-sm"></iconify-icon>';
                    stateClass = 'bg-[#10b981] text-white';
                } else if (p.step === projectData.currentPhase) {
                    iconHtml = `<span class="text-white font-bold text-xs">${p.step}</span>`;
                    stateClass = 'bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] text-white shadow-md ring-2 ring-[#6C5BB5]/30';
                } else {
                    iconHtml = `<span class="text-[#8890AA] text-xs font-semibold">${p.step}</span>`;
                    stateClass = 'bg-white border border-[rgba(19,34,75,0.15)] text-[#8890AA]';
                }

                const itemHtml = `
                    <div class="p-3 rounded-2xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 ${stateClass}">
                            ${iconHtml}
                        </div>
                        <div>
                            <div class="text-[9px] uppercase font-bold text-[#8890AA]">Phase ${p.step}</div>
                            <div class="text-xs font-bold text-[#13224B] truncate">${p.name}</div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', itemHtml);
            });
        }

        function handlePhaseChange(val) {
            const phaseNum = parseInt(val);
            const phaseObj = PHASES.find(p => p.step === phaseNum);
            let statusText = 'In Design';
            let statusType = 'in_design';

            if (phaseNum === 1) { statusText = 'Discovery'; statusType = 'pending'; }
            if (phaseNum === 2) { statusText = 'Wireframing'; statusType = 'in_design'; }
            if (phaseNum === 3) { statusText = 'UI Design'; statusType = 'in_design'; }
            if (phaseNum === 4) { statusText = 'Prototyping'; statusType = 'in_design'; }
            if (phaseNum === 5) { statusText = 'Delivered'; statusType = 'completed'; }

            AntigoData.updateProjectPhase(currentProjectId, phaseNum, phaseObj.name, statusText, statusType);
            projectData = AntigoData.getProject(currentProjectId);
            renderStepper();
            alert(`Project phase updated to: Phase ${phaseNum} - ${phaseObj.name}. Synced to client portal.`);
        }

        function saveAdminNotes() {
            const notes = document.getElementById('adminNotesTextarea').value;
            AntigoData.updateProjectNotes(currentProjectId, notes);
            alert('Admin internal notes saved securely.');
        }

        function saveProjectChanges() {
            saveAdminNotes();
            alert('All project settings saved.');
        }

        function renderFiles() {
            const container = document.getElementById('adminFilesList');
            container.innerHTML = '';
            const files = projectData.files || [];
            document.getElementById('adminFilesCount').innerText = `${files.length} Files`;

            files.forEach(f => {
                const rowHtml = `
                    <div class="p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-white border border-[rgba(19,34,75,0.08)] flex items-center justify-center text-[#6C5BB5]">
                                <iconify-icon icon="lucide:file-text"></iconify-icon>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#13224B]">${f.name}</div>
                                <div class="text-[10px] text-[#8890AA]">${f.size} · Uploaded ${f.date}</div>
                            </div>
                        </div>
                        <button onclick="alert('Downloading ${f.name}...')" class="text-xs text-[#4C6CCB] font-bold hover:underline">
                            Download
                        </button>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', rowHtml);
            });
        }

        function handleAdminUpload(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const newFileObj = {
                    name: file.name,
                    size: (file.size / (1024 * 1024)).toFixed(1) + ' MB',
                    date: 'Just now'
                };
                AntigoData.addProjectFile(currentProjectId, newFileObj);
                projectData = AntigoData.getProject(currentProjectId);
                renderFiles();
                alert(`Deliverable "${file.name}" attached for client access.`);
            }
        }

        function renderMessages() {
            const container = document.getElementById('adminMessagesContainer');
            container.innerHTML = '';
            const messages = AntigoData.getProjectMessages(currentProjectId);

            messages.forEach(m => {
                const isAdmin = m.role === 'designer';
                const msgHtml = `
                    <div class="flex flex-col ${isAdmin ? 'items-end' : 'items-start'}">
                        <div class="text-[10px] text-[#8890AA] mb-1 px-1">${m.sender} · ${m.time}</div>
                        <div class="max-w-[85%] p-3 text-xs leading-relaxed ${isAdmin ? 'chat-bubble-admin' : 'chat-bubble-client'}">
                            ${m.text}
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', msgHtml);
            });
            container.scrollTop = container.scrollHeight;
        }

        function sendAdminMessage(e) {
            e.preventDefault();
            const input = document.getElementById('adminChatInput');
            const text = input.value.trim();
            if (!text) return;

            AntigoData.addProjectMessage(currentProjectId, 'Kimberly Jayne Antigo', 'designer', text);
            input.value = '';
            renderMessages();
        }
    </script>
</body>
</html>
