<?php
/**
 * ==========================================
 * MASTER LAYOUT ENGINE (HEADER + SIDEBAR + NAVBAR)
 * Warm boutique-hotel design system — light & dark
 * ==========================================
 */

require_once __DIR__ . '/icon.php';

// Dynamically determine the active page and folder for mapping
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

$pageTitles = [
    'students'    => 'Student Management',
    'attendance'  => 'Attendance Management',
    'payments'    => 'Payment Management',
    'staff'       => 'Staff Management',
    'reports'     => 'Reports & Analytics',
    'settings'    => 'Settings & Configurations',
    'meals'       => 'Meal Management',
    'complaints'  => 'Complaint Management',
    'leaves'      => 'Leave Management',
    'allocations' => 'Room Allocation',
    'notices'     => 'Notice Board'
];

$page_title_display = $pageTitles[$current_dir] ?? 'Dashboard';

/* Fetch Active Notices for the CSS Ticker */
$ticker_text = "";
$tickerSql = "SELECT title FROM notices WHERE status = 'Active' ORDER BY id DESC LIMIT 5";
$tickerResult = $conn->query($tickerSql);
if ($tickerResult && $tickerResult->num_rows > 0) {
    $notices_array = [];
    while ($t_row = $tickerResult->fetch_assoc()) {
        $notices_array[] = "&bull; " . htmlspecialchars($t_row['title']);
    }
    $ticker_text = implode(" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ", $notices_array);
} else {
    $ticker_text = "&bull; Welcome to the Boarding Hotel Management panel.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title_display ?> - Boarding Hotel</title>

    <!-- Early theme resolution: runs before first paint to prevent FOUC -->
    <script>
    (function () {
        try {
            var t = localStorage.getItem('bhm-theme');
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        terracotta: { DEFAULT: '#C94F1B', dark: '#A93C16', soft: '#FF875F' },
                        peach: { DEFAULT: '#FFB18A', soft: '#FFD0B8' },
                        ivory: '#FFF8F3',
                        cream: '#F7EDE5',
                        warmgray: '#E7DDD5',
                        charcoal: '#292521',
                        cocoabrown: '#786B63'
                    },
                    fontFamily: {
                        sans: ['Manrope', 'Inter', 'system-ui', 'Segoe UI', 'sans-serif']
                    }
                }
            }
        };
    </script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/theme.css">
    <script src="<?= BASE_URL ?>assets/app.js" defer></script>
</head>
<body>

    <!-- Mobile Overlay -->
    <div class="hidden fixed inset-0 bg-black/40 z-40" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <!-- MASTER SIDEBAR -->
    <aside class="sidebar no-print" id="projectSidebar">
        <div class="p-6 sidebar-brand-border">
            <div class="flex items-center justify-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg" style="background: var(--grad-primary);">
                    <?= icon('building', 'w-5 h-5') ?>
                </div>
                <div>
                    <h1 class="text-base font-extrabold tracking-wide leading-tight" style="color: var(--sidebar-text-strong);">Boarding Hotel</h1>
                    <p class="text-[10px] font-semibold mt-0.5 tracking-[0.18em] uppercase" style="color: #8A786B;">Management System</p>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav flex-1 p-4 overflow-y-auto">
            <a href="<?= BASE_URL ?>index.php" class="<?= ($current_page === 'index.php' && empty($current_dir)) ? 'active' : '' ?>">
                <?= icon('dashboard', 'w-[18px] h-[18px]') ?>
                <span>Dashboard</span>
            </a>

            <?php if (in_array($user_role, ['Admin', 'Teacher', 'Hostel Supervisor'])): ?>
                <a href="<?= BASE_URL ?>students/list.php" class="<?= $current_dir === 'students' ? 'active' : '' ?>">
                    <?= icon('students', 'w-[18px] h-[18px]') ?>
                    <span>Students</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Teacher'])): ?>
                <div class="sidebar-section-label">Academic</div>
                <a href="<?= BASE_URL ?>attendance/manage.php" class="<?= ($current_dir === 'attendance' && $current_page === 'manage.php') ? 'active' : '' ?>">
                    <?= icon('attendance', 'w-[18px] h-[18px]') ?>
                    <span>Attendance Sheets</span>
                </a>
                <a href="<?= BASE_URL ?>attendance/report.php" class="<?= ($current_dir === 'attendance' && $current_page === 'report.php') ? 'active' : '' ?>">
                    <?= icon('report', 'w-[18px] h-[18px]') ?>
                    <span>Attendance Report</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
                <div class="sidebar-section-label">Hostel</div>
                <a href="<?= BASE_URL ?>allocations/list.php" class="<?= $current_dir === 'allocations' ? 'active' : '' ?>">
                    <?= icon('allocations', 'w-[18px] h-[18px]') ?>
                    <span>Allocations</span>
                </a>
                <a href="<?= BASE_URL ?>meals/list.php" class="<?= $current_dir === 'meals' ? 'active' : '' ?>">
                    <?= icon('meals', 'w-[18px] h-[18px]') ?>
                    <span>Meals</span>
                </a>
                <a href="<?= BASE_URL ?>complaints/list.php" class="<?= $current_dir === 'complaints' ? 'active' : '' ?>">
                    <?= icon('complaints', 'w-[18px] h-[18px]') ?>
                    <span>Complaints</span>
                </a>
                <a href="<?= BASE_URL ?>leaves/list.php" class="<?= $current_dir === 'leaves' ? 'active' : '' ?>">
                    <?= icon('leaves', 'w-[18px] h-[18px]') ?>
                    <span>Leaves</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Accountant'])): ?>
                <div class="sidebar-section-label">Financial</div>
                <a href="<?= BASE_URL ?>payments/list.php" class="<?= $current_dir === 'payments' ? 'active' : '' ?>">
                    <?= icon('payments', 'w-[18px] h-[18px]') ?>
                    <span>Payments</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
                <div class="sidebar-section-label">Analytics</div>
                <a href="<?= BASE_URL ?>reports/analytics.php" class="<?= $current_dir === 'reports' ? 'active' : '' ?>">
                    <?= icon('analytics', 'w-[18px] h-[18px]') ?>
                    <span>Reports &amp; Analytics</span>
                </a>
            <?php endif; ?>

            <div class="sidebar-section-label">Communication</div>
            <a href="<?= BASE_URL ?>notices/list.php" class="<?= $current_dir === 'notices' ? 'active' : '' ?>">
                <?= icon('notices', 'w-[18px] h-[18px]') ?>
                <span>Notice Board</span>
            </a>

            <?php if ($user_role === 'Admin'): ?>
                <div class="sidebar-section-label">Administration</div>
                <a href="<?= BASE_URL ?>staff/list.php" class="<?= $current_dir === 'staff' ? 'active' : '' ?>">
                    <?= icon('staff', 'w-[18px] h-[18px]') ?>
                    <span>Staff Management</span>
                </a>
                <a href="<?= BASE_URL ?>users/list.php" class="<?= $current_dir === 'users' ? 'active' : '' ?>">
                    <?= icon('usermgmt', 'w-[18px] h-[18px]') ?>
                    <span>User Management</span>
                </a>
                <a href="<?= BASE_URL ?>settings/index.php" class="<?= $current_dir === 'settings' ? 'active' : '' ?>">
                    <?= icon('settings', 'w-[18px] h-[18px]') ?>
                    <span>Settings</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 sidebar-footer">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-extrabold text-xs shadow-md" style="background: var(--grad-primary);">
                    <?= strtoupper(substr(htmlspecialchars($user_name), 0, 2)) ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <h4 class="text-xs font-bold truncate" style="color: var(--sidebar-text-strong);"><?= htmlspecialchars($user_name) ?></h4>
                    <p class="text-[10px] truncate" style="color: var(--sidebar-text);"><?= htmlspecialchars($user_role) ?></p>
                </div>
                <a href="<?= BASE_URL ?>auth/logout.php" title="Log out" class="p-2 rounded-lg transition hover:bg-white/10" style="color: var(--sidebar-text);">
                    <?= icon('logout', 'w-4 h-4') ?>
                </a>
            </div>
        </div>
    </aside>

    <!-- MAIN WRAPPER CONTAINER -->
    <div class="main-container">

        <!-- TOP NAVBAR -->
        <header class="top-navbar px-6 flex justify-between items-center no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" aria-label="Toggle navigation" class="lg:hidden p-2 rounded-lg transition hover:bg-black/5" style="color: var(--text-2);"><?= icon('menu', 'w-5 h-5') ?></button>
                <div class="flex items-center gap-2 text-xs font-semibold" style="color: var(--text-3);">
                    <a href="<?= BASE_URL ?>index.php" class="transition hover:opacity-70">Dashboard</a>
                    <?php if(!empty($current_dir)): ?>
                        <span><?= icon('chevron-right', 'w-3 h-3') ?></span>
                        <span class="uppercase tracking-wider"><?= $current_dir ?></span>
                        <span><?= icon('chevron-right', 'w-3 h-3') ?></span>
                    <?php endif; ?>
                    <span class="font-bold" style="color: var(--text);"><?= $page_title_display ?></span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <span class="text-[10px] font-bold uppercase block tracking-wider" style="color: var(--text-3);">Logged in as</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-lg border" style="color: var(--terracotta); background: rgba(201,79,27,0.08); border-color: var(--peach-soft);"><?= htmlspecialchars($user_role) ?></span>
                </div>
                <div class="w-px h-6" style="background: var(--border);"></div>
                <div class="flex items-center gap-2 text-xs font-semibold" style="color: var(--text-2);">
                    <?= icon('calendar', 'w-3.5 h-3.5') ?>
                    <?= date('d M, Y') ?>
                </div>
                <button type="button" class="theme-toggle" aria-label="Switch to dark theme" aria-pressed="false" title="Toggle theme">
                    <?= icon('sun', 'icon-sun w-[18px] h-[18px]') ?>
                    <?= icon('moon', 'icon-moon w-[18px] h-[18px]') ?>
                </button>
            </div>
        </header>

        <!-- Notice ticker -->
        <div class="ticker-wrap no-print">
            <div class="ticker-label"><?= icon('notices', 'w-3.5 h-3.5') ?> Latest Notice</div>
            <div class="ticker-move">
                <?= $ticker_text ?>
            </div>
        </div>

        <main class="content-body">
