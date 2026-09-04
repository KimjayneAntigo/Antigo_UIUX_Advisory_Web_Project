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
        /* Brand core */
        :root {
            --navy: #13224B;         /* primary text, dark surfaces (sidebar, footer, gradient) */
            --violet: #6C5BB5;       /* accent, gradient midpoint */
            --blue: #4C6CCB;         /* accent, gradient end, primary actions */
            --white: #FFFFFF;        /* base surface */
            --light-blue: #DDEBFF;   /* soft accent backgrounds, info chips */
            --light-gray: #F4F6F8;   /* section backgrounds, input fields, alt surfaces */
            --dark-gray: #4b4b4b;    /* body / supporting text */

            /* Semantic tokens */
            --text: #13224B;         /* headings, primary text */
            --text-soft: #4b4b4b;    /* body copy */
            --text-faint: #8890AA;   /* captions, placeholders, meta */
            --surface: #FFFFFF;      /* cards, modals, inputs-on-focus */
            --surface-alt: #F4F6F8;  /* section backgrounds, inputs-at-rest */
            --border: rgba(19, 34, 75, 0.09); /* hairlines, card borders */
            --grad: linear-gradient(100deg, #13224B 0%, #6C5BB5 55%, #4C6CCB 100%);
            --grad-soft: linear-gradient(135deg, #4C6CCB, #6C5BB5);

            /* Utility (unchanged — neutral, not brand-specific) */
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--surface-alt);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        /* Glassmorphism Header */
        .glass-header {
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border);
        }

        /* Gradient & Background Elements */
        .bg-gradient-brand {
            background: var(--grad);
        }

        .bg-gradient-soft {
            background: var(--grad-soft);
        }

        .dot-pattern {
            background-image: radial-gradient(rgba(76, 108, 203, 0.14) 1.5px, transparent 1.5px);
            background-size: 24px 24px;
        }

        .diagonal-stripes {
            background-image: repeating-linear-gradient(45deg, rgba(19, 34, 75, 0.02) 0px, rgba(19, 34, 75, 0.02) 1px, transparent 1px, transparent 10px);
        }

        .blob {
            position: absolute;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            filter: blur(140px);
            z-index: 0;
            pointer-events: none;
        }

        /* Card Styles */
        .service-card {
            background: var(--surface);
            border: 1px solid var(--border);
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 18px -6px rgba(19, 34, 75, 0.05);
        }

        .service-card:hover {
            border-color: var(--blue);
            transform: translateY(-4px);
            box-shadow: 0 16px 36px -10px rgba(76, 108, 203, 0.18);
        }

        .service-card.selected {
            border-color: var(--blue);
            background: var(--surface);
            box-shadow: 0 0 0 2px var(--blue), 0 16px 36px -10px rgba(76, 108, 203, 0.22);
        }

        /* Custom Radio Button */
        .custom-radio {
            width: 22px;
            height: 22px;
            border: 2px solid rgba(19, 34, 75, 0.22);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            background: var(--white);
            flex-shrink: 0;
        }

        .selected .custom-radio {
            border-color: var(--blue);
        }

        .selected .custom-radio::after {
            content: '';
            width: 10px;
            height: 10px;
            background: var(--blue);
            border-radius: 50%;
        }

        /* Primary Action Button */
        .btn-brand-primary {
            background: var(--grad);
            color: var(--white);
            box-shadow: 0 14px 28px -10px rgba(76, 108, 203, 0.45);
            transition: transform 0.25s ease, box-shadow 0.25s ease, opacity 0.25s ease;
        }

        .btn-brand-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 18px 34px -8px rgba(76, 108, 203, 0.6);
        }

        .btn-brand-primary:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Custom Dropdown Styling */
        .duration-select {
            background: var(--light-gray);
            border: 1px solid var(--border);
            color: var(--navy);
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .duration-select:focus {
            border-color: var(--blue);
            background: var(--white);
            outline: none;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Decorative Backgrounds -->
    <div class="blob bg-[#4C6CCB] opacity-[0.14] top-[-180px] right-[-100px]"></div>
    <div class="blob bg-[#6C5BB5] opacity-[0.12] bottom-[-160px] left-[-100px]"></div>
    <div class="absolute top-0 right-0 w-80 h-80 dot-pattern pointer-events-none"></div>

    <!-- Sticky Header -->
    <header class="sticky top-0 z-50 w-full glass-header">
        <nav class="max-w-[1360px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="index.php" id="nav-logo" class="flex items-center gap-3 group">
                <div class="w-10 h-10 bg-gradient-brand rounded-xl flex items-center justify-center font-extrabold text-white text-base shadow-md shadow-blue-900/20 group-hover:scale-105 transition-transform">
                    AJ
                </div>
                <div class="flex flex-col leading-tight">
                    <span class="font-extrabold tracking-wide uppercase text-sm text-[#13224B]">Antigo</span>
                    <span class="text-[9px] text-[#6C5BB5] font-bold tracking-[0.22em] uppercase">UI/UX Advisory</span>
                </div>
            </a>

            <a href="index.php" id="back-home-btn" class="flex items-center gap-2 text-sm text-[#4b4b4b] hover:text-[#4C6CCB] transition-colors font-medium group">
                <iconify-icon icon="lucide:arrow-left" class="text-base group-hover:-translate-x-0.5 transition-transform"></iconify-icon>
                <span>Back to Home</span>
            </a>
        </nav>
    </header>

    <!-- Main Content Layout -->
    <main class="flex-1 w-full max-w-[1360px] mx-auto px-6 lg:px-10 py-10 lg:py-14 flex flex-col lg:flex-row gap-10 relative z-10">
        
        <!-- Left Side (70%) -->
        <div class="w-full lg:flex-[0.7] flex flex-col">
            
            <!-- Stepper -->
            <div class="flex items-center gap-4 sm:gap-6 mb-10 overflow-x-auto pb-2">
                <!-- Step 1 (Active) -->
                <div class="flex items-center gap-3 flex-shrink-0">
                    <div class="w-9 h-9 rounded-full bg-gradient-soft flex items-center justify-center text-xs font-bold text-white shadow-md shadow-blue-500/25">1</div>
                    <span class="text-sm font-bold text-[#13224B]">Service Selection</span>
                </div>
                <div class="h-[2px] w-10 bg-[rgba(19,34,75,0.12)] flex-shrink-0"></div>
                <!-- Step 2 -->
                <div class="flex items-center gap-3 flex-shrink-0 opacity-60">
                    <div class="w-9 h-9 rounded-full bg-white border border-[rgba(19,34,75,0.15)] flex items-center justify-center text-xs font-semibold text-[#8890AA]">2</div>
                    <span class="text-sm font-medium text-[#8890AA]">Calendar &amp; Time</span>
                </div>
                <div class="h-[2px] w-10 bg-[rgba(19,34,75,0.12)] flex-shrink-0"></div>
                <!-- Step 3 -->
                <div class="flex items-center gap-3 flex-shrink-0 opacity-60">
                    <div class="w-9 h-9 rounded-full bg-white border border-[rgba(19,34,75,0.15)] flex items-center justify-center text-xs font-semibold text-[#8890AA]">3</div>
                    <span class="text-sm font-medium text-[#8890AA]">Details &amp; Summary</span>
                </div>
            </div>

            <!-- Section Heading -->
            <div class="mb-8">
                <span class="text-[12px] font-bold uppercase tracking-[0.2em] text-[#6C5BB5] block mb-1">Step 1 of 3</span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-[#13224B] mb-2 tracking-tight">Select Your Service</h1>
                <p class="text-[#4b4b4b] text-base sm:text-lg">Choose the advisory service that best fits your product requirements.</p>
            </div>

            <!-- Service Grid (2x3) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
                
                <!-- Card 1: UI Design -->
                <div onclick="selectService(this, 'UI Design', 500, 900)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="UI Design" data-price30="500" data-price60="900">
                    <div class="absolute inset-0 diagonal-stripes opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                            <iconify-icon icon="lucide:layout" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                        </div>
                        <div class="custom-radio"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[#13224B] mb-2 relative z-10">UI Design</h3>
                    <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed relative z-10">Create clean, on-brand interfaces that turn users into loyal customers.</p>
                    
                    <div class="flex items-end justify-between relative z-10 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                        <div class="flex flex-col gap-1.5" onclick="event.stopPropagation();">
                            <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                            <select onchange="updateDuration(this)" class="duration-select rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none cursor-pointer">
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest mb-0.5">Starts at</span>
                            <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">$500</span>
                        </div>
                    </div>
                </div>

                <!-- Card 2: UX Research -->
                <div onclick="selectService(this, 'UX Research', 600, 1000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="UX Research" data-price30="600" data-price60="1000">
                    <div class="absolute inset-0 diagonal-stripes opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                            <iconify-icon icon="lucide:search" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                        </div>
                        <div class="custom-radio"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[#13224B] mb-2 relative z-10">UX Research</h3>
                    <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed relative z-10">Interviews &amp; usability testing to ground decisions in real user evidence.</p>
                    
                    <div class="flex items-end justify-between relative z-10 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                        <div class="flex flex-col gap-1.5" onclick="event.stopPropagation();">
                            <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                            <select onchange="updateDuration(this)" class="duration-select rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none cursor-pointer">
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest mb-0.5">Starts at</span>
                            <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">$600</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Wireframing -->
                <div onclick="selectService(this, 'Wireframing', 400, 700)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Wireframing" data-price30="400" data-price60="700">
                    <div class="absolute inset-0 diagonal-stripes opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                            <iconify-icon icon="lucide:layers" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                        </div>
                        <div class="custom-radio"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[#13224B] mb-2 relative z-10">Wireframing</h3>
                    <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed relative z-10">Low-fidelity structural flows that map out logic before pixels are styled.</p>
                    
                    <div class="flex items-end justify-between relative z-10 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                        <div class="flex flex-col gap-1.5" onclick="event.stopPropagation();">
                            <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                            <select onchange="updateDuration(this)" class="duration-select rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none cursor-pointer">
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest mb-0.5">Starts at</span>
                            <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">$400</span>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Prototyping -->
                <div onclick="selectService(this, 'Interactive Prototyping', 800, 1400)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Interactive Prototyping" data-price30="800" data-price60="1400">
                    <div class="absolute inset-0 diagonal-stripes opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                            <iconify-icon icon="lucide:play-circle" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                        </div>
                        <div class="custom-radio"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[#13224B] mb-2 relative z-10">Interactive Prototyping</h3>
                    <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed relative z-10">Clickable high-fidelity prototypes for seamless stakeholder demos.</p>
                    
                    <div class="flex items-end justify-between relative z-10 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                        <div class="flex flex-col gap-1.5" onclick="event.stopPropagation();">
                            <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                            <select onchange="updateDuration(this)" class="duration-select rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none cursor-pointer">
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest mb-0.5">Starts at</span>
                            <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">$800</span>
                        </div>
                    </div>
                </div>

                <!-- Card 5: Responsive Web -->
                <div onclick="selectService(this, 'Responsive Web Design', 1000, 1800)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Responsive Web Design" data-price30="1000" data-price60="1800">
                    <div class="absolute inset-0 diagonal-stripes opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                            <iconify-icon icon="lucide:smartphone" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                        </div>
                        <div class="custom-radio"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[#13224B] mb-2 relative z-10">Responsive Web Design</h3>
                    <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed relative z-10">Layouts that adapt gracefully across desktop screens and mobile devices.</p>
                    
                    <div class="flex items-end justify-between relative z-10 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                        <div class="flex flex-col gap-1.5" onclick="event.stopPropagation();">
                            <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                            <select onchange="updateDuration(this)" class="duration-select rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none cursor-pointer">
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest mb-0.5">Starts at</span>
                            <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">$1000</span>
                        </div>
                    </div>
                </div>

                <!-- Card 6: Design Systems -->
                <div onclick="selectService(this, 'Design Systems', 1200, 2000)" class="service-card rounded-2xl p-6 cursor-pointer relative group overflow-hidden" data-name="Design Systems" data-price30="1200" data-price60="2000">
                    <div class="absolute inset-0 diagonal-stripes opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="w-12 h-12 bg-[#DDEBFF] rounded-xl flex items-center justify-center transition-transform group-hover:scale-105">
                            <iconify-icon icon="lucide:component" class="text-2xl text-[#4C6CCB]"></iconify-icon>
                        </div>
                        <div class="custom-radio"></div>
                    </div>
                    <h3 class="text-xl font-bold text-[#13224B] mb-2 relative z-10">Design Systems</h3>
                    <p class="text-[#4b4b4b] text-sm mb-6 leading-relaxed relative z-10">Reusable tokens &amp; components that keep your digital product team shipping fast.</p>
                    
                    <div class="flex items-end justify-between relative z-10 pt-4 border-t border-[rgba(19,34,75,0.06)]">
                        <div class="flex flex-col gap-1.5" onclick="event.stopPropagation();">
                            <label class="text-[10px] uppercase font-bold text-[#8890AA] tracking-widest">Duration</label>
                            <select onchange="updateDuration(this)" class="duration-select rounded-lg px-3 py-1.5 text-xs font-semibold focus:outline-none cursor-pointer">
                                <option value="30">30 min</option>
                                <option value="60">60 min</option>
                            </select>
                        </div>
                        <div class="text-right">
                            <span class="text-[10px] block font-bold text-[#8890AA] uppercase tracking-widest mb-0.5">Starts at</span>
                            <span class="text-2xl font-extrabold text-[#4C6CCB] price-display">$1200</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Controls -->
            <div class="mt-10 flex justify-end">
                <button id="next-btn" onclick="proceedNextStep()" disabled class="btn-brand-primary w-full sm:w-auto px-8 h-[52px] rounded-full font-bold uppercase text-xs tracking-wider flex items-center justify-center gap-2.5">
                    <span>Continue to Calendar</span>
                    <iconify-icon icon="lucide:arrow-right" class="text-base"></iconify-icon>
                </button>
            </div>
        </div>

        <!-- Right Sidebar (30%) -->
        <aside class="w-full lg:flex-[0.3]">
            <div class="sticky top-28">
                <h2 class="text-xl font-bold text-[#13224B] mb-4">Booking Summary</h2>
                <div id="summary-card" class="bg-white rounded-2xl p-7 border-2 border-dashed border-[rgba(19,34,75,0.14)] flex flex-col items-center justify-center text-center min-h-[380px] shadow-sm">
                    
                    <!-- Empty State -->
                    <div id="summary-empty" class="flex flex-col items-center gap-4 py-8">
                        <div class="w-16 h-16 rounded-full bg-[#F4F6F8] flex items-center justify-center text-[#8890AA]">
                            <iconify-icon icon="lucide:calendar-check" class="text-3xl"></iconify-icon>
                        </div>
                        <p class="text-[#4b4b4b] text-sm leading-relaxed max-w-[240px]">
                            Select a service from the left to preview your consultation summary and rates.
                        </p>
                    </div>

                    <!-- Selected Content State -->
                    <div id="summary-content" class="hidden w-full text-left">
                        <div class="mb-5 pb-4 border-b border-[rgba(19,34,75,0.08)]">
                            <div class="inline-block px-2.5 py-1 bg-[#DDEBFF] text-[#13224B] rounded-md text-[10px] font-bold tracking-wider uppercase mb-2">
                                Selected Service
                            </div>
                            <h3 id="summary-service-name" class="text-xl font-extrabold text-[#13224B]">UI Design</h3>
                        </div>

                        <div class="mb-5 flex justify-between items-center">
                            <span class="text-xs font-semibold text-[#8890AA] uppercase tracking-wider">Session Duration</span>
                            <span id="summary-duration" class="text-sm font-bold text-[#13224B] bg-[#F4F6F8] px-3 py-1 rounded-full">60 min</span>
                        </div>

                        <div class="h-[1px] w-full bg-[rgba(19,34,75,0.08)] my-5"></div>

                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs uppercase font-bold text-[#8890AA] tracking-wider block">Advisory Fee</span>
                                <span class="text-[11px] text-[#4b4b4b]">Direct consultation</span>
                            </div>
                            <span id="summary-price" class="text-3xl font-extrabold text-[#4C6CCB]">$900</span>
                        </div>

                        <div class="mt-6 p-3.5 bg-[#F4F6F8] rounded-xl border border-[rgba(19,34,75,0.06)]">
                            <p class="text-[11px] text-[#4b4b4b] leading-relaxed flex items-start gap-2">
                                <iconify-icon icon="lucide:check-circle-2" class="text-sm text-[#10b981] mt-0.5 flex-shrink-0"></iconify-icon>
                                <span>Includes initial project discovery, expert audit, and strategic action plan.</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </main>

    <!-- Footer -->
    <footer class="w-full bg-[#13224B] text-white py-10 mt-16 border-t border-[rgba(255,255,255,0.08)]">
        <div class="max-w-[1360px] mx-auto px-6 lg:px-10 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center font-bold text-xs text-white">AJ</div>
                <p class="text-xs text-[#8890AA]">© 2026 Antigo UI/UX Advisory. All rights reserved.</p>
            </div>
            <div class="flex items-center gap-6 text-xs text-[#8890AA]">
                <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                <a href="index.php#contact" class="hover:text-white transition-colors">Contact Support</a>
            </div>
        </div>
    </footer>

    <script>
        let selectedServiceData = null;

        function selectService(element, name, price30, price60) {
            // Remove selection from all cards
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selection to clicked card
            element.classList.add('selected');

            // Calculate duration and price
            const selectEl = element.querySelector('select');
            const duration = selectEl ? selectEl.value : '30';
            const finalPrice = duration === "30" ? price30 : price60;

            // Update Summary Card
            document.getElementById('summary-empty').classList.add('hidden');
            const content = document.getElementById('summary-content');
            content.classList.remove('hidden');

            document.getElementById('summary-service-name').innerText = name;
            document.getElementById('summary-duration').innerText = `${duration} minutes`;
            document.getElementById('summary-price').innerText = `$${finalPrice}`;

            selectedServiceData = { 
                name: name, 
                duration: duration, 
                price: finalPrice, 
                price30: price30, 
                price60: price60 
            };
            
            // Enable the Next button
            const nextBtn = document.getElementById('next-btn');
            nextBtn.disabled = false;
        }

        function updateDuration(selectElement) {
            const card = selectElement.closest('.service-card');
            const name = card.dataset.name;
            const price30 = parseInt(card.dataset.price30, 10);
            const price60 = parseInt(card.dataset.price60, 10);
            const duration = selectElement.value;
            const price = duration === "30" ? price30 : price60;

            // Update card price indicator
            const priceDisplay = card.querySelector('.price-display');
            if (priceDisplay) {
                priceDisplay.innerText = `$${price}`;
            }

            // If this card is currently selected, update the sidebar
            if (card.classList.contains('selected')) {
                document.getElementById('summary-duration').innerText = `${duration} minutes`;
                document.getElementById('summary-price').innerText = `$${price}`;
                selectedServiceData = { 
                    name: name, 
                    duration: duration, 
                    price: price, 
                    price30: price30, 
                    price60: price60 
                };
            }
        }

        function proceedNextStep() {
            if (!selectedServiceData) return;
            alert(`Selected: ${selectedServiceData.name} (${selectedServiceData.duration} min) - $${selectedServiceData.price}\nProceeding to Step 2: Calendar & Time...`);
        }
    </script>
</body>
</html>
