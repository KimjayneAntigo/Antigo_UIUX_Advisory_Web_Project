<?php
// 500.php — custom internal server / database error page for Antigo Web App
http_response_code(500);

// Log error only, do not display raw traces to visitor
ini_set('display_errors', 0);

$isDbError = (isset($_GET['error']) && $_GET['error'] === 'db');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isDbError ? 'Database Connection Error' : 'Server Error' ?> | Antigo UI/UX Advisory</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        :root {
            --navy: #13224B; --violet: #6C5BB5; --blue: #4C6CCB;
            --grad: linear-gradient(100deg, #13224B 0%, #6C5BB5 55%, #4C6CCB 100%);
        }
        body { font-family: 'Poppins', sans-serif; background: #F4F6F8; color: #13224B; margin:0; }
        .btn-primary { background: var(--grad); color: #fff; box-shadow: 0 10px 24px -8px rgba(76,108,203,.45); transition: transform .2s, box-shadow .2s; border-radius: 50px; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 14px 28px -8px rgba(76,108,203,.6); }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center justify-center p-8 text-center">

    <div class="max-w-lg">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-3 mb-10">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-r from-[#4C6CCB] to-[#6C5BB5] flex items-center justify-center text-white font-extrabold text-lg shadow-sm">
                A
            </div>
            <div class="text-left">
                <div style="font-weight:800;font-size:18px;letter-spacing:.05em;color:#13224B;">ANTIGO</div>
                <div style="font-size:9px;letter-spacing:.22em;color:#6C5BB5;text-transform:uppercase;font-weight:700;">UI/UX ADVISORY</div>
            </div>
        </div>

        <!-- 500 graphic -->
        <div class="mb-6">
            <div class="text-[96px] font-extrabold leading-none" style="background: linear-gradient(100deg,#13224B,#6C5BB5,#4C6CCB); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">500</div>
        </div>

        <h1 class="text-2xl font-extrabold text-[#13224B] mb-3">
            <?= $isDbError ? 'Database Connection Error' : 'Unexpected Server Error' ?>
        </h1>
        <p class="text-sm text-[#4b4b4b] mb-8 leading-relaxed">
            <?= $isDbError 
                ? 'We are experiencing difficulty connecting to the database server. Please ensure the MySQL service is running in your control panel and refresh.'
                : 'An unexpected error occurred while processing your request. Please try again or return to the main workspace.' ?>
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <?php
                $homePath = (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/') || str_contains($_SERVER['REQUEST_URI'] ?? '', '/client/'))
                    ? '../home.php'
                    : 'home.php';
            ?>
            <a href="<?= $homePath ?>" class="btn-primary px-8 py-3.5 font-bold text-xs uppercase tracking-wider inline-flex items-center justify-center gap-2">
                <iconify-icon icon="lucide:home"></iconify-icon>
                Back to Home
            </a>
            <a href="javascript:location.reload()" class="px-8 py-3.5 rounded-full font-bold text-xs uppercase tracking-wider inline-flex items-center justify-center gap-2 border border-[rgba(19,34,75,0.12)] text-[#4b4b4b] hover:bg-white transition-colors">
                <iconify-icon icon="lucide:refresh-cw"></iconify-icon>
                Try Again
            </a>
        </div>
    </div>

    <footer class="mt-16 text-xs text-[#8890AA]">
        &copy; 2026 Antigo UI/UX Advisory. All rights reserved.
    </footer>
</body>
</html>
