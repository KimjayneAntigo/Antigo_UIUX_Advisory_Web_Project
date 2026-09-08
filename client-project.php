<?php

require_once 'config/session.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Workspace | Antigo UI/UX Advisory</title>
    
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

        .workspace-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: 0 4px 18px -4px rgba(19, 34, 75, 0.05);
        }

        .chat-bubble-client {
            background: var(--grad);
            color: #FFFFFF;
            border-radius: 18px 18px 4px 18px;
        }

        .chat-bubble-designer {
            background: var(--surface-alt);
            color: var(--navy);
            border: 1px solid var(--border);
            border-radius: 18px 18px 18px 4px;
        }

        .dropzone {
            border: 2px dashed rgba(19, 34, 75, 0.14);
            transition: all 0.2s ease;
        }
        .dropzone:hover {
            border-color: var(--violet);
            background: rgba(108, 91, 181, 0.02);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="w-full bg-white border-b border-[rgba(19,34,75,0.08)] sticky top-0 z-50">
        <div class="max-w-[1400px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="client-dashboard.php" class="w-10 h-10 rounded-full border border-[rgba(19,34,75,0.1)] flex items-center justify-center text-[#4b4b4b] hover:bg-[#F4F6F8] transition-colors">
                    <iconify-icon icon="lucide:arrow-left" class="text-lg"></iconify-icon>
                </a>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-[#6C5BB5]" id="headerCategory">Fintech · Mobile App</span>
                    <h1 class="text-lg font-bold text-[#13224B]" id="headerTitle">Pesolink Mobile Banking Redesign</h1>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="px-3.5 py-1 rounded-full text-xs font-bold bg-[#DDEBFF] text-[#13224B]" id="headerStatus">
                    In Design
                </span>
                <a href="book-consultation.php" class="hidden sm:inline-flex items-center gap-2 px-4 py-2 rounded-full text-xs font-bold text-white shadow-sm" style="background:var(--grad);">
                    <iconify-icon icon="lucide:calendar"></iconify-icon>
                    <span>Schedule Sync</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Workspace Container -->
    <main class="flex-1 max-w-[1400px] w-full mx-auto px-6 lg:px-10 py-8 space-y-8">
        
        <!-- Interactive Phase Stepper Card -->
        <div class="workspace-card p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-[#13224B]">Project Roadmap &amp; Deliverable Milestones</h2>
                    <p class="text-xs text-[#8890AA] mt-0.5">Phases are verified and updated in real time by your principal consultant.</p>
                </div>
                <div class="text-xs font-bold text-[#6C5BB5] bg-[#F4F6F8] px-3.5 py-1.5 rounded-full border border-[rgba(19,34,75,0.06)]" id="phaseProgressLabel">
                    Phase 3 of 5 · 60% Complete
                </div>
            </div>

            <!-- Stepper Items -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3" id="stepperContainer">
                <!-- Stepper generated dynamically -->
            </div>
        </div>

        <!-- Split Grid: Files & Deliverables vs Live Collaboration Messages -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left Side: Deliverables & Files Repository (7 Cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Deliverables Card -->
                <div class="workspace-card p-6 sm:p-8">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-base font-bold text-[#13224B]">Design Deliverables &amp; Assets</h3>
                            <p class="text-xs text-[#8890AA]">Figma files, design tokens, and research documentation</p>
                        </div>
                        <span class="text-xs font-semibold text-[#6C5BB5]" id="filesCount">3 Files</span>
                    </div>

                    <!-- Files List -->
                    <div class="space-y-3 mb-6" id="filesListContainer">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Upload Client Brief / Attachment -->
                    <div class="dropzone p-5 rounded-2xl text-center cursor-pointer bg-[#F4F6F8]" onclick="document.getElementById('clientFileUpload').click()">
                        <input type="file" id="clientFileUpload" onchange="handleClientUpload(this)" class="hidden">
                        <iconify-icon icon="lucide:upload-cloud" class="text-2xl text-[#6C5BB5] mb-1"></iconify-icon>
                        <div class="text-xs font-bold text-[#13224B]">Upload project reference / feedback file</div>
                        <div class="text-[10px] text-[#8890AA]">PDF, Figma, images, or archives up to 15MB</div>
                    </div>
                </div>

                <!-- Scope & Budget Overview Card -->
                <div class="workspace-card p-6 sm:p-8">
                    <h3 class="text-base font-bold text-[#13224B] mb-4">Project Contract &amp; Scope Summary</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Contract Budget</span>
                            <strong class="text-sm font-extrabold text-[#13224B]" id="contractBudget">₱250,000</strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[#8890AA] block mb-1">Target Handover</span>
                            <strong class="text-sm font-bold text-[#13224B]" id="targetDate">Sep 30, 2026</strong>
                        </div>
                        <div class="p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)] col-span-2 sm:col-span-1">
                            <span class="text-[#8890AA] block mb-1">Assigned Studio</span>
                            <strong class="text-sm font-bold text-[#6C5BB5]">Antigo Advisory</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Live Messages & Designer Card (5 Cols) -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Assigned Designer Card -->
                <div class="workspace-card p-6 bg-gradient-to-br from-white to-[#F4F6F8]">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-2xl overflow-hidden border-2 border-white shadow-md flex-shrink-0">
                            <img src="images/profile.png" alt="Kimberly Jayne Antigo" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-[#DDEBFF] text-[#13224B]">Lead Designer</span>
                            <h4 class="text-base font-bold text-[#13224B] mt-0.5">Kimberly Jayne Antigo</h4>
                            <p class="text-xs text-[#8890AA]">antigokimberlyjayne@gmail.com</p>
                        </div>
                    </div>
                </div>

                <!-- Messaging Thread Card -->
                <div class="workspace-card p-6 flex flex-col h-[520px]">
                    <div class="flex items-center justify-between pb-4 border-b border-[rgba(19,34,75,0.08)] mb-4">
                        <div class="flex items-center gap-2">
                            <iconify-icon icon="lucide:messages-square" class="text-lg text-[#6C5BB5]"></iconify-icon>
                            <h3 class="text-sm font-bold text-[#13224B]">Project Thread</h3>
                        </div>
                        <span class="text-[10px] text-[#127A45] font-semibold flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-[#10b981]"></span>
                            Direct Studio Channel
                        </span>
                    </div>

                    <!-- Messages Scroll Container -->
                    <div id="messagesContainer" class="flex-1 overflow-y-auto space-y-4 pr-1 text-xs">
                        <!-- Populated by JS -->
                    </div>

                    <!-- Chat Input Field -->
                    <form onsubmit="sendMessage(event)" class="mt-4 pt-3 border-t border-[rgba(19,34,75,0.08)] flex gap-2">
                        <input type="text" id="chatInput" placeholder="Send a message to Kimberly..." required class="flex-1 px-4 py-3 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] text-xs text-[#13224B] focus:outline-none focus:border-[#6C5BB5] focus:bg-white">
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
        &copy; 2026 Antigo UI/UX Advisory &middot; Client Workspace
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

            renderProjectDetails();
            renderStepper();
            renderFiles();
            renderMessages();
        });

        function renderProjectDetails() {
            document.getElementById('headerCategory').innerText = projectData.category;
            document.getElementById('headerTitle').innerText = projectData.title;
            document.getElementById('headerStatus').innerText = projectData.status;
            document.getElementById('contractBudget').innerText = projectData.budget;
            document.getElementById('targetDate').innerText = projectData.dueDate;
            document.getElementById('phaseProgressLabel').innerText = `Phase ${projectData.currentPhase} of 5 · ${projectData.progress}% Complete`;
        }

        function renderStepper() {
            const container = document.getElementById('stepperContainer');
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
                            <div class="text-[9px] uppercase font-bold text-[#8890AA] tracking-wider">Phase ${p.step}</div>
                            <div class="text-xs font-bold text-[#13224B] truncate">${p.name}</div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', itemHtml);
            });
        }

        function renderFiles() {
            const container = document.getElementById('filesListContainer');
            container.innerHTML = '';
            const files = projectData.files || [];
            document.getElementById('filesCount').innerText = `${files.length} Files`;

            files.forEach(f => {
                const rowHtml = `
                    <div class="p-3.5 rounded-xl bg-[#F4F6F8] border border-[rgba(19,34,75,0.06)] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg bg-white border border-[rgba(19,34,75,0.08)] flex items-center justify-center text-[#6C5BB5]">
                                <iconify-icon icon="lucide:file-text" class="text-lg"></iconify-icon>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-[#13224B]">${f.name}</div>
                                <div class="text-[10px] text-[#8890AA]">${f.size} · Uploaded ${f.date}</div>
                            </div>
                        </div>
                        <button onclick="alert('Downloading ${f.name}...')" class="px-3 py-1.5 rounded-lg bg-white border border-[rgba(19,34,75,0.1)] text-xs font-bold text-[#4C6CCB] hover:bg-[#DDEBFF] transition-colors flex items-center gap-1.5">
                            <iconify-icon icon="lucide:download"></iconify-icon>
                            <span>Download</span>
                        </button>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', rowHtml);
            });
        }

        function renderMessages() {
            const container = document.getElementById('messagesContainer');
            container.innerHTML = '';
            const messages = AntigoData.getProjectMessages(currentProjectId);

            messages.forEach(m => {
                const isClient = m.role === 'client';
                const msgHtml = `
                    <div class="flex flex-col ${isClient ? 'items-end' : 'items-start'}">
                        <div class="text-[10px] text-[#8890AA] mb-1 px-1">${m.sender} · ${m.time}</div>
                        <div class="max-w-[85%] p-3 text-xs leading-relaxed ${isClient ? 'chat-bubble-client' : 'chat-bubble-designer'}">
                            ${m.text}
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', msgHtml);
            });
            container.scrollTop = container.scrollHeight;
        }

        function sendMessage(e) {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const text = input.value.trim();
            if (!text) return;

            const user = AntigoData.getCurrentUser() || { name: 'Demo Client', role: 'client' };
            AntigoData.addProjectMessage(currentProjectId, user.name, 'client', text);
            input.value = '';
            renderMessages();
        }

        function handleClientUpload(input) {
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
                alert(`File "${file.name}" uploaded to project repository.`);
            }
        }
    </script>
</body>
</html>
