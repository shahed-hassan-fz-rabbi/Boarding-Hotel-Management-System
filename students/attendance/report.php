<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Advanced Attendance Analytics (Part 1)
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Role Guard
if (!in_array($user_role, ['Admin', 'Teacher', 'Hostel Supervisor'])) {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

// Filters Mapping
$selected_month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_order = isset($_GET['sort']) ? $_GET['sort'] : 'code_asc';

/* ============================
   1. Aggregate Stat Cards Query
============================ */
$cardsSql = "SELECT 
                COUNT(DISTINCT s.id) as total_students,
                COUNT(a.id) as total_records,
                COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as total_p,
                COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as total_a,
                COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as total_l
             FROM students s
             LEFT JOIN attendance a ON s.id = a.student_id 
                AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
             WHERE s.status = 'Active'";

$stmt = $conn->prepare($cardsSql);
$stmt->bind_param('ii', $selected_month, $selected_year);
$stmt->execute();
$cards = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Math Calculations safely
$t_rec = $cards['total_records'] > 0 ? $cards['total_records'] : 1;
$avg_present = round(($cards['total_p'] / $t_rec) * 100, 1);
$avg_absent = round(($cards['total_a'] / $t_rec) * 100, 1);
$avg_late = round(($cards['total_l'] / $t_rec) * 100, 1);

/* ============================
   2. Target Absentee High Alert Pipeline (>= 3 Absences)
============================ */
$absenteeSql = "SELECT s.student_code, s.first_name, s.last_name, s.phone, COUNT(a.id) as total_absences
                FROM students s
                JOIN attendance a ON s.id = a.student_id
                WHERE a.status = 'Absent' AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?
                GROUP BY s.id
                HAVING total_absences >= 3
                ORDER BY total_absences DESC";

$stmt = $conn->prepare($absenteeSql);
$stmt->bind_param('ii', $selected_month, $selected_year);
$stmt->execute();
$absenteeResult = $stmt->get_result();
$stmt->close();

/* ============================
   3. Main Report Sheet with Sort Engine
============================ */
$sortMapping = [
    'code_asc'  => 's.student_code ASC',
    'pct_desc'  => 'present_pct DESC',
    'pct_asc'   => 'present_pct ASC',
    'abs_desc'  => 'absent_days DESC'
];
$orderBy = $sortMapping[$sort_order] ?? 's.student_code ASC';

$mainSql = "SELECT s.id, s.student_code, s.first_name, s.last_name, s.gender, s.phone,
                COUNT(a.id) as total_days,
                COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_days,
                COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_days,
                COUNT(CASE WHEN a.status = 'Late' THEN 1 END) as late_days,
                (COUNT(CASE WHEN a.status = 'Present' THEN 1 END) / IF(COUNT(a.id)>0, COUNT(a.id), 1)) * 100 as present_pct
            FROM students s
            LEFT JOIN attendance a ON s.id = a.student_id AND MONTH(a.attendance_date) = ? AND YEAR(a.attendance_date) = ?" ;

if (!empty($search_query)) {
    $mainSql .= " WHERE s.status = 'Active' AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_code LIKE ?) ";
} else {
    $mainSql .= " WHERE s.status = 'Active' ";
}
$mainSql .= " GROUP BY s.id ORDER BY {$orderBy}";

$stmt = $conn->prepare($mainSql);
if (!empty($search_query)) {
    $like = "%{$search_query}%";
    $stmt->bind_param('iisss', $selected_month, $selected_year, $like, $like, $like);
} else {
    $stmt->bind_param('ii', $selected_month, $selected_year);
}
$stmt->execute();
$reportResult = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Analytics - Boarding School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
        .input-field { border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.5rem 1rem; border-radius: 10px; outline: none; transition: all 0.3s; color: #1e293b; font-weight: 500; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { padding: 1rem; background: #f1f5f9; text-align: left; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 1rem; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.95rem; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        @media print {
            body { background: white !important; }
            .no-print, aside, .mobile-header { display: none !important; }
            .main-container { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            .glass { box-shadow: none !important; border: none !important; background: white !important; }
        }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    
    <!-- Professional Title Module Header Area -->
    <div class="glass rounded-2xl p-6 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">📊 Attendance Analytics</h2>
            <p class="text-slate-600 mt-1">Monthly Attendance Report & Aggregated Statistical Chart Matrix</p>
        </div>
        <div class="flex gap-2 no-print">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-xl font-semibold hover:bg-slate-700 transition">🖨️ Print</button>
            <?php if (in_array($user_role, ['Admin', 'Teacher', 'Hostel Supervisor'])): ?>
                <button onclick="exportCSV()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-500 transition">📥 Export CSV</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 4 Dashboard Stat Cards Component -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="glass p-5 rounded-2xl text-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Active Students</p>
            <h3 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $cards['total_students'] ?></h3>
        </div>
        <div class="glass p-5 rounded-2xl text-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Net Monthly Present %</p>
            <h3 class="text-2xl font-extrabold text-emerald-600 mt-1"><?= $avg_present ?>%</h3>
        </div>
        <div class="glass p-5 rounded-2xl text-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Average Absent Rate</p>
            <h3 class="text-2xl font-extrabold text-rose-600 mt-1"><?= $avg_absent ?>%</h3>
        </div>
        <div class="glass p-5 rounded-2xl text-center">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Average Late Deviation</p>
            <h3 class="text-2xl font-extrabold text-amber-600 mt-1"><?= $avg_late ?>%</h3>
        </div>
    </div>

    <!-- Filters and Live Smart Query Panel -->
    <div class="glass rounded-2xl p-6 mb-6 no-print">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Search Identifier</label>
                <input type="text" name="search" class="input-field w-full" placeholder="ID or Name..." value="<?= htmlspecialchars($search_query) ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Target Month</label>
                <select name="month" class="input-field w-full" onchange="this.form.submit()">
                    <?php
                    $mNames = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
                    for ($m = 1; $m <= 12; $m++) {
                        echo "<option value='{$m}' ".($m == $selected_month ? 'selected':'').">{$mNames[$m]}</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Target Year</label>
                <select name="year" class="input-field w-full" onchange="this.form.submit()">
                    <?php
                    for ($y = date('Y')-2; $y <= date('Y')+1; $y++) {
                        echo "<option value='{$y}' ".($y == $selected_year ? 'selected':'').">{$y}</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-600 mb-1">Sort Matrix Engine</label>
                <select name="sort" class="input-field w-full" onchange="this.form.submit()">
                    <option value="code_asc" <?= $sort_order=='code_asc'?'selected':'' ?>>Student Code (Asc)</option>
                    <option value="pct_desc" <?= $sort_order=='pct_desc'?'selected':'' ?>>Highest Attendance %</option>
                    <option value="pct_asc" <?= $sort_order=='pct_asc'?'selected':'' ?>>Lowest Attendance %</option>
                    <option value="abs_desc" <?= $sort_order=='abs_desc'?'selected':'' ?>>Most Absences Tracked</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Layout Grid Systems -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        
        <!-- Live Table and Chart.js Display Area -->
        <div class="glass rounded-2xl p-6 lg:col-span-3 overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h3 class="text-lg font-bold text-slate-800">📋 Student Attendance Roll Records</h3>
                <div class="w-full sm:w-44 h-32 no-print"><canvas id="monthDistributionChart"></canvas></div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="data-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>Student Demographics</th>
                            <th>Gender</th>
                            <th class="text-center">P</th><th class="text-center">A</th><th class="text-center">L</th>
                            <th style="width: 200px;">Visual Occupancy Bar</th>
                            <th class="no-print">Secure Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($reportResult->num_rows > 0): ?>
                            <?php while ($r = $reportResult->fetch_assoc()): 
                                $pct = round($r['present_pct']);
                                $barColor = $pct >= 90 ? 'bg-emerald-500':($pct >= 75 ? 'bg-blue-500':($pct >= 60 ? 'bg-amber-500':'bg-rose-500'));
                                $textColor = $pct >= 90 ? 'text-emerald-600':($pct >= 75 ? 'text-blue-600':($pct >= 60 ? 'text-amber-600':'text-rose-600'));
                            ?>
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td>
                                        <div class="font-bold text-slate-900"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div>
                                        <div class="text-xs text-slate-400 font-mono flex items-center gap-2">
                                            <span><?= $r['student_code'] ?></span>
                                            <span>|</span>
                                            <a href="tel:<?= $r['phone'] ?>" class="text-indigo-600 hover:underline">📞 <?= htmlspecialchars($r['phone']) ?></a>
                                        </div>
                                    </td>
                                    <td><span class="text-xs px-2 py-1 rounded bg-slate-100 font-medium"><?= $r['gender'] ?></span></td>
                                    <td class="text-center font-bold text-emerald-600"><?= $r['present_days'] ?></td>
                                    <td class="text-center font-bold text-rose-600"><?= $r['absent_days'] ?></td>
                                    <td class="text-center font-bold text-amber-600"><?= $r['late_days'] ?></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-slate-200 rounded-full h-2.5">
                                                <div class="<?= $barColor ?> h-2.5 rounded-full" style="width: <?= $pct ?>%"></div>
                                            </div>
                                            <span class="font-bold text-xs w-8 text-right <?= $textColor ?>"><?= $pct ?>%</span>
                                        </div>
                                    </td>
                                    <td class="no-print">
                                        <?php if (in_array($user_role, ['Admin', 'Teacher'])): ?>
                                            <a href="manage.php?date=<?= date('Y-m-d') ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2 py-1 rounded">Modify Log</a>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">View Only</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-6 text-slate-400">No synchronized log rows match parameters.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3 Day Absent Tracker Sidebar Alert Map Component -->
        <div class="glass rounded-2xl p-6 overflow-hidden h-fit">
            <h3 class="text-md font-bold text-red-700 flex items-center gap-2">🚨 Critical High-Alert Tracker</h3>
            <p class="text-xs text-slate-500 mt-1 mb-4">Lists active students recorded <strong>Absent ≥ 3 Times</strong> during filter month.</p>
            
            <div class="space-y-3">
                <?php if ($absenteeResult->num_rows > 0): ?>
                    <?php while ($a = $absenteeResult->fetch_assoc()): ?>
                        <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl flex justify-between items-center transition hover:scale-[1.02]">
                            <div>
                                <h4 class="text-sm font-bold text-slate-800"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></h4>
                                <a href="tel:<?= $a['phone'] ?>" class="text-[11px] text-slate-500 font-mono block mt-0.5 hover:underline">📱 Call Guardian</a>
                            </div>
                            <span class="bg-rose-600 text-white text-[11px] font-extrabold px-2.5 py-1.5 rounded-lg">
                                <?= $a['total_absences'] ?> Days Absent
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-6 text-slate-400 font-medium bg-emerald-50 rounded-xl border border-emerald-200 text-emerald-800 text-xs p-3">
                        ✨ Operational Stability Secure!<br><span class="font-normal opacity-80">No active students skipped ≥ 3 days this month.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Core Engineering Script Execution Engine Mapping -->
<script>
    // 1. Chart.js Implementation
    new Chart(document.getElementById('monthDistributionChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [<?= $cards['total_p'] ?>, <?= $cards['total_a'] ?>, <?= $cards['total_l'] ?>],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 1
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 2. Pure JavaScript Tabular Row CSV Export Mapping Engine
    function exportCSV() {
        let csv = 'Student Code,Student Name,Gender,Present Days,Absent Days,Late Days,Attendance Percentage\n';
        let rows = document.querySelectorAll('#reportTable tbody tr');
        
        rows.forEach(tr => {
            let cols = tr.querySelectorAll('td');
            if(cols.length >= 6) {
                let name = cols[0].querySelector('.font-bold').innerText.replace(',','');
                let code = cols[0].querySelector('.font-mono text-xs, span').innerText;
                let gender = cols[1].innerText;
                let p = cols[2].innerText;
                let a = cols[3].innerText;
                let l = cols[4].innerText;
                let pct = cols[5].querySelector('.font-bold').innerText;
                csv += `${code},${name},${gender},${p},${a},${l},${pct}\n`;
            }
        });

        let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", `Attendance_Analytics_Report_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php require '../components/footer.php'; ?>
</body>
</html>
