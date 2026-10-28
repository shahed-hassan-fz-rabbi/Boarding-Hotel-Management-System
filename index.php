<?php
/**
 * ==========================================
 * BOARDING HOTEL MANAGEMENT SYSTEM
 * Central Dashboard — hero, live statistics,
 * analytics & room grid panel
 * ==========================================
 */

require_once 'includes/auth_check.php'; // Fetch db.php & connection internally
require 'components/header.php';

/* ============================
   Fetch All Hostel Rooms
============================ */
$sql = "SELECT * FROM hostel_rooms ORDER BY room_number ASC";
$result = $conn->query($sql);

$rooms = [];
$totalRooms = 0;
$availableRooms = 0;
$fullRooms = 0;
$totalBeds = 0;
$occupiedBeds = 0;
$availableBeds = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['available_beds'] = $row['total_beds'] - $row['allocated_beds'];
        $row['occupancy_percent'] = $row['total_beds'] > 0 ? round(($row['allocated_beds'] / $row['total_beds']) * 100) : 0;

        $rooms[] = $row;

        $totalRooms++;
        if ($row['status'] == 'Available') {
            $availableRooms++;
        } else {
            $fullRooms++;
        }

        $totalBeds += $row['total_beds'];
        $occupiedBeds += $row['allocated_beds'];
        $availableBeds += $row['available_beds'];
    }
}

$occupancyPercent = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;

/* ============================
   Today's check-ins (real data)
============================ */
$todayCheckins = 0;
if ($r = $conn->query("SELECT COUNT(*) c FROM room_allocations WHERE check_in = CURDATE() AND status = 'Active'")) {
    $todayCheckins = (int)$r->fetch_assoc()['c'];
}

/* ============================
   Revenue: collected this month + outstanding dues
============================ */
$collectedThisMonth = 0.0;
$outstandingDues = 0.0;
$thisMonth = (int)date('n');
$thisYear  = (int)date('Y');

if ($r = $conn->query("SELECT COALESCE(SUM(paid_amount),0) s FROM payments WHERE month = $thisMonth AND year = $thisYear")) {
    $collectedThisMonth = (float)$r->fetch_assoc()['s'];
}
if ($r = $conn->query("SELECT COALESCE(SUM(due_amount),0) s FROM payments")) {
    $outstandingDues = (float)$r->fetch_assoc()['s'];
}

/* ============================
   Revenue by month — last 6 months (real payment data)
============================ */
$revenueSeries = [];
for ($i = 5; $i >= 0; $i--) {
    $m = $thisMonth - $i;
    $y = $thisYear;
    while ($m <= 0) { $m += 12; $y--; }
    $revenueSeries[] = ['month' => $m, 'year' => $y, 'label' => date('M', mktime(0, 0, 0, $m, 1)), 'total' => 0.0];
}
if (!empty($revenueSeries)) {
    $from = reset($revenueSeries);
    $to = end($revenueSeries);
    $revRes = $conn->query("SELECT month, year, COALESCE(SUM(paid_amount),0) s
                            FROM payments
                            WHERE (year > {$from['year']}) OR (year = {$from['year']} AND month >= {$from['month']})");
    if ($revRes) {
        while ($row = $revRes->fetch_assoc()) {
            foreach ($revenueSeries as &$slot) {
                if ((int)$slot['month'] === (int)$row['month'] && (int)$slot['year'] === (int)$row['year']) {
                    $slot['total'] = (float)$row['s'];
                }
            }
            unset($slot);
        }
    }
}
$maxRevenue = max($revenueSeries);

/* ============================
   Occupancy by wing (from real room data)
============================ */
$wings = ['Boys' => ['t' => 0, 'o' => 0], 'Girls' => ['t' => 0, 'o' => 0], 'Staff' => ['t' => 0, 'o' => 0]];
foreach ($rooms as $room) {
    $w = $room['room_type'] ?? '';
    if (isset($wings[$w])) {
        $wings[$w]['t'] += (int)$room['total_beds'];
        $wings[$w]['o'] += (int)$room['allocated_beds'];
    }
}

/* ============================
   Recent payments (real data, last 5)
============================ */
$recentPayments = [];
if ($r = $conn->query("SELECT p.id, p.month, p.year, p.paid_amount, p.due_amount, p.payment_status,
                              CONCAT(s.first_name, ' ', s.last_name) AS student_name, s.student_code
                       FROM payments p
                       LEFT JOIN students s ON s.id = p.student_id
                       ORDER BY p.updated_at DESC, p.id DESC
                       LIMIT 5")) {
    while ($row = $r->fetch_assoc()) { $recentPayments[] = $row; }
}

/* ============================
   Recent activity (real audit trail, last 6)
============================ */
$recentActivity = [];
if ($r = $conn->query("SELECT a.action, a.created_at, u.full_name, u.role
                       FROM activity_logs a
                       LEFT JOIN users u ON u.id = a.user_id
                       ORDER BY a.id DESC
                       LIMIT 6")) {
    while ($row = $r->fetch_assoc()) { $recentActivity[] = $row; }
}

$roomsJSON = json_encode($rooms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

/* Helpers for presentable money + month names */
function money_bdt(float $n): string { return 'Tk ' . number_format($n, 0); }
function month_name(int $m): string { return date('M', mktime(0, 0, 0, $m, 1)); }
?>

<style>
    .dash-hero {
        position: relative;
        border-radius: 24px;
        overflow: hidden;
        background:
            radial-gradient(760px 340px at 88% 10%, rgba(255, 209, 184, 0.38) 0%, transparent 55%),
            radial-gradient(620px 420px at 8% 90%, rgba(41, 37, 33, 0.35) 0%, transparent 52%),
            var(--grad-primary);
        color: #FFF4EC;
        padding: 2.4rem 2.5rem;
    }

    .dash-hero::before {
        content: "";
        position: absolute; inset: 0;
        background:
            radial-gradient(circle at 78% 30%, rgba(255,244,236,0.14) 0%, transparent 32%),
            radial-gradient(circle at 12% 12%, rgba(255,244,236,0.10) 0%, transparent 26%);
        pointer-events: none;
    }

    .hero-chip {
        display: inline-flex; align-items: center; gap: 0.5rem;
        background: rgba(255, 248, 243, 0.14);
        border: 1px solid rgba(255, 248, 243, 0.30);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        border-radius: 999px;
        padding: 0.45rem 0.95rem;
        font-size: 0.78rem; font-weight: 700;
        color: #FFF4EC;
    }

    .hero-action {
        display: inline-flex; align-items: center; gap: 0.5rem;
        background: rgba(255, 248, 243, 0.14);
        border: 1px solid rgba(255, 248, 243, 0.30);
        -webkit-backdrop-filter: blur(10px);
        backdrop-filter: blur(10px);
        color: #FFF4EC;
        border-radius: 12px;
        padding: 0.6rem 1.05rem;
        font-size: 0.82rem; font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .hero-action:hover {
        background: rgba(255, 248, 243, 0.24);
        transform: translateY(-1px);
        color: #fff;
    }

    .stat-tile {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.1rem 1.2rem;
        box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 0.9rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.3s ease, border-color 0.3s ease;
    }

    .stat-tile:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }

    .stat-tile .tile-icon {
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }

    .tile-terracotta { background: rgba(201, 79, 27, 0.10); color: var(--terracotta); }
    .tile-coral      { background: rgba(255, 135, 95, 0.12); color: var(--coral); }
    .tile-peach      { background: rgba(255, 177, 138, 0.16); color: #C05A33; }
    [data-theme="dark"] .tile-peach { color: var(--peach); }
    .tile-success    { background: var(--success-soft); color: var(--success); }
    .tile-warning    { background: var(--warning-soft); color: var(--warning); }
    .tile-error      { background: var(--error-soft); color: var(--error); }

    .chart-bar {
        fill: url(#revGradient);
        rx: 5;
        transition: opacity 0.15s ease;
        cursor: default;
    }
    .chart-bar:hover { opacity: 0.82; }

    .wing-track { background: var(--bg-2); border-radius: 999px; overflow: hidden; height: 8px; }
    [data-theme="dark"] .wing-track { background: rgba(25, 22, 19, 0.55); }

    .card-room { animation: fadeInUp 0.4s ease-out forwards; opacity: 0; }

    .donut-center { font-weight: 800; }

    .section-title {
        font-size: 1.02rem; font-weight: 800; letter-spacing: -0.01em;
        color: var(--text);
    }

    .activity-item { display: flex; gap: 0.8rem; padding: 0.7rem 0; border-bottom: 1px solid var(--border); }
    .activity-item:last-child { border-bottom: none; }
</style>

<div class="max-w-[1600px] mx-auto space-y-6">

    <!-- ================= HERO ================= -->
    <section class="dash-hero">
        <div style="position: relative; z-index: 2;" class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
            <div>
                <span class="hero-chip">
                    <?= icon('calendar', 'w-3.5 h-3.5') ?>
                    <?= date('l, d F Y') ?>
                </span>
                <h1 class="text-2xl lg:text-3xl font-extrabold mt-4 tracking-tight" style="color: #FFF4EC;">
                    Welcome back, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>
                </h1>
                <p class="text-sm mt-1.5 font-semibold" style="color: rgba(255,244,236,0.78); max-width: 34rem;">
                    Here is how the boarding house is doing today — occupancy, collections and the front desk at a glance.
                </p>
            </div>

            <div class="flex flex-wrap gap-2.5">
                <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
                    <a href="<?= BASE_URL ?>allocations/list.php" class="hero-action"><?= icon('allocations', 'w-4 h-4') ?> Allocate Room</a>
                <?php endif; ?>
                <?php if (in_array($user_role, ['Admin', 'Teacher', 'Hostel Supervisor'])): ?>
                    <a href="<?= BASE_URL ?>students/add.php" class="hero-action"><?= icon('students', 'w-4 h-4') ?> New Admission</a>
                <?php endif; ?>
                <?php if (in_array($user_role, ['Admin', 'Accountant'])): ?>
                    <a href="<?= BASE_URL ?>payments/add.php" class="hero-action"><?= icon('payments', 'w-4 h-4') ?> Record Payment</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ================= STATISTICS ================= -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        <div class="stat-tile">
            <div class="tile-icon tile-terracotta"><?= icon('building', 'w-5 h-5') ?></div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Total Rooms</p>
                <h3 class="text-2xl font-extrabold mt-0.5" style="color: var(--text);"><?= $totalRooms ?></h3>
            </div>
        </div>
        <div class="stat-tile">
            <div class="tile-icon tile-success"><?= icon('check-circle', 'w-5 h-5') ?></div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Available Rooms</p>
                <h3 class="text-2xl font-extrabold mt-0.5" style="color: var(--success);"><?= $availableRooms ?></h3>
            </div>
        </div>
        <div class="stat-tile">
            <div class="tile-icon tile-error"><?= icon('x-circle', 'w-5 h-5') ?></div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Full Rooms</p>
                <h3 class="text-2xl font-extrabold mt-0.5" style="color: var(--error);"><?= $fullRooms ?></h3>
            </div>
        </div>
        <div class="stat-tile">
            <div class="tile-icon tile-coral"><?= icon('bed', 'w-5 h-5') ?></div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Occupied Beds</p>
                <h3 class="text-2xl font-extrabold mt-0.5" style="color: var(--text);"><?= $occupiedBeds ?><span class="text-sm font-bold" style="color: var(--text-3);">/<?= $totalBeds ?></span></h3>
            </div>
        </div>
        <div class="stat-tile">
            <div class="tile-icon tile-peach"><?= icon('log-in', 'w-5 h-5') ?></div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Today's Check-ins</p>
                <h3 class="text-2xl font-extrabold mt-0.5" style="color: var(--text);"><?= $todayCheckins ?></h3>
            </div>
        </div>
        <div class="stat-tile">
            <div class="tile-icon tile-warning"><?= icon('wallet', 'w-5 h-5') ?></div>
            <div>
                <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Collected (<?= date('M') ?>)</p>
                <h3 class="text-2xl font-extrabold mt-0.5" style="color: var(--text);"><?= money_bdt($collectedThisMonth) ?></h3>
            </div>
        </div>
    </div>

    <!-- ================= ANALYTICS ================= -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Revenue by month -->
        <div class="glass p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-2">
                <h2 class="section-title flex items-center gap-2"><?= icon('banknote', 'w-4.5 h-4.5') ?> Revenue — last 6 months</h2>
                <span class="badge" style="background: var(--warning-soft); color: var(--warning);">Outstanding <?= money_bdt($outstandingDues) ?></span>
            </div>
            <p class="text-xs font-semibold mb-4" style="color: var(--text-3);">Collected payments per month (room + meal + fines)</p>
            <svg viewBox="0 0 560 200" class="w-full" role="img" aria-label="Revenue by month bar chart" style="max-height: 210px;">
                <defs>
                    <linearGradient id="revGradient" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#FF875F"/>
                        <stop offset="100%" stop-color="#C94F1B"/>
                    </linearGradient>
                </defs>
                <!-- gridlines -->
                <?php for ($i = 1; $i <= 4; $i++): $gy = 180 - $i * 40; ?>
                    <line x1="34" y1="<?= $gy ?>" x2="552" y2="<?= $gy ?>" stroke="var(--border)" stroke-width="1" stroke-dasharray="3 5" opacity="0.6"/>
                <?php endfor; ?>
                <?php
                $n = count($revenueSeries);
                $chartW = 518; $baseY = 180; $maxBar = 140;
                $slotW = $chartW / $n;
                foreach ($revenueSeries as $i => $slot):
                    $h = $maxRevenue['total'] > 0 ? round(($slot['total'] / $maxRevenue['total']) * $maxBar) : 0;
                    $h = max($h, 2);
                    $x = 34 + $i * $slotW + $slotW * 0.22;
                    $w = $slotW * 0.56;
                    $y = $baseY - $h;
                    $labelX = 34 + $i * $slotW + $slotW / 2;
                ?>
                    <rect class="chart-bar" x="<?= $x ?>" y="<?= $y ?>" width="<?= $w ?>" height="<?= $h ?>" rx="6"/>
                    <text x="<?= $labelX ?>" y="196" text-anchor="middle" font-size="11" font-weight="700" fill="var(--text-3)"><?= htmlspecialchars($slot['label']) ?></text>
                    <?php if ($slot['total'] > 0): ?>
                        <text x="<?= $labelX ?>" y="<?= $y - 7 ?>" text-anchor="middle" font-size="10.5" font-weight="800" fill="var(--text-2)"><?= number_format($slot['total']) ?></text>
                    <?php endif; ?>
                <?php endforeach; ?>
            </svg>
        </div>

        <!-- Occupancy overview -->
        <div class="glass p-6">
            <h2 class="section-title flex items-center gap-2 mb-1"><?= icon('pie', 'w-4.5 h-4.5') ?> Occupancy overview</h2>
            <p class="text-xs font-semibold mb-4" style="color: var(--text-3);">Beds occupied across all wings</p>

            <div class="flex items-center justify-center my-2">
                <svg viewBox="0 0 120 120" width="170" height="170" role="img" aria-label="Overall occupancy <?= $occupancyPercent ?> percent">
                    <circle cx="60" cy="60" r="48" fill="none" stroke="var(--bg-2)" stroke-width="14"/>
                    <circle cx="60" cy="60" r="48" fill="none" stroke="url(#donutGrad)" stroke-width="14"
                            stroke-linecap="round"
                            stroke-dasharray="<?= round(2 * M_PI * 48 * $occupancyPercent / 100) ?> <?= round(2 * M_PI * 48) ?>"
                            transform="rotate(-90 60 60)"/>
                    <defs>
                        <linearGradient id="donutGrad" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#FF875F"/>
                            <stop offset="100%" stop-color="#C94F1B"/>
                        </linearGradient>
                    </defs>
                    <text class="donut-center" x="60" y="58" text-anchor="middle" font-size="24" fill="var(--text)"><?= $occupancyPercent ?>%</text>
                    <text x="60" y="76" text-anchor="middle" font-size="9.5" font-weight="700" fill="var(--text-3)" letter-spacing="1">OCCUPIED</text>
                </svg>
            </div>

            <?php foreach ($wings as $wingName => $w):
                $pct = $w['t'] > 0 ? round(($w['o'] / $w['t']) * 100) : 0; ?>
                <div class="mb-3">
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-xs font-bold" style="color: var(--text-2);"><?= $wingName ?> wing</span>
                        <span class="text-xs font-extrabold" style="color: var(--text);"><?= $w['o'] ?>/<?= $w['t'] ?> beds</span>
                    </div>
                    <div class="wing-track">
                        <div class="progress-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ================= ROOM GRID ================= -->
    <div class="flex items-center justify-between">
        <h2 class="section-title flex items-center gap-2"><?= icon('building', 'w-4.5 h-4.5') ?> Room Registry</h2>
    </div>

    <!-- Toolbar Filters -->
    <div class="glass p-4 flex flex-col md:flex-row gap-4 no-print">
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--text-3);"><?= icon('search', 'w-4 h-4') ?></span>
            <input type="text" id="searchInput" placeholder="Search by room number..." class="input-field pl-9">
        </div>

        <select id="filterType" class="input-field md:w-52">
            <option value="">All Room Wings</option>
            <option value="Boys">Boys Wing</option>
            <option value="Girls">Girls Wing</option>
            <option value="Staff">Staff Wing</option>
        </select>

        <select id="sortRooms" class="input-field md:w-60">
            <option value="asc">Sort: Room Number (Asc)</option>
            <option value="desc">Sort: Room Number (Desc)</option>
            <option value="available">Most Available Beds</option>
            <option value="occupied">Most Occupied Beds</option>
        </select>
    </div>

    <!-- Room Layout Grid -->
    <div id="roomContainer" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Cards Injected Dynamically via JS -->
    </div>

    <!-- Zero State Box -->
    <div id="noResults" class="hidden no-results">
        No rooms match your search or filter parameters.
    </div>

    <!-- ================= RECENT FEEDS ================= -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        <!-- Recent payments -->
        <div class="glass p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="section-title flex items-center gap-2"><?= icon('wallet', 'w-4.5 h-4.5') ?> Recent Payments</h2>
                <?php if (in_array($user_role, ['Admin', 'Accountant'])): ?>
                    <a href="<?= BASE_URL ?>payments/list.php" class="btn-sm" style="text-decoration: none;">View all <?= icon('arrow-right', 'w-3.5 h-3.5') ?></a>
                <?php endif; ?>
            </div>

            <?php if (empty($recentPayments)): ?>
                <div class="no-results" style="padding: 2rem;">No payments recorded yet.</div>
            <?php else: ?>
                <div class="space-y-1">
                    <?php foreach ($recentPayments as $p): ?>
                        <div class="activity-item">
                            <div class="tile-icon <?= $p['payment_status'] === 'Paid' ? 'tile-success' : ($p['payment_status'] === 'Partial' ? 'tile-warning' : 'tile-error') ?>" style="width: 36px; height: 36px;">
                                <?= icon('user', 'w-4 h-4') ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold truncate" style="color: var(--text);"><?= htmlspecialchars($p['student_name'] ?? 'Unknown student') ?></p>
                                <p class="text-xs font-semibold" style="color: var(--text-3);">
                                    <?= htmlspecialchars($p['student_code'] ?? '') ?> — <?= month_name((int)$p['month']) ?> <?= (int)$p['year'] ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-extrabold" style="color: var(--text);"><?= money_bdt((float)$p['paid_amount']) ?></p>
                                <span class="status-<?= strtolower($p['payment_status']) ?> mt-0.5 inline-block"><?= $p['payment_status'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent activity -->
        <div class="glass p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="section-title flex items-center gap-2"><?= icon('activity', 'w-4.5 h-4.5') ?> Recent Activity</h2>
                <?php if ($user_role === 'Admin'): ?>
                    <a href="<?= BASE_URL ?>settings/logs.php" class="btn-sm" style="text-decoration: none;">Audit trail <?= icon('arrow-right', 'w-3.5 h-3.5') ?></a>
                <?php endif; ?>
            </div>

            <?php if (empty($recentActivity)): ?>
                <div class="no-results" style="padding: 2rem;">No activity recorded yet.</div>
            <?php else: ?>
                <div class="space-y-1">
                    <?php foreach ($recentActivity as $a): ?>
                        <div class="activity-item">
                            <div class="tile-icon tile-coral" style="width: 36px; height: 36px;">
                                <?= icon('activity', 'w-4 h-4') ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold truncate" style="color: var(--text);" title="<?= htmlspecialchars($a['action']) ?>">
                                    <?= htmlspecialchars($a['action']) ?>
                                </p>
                                <p class="text-xs font-semibold" style="color: var(--text-3);">
                                    <?= htmlspecialchars($a['full_name'] ?? 'System') ?> · <?= date('d M, h:i A', strtotime($a['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
    const allRooms = <?php echo $roomsJSON; ?>;

    /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
    function esc(v) {
        return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    const searchInput = document.getElementById('searchInput');
    const filterType = document.getElementById('filterType');
    const sortRooms = document.getElementById('sortRooms');
    const roomContainer = document.getElementById('roomContainer');
    const noResults = document.getElementById('noResults');

    function renderRooms(rooms) {
        roomContainer.innerHTML = '';

        if (rooms.length === 0) {
            noResults.classList.remove('hidden');
            return;
        }
        noResults.classList.add('hidden');

        rooms.forEach((room, index) => {
            const badgeClass = room.status === 'Available' ? 'badge-available' : 'badge-full';
            const badgeText = room.status === 'Available' ? 'Available' : 'Full';
            const roomTypeClass = `room-type-${room.room_type?.toLowerCase()}`;

            const card = document.createElement('div');
            card.className = 'glass card-room overflow-hidden';
            card.style.animationDelay = `${index * 0.02}s`;
            card.innerHTML = `
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-xl font-extrabold" style="color: var(--text);">${esc(room.room_number)}</h3>
                            <span class="room-type-badge mt-1.5 ${roomTypeClass}">${esc(room.room_type)} Wing</span>
                        </div>
                        <span class="${badgeClass}">${badgeText}</span>
                    </div>

                    <p class="text-xs font-medium mb-4 italic truncate" style="color: var(--text-3);">${esc(room.description || 'No description available.')}</p>

                    <div class="grid grid-cols-3 gap-2.5 mb-4">
                        <div class="rounded-xl p-2 text-center" style="background: var(--surface-2); border: 1px solid var(--border);">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Total</p>
                            <p class="text-md font-extrabold" style="color: var(--text);">${room.total_beds}</p>
                        </div>
                        <div class="rounded-xl p-2 text-center" style="background: var(--surface-2); border: 1px solid var(--border);">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Occupied</p>
                            <p class="text-md font-extrabold" style="color: var(--terracotta);">${room.allocated_beds}</p>
                        </div>
                        <div class="rounded-xl p-2 text-center" style="background: var(--surface-2); border: 1px solid var(--border);">
                            <p class="text-[10px] font-extrabold uppercase tracking-wider" style="color: var(--text-3);">Vacant</p>
                            <p class="text-md font-extrabold" style="color: var(--success);">${room.available_beds}</p>
                        </div>
                    </div>

                    <div class="mb-1">
                        <div class="flex justify-between items-center mb-1">
                            <p class="text-xs font-bold" style="color: var(--text-2);">Occupancy Rate</p>
                            <p class="text-xs font-extrabold" style="color: var(--text);">${room.occupancy_percent}%</p>
                        </div>
                        <div class="progress-track rounded-full h-2">
                            <div class="progress-fill" style="width: ${room.occupancy_percent}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 pt-2">
                        <a href="allocations/list.php" class="btn-secondary w-full text-xs" style="width: 100%;">
                            Open Room Registry
                        </a>
                    </div>
                </div>
            `;
            roomContainer.appendChild(card);
        });
    }

    function applyFiltersAndSort() {
        let result = [...allRooms];
        const searchTerm = searchInput.value.toLowerCase().trim();
        const filterValue = filterType.value;
        const sortValue = sortRooms.value;

        if (searchTerm) {
            result = result.filter(room => room.room_number.toLowerCase().includes(searchTerm));
        }

        if (filterValue) {
            result = result.filter(room => room.room_type === filterValue);
        }

        if (sortValue === 'asc') {
            result.sort((a, b) => a.room_number.localeCompare(b.room_number));
        } else if (sortValue === 'desc') {
            result.sort((a, b) => b.room_number.localeCompare(a.room_number));
        } else if (sortValue === 'available') {
            result.sort((a, b) => b.available_beds - a.available_beds);
        } else if (sortValue === 'occupied') {
            result.sort((a, b) => b.allocated_beds - a.allocated_beds);
        }

        renderRooms(result);
    }

    searchInput.addEventListener('input', applyFiltersAndSort);
    filterType.addEventListener('change', applyFiltersAndSort);
    sortRooms.addEventListener('change', applyFiltersAndSort);

    // Bootstrap execution
    renderRooms(allRooms);
</script>

<?php
require 'components/footer.php';
?>
