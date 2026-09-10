<?php
require_once __DIR__ . '/includes/routing.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$prefill_name    = '';
$prefill_email   = '';
$prefill_company = '';
$prefill_phone   = '';
$is_client_user  = is_logged_in();

if ($is_client_user) {
    $prefill_name  = $_SESSION['user_name'] ?? $_SESSION['name'] ?? '';
    $prefill_email = $_SESSION['email'] ?? '';
    try {
        $uStmt = $pdo->prepare('SELECT name, email, company FROM users WHERE id = ? LIMIT 1');
        $uStmt->execute([(int)$_SESSION['user_id']]);
        $userRow = $uStmt->fetch();
        if ($userRow) {
            $prefill_name    = $userRow['name'] ?: $prefill_name;
            $prefill_email   = $userRow['email'] ?: $prefill_email;
            $prefill_company = $userRow['company'] ?? '';
        }
    } catch (\PDOException $e) {
        error_log('inquiry.php prefill user error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start a Project — Project Inquiry | Antigo UI/UX Advisory</title>
    
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
            box-shadow: 0 0 0 3px rgba(108, 91, 181, 0.12);
        }

        .dropzone {
            border: 2px dashed rgba(19, 34, 75, 0.14);
            transition: all 0.25s ease;
        }

        .dropzone:hover, .dropzone.dragover {
            border-color: var(--violet);
            background-color: rgba(108, 91, 181, 0.03);
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

        .blob {
            position: absolute;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            filter: blur(140px);
            z-index: 0;
            pointer-events: none;
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
<body class="min-h-screen flex flex-col relative overflow-x-hidden">

    <!-- Decorative Blurs -->
    <div class="blob bg-[#4C6CCB] opacity-[0.12] top-[-150px] right-[-100px]"></div>
    <div class="blob bg-[#6C5BB5] opacity-[0.10] bottom-[-150px] left-[-100px]"></div>

    <!-- Header -->
    <header class="sticky top-0 z-50 w-full glass-header">
        <nav class="max-w-[1360px] mx-auto px-6 lg:px-10 h-20 flex items-center justify-between">
            <a href="home.php" class="logo">
                <img src="images/antigo-mark.png?v=2.0" alt="Antigo Logo" class="logo-mark">
                <div class="logo-text">
                    <div class="word">ANTIGO</div>
                    <div class="sub">UI/UX ADVISORY</div>
                </div>
            </a>

            <div class="flex items-center gap-6">
                <a href="<?= home_url() ?>" class="flex items-center gap-2 text-sm text-[#4b4b4b] hover:text-[#4C6CCB] transition-colors font-medium">
                    <iconify-icon icon="lucide:arrow-left"></iconify-icon>
                    <span>Back to Home</span>
                </a>
            </div>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="flex-1 w-full max-w-[1100px] mx-auto px-6 py-12 relative z-10">
        
        <!-- Header Introduction -->
        <div class="text-center max-w-2xl mx-auto mb-12">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-[#13224B] tracking-tight mb-3">Start a Project</h1>
            <p class="text-base text-[#4b4b4b] leading-relaxed">
                Tell us about your product goals, requirements, and budget. Submitting this form qualifies your project and generates a direct link to schedule your strategic consultation.
            </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-3xl p-8 sm:p-12 border border-[rgba(19,34,75,0.08)] shadow-xl">
            <form id="inquiryForm" onsubmit="submitInquiry(event)" class="space-y-8">
                
                <!-- Contact Details -->
                <div>
                    <h2 class="text-lg font-bold text-[#13224B] mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#DDEBFF] text-[#4C6CCB] text-xs font-bold flex items-center justify-center">1</span>
                        Your Contact Information
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <label for="fullName" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Full Name *</label>
                            <input type="text" id="fullName" required placeholder="e.g. Maria Santos"
                                   value="<?= htmlspecialchars($prefill_name, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= $is_client_user ? 'readonly' : '' ?>
                                   class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium <?= $is_client_user ? 'bg-gray-100 cursor-not-allowed opacity-90' : '' ?>">
                            <p id="err-name" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Email Address *</label>
                            <input type="email" id="email" required placeholder="maria@company.com"
                                   value="<?= htmlspecialchars($prefill_email, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= $is_client_user ? 'readonly' : '' ?>
                                   class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium <?= $is_client_user ? 'bg-gray-100 cursor-not-allowed opacity-90' : '' ?>">
                            <p id="err-email" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                        <div>
                            <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Phone Number *</label>
                            <input type="tel" id="phone" required placeholder="e.g. 0917 123 4567"
                                   class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                            <p id="err-phone" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                        <div class="sm:col-span-3">
                            <label for="company" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Company / Organization <span class="normal-case text-[#8890AA] font-normal">(Optional)</span></label>
                            <input type="text" id="company" placeholder="e.g. Pesolink Financial Services"
                                   value="<?= htmlspecialchars($prefill_company, ENT_QUOTES, 'UTF-8') ?>"
                                   class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium">
                            <p id="err-company" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                    </div>
                </div>

                <div class="h-[1px] bg-[rgba(19,34,75,0.06)]"></div>

                <!-- Project Scope & Budget -->
                <div>
                    <h2 class="text-lg font-bold text-[#13224B] mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#DDEBFF] text-[#4C6CCB] text-xs font-bold flex items-center justify-center">2</span>
                        Project Scope &amp; Estimates
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div>
                            <label for="projectType" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Primary Service *</label>
                            <select id="projectType" required class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium cursor-pointer">
                                <option value="" disabled selected>Select service</option>
                                <option value="UI Design">UI Design</option>
                                <option value="UX Research">UX Research</option>
                                <option value="Wireframing">Wireframing</option>
                                <option value="Interactive Prototyping">Interactive Prototyping</option>
                                <option value="Responsive Web Design">Responsive Web Design</option>
                                <option value="Design Systems">Design Systems</option>
                            </select>
                            <p id="err-projectType" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                        <div>
                            <label for="budget" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Budget Range (₱ PHP) *</label>
                            <select id="budget" required class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium cursor-pointer">
                                <option value="" disabled selected>Select budget range</option>
                                <option value="Under $50,000">Under $50,000</option>
                                <option value="$50,000 – $150,000">$50,000 – $150,000</option>
                                <option value="$150,000 – $300,000">$150,000 – $300,000</option>
                                <option value="$300,000+">$300,000+</option>
                            </select>
                            <p id="err-budget" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                        <div>
                            <label for="timeline" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Ideal Timeline *</label>
                            <select id="timeline" required class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium cursor-pointer">
                                <option value="" disabled selected>Select timeline</option>
                                <option value="Urgent (< 2 weeks)">Urgent (&lt; 2 weeks)</option>
                                <option value="1 Month">1 Month</option>
                                <option value="2–3 Months">2–3 Months</option>
                                <option value="Flexible">Flexible</option>
                            </select>
                            <p id="err-timeline" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>
                    </div>
                </div>

                <div class="h-[1px] bg-[rgba(19,34,75,0.06)]"></div>

                <!-- Project Overview & Files -->
                <div>
                    <h2 class="text-lg font-bold text-[#13224B] mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full bg-[#DDEBFF] text-[#4C6CCB] text-xs font-bold flex items-center justify-center">3</span>
                        Project Overview &amp; Files
                    </h2>
                    <div class="space-y-5">
                        <div>
                            <label for="description" class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Project Description * (Min 20 characters)</label>
                            <textarea id="description" rows="4" required minlength="20" placeholder="Describe the problem you're looking to solve, your target users, and key features needed..." class="input-field w-full px-4 py-3.5 rounded-xl text-sm font-medium leading-relaxed"></textarea>
                            <p id="err-description" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                        </div>

                        <!-- File -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#8890AA] mb-2">Reference Files / Brief <span class="normal-case text-[#8890AA] font-normal">(Optional, max 5MB — PDF, PNG, JPG, DOCX)</span></label>
                            <div id="dropzone" onclick="document.getElementById('fileInput').click()" class="dropzone rounded-2xl p-6 text-center cursor-pointer bg-[#F4F6F8]">
                                <input type="file" id="fileInput" onchange="handleFileSelected(this)" class="hidden" accept=".pdf,.png,.jpg,.jpeg,.docx">
                                <iconify-icon icon="lucide:upload-cloud" class="text-3xl text-[#6C5BB5] mb-2"></iconify-icon>
                                <div class="text-sm font-semibold text-[#13224B]">Click to attach file or drag and drop</div>
                                <div class="text-xs text-[#8890AA] mt-1">PDF, PNG, JPG, DOCX up to 5MB</div>
                            </div>
                            <p id="err-file" class="text-xs text-red-500 mt-1.5 font-medium hidden"></p>
                            <div id="fileChip" class="hidden mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#DDEBFF] text-[#13224B] text-xs font-medium">
                                <iconify-icon icon="lucide:file-text" class="text-[#4C6CCB]"></iconify-icon>
                                <span id="fileNameDisplay">file.pdf</span>
                                <button type="button" onclick="removeFile(event)" class="text-[#4b4b4b] hover:text-red-500 ml-1">
                                    <iconify-icon icon="lucide:x"></iconify-icon>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <p class="text-xs text-[#8890AA]">
                        🔒 Your project details are kept strictly confidential.
                    </p>
                    <button type="submit" class="btn-brand-primary w-full sm:w-auto px-10 py-4 rounded-full font-bold uppercase text-xs tracking-wider flex items-center justify-center gap-2">
                        <span>Submit Project Inquiry</span>
                        <iconify-icon icon="lucide:arrow-right" class="text-base"></iconify-icon>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-3xl max-w-lg w-full p-8 sm:p-10 border border-[rgba(19,34,75,0.08)] shadow-2xl text-center">
            <div class="w-16 h-16 rounded-full bg-[#DFF6E8] text-[#127A45] flex items-center justify-center text-3xl mx-auto mb-5 shadow-inner">
                <iconify-icon icon="lucide:check-circle-2"></iconify-icon>
            </div>
            
            <h3 class="text-2xl sm:text-3xl font-extrabold text-[#13224B] mb-2 tracking-tight">Inquiry Received!</h3>
            <p class="text-sm text-[#4b4b4b] leading-relaxed mb-6">
                Thank you, <strong id="leadNameConfirm" class="text-[#13224B]">there</strong>. We have logged your project inquiry in our studio system. You can now schedule your strategic consultation directly on our calendar.
            </p>

            <div class="bg-[#F4F6F8] rounded-2xl p-4 mb-8 text-left text-xs text-[#4b4b4b] space-y-1.5 border border-[rgba(19,34,75,0.06)]">
                <div class="flex justify-between"><span class="text-[#8890AA]">Reference ID:</span> <strong id="inquiryIdDisplay" class="text-[#13224B]">INQ-1004</strong></div>
                <div class="flex justify-between"><span class="text-[#8890AA]">Selected Service:</span> <strong id="serviceDisplay" class="text-[#13224B]">UI Design</strong></div>
                <div class="flex justify-between"><span class="text-[#8890AA]">Budget Band:</span> <strong id="budgetDisplay" class="text-[#13224B]">$50,000 – $150,000</strong></div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <button type="button" onclick="goToBooking()" class="btn-brand-primary flex-1 py-3.5 rounded-full font-bold uppercase text-xs tracking-wider flex items-center justify-center gap-2">
                    <span>Book a Consultation</span>
                    <iconify-icon icon="lucide:arrow-right"></iconify-icon>
                </button>
                <a href="<?= home_url() ?>" class="px-6 py-3.5 rounded-full font-bold uppercase text-xs tracking-wider border border-[rgba(19,34,75,0.12)] text-[#4b4b4b] hover:bg-[#F4F6F8] transition-colors">
                    Back to Home
                </a>
            </div>

            <?php if (!is_logged_in()): ?>
            <!-- Trusted-session registration CTA (guests only) -->
            <div class="mt-5 pt-4 border-t border-[rgba(19,34,75,0.08)]">
                <a href="register.php" class="flex items-center justify-center gap-2 text-xs font-semibold text-[#6C5BB5] hover:underline">
                    <iconify-icon icon="lucide:user-plus"></iconify-icon>
                    Create an account to track this inquiry in your dashboard →
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="w-full bg-[#13224B] text-white py-8 border-t border-[rgba(255,255,255,0.08)] mt-16">
        <div class="max-w-[1360px] mx-auto px-6 lg:px-10 flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-[#8890AA]">
            <p>&copy; 2026 Antigo UI/UX Advisory. All rights reserved.</p>
            <div class="flex items-center gap-6">
                <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                <a href="home.php#contact" class="hover:text-white transition-colors">Studio Channels</a>
            </div>
        </div>
    </footer>

    <script src="js/app-data.js"></script>
    <script>
        let attachedFileName = null;
        let createdInquiryId = null;

        function handleFileSelected(input) {
            if (input.files && input.files[0]) {
                attachedFileName = input.files[0].name;
                document.getElementById('fileNameDisplay').innerText = attachedFileName;
                document.getElementById('fileChip').classList.remove('hidden');
            }
        }

        function removeFile(e) {
            e.stopPropagation();
            attachedFileName = null;
            document.getElementById('fileInput').value = '';
            document.getElementById('fileChip').classList.add('hidden');
        }

        let submittedData = null;

        function clearErrors() {
            const errEls = document.querySelectorAll('[id^="err-"]');
            errEls.forEach(el => {
                el.innerText = '';
                el.classList.add('hidden');
            });
            const inputs = document.querySelectorAll('.input-field');
            inputs.forEach(inp => inp.classList.remove('border-red-500'));
        }

        function showFieldError(field, msg) {
            const errEl = document.getElementById('err-' + field);
            if (errEl) {
                errEl.innerText = msg;
                errEl.classList.remove('hidden');
            }
            const inputMap = {
                name: 'fullName',
                email: 'email',
                phone: 'phone',
                company: 'company',
                projectType: 'projectType',
                budget: 'budget',
                timeline: 'timeline',
                description: 'description',
                file: 'fileInput'
            };
            const inp = document.getElementById(inputMap[field] || field);
            if (inp) {
                inp.classList.add('border-red-500');
            }
        }

        async function submitInquiry(e) {
            e.preventDefault();
            clearErrors();

            const submitBtn = document.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.querySelector('span').innerText = 'Submitting…';

            const body = new FormData();
            body.append('name',        document.getElementById('fullName').value.trim());
            body.append('email',       document.getElementById('email').value.trim());
            body.append('phone',       document.getElementById('phone').value.trim());
            body.append('company',     document.getElementById('company').value.trim());
            body.append('projectType', document.getElementById('projectType').value);
            body.append('budget',      document.getElementById('budget').value);
            body.append('timeline',    document.getElementById('timeline').value);
            body.append('description', document.getElementById('description').value.trim());
            body.append('csrf_token',  <?= json_encode(generate_csrf_token()) ?>);

            const fileInput = document.getElementById('fileInput');
            if (fileInput.files && fileInput.files[0]) {
                body.append('file', fileInput.files[0]);
            }

            try {
                const res = await fetch('inquiry-handler.php', {
                    method: 'POST',
                    body,
                    credentials: 'same-origin'
                });

                const rawText = await res.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch (parseError) {
                    console.error('inquiry-handler returned non-JSON response:', rawText);
                    throw new Error('Server returned an unexpected response format.');
                }

                if (!res.ok || !data.success) {
                    if (data.field_errors) {
                        for (const [field, msg] of Object.entries(data.field_errors)) {
                            showFieldError(field, msg);
                        }
                    } else {
                        alert(data.error || 'Submission failed. Please check the form.');
                    }
                    submitBtn.disabled = false;
                    submitBtn.querySelector('span').innerText = 'Submit Project Inquiry';
                    return;
                }

                // If submitter is a logged-in client, skip modal and redirect straight to dashboard
                if (data.is_logged_in) {
                    window.location.href = data.redirect || 'client-dashboard.php';
                    return;
                }

                // Guest submitter: Keep raw numeric ID and submitted data for the booking handoff
                createdInquiryId = data.raw_id;
                submittedData    = data;

                // Populate & show confirmation modal
                document.getElementById('leadNameConfirm').innerText  = data.name;
                document.getElementById('inquiryIdDisplay').innerText = data.inquiry_id;
                document.getElementById('serviceDisplay').innerText   = data.service;
                document.getElementById('budgetDisplay').innerText    = data.budget;
                document.getElementById('successModal').classList.remove('hidden');

            } catch (err) {
                console.error('Inquiry submission caught error:', err);
                alert(err.message || 'A network error occurred. Please check your connection and try again.');
                submitBtn.disabled = false;
                submitBtn.querySelector('span').innerText = 'Submit Project Inquiry';
            }
        }

        function goToBooking() {
            if (createdInquiryId && submittedData) {
                const p = new URLSearchParams({
                    inquiry_id: createdInquiryId,
                    name: submittedData.name || '',
                    email: submittedData.email || '',
                    service: submittedData.service || ''
                });
                window.location.href = `book-consultation.php?${p.toString()}`;
            } else if (createdInquiryId) {
                window.location.href = `book-consultation.php?inquiry_id=${createdInquiryId}`;
            } else {
                window.location.href = 'book-consultation.php';
            }
        }
    </script>
</body>
</html>
