<?php
/**
 * ==========================================
 * MASTER LAYOUT ENGINE (HEADER + SIDEBAR + NAVBAR)
 * Refactored Enterprise Edition for Final Year Submission
 * ==========================================
 */

// Dynamically determine the active page and folder for mapping
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// FIXED: Replaced ugly if-elseif block with a clean, maintainable Array Mapping
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
        $notices_array[] = "• " . htmlspecialchars($t_row['title']);
    }
    $ticker_text = implode(" &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ", $notices_array);
} else {
    $ticker_text = "• Welcome to Boarding School Management System Panel.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title_display ?> - Boarding School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* FIXED: Changed main background from gradient to clean Enterprise light-gray */
        body {
            background-color: #f8fafc;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
        }

        .glass {
            background: rgba(255, 255, 255, 1);
            border: 1px solid #e2e8f0;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.1);
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            z-index: 50;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .sidebar-nav a:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #f8fafc;
        }

        .sidebar-nav a.active {
            background: linear-gradient(90deg, #4f46e5 0%, #6366f1 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .main-container {
            margin-left: 280px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* FIXED: Clean top navbar with clear border */
        .top-navbar {
            height: 65px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: fixed;
            top: 0;
            right: 0;
            left: 280px;
            z-index: 40;
        }

        /* FIXED: Modern Scrolling Notice Ticker */
        .ticker-wrap {
            position: fixed;
            top: 65px;
            right: 0;
            left: 280px;
            height: 35px;
            z-index: 35;
            overflow: hidden;
            background: #fffbeb;
            border-bottom: 1px solid #fef3c7;
            display: flex;
            align-items: center;
        }

        .ticker-label {
            position: absolute;
            left: 0;
            height: 100%;
            background: #d97706;
            color: white;
            padding: 0 1.25rem;
            font-weight: 700;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            z-index: 10;
        }

        .ticker-move {
            display: inline-block;
            white-space: nowrap;
            padding-left: 100%;
            animation: ticker-animation 30s linear infinite;
            font-weight: 600;
            font-size: 0.85rem;
            color: #92400e;
        }

        .ticker-move:hover {
            animation-play-state: paused;
            cursor: pointer;
        }

        @keyframes ticker-animation {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-100%, 0, 0); }
        }

        .content-body {
            margin-top: 100px; /* Navbar (65px) + Ticker (35px) */
            padding: 2rem;
            flex: 1;
        }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-container { margin-left: 0; }
            .top-navbar { left: 0; }
            .ticker-wrap { left: 0; }
        }
    </style>
</head>
<body>

    <!-- Mobile Overlay -->
    <div class="hidden fixed inset-0 bg-black/40 z-40" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <!-- MASTER SIDEBAR -->
    <aside class="sidebar no-print" id="projectSidebar">
        <!-- FIXED: Renamed to Proposal Title -->
        <div class="p-6 border-b border-slate-800">
            <h1 class="text-xl font-black text-white tracking-wide text-center">🏫 Boarding School</h1>
            <p class="text-slate-500 text-[11px] text-center font-semibold mt-1">Management System</p>
        </div>

        <nav class="sidebar-nav flex-1 p-4 overflow-y-auto">
            <a href="<?= BASE_URL ?>index.php" class="<?= ($current_page === 'index.php' && empty($current_dir)) ? 'active' : '' ?>">
                <span>📊 Dashboard</span>
            </a>

            <?php if (in_array($user_role, ['Admin', 'Teacher', 'Hostel Supervisor'])): ?>
                <a href="<?= BASE_URL ?>students/list.php" class="<?= $current_dir === 'students' ? 'active' : '' ?>">
                    <span>👨‍🎓 Students</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Teacher'])): ?>
                <div class="text-[10px] font-bold text-slate-600 uppercase px-3 mt-4 mb-1 tracking-wider">Academic</div>
                <a href="<?= BASE_URL ?>attendance/manage.php" class="<?= ($current_dir === 'attendance' && $current_page === 'manage.php') ? 'active' : '' ?>">
                    <span>📝 Attendance Sheets</span>
                </a>
                <a href="<?= BASE_URL ?>attendance/report.php" class="<?= ($current_dir === 'attendance' && $current_page === 'report.php') ? 'active' : '' ?>">
                    <span>📉 Attendance Report</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
                <div class="text-[10px] font-bold text-slate-600 uppercase px-3 mt-4 mb-1 tracking-wider">Hostel</div>
                <a href="<?= BASE_URL ?>allocations/list.php" class="<?= $current_dir === 'allocations' ? 'active' : '' ?>">
                    <span>🔑 Allocations</span>
                </a>
                <a href="<?= BASE_URL ?>meals/list.php" class="<?= $current_dir === 'meals' ? 'active' : '' ?>">
                    <span>🍛 Meals</span>
                </a>
                <a href="<?= BASE_URL ?>complaints/list.php" class="<?= $current_dir === 'complaints' ? 'active' : '' ?>">
                    <span>⚠️ Complaints</span>
                </a>
                <a href="<?= BASE_URL ?>leaves/list.php" class="<?= $current_dir === 'leaves' ? 'active' : '' ?>">
                    <span>🏖️ Leaves</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Accountant'])): ?>
                <div class="text-[10px] font-bold text-slate-600 uppercase px-3 mt-4 mb-1 tracking-wider">Financial</div>
                <a href="<?= BASE_URL ?>payments/list.php" class="<?= $current_dir === 'payments' ? 'active' : '' ?>">
                    <span>💰 Payments</span>
                </a>
            <?php endif; ?>

            <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
                <div class="text-[10px] font-bold text-slate-600 uppercase px-3 mt-4 mb-1 tracking-wider">Analytics</div>
                <a href="<?= BASE_URL ?>reports/analytics.php" class="<?= $current_dir === 'reports' ? 'active' : '' ?>">
                    <span>📈 Reports & Analytics</span>
                </a>
            <?php endif; ?>

            <div class="text-[10px] font-bold text-slate-600 uppercase px-3 mt-4 mb-1 tracking-wider">Communication</div>
            <a href="<?= BASE_URL ?>notices/list.php" class="<?= $current_dir === 'notices' ? 'active' : '' ?>">
                <span>📢 Notice Board</span>
            </a>

            <?php if ($user_role === 'Admin'): ?>
                <div class="text-[10px] font-bold text-slate-600 uppercase px-3 mt-4 mb-1 tracking-wider">Administration</div>
                <a href="<?= BASE_URL ?>staff/list.php" class="<?= $current_dir === 'staff' ? 'active' : '' ?>">
                    <span>👥 Staff Management</span>
                </a>
                <a href="<?= BASE_URL ?>users/list.php" class="<?= $current_dir === 'users' ? 'active' : '' ?>">
                    <span>🔐 User Management</span>
                </a>
                <a href="<?= BASE_URL ?>settings/index.php" class="<?= $current_dir === 'settings' ? 'active' : '' ?>">
                    <span>⚙️ Settings</span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="p-4 border-t border-slate-800 bg-slate-950/40">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-black text-xs">
                    <?= strtoupper(substr($user_name, 0, 2)) ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <h4 class="text-white text-xs font-bold truncate"><?= htmlspecialchars($user_name) ?></h4>
                    <p class="text-slate-400 text-[10px] truncate"><?= htmlspecialchars($user_role) ?></p>
                </div>
            </div>
            <!-- FIXED: Changed to simple "Logout" -->
            <a href="<?= BASE_URL ?>auth/logout.php" class="w-full mt-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg text-xs font-bold text-center block transition">Logout</a>
        </div>
    </aside>

    <!-- MAIN WRAPPER CONTAINER -->
    <div class="main-container">
        
        <!-- TOP NAVBAR -->
        <header class="top-navbar px-6 flex justify-between items-center no-print">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg text-lg">☰</button>
                <!-- FIXED: Implemented Proper Breadcrumb System -->
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
                    <span class="hover:text-slate-600 cursor-pointer">Dashboard</span> 
                    <span>&gt;</span> 
                    <?php if(!empty($current_dir)): ?>
                        <span class="hover:text-slate-600 cursor-pointer uppercase"><?= $current_dir ?></span> 
                        <span>&gt;</span> 
                    <?php endif; ?>
                    <span class="text-slate-800 font-bold"><?= $page_title_display ?></span>
                </div>
            </div>

            <!-- FIXED: Changed Security Shield to "Logged in as" -->
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <span class="text-[10px] font-bold text-slate-400 uppercase block tracking-wider">Logged in as</span>
                    <span class="text-xs text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100"><?= htmlspecialchars($user_role) ?></span>
                </div>
                <div class="w-px h-6 bg-slate-200"></div>
                <div class="text-xs text-slate-500 font-medium">
                    🗓️ <?= date('d M, Y') ?>
                </div>
            </div>
        </header>

        <!-- FIXED: Notice Label to "Latest Notice" -->
        <div class="ticker-wrap no-print">
            <div class="ticker-label">📢 Latest Notice</div>
            <div class="ticker-move">
                <?= $ticker_text ?>
            </div>
        </div>

        <main class="content-body">