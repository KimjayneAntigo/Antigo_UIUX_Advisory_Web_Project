<?php
// Antigo UI/UX Advisory — Book a Consultation Page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Consultation | Antigo UI/UX Advisory</title>
    
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
            -webkit-font-smoothing: antialiased;
        }

        .glass-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
        }

        .bg-gradient-brand {
            background: var(--grad);
        }

        .bg-gradient-soft {
            background: var(--grad-soft);
        }

        .service-card {
            background: var(--surface);
            border: 1px solid var(--border);
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 18px -6px rgba(19, 34, 75, 0.05);
        }

        .service-card:hover {
            border-color: var(--violet);
            background-color: #FFFFFF;
            transform: translateY(-4px);
            box-shadow: 0 16px 36px -10px rgba(76, 108, 203, 0.18);
        }

        .service-card.selected {
            border-color: var(--violet);
            background: var(--surface);
            box-shadow: 0 0 0 2px var(--violet), 0 16px 36px -10px rgba(108, 91, 181, 0.25);
        }

        .custom-radio {
            width: 22px;
            height: 22px;
            border: 2px solid rgba(19, 34, 75, 0.22);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .selected .custom-radio {
            border-color: var(--violet);
        }

        .selected .custom-radio::after {
            content: '';
            width: 10px;
            height: 10px;
            background: var(--violet);
            border-radius: 50%;
        }

        /* Time slot chip */
        .time-slot {
            background: var(--white);
            border: 1px solid var(--border);
            color: var(--text);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .time-slot:hover {
            border-color: var(--violet);
            color: var(--violet);
        }

        .time-slot.selected {
            background: var(--navy);
            border-color: var(--navy);
            color: #FFFFFF;
            font-weight: 700;
            box-shadow: 0 4px 14px rgba(19, 34, 75, 0.25);
        }

        /* Calendar Date styling */
        .cal-day {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .cal-day:not(.disabled):hover {
            background: var(--light-blue);
            color: var(--navy);
        }

        .cal-day.selected {
            background: var(--grad) !important;
            color: #FFFFFF !important;
            box-shadow: 0 4px 12px rgba(76, 108, 203, 0.4);
        }

        .cal-day.today {
            border-bottom: 3px solid var(--violet);
        }

        .cal-day.disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .btn-brand-primary {
            background: var(--grad);
            color: var(--white);
            box-shadow: 0 14px 28px -10px rgba(76, 108, 203, 0.45);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .btn-brand-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px -8px rgba(76, 108, 203, 0.6);
        }

        .btn-brand-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .input-field {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            color: var(--navy);
            transition: all 0.2s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--violet);
            background: #FFFFFF;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Header -->
    <header class="sticky top-0 z-50 w-full glass-header">
        <nav class="max-w-[1360px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="home.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-gradient-brand rounded-xl flex items-center justify-center font-extrabold text-white text-base shadow-md group-hover:scale-105 transition-transform">
                    AJ
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="font-extrabold tracking-wide uppercase text-sm text-[#13224B]">Antigo</span>
                    <span class="text-[9px] text-[#6C5BB5] font-bold tracking-[0.22em] uppercase">UI/UX Advisory</span>
                </div>
            </a>

            <div class="flex items-center gap-6">
                <div id="leadBadge" class="hidden sm:inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#DDEBFF] text-[#13224B] text-xs font-semibold">
                    <iconify-icon icon="lucide:user-check" class="text-[#4C6CCB]"></iconify-icon>
                    <span id="leadBadgeText">Maria Santos (Pesolink)</span>
                </div>
                <a href="home.php" class="flex items-center gap-2 text-sm text-[#4b4b4b] hover:text-[#4C6CCB] transition-colors font-medium">
                    <iconify-icon icon="lucide:arrow-left"></iconify-icon>
                    <span>Back to Home</span>
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Content Layout -->
    <main class="flex-1 w-full max-w-[1360px] mx-auto px-6 lg:px-10 py-10 lg:py-14 flex flex-col lg:flex-row gap-10 relative z-10">
        
        <!-- Left Side (70%) -->
        <div class="w-full lg:flex-[0.7] flex flex-col">
            
            <!-- Stepper -->
            <div class="flex items-center gap-4 sm:gap-6 mb-10 overflow-x-auto pb-2">
                <!-- Step 1 -->
                <div class="flex items-center gap-3 flex-shrink-0 cursor-pointer" onclick="goToStep(1)">
                    <div id="step-indicator-1" class="w-9 h-9 rounded-full bg-gradient-soft flex items-center justify-center text-xs font-bold text-white shadow-md">1</div>
                    <span id="step-text-1" class="text-sm font-bold text-[#13224B]">Service Selection</span>
                </div>
                <div class="h-[2px] w-10 bg-[rgba(19,34,75,0.12)] flex-shrink-0"></div>
                <!-- Step 2 -->
                <div class="flex items-center gap-3 flex-shrink-0 cursor-pointer" onclick="goToStep(2)">
                    <div id="step-indicator-2" class="w-9 h-9 rounded-full bg-white border border-[rgba(19,34,75,0.15)] flex items-center justify-center text-xs font-semibold text-[#8890AA]">2</div>
                    <span id="step-text-2" class="text-sm font-medium text-[#8890AA]">Calendar &amp; Time</span>
                </div>
                <div class="h-[2px] w-10 bg-[rgba(19,34,75,0.12)] flex-shrink-0"></div>
                <!-- Step 3 -->
                <div class="flex items-center gap-3 flex-shrink-0 cursor-pointer" onclick="goToStep(3)">
                    <div id="step-indicator-3" class="w-9 h-9 rounded-full bg-white border border-[rgba(19,34,75,0.15)] flex items-center justify-center text-xs font-semibold text-[#8890AA]">3</div>
                    <span id="step-text-3" class="text-sm font-medium text-[#8890AA]">Summary Review</span>
                </div>
            </div>

            <!-- STEP 1 CONTAINER: SERVICE SELECTION -->
            <div id="step-container-1">
                <div class="mb-8">
                    <span class="text-[12px] font-bold uppercase tracking-[0.2em] text-[#6C5BB5] block mb-1">Step 1 of 3</span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-[#13224B] mb-2 tracking-tight">Select Advisory Service</h1>
                    <p class="text-[#4b4b4b] text-base sm:text-lg">Choose the advisory focus for your strategic session. Pricing in ₱ (PHP).</p>
                </div>

                <!-- Cold visitor lead check fallback (Only shown if no lead exists) -->
                <div id="inlineLeadFields" class="hidden mb-8 p-6 bg-white rounded-2xl border border-[rgba(19,34,75,0.08)] shadow-sm">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#6C5BB5] mb-2">Participant Details</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-[#8890AA] mb-1">Your Full Name *</label>
                            <input type="text" id="coldName" placeholder="Maria Santos" class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-[#8890AA] mb-1">Email Address *</label>
                            <input type="email" id="coldEmail" placeholder="maria@pesolink.com" class="input-field w-full px-3.5 py-2.5 rounded-xl text-sm font-medium">
                        </div>
                    </div>
                </div>

                <!-- Service Grid (2x3) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                    
                    <!-- Card 1: UI Design -->
                    <div onclick="selectServiceCard(this, 'UI Design', 25000, 45000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="UI Design" data-price30="25000" data-price60="45000">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:layout" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                            </div>
                            <div class="custom-radio"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#13224B] mb-2">UI Design</h3>
                        <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed">Clean, on-brand interfaces designed pixel by pixel across desktop and mobile screens.</p>
                        
                        <div class="flex items-end justify-between pt-4 border-t border-[rgba(19,34,75,0.06)]">
                            <div class="flex flex-col gap-1" onclick="event.stopPropagation();">
                                <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                                <select onchange="updateDurationSelect(this)" class="duration-select bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#13224B] focus:outline-none">
                                    <option value="30">30 min</option>
                                    <option value="60" selected>60 min</option>
                                </select>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest">Rate</span>
                                <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">₱45,000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: UX Research -->
                    <div onclick="selectServiceCard(this, 'UX Research', 30000, 50000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="UX Research" data-price30="30000" data-price60="50000">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:search" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                            </div>
                            <div class="custom-radio"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#13224B] mb-2">UX Research</h3>
                        <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed">Interviews, user journeys &amp; usability audits to ground roadmap decisions in real evidence.</p>
                        
                        <div class="flex items-end justify-between pt-4 border-t border-[rgba(19,34,75,0.06)]">
                            <div class="flex flex-col gap-1" onclick="event.stopPropagation();">
                                <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                                <select onchange="updateDurationSelect(this)" class="duration-select bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#13224B] focus:outline-none">
                                    <option value="30">30 min</option>
                                    <option value="60" selected>60 min</option>
                                </select>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest">Rate</span>
                                <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">₱50,000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Wireframing -->
                    <div onclick="selectServiceCard(this, 'Wireframing', 20000, 35000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Wireframing" data-price30="20000" data-price60="35000">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:layers" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                            </div>
                            <div class="custom-radio"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#13224B] mb-2">Wireframing</h3>
                        <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed">Low-fidelity blueprints that map out user journeys before visual styling begins.</p>
                        
                        <div class="flex items-end justify-between pt-4 border-t border-[rgba(19,34,75,0.06)]">
                            <div class="flex flex-col gap-1" onclick="event.stopPropagation();">
                                <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                                <select onchange="updateDurationSelect(this)" class="duration-select bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#13224B] focus:outline-none">
                                    <option value="30">30 min</option>
                                    <option value="60" selected>60 min</option>
                                </select>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest">Rate</span>
                                <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">₱35,000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Prototyping -->
                    <div onclick="selectServiceCard(this, 'Interactive Prototyping', 40000, 70000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Interactive Prototyping" data-price30="40000" data-price60="70000">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:play-circle" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                            </div>
                            <div class="custom-radio"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#13224B] mb-2">Interactive Prototyping</h3>
                        <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed">Click-through high-fidelity demos for pitch presentations and stakeholder alignment.</p>
                        
                        <div class="flex items-end justify-between pt-4 border-t border-[rgba(19,34,75,0.06)]">
                            <div class="flex flex-col gap-1" onclick="event.stopPropagation();">
                                <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                                <select onchange="updateDurationSelect(this)" class="duration-select bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#13224B] focus:outline-none">
                                    <option value="30">30 min</option>
                                    <option value="60" selected>60 min</option>
                                </select>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest">Rate</span>
                                <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">₱70,000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 5: Responsive Web -->
                    <div onclick="selectServiceCard(this, 'Responsive Web Design', 50000, 90000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Responsive Web Design" data-price30="50000" data-price60="90000">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:smartphone" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                            </div>
                            <div class="custom-radio"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#13224B] mb-2">Responsive Web Design</h3>
                        <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed">Modern web layout architectures optimized for conversion on any device size.</p>
                        
                        <div class="flex items-end justify-between pt-4 border-t border-[rgba(19,34,75,0.06)]">
                            <div class="flex flex-col gap-1" onclick="event.stopPropagation();">
                                <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                                <select onchange="updateDurationSelect(this)" class="duration-select bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#13224B] focus:outline-none">
                                    <option value="30">30 min</option>
                                    <option value="60" selected>60 min</option>
                                </select>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest">Rate</span>
                                <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">₱90,000</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 6: Design Systems -->
                    <div onclick="selectServiceCard(this, 'Design Systems', 60000, 100000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Design Systems" data-price30="60000" data-price60="100000">
                        <div class="flex justify-between items-start mb-4">
                            <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center">
                                <iconify-icon icon="lucide:component" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                            </div>
                            <div class="custom-radio"></div>
                        </div>
                        <h3 class="text-xl font-bold text-[#13224B] mb-2">Design Systems</h3>
                        <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed">Scalable design tokens, UI kits, and documentation that keep product teams aligned.</p>
                        
                        <div class="flex items-end justify-between pt-4 border-t border-[rgba(19,34,75,0.06)]">
                            <div class="flex flex-col gap-1" onclick="event.stopPropagation();">
                                <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                                <select onchange="updateDurationSelect(this)" class="duration-select bg-[#F4F6F8] border border-[rgba(19,34,75,0.1)] rounded-lg px-2.5 py-1.5 text-xs font-semibold text-[#13224B] focus:outline-none">
                                    <option value="30">30 min</option>
                                    <option value="60" selected>60 min</option>
                                </select>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest">Rate</span>
                                <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">₱100,000</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-end">
                    <button id="step1-next" onclick="goToStep(2)" class="btn-brand-primary px-8 h-[52px] rounded-full font-bold uppercase text-xs tracking-wider flex items-center gap-2">
                        <span>Continue to Calendar</span>
                        <iconify-icon icon="lucide:arrow-right" class="text-base"></iconify-icon>
                    </button>
                </div>
            </div>

            <!-- STEP 2 CONTAINER: CALENDAR & TIME -->
            <div id="step-container-2" class="hidden">
                <div class="mb-8">
                    <span class="text-[12px] font-bold uppercase tracking-[0.2em] text-[#6C5BB5] block mb-1">Step 2 of 3</span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-[#13224B] mb-2 tracking-tight">Select Date &amp; Format</h1>
                    <p class="text-[#4b4b4b] text-base sm:text-lg">Pick your preferred meeting slot (Philippine Standard Time, GMT+8).</p>
                </div>

                <!-- Meeting Format Selector (v1.1) -->
                <div class="mb-8 p-6 bg-white rounded-2xl border border-[rgba(19,34,75,0.08)] shadow-sm">
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-3">Session Format</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-[rgba(19,34,75,0.1)] cursor-pointer hover:border-[#6C5BB5] transition-all bg-[#F4F6F8] has-[:checked]:bg-white has-[:checked]:border-[#6C5BB5] has-[:checked]:ring-2 has-[:checked]:ring-[#6C5BB5]/20">
                            <input type="radio" name="meetingFormat" value="Video Call (Google Meet)" checked onchange="updateFormat(this.value)" class="text-[#6C5BB5] focus:ring-[#6C5BB5]">
                            <div>
                                <div class="text-xs font-bold text-[#13224B]">Google Meet</div>
                                <div class="text-[10px] text-[#8890AA]">Video Conference</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-[rgba(19,34,75,0.1)] cursor-pointer hover:border-[#6C5BB5] transition-all bg-[#F4F6F8] has-[:checked]:bg-white has-[:checked]:border-[#6C5BB5] has-[:checked]:ring-2 has-[:checked]:ring-[#6C5BB5]/20">
                            <input type="radio" name="meetingFormat" value="Phone Call (+63)" onchange="updateFormat(this.value)" class="text-[#6C5BB5] focus:ring-[#6C5BB5]">
                            <div>
                                <div class="text-xs font-bold text-[#13224B]">Direct Call</div>
                                <div class="text-[10px] text-[#8890AA]">Mobile Phone</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3.5 rounded-xl border border-[rgba(19,34,75,0.1)] cursor-pointer hover:border-[#6C5BB5] transition-all bg-[#F4F6F8] has-[:checked]:bg-white has-[:checked]:border-[#6C5BB5] has-[:checked]:ring-2 has-[:checked]:ring-[#6C5BB5]/20">
                            <input type="radio" name="meetingFormat" value="In-Person Studio (Dumaguete)" onchange="updateFormat(this.value)" class="text-[#6C5BB5] focus:ring-[#6C5BB5]">
                            <div>
                                <div class="text-xs font-bold text-[#13224B]">Studio Meeting</div>
                                <div class="text-[10px] text-[#8890AA]">Dumaguete City</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Interactive Calendar & Slots Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-white rounded-3xl p-6 sm:p-8 border border-[rgba(19,34,75,0.08)] shadow-sm">
                    <!-- Calendar View -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-bold text-[#13224B]" id="calMonthTitle">September 2026</span>
                            <div class="flex items-center gap-1">
                                <button type="button" class="w-8 h-8 rounded-lg border border-[rgba(19,34,75,0.1)] flex items-center justify-center text-[#4b4b4b] hover:bg-[#F4F6F8]">
                                    <iconify-icon icon="lucide:chevron-left"></iconify-icon>
                                </button>
                                <button type="button" class="w-8 h-8 rounded-lg border border-[rgba(19,34,75,0.1)] flex items-center justify-center text-[#4b4b4b] hover:bg-[#F4F6F8]">
                                    <iconify-icon icon="lucide:chevron-right"></iconify-icon>
                                </button>
                            </div>
                        </div>

                        <!-- Days Header -->
                        <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold text-[#8890AA] mb-2 uppercase tracking-wider">
                            <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                        </div>

                        <!-- Date Grid -->
                        <div id="calendarDates" class="grid grid-cols-7 gap-1 text-center">
                            <!-- Past/Disabled -->
                            <div class="cal-day disabled">31</div>
                            <div class="cal-day disabled">1</div>
                            <div class="cal-day disabled">2</div>
                            <div class="cal-day disabled">3</div>
                            <!-- Active -->
                            <div class="cal-day today" onclick="selectDate(this, '2026-09-04', 'Sep 4, 2026')">4</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-05', 'Sep 5, 2026')">5</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-06', 'Sep 6, 2026')">6</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-07', 'Sep 7, 2026')">7</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-08', 'Sep 8, 2026')">8</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-09', 'Sep 9, 2026')">9</div>
                            <div class="cal-day selected" onclick="selectDate(this, '2026-09-10', 'Sep 10, 2026')">10</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-11', 'Sep 11, 2026')">11</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-12', 'Sep 12, 2026')">12</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-13', 'Sep 13, 2026')">13</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-14', 'Sep 14, 2026')">14</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-15', 'Sep 15, 2026')">15</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-16', 'Sep 16, 2026')">16</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-17', 'Sep 17, 2026')">17</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-18', 'Sep 18, 2026')">18</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-19', 'Sep 19, 2026')">19</div>
                            <div class="cal-day" onclick="selectDate(this, '2026-09-20', 'Sep 20, 2026')">20</div>
                        </div>
                    </div>

                    <!-- Time Slots -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm font-bold text-[#13224B]">Available Time Slots</span>
                            <span class="text-[11px] text-[#6C5BB5] font-semibold" id="selectedDateBadge">Thu, Sep 10, 2026</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2.5">
                            <div onclick="selectTime(this, '09:00 AM')" class="time-slot p-3 rounded-xl text-xs font-semibold text-center">09:00 AM</div>
                            <div onclick="selectTime(this, '10:00 AM')" class="time-slot selected p-3 rounded-xl text-xs font-semibold text-center">10:00 AM</div>
                            <div onclick="selectTime(this, '11:30 AM')" class="time-slot p-3 rounded-xl text-xs font-semibold text-center">11:30 AM</div>
                            <div onclick="selectTime(this, '01:30 PM')" class="time-slot p-3 rounded-xl text-xs font-semibold text-center">01:30 PM</div>
                            <div onclick="selectTime(this, '03:00 PM')" class="time-slot p-3 rounded-xl text-xs font-semibold text-center">03:00 PM</div>
                            <div onclick="selectTime(this, '04:30 PM')" class="time-slot p-3 rounded-xl text-xs font-semibold text-center">04:30 PM</div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-between">
                    <button onclick="goToStep(1)" class="px-6 h-[52px] rounded-full font-bold uppercase text-xs tracking-wider border border-[rgba(19,34,75,0.12)] text-[#4b4b4b] hover:bg-white transition-colors">
                        ← Back to Services
                    </button>
                    <button onclick="goToStep(3)" class="btn-brand-primary px-8 h-[52px] rounded-full font-bold uppercase text-xs tracking-wider flex items-center gap-2">
                        <span>Review &amp; Summary</span>
                        <iconify-icon icon="lucide:arrow-right" class="text-base"></iconify-icon>
                    </button>
                </div>
            </div>

            <!-- STEP 3 CONTAINER: SUMMARY REVIEW ONLY (v1.1) -->
            <div id="step-container-3" class="hidden">
                <div class="mb-8">
                    <span class="text-[12px] font-bold uppercase tracking-[0.2em] text-[#6C5BB5] block mb-1">Step 3 of 3</span>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-[#13224B] mb-2 tracking-tight">Review &amp; Confirm Booking</h1>
                    <p class="text-[#4b4b4b] text-base sm:text-lg">No re-typing needed. Confirm your consultation slot below to finalize.</p>
                </div>

                <!-- Read-Only Summary Card -->
                <div class="bg-white rounded-3xl p-8 sm:p-10 border border-[rgba(19,34,75,0.08)] shadow-sm space-y-6">
                    
                    <!-- Lead Profile -->
                    <div class="flex items-center justify-between pb-5 border-b border-[rgba(19,34,75,0.08)]">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-soft text-white flex items-center justify-center font-bold text-base" id="leadInitials">
                                MS
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-[#13224B]" id="reviewLeadName">Maria Santos</h3>
                                <p class="text-xs text-[#8890AA]" id="reviewLeadEmail">maria@pesolink.com</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-[#DDEBFF] text-[#13224B] text-[11px] font-bold rounded-full">
                            Verified Lead
                        </span>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                        <div class="bg-[#F4F6F8] p-4 rounded-2xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Advisory Service</span>
                            <div class="font-bold text-[#13224B] text-base" id="reviewService">UI Design</div>
                            <div class="text-xs text-[#6C5BB5] font-semibold mt-0.5" id="reviewDuration">60 minutes session</div>
                        </div>

                        <div class="bg-[#F4F6F8] p-4 rounded-2xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Schedule &amp; Time</span>
                            <div class="font-bold text-[#13224B] text-base" id="reviewDateTime">Sep 10, 2026 · 10:00 AM</div>
                            <div class="text-xs text-[#8890AA] mt-0.5">PST (GMT+8, Dumaguete)</div>
                        </div>

                        <div class="bg-[#F4F6F8] p-4 rounded-2xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Session Format</span>
                            <div class="font-bold text-[#13224B]" id="reviewFormat">Video Call (Google Meet)</div>
                            <div class="text-xs text-[#8890AA] mt-0.5">Link sent via calendar invite</div>
                        </div>

                        <div class="bg-[#F4F6F8] p-4 rounded-2xl border border-[rgba(19,34,75,0.06)]">
                            <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Consultation Fee</span>
                            <div class="text-2xl font-extrabold text-[#4C6CCB]" id="reviewPrice">₱45,000</div>
                            <div class="text-[10px] text-[#8890AA]">Includes pre-audit &amp; action plan</div>
                        </div>
                    </div>

                    <div class="p-4 bg-[#DDEBFF]/40 rounded-2xl border border-[#4C6CCB]/20 text-xs text-[#13224B] flex items-start gap-2.5">
                        <iconify-icon icon="lucide:calendar-clock" class="text-[#4C6CCB] text-base mt-0.5 flex-shrink-0"></iconify-icon>
                        <div>
                            <strong>Calendar Integration:</strong> Once confirmed, Kimberly will accept the booking in the studio admin dashboard and send calendar invites directly to your email.
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-between">
                    <button onclick="goToStep(2)" class="px-6 h-[52px] rounded-full font-bold uppercase text-xs tracking-wider border border-[rgba(19,34,75,0.12)] text-[#4b4b4b] hover:bg-white transition-colors">
                        ← Back to Calendar
                    </button>
                    <button onclick="confirmBooking()" class="btn-brand-primary px-10 h-[52px] rounded-full font-bold uppercase text-xs tracking-wider flex items-center gap-2">
                        <iconify-icon icon="lucide:check-circle" class="text-base"></iconify-icon>
                        <span>Confirm Consultation</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Sidebar (30%) -->
        <aside class="w-full lg:flex-[0.3]">
            <div class="sticky top-28">
                <h2 class="text-xl font-bold text-[#13224B] mb-4">Live Summary</h2>
                <div class="bg-white rounded-2xl p-6 border-2 border-dashed border-[rgba(19,34,75,0.14)] shadow-sm">
                    <div class="mb-4 pb-4 border-b border-[rgba(19,34,75,0.08)]">
                        <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Selected Focus</span>
                        <h3 id="sb-service" class="text-lg font-bold text-[#13224B]">UI Design</h3>
                        <div id="sb-duration" class="text-xs text-[#6C5BB5] font-semibold mt-0.5">60 minutes</div>
                    </div>

                    <div class="mb-4 pb-4 border-b border-[rgba(19,34,75,0.08)]">
                        <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block mb-1">Slot &amp; Date</span>
                        <div id="sb-datetime" class="text-sm font-semibold text-[#13224B]">Sep 10, 2026 · 10:00 AM</div>
                        <div id="sb-format" class="text-xs text-[#8890AA] mt-0.5">Google Meet</div>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#8890AA] tracking-wider block">Advisory Rate</span>
                            <span class="text-[11px] text-[#4b4b4b]">Direct consultation</span>
                        </div>
                        <span id="sb-price" class="text-2xl font-extrabold text-[#4C6CCB]">₱45,000</span>
                    </div>

                    <div class="p-3 bg-[#F4F6F8] rounded-xl text-[11px] text-[#4b4b4b] leading-relaxed flex items-start gap-2">
                        <iconify-icon icon="lucide:check" class="text-[#10b981] text-sm mt-0.5"></iconify-icon>
                        <span>Strategic roadmap and session notes included.</span>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <!-- Confirmation Success Modal -->
    <div id="bookingConfirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-md w-full p-8 text-center border border-[rgba(19,34,75,0.08)] shadow-2xl">
            <div class="w-16 h-16 rounded-full bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-3xl mx-auto mb-4">
                <iconify-icon icon="lucide:calendar-check"></iconify-icon>
            </div>
            <h3 class="text-2xl font-extrabold text-[#13224B] mb-2">Booking Confirmed!</h3>
            <p class="text-xs sm:text-sm text-[#4b4b4b] mb-6">
                Your consultation has been successfully scheduled. Kimberly will review and send calendar coordinates shortly.
            </p>
            <div class="bg-[#F4F6F8] rounded-2xl p-4 text-xs text-left mb-6 space-y-1">
                <div class="flex justify-between"><span class="text-[#8890AA]">Booking ID:</span> <strong id="modalBookingId" class="text-[#13224B]">BKG-2003</strong></div>
                <div class="flex justify-between"><span class="text-[#8890AA]">Service:</span> <strong id="modalServiceName" class="text-[#13224B]">UI Design</strong></div>
                <div class="flex justify-between"><span class="text-[#8890AA]">Date &amp; Time:</span> <strong id="modalDateTime" class="text-[#13224B]">Sep 10, 2026 · 10:00 AM</strong></div>
            </div>
            <div class="flex flex-col gap-2.5">
                <a href="client-dashboard.php" class="btn-brand-primary py-3 rounded-full font-bold uppercase text-xs tracking-wider">
                    Go to Client Dashboard
                </a>
                <a href="home.php" class="py-3 rounded-full font-bold uppercase text-xs tracking-wider border border-[rgba(19,34,75,0.12)] text-[#4b4b4b] hover:bg-[#F4F6F8]">
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full bg-[#13224B] text-white py-8 border-t border-[rgba(255,255,255,0.08)] mt-16">
        <div class="max-w-[1360px] mx-auto px-6 lg:px-10 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-[#8890AA]">
            <p>© 2026 Antigo UI/UX Advisory. All rights reserved.</p>
            <div class="flex items-center gap-6">
                <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                <a href="index.php#contact" class="hover:text-white transition-colors">Studio Channels</a>
            </div>
        </div>
    </footer>

    <script src="js/app-data.js"></script>
    <script>
        // State
        let bookingState = {
            inquiryId: null,
            clientName: 'Maria Santos',
            clientEmail: 'maria@pesolink.com',
            service: 'UI Design',
            duration: '60 min',
            price: '₱45,000',
            price30: 25000,
            price60: 45000,
            dateRaw: '2026-09-10',
            dateFormatted: 'Sep 10, 2026',
            time: '10:00 AM',
            format: 'Video Call (Google Meet)'
        };

        // Initialize from URL / LocalStorage
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const inquiryId = urlParams.get('inquiry_id');
            
            if (inquiryId) {
                const inq = AntigoData.getInquiry(inquiryId);
                if (inq) {
                    bookingState.inquiryId = inq.id;
                    bookingState.clientName = inq.name;
                    bookingState.clientEmail = inq.email;
                    if (inq.projectType) {
                        bookingState.service = inq.projectType;
                    }
                    document.getElementById('leadBadgeText').innerText = `${inq.name} (${inq.company || 'Inquiry'})`;
                    document.getElementById('leadBadge').classList.remove('hidden');
                }
            } else {
                // Check if client is logged in
                const user = AntigoData.getCurrentUser();
                if (user && user.role === 'client') {
                    bookingState.clientName = user.name;
                    bookingState.clientEmail = user.email;
                    document.getElementById('leadBadgeText').innerText = `${user.name}`;
                    document.getElementById('leadBadge').classList.remove('hidden');
                } else {
                    // Show cold visitor fields
                    document.getElementById('inlineLeadFields').classList.remove('hidden');
                }
            }

            // Select default service matching project type
            autoSelectMatchingService(bookingState.service);
            syncLiveSidebar();
        });

        function autoSelectMatchingService(serviceName) {
            const cards = document.querySelectorAll('.service-card');
            let matched = false;
            cards.forEach(card => {
                if (card.dataset.name === serviceName) {
                    selectServiceCard(card, card.dataset.name, parseInt(card.dataset.price30), parseInt(card.dataset.price60));
                    matched = true;
                }
            });
            if (!matched && cards.length > 0) {
                selectServiceCard(cards[0], cards[0].dataset.name, parseInt(cards[0].dataset.price30), parseInt(cards[0].dataset.price60));
            }
        }

        function selectServiceCard(cardEl, name, p30, p60) {
            document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
            cardEl.classList.add('selected');

            const selectEl = cardEl.querySelector('select');
            const dur = selectEl ? selectEl.value : '60';
            const cost = dur === '30' ? p30 : p60;

            bookingState.service = name;
            bookingState.duration = `${dur} min`;
            bookingState.price = `₱${cost.toLocaleString()}`;
            bookingState.price30 = p30;
            bookingState.price60 = p60;

            syncLiveSidebar();
        }

        function updateDurationSelect(selectEl) {
            const card = selectEl.closest('.service-card');
            const dur = selectEl.value;
            const p30 = parseInt(card.dataset.price30);
            const p60 = parseInt(card.dataset.price60);
            const cost = dur === '30' ? p30 : p60;

            const displayEl = card.querySelector('.price-display');
            if (displayEl) {
                displayEl.innerText = `₱${cost.toLocaleString()}`;
            }

            if (card.classList.contains('selected')) {
                bookingState.duration = `${dur} min`;
                bookingState.price = `₱${cost.toLocaleString()}`;
                syncLiveSidebar();
            }
        }

        function selectDate(dayEl, rawDate, formattedDate) {
            document.querySelectorAll('.cal-day').forEach(d => d.classList.remove('selected'));
            dayEl.classList.add('selected');
            bookingState.dateRaw = rawDate;
            bookingState.dateFormatted = formattedDate;
            document.getElementById('selectedDateBadge').innerText = formattedDate;
            syncLiveSidebar();
        }

        function selectTime(slotEl, timeStr) {
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            slotEl.classList.add('selected');
            bookingState.time = timeStr;
            syncLiveSidebar();
        }

        function updateFormat(val) {
            bookingState.format = val;
            syncLiveSidebar();
        }

        function syncLiveSidebar() {
            document.getElementById('sb-service').innerText = bookingState.service;
            document.getElementById('sb-duration').innerText = bookingState.duration;
            document.getElementById('sb-datetime').innerText = `${bookingState.dateFormatted} · ${bookingState.time}`;
            document.getElementById('sb-format').innerText = bookingState.format;
            document.getElementById('sb-price').innerText = bookingState.price;
        }

        function goToStep(stepNumber) {
            // Validation if moving from Step 1 to 2
            if (stepNumber >= 2) {
                const coldNameInput = document.getElementById('coldName');
                const coldEmailInput = document.getElementById('coldEmail');
                if (coldNameInput && coldNameInput.value.trim()) {
                    bookingState.clientName = coldNameInput.value.trim();
                }
                if (coldEmailInput && coldEmailInput.value.trim()) {
                    bookingState.clientEmail = coldEmailInput.value.trim();
                }
            }

            // If Step 3, populate review items
            if (stepNumber === 3) {
                document.getElementById('reviewLeadName').innerText = bookingState.clientName;
                document.getElementById('reviewLeadEmail').innerText = bookingState.clientEmail;
                const initials = bookingState.clientName.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
                document.getElementById('leadInitials').innerText = initials || 'AJ';

                document.getElementById('reviewService').innerText = bookingState.service;
                document.getElementById('reviewDuration').innerText = `${bookingState.duration} session`;
                document.getElementById('reviewDateTime').innerText = `${bookingState.dateFormatted} · ${bookingState.time}`;
                document.getElementById('reviewFormat').innerText = bookingState.format;
                document.getElementById('reviewPrice').innerText = bookingState.price;
            }

            // Toggle step visibility
            [1, 2, 3].forEach(s => {
                const container = document.getElementById(`step-container-${s}`);
                const indicator = document.getElementById(`step-indicator-${s}`);
                const text = document.getElementById(`step-text-${s}`);

                if (s === stepNumber) {
                    container.classList.remove('hidden');
                    indicator.className = 'w-9 h-9 rounded-full bg-gradient-soft flex items-center justify-center text-xs font-bold text-white shadow-md';
                    text.className = 'text-sm font-bold text-[#13224B]';
                } else if (s < stepNumber) {
                    container.classList.add('hidden');
                    indicator.className = 'w-9 h-9 rounded-full bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-xs font-bold';
                    indicator.innerHTML = '<iconify-icon icon="lucide:check"></iconify-icon>';
                    text.className = 'text-sm font-semibold text-[#13224B]';
                } else {
                    container.classList.add('hidden');
                    indicator.className = 'w-9 h-9 rounded-full bg-white border border-[rgba(19,34,75,0.15)] flex items-center justify-center text-xs font-semibold text-[#8890AA]';
                    indicator.innerText = s;
                    text.className = 'text-sm font-medium text-[#8890AA]';
                }
            });

            window.scrollTo({ top: 120, behavior: 'smooth' });
        }

        function confirmBooking() {
            // Write to shared localStorage layer
            const newBooking = AntigoData.addBooking({
                inquiryId: bookingState.inquiryId,
                clientName: bookingState.clientName,
                clientEmail: bookingState.clientEmail,
                service: bookingState.service,
                duration: bookingState.duration,
                price: bookingState.price,
                date: bookingState.dateRaw,
                time: bookingState.time,
                format: bookingState.format
            });

            // Show confirmation modal
            document.getElementById('modalBookingId').innerText = newBooking.id;
            document.getElementById('modalServiceName').innerText = newBooking.service;
            document.getElementById('modalDateTime').innerText = `${bookingState.dateFormatted} · ${bookingState.time}`;
            document.getElementById('bookingConfirmModal').classList.remove('hidden');
        }
    </script>
</body>
</html>

