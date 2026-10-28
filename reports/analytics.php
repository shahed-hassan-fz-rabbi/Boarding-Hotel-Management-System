<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Reports & Analytics Dashboard (Part 1)
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Only Admin/Hostel Supervisor can access
if ($user_role !== 'Admin' && $user_role !== 'Hostel Supervisor') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

/* ============================
   1. General Statistics Cards
============================ */
$statsSql = "SELECT 
                (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
                (SELECT COUNT(*) FROM hostel_rooms) as total_rooms,
                (SELECT COUNT(*) FROM room_beds) as total_beds,
                (SELECT COUNT(*) FROM room_beds WHERE status = 'Occupied') as occupied_beds,
                (SELECT IFNULL(SUM(paid_amount), 0) FROM payments) as total_collection,
                (SELECT IFNULL(SUM(due_amount), 0) FROM payments) as total_due,
                (SELECT COUNT(*) FROM meal_records WHERE meal_date = CURDATE() AND served = 1) as meals_today,
                (SELECT COUNT(*) FROM complaints WHERE status = 'Open') as pending_complaints";

$statsResult = $conn->query($statsSql);
$stats = $statsResult ? $statsResult->fetch_assoc() : [];

/* ============================
   2. Gender Distribution (Pie Chart)
============================ */
$genderSql = "SELECT gender, COUNT(*) as count FROM students WHERE status = 'Active' GROUP BY gender";
$genderResult = $conn->query($genderSql);
$genderData = [];
while ($row = $genderResult->fetch_assoc()) {
    $genderData[] = $row;
}

/* ============================
   3. Room Occupancy by Type (Bar Chart)
============================ */
$roomOccupancySql = "SELECT 
                        hr.room_type,
                        COUNT(DISTINCT hr.id) as total_rooms,
                        COUNT(DISTINCT rb.id) as total_beds,
                        COUNT(DISTINCT CASE WHEN rb.status = 'Occupied' THEN rb.id END) as occupied_beds
                    FROM hostel_rooms hr
                    LEFT JOIN room_beds rb ON hr.id = rb.room_id
                    GROUP BY hr.room_type";

$roomOccupancyResult = $conn->query($roomOccupancySql);
$roomOccupancyData = [];
while ($row = $roomOccupancyResult->fetch_assoc()) {
    $roomOccupancyData[] = $row;
}

/* ============================
   4. Monthly Payment Trend (Line Chart)
============================ */
$paymentSql = "SELECT 
                    month,
                    year,
                    IFNULL(SUM(paid_amount), 0) as total_paid,
                    IFNULL(SUM(due_amount), 0) as total_due
                FROM payments
                WHERE year = YEAR(CURDATE())
                GROUP BY month, year
                ORDER BY month ASC";

$paymentResult = $conn->query($paymentSql);
$paymentData = [];
while ($row = $paymentResult->fetch_assoc()) {
    $paymentData[] = $row;
}

/* ============================
   5. Complaint Status Distribution
============================ */
$complaintSql = "SELECT status, COUNT(*) as count FROM complaints GROUP BY status";
$complaintResult = $conn->query($complaintSql);
$complaintData = [];
while ($row = $complaintResult->fetch_assoc()) {
    $complaintData[] = $row;
}

/* ============================
   6. Leave Statistics (Fixed Column 'id' Ambiguity)
============================ */
$leaveSql = "SELECT 
                lt.leave_type_name,
                COUNT(la.id) as total_applications,
                COUNT(CASE WHEN la.status = 'Approved' THEN la.id END) as approved,
                COUNT(CASE WHEN la.status = 'Pending' THEN la.id END) as pending,
                COUNT(CASE WHEN la.status = 'Rejected' THEN la.id END) as rejected
            FROM leave_types lt
            LEFT JOIN leave_applications la ON lt.id = la.leave_type_id AND YEAR(la.created_at) = YEAR(CURDATE())
            GROUP BY lt.id, lt.leave_type_name";

$leaveResult = $conn->query($leaveSql);
$leaveData = [];
while ($row = $leaveResult->fetch_assoc()) {
    $leaveData[] = $row;
}

/* JSON Formatting for Javascript Charts */
$genderJSON = json_encode($genderData);
$roomOccupancyJSON = json_encode($roomOccupancyData);
$paymentJSON = json_encode($paymentData);
$complaintJSON = json_encode($complaintData);
$leaveJSON = json_encode($leaveData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Boarding School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 16px;
            transition: transform 0.3s ease;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label { color: #64748b; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .chart-container { position: relative; height: 260px; width: 100%; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { padding: 0.75rem 1rem; background: #f1f5f9; text-align: left; font-weight: 600; color: #1e293b; }
        .data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e2e8f0; color: #475569; font-size: 0.9rem; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        
        @media print {
            body { background: white !important; color: black; }
            .no-print, aside, .mobile-header { display: none !important; }
            .main-container { margin-left: 0 !important; padding: 0 !important; }
            .glass { box-shadow: none !important; border: none !important; background: white !important; }
        }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    
    <!-- Title and Toolbar -->
    <div class="glass rounded-2xl p-6 mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">📊 Production Reports & Analytics</h2>
            <p class="text-slate-600 mt-1">Real-time data synchronization with active hostel database</p>
        </div>
        <div class="flex gap-3 no-print">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 text-white rounded-xl font-semibold hover:bg-slate-700 transition flex items-center gap-2 shadow-lg shadow-slate-800/20">
                🖨️ Print Report
            </button>
            <button onclick="exportCSV()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-semibold hover:bg-emerald-500 transition flex items-center gap-2 shadow-lg shadow-emerald-600/20">
                📥 Export CSV
            </button>
        </div>
    </div>

    <!-- 8 Main Core Dashboard Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="glass stat-card">
            <p class="stat-label">👨 Total Students</p>
            <h3 class="stat-number mt-2"><?= $stats['total_students'] ?? 0; ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">🏠 Total Rooms</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); -webkit-background-clip: text;"><?= $stats['total_rooms'] ?? 0; ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">🛏️ Total Beds</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #ec4899, #be185d); -webkit-background-clip: text;"><?= $stats['total_beds'] ?? 0; ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">✅ Occupied Beds</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #10b981, #047857); -webkit-background-clip: text;"><?= $stats['occupied_beds'] ?? 0; ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">💰 Total Collection</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #059669, #064e3b); -webkit-background-clip: text;">৳<?= number_format($stats['total_collection'] ?? 0, 0); ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">💸 Total Due</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #ef4444, #b91c1c); -webkit-background-clip: text;">৳<?= number_format($stats['total_due'] ?? 0, 0); ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">🍛 Meals Served Today</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #f59e0b, #d97706); -webkit-background-clip: text;"><?= $stats['meals_today'] ?? 0; ?></h3>
        </div>
        <div class="glass stat-card">
            <p class="stat-label">⚠️ Open Complaints</p>
            <h3 class="stat-number mt-2" style="background: linear-gradient(135deg, #7c3aed, #5b21b6); -webkit-background-clip: text;"><?= $stats['pending_complaints'] ?? 0; ?></h3>
        </div>
    </div>

    <!-- 5 Analytics Charts Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="glass rounded-2xl p-5">
            <h4 class="text-md font-bold text-slate-700 mb-3">Pie: Student Gender Ratio</h4>
            <div class="chart-container"><canvas id="genderChart"></canvas></div>
        </div>
        <div class="glass rounded-2xl p-5 lg:col-span-2">
            <h4 class="text-md font-bold text-slate-700 mb-3">Bar: Room Occupancy Capacity</h4>
            <div class="chart-container"><canvas id="occupancyChart"></canvas></div>
        </div>
        <div class="glass rounded-2xl p-5 lg:col-span-2">
            <h4 class="text-md font-bold text-slate-700 mb-3">Line: Monthly Collection vs Due (Current Year)</h4>
            <div class="chart-container"><canvas id="paymentChart"></canvas></div>
        </div>
        <div class="glass rounded-2xl p-5">
            <h4 class="text-md font-bold text-slate-700 mb-3">Doughnut: Complaints Status Radar</h4>
            <div class="chart-container"><canvas id="complaintChart"></canvas></div>
        </div>
        <div class="glass rounded-2xl p-5 lg:col-span-3">
            <h4 class="text-md font-bold text-slate-700 mb-3">Bar: Yearly Leave Mismatch Analytics</h4>
            <div class="chart-container" style="height: 220px;"><canvas id="leaveChart"></canvas></div>
        </div>
    </div>

    <!-- 4 Real-time SQL Data Reports Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Recent Payments -->
        <div class="glass rounded-2xl p-5 overflow-hidden">
            <h4 class="text-md font-bold text-slate-700 mb-3">💰 Recent Payments Feed</h4>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Student</th><th>Month/Year</th><th>Paid</th><th>Due</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $rpSql = "SELECT p.*, s.first_name, s.last_name FROM payments p JOIN students s ON p.student_id = s.id ORDER BY p.id DESC LIMIT 5";
                        $rpRes = $conn->query($rpSql);
                        if($rpRes && $rpRes->num_rows > 0) {
                            while($r = $rpRes->fetch_assoc()) {
                                $stClass = $r['payment_status']=='Paid'?'bg-emerald-100 text-emerald-800':($r['payment_status']=='Partial'?'bg-amber-100 text-amber-800':'bg-rose-100 text-rose-800');
                                echo "<tr>
                                    <td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td>
                                    <td>Month: ".$r['month']."/".$r['year']."</td>
                                    <td class='font-semibold text-emerald-600'>৳".$r['paid_amount']."</td>
                                    <td class='font-semibold text-rose-600'>৳".$r['due_amount']."</td>
                                    <td><span class='badge {$stClass}'>".$r['payment_status']."</span></td>
                                </tr>";
                            }
                        } else { echo "<tr><td colspan='5' class='text-center py-4 text-slate-400'>No records found</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Leave Applications -->
        <div class="glass rounded-2xl p-5 overflow-hidden">
            <h4 class="text-md font-bold text-slate-700 mb-3">🏖️ Active Leave Pipeline</h4>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Student</th><th>Duration</th><th>Days</th><th>Reason</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $rlSql = "SELECT la.*, s.first_name, s.last_name FROM leave_applications la JOIN students s ON la.student_id = s.id ORDER BY la.id DESC LIMIT 5";
                        $rlRes = $conn->query($rlSql);
                        if($rlRes && $rlRes->num_rows > 0) {
                            while($r = $rlRes->fetch_assoc()) {
                                $lClass = $r['status']=='Approved'?'bg-emerald-100 text-emerald-800':($r['status']=='Pending'?'bg-amber-100 text-amber-800':'bg-rose-100 text-rose-800');
                                echo "<tr>
                                    <td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td>
                                    <td>".$r['from_date']." to ".$r['to_date']."</td>
                                    <td>".$r['number_of_days']." Days</td>
                                    <td>".htmlspecialchars(substr($r['reason'],0,20))."...</td>
                                    <td><span class='badge {$lClass}'>".$r['status']."</span></td>
                                </tr>";
                            }
                        } else { echo "<tr><td colspan='5' class='text-center py-4 text-slate-400'>No records found</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Urgent Complaints -->
        <div class="glass rounded-2xl p-5 overflow-hidden">
            <h4 class="text-md font-bold text-slate-700 mb-3">⚠️ Critical Student Complaints</h4>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th>Student</th><th>Title</th><th>Priority</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $rcSql = "SELECT c.*, s.first_name, s.last_name FROM complaints c JOIN students s ON c.student_id = s.id ORDER BY c.id DESC LIMIT 5";
                        $rcRes = $conn->query($rcSql);
                        if($rcRes && $rcRes->num_rows > 0) {
                            while($r = $rcRes->fetch_assoc()) {
                                $pClass = $r['priority']=='High'?'bg-rose-100 text-rose-800':($r['priority']=='Medium'?'bg-amber-100 text-amber-800':'bg-slate-100 text-slate-800');
                                $sClass = $r['status']=='Resolved'?'bg-emerald-100 text-emerald-800':'bg-amber-100 text-amber-800';
                                echo "<tr>
                                    <td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td>
                                    <td>".htmlspecialchars($r['complaint_title'])."</td>
                                    <td><span class='badge {$pClass}'>".$r['priority']."</span></td>
                                    <td><span class='badge {$sClass}'>".$r['status']."</span></td>
                                </tr>";
                            }
                        } else { echo "<tr><td colspan='4' class='text-center py-4 text-slate-400'>No records found</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- System Activity Logs -->
        <div class="glass rounded-2xl p-5 overflow-hidden">
            <h4 class="text-md font-bold text-slate-700 mb-3">🛡️ Security Logs & Operations</h4>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr><th>User Node</th><th>Performed Action</th><th>IP Mapping</th><th>Timestamp</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $logSql = "SELECT al.*, u.full_name FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id ORDER BY al.id DESC LIMIT 5";
                        $logRes = $conn->query($logSql);
                        if($logRes && $logRes->num_rows > 0) {
                            while($r = $logRes->fetch_assoc()) {
                                echo "<tr>
                                    <td class='font-medium text-slate-800'>".htmlspecialchars($r['full_name'] ?? 'System')."</td>
                                    <td>".htmlspecialchars($r['action'])."</td>
                                    <td class='font-mono text-xs'>".$r['ip_address']."</td>
                                    <td>".$r['created_at']."</td>
                                </tr>";
                            }
                        } else { echo "<tr><td colspan='4' class='text-center py-4 text-slate-400'>No logs caught</td></tr>"; }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<!-- Core Dashboard Engine Dynamic JavaScript Data Mapping -->
<script>
    const genderRaw = <?= $genderJSON; ?>;
    const occupancyRaw = <?= $roomOccupancyJSON; ?>;
    const paymentRaw = <?= $paymentJSON; ?>;
    const complaintRaw = <?= $complaintJSON; ?>;
    const leaveRaw = <?= $leaveJSON; ?>;

    const chartConfig = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } };

    // 1. Gender Pie Chart
    new Chart(document.getElementById('genderChart').getContext('2d'), {
        type: 'pie',
        data: {
            labels: genderRaw.map(d => d.gender),
            datasets: [{ data: genderRaw.map(d => d.count), backgroundColor: ['#3b82f6', '#ec4899'] }]
        },
        options: chartConfig
    });

    // 2. Room Occupancy Chart
    new Chart(document.getElementById('occupancyChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: occupancyRaw.map(d => d.room_type + ' Rooms'),
            datasets: [
                { label: 'Occupied Beds', data: occupancyRaw.map(d => d.occupied_beds), backgroundColor: '#6366f1' },
                { label: 'Available Structural Capacity', data: occupancyRaw.map(d => d.total_beds - d.occupied_beds), backgroundColor: '#10b981' }
            ]
        },
        options: { ...chartConfig, scales: { x: { stacked: true }, y: { stacked: true } } }
    });

    // 3. Monthly Payment Chart
    const mNames = ["", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    new Chart(document.getElementById('paymentChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: paymentRaw.map(d => mNames[d.month]),
            datasets: [
                { label: 'Total Collections', data: paymentRaw.map(d => d.total_paid), borderColor: '#10b981', tension: 0.2, fill: false },
                { label: 'Outstanding Dues', data: paymentRaw.map(d => d.total_due), borderColor: '#ef4444', tension: 0.2, fill: false }
            ]
        },
        options: chartConfig
    });

    // 4. Complaint Chart
    new Chart(document.getElementById('complaintChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: complaintRaw.map(d => d.status),
            datasets: [{ data: complaintRaw.map(d => d.count), backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'] }]
        },
        options: chartConfig
    });

    // 5. Leave Analytics Chart
    new Chart(document.getElementById('leaveChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: leaveRaw.map(d => d.leave_type_name),
            datasets: [
                { label: 'Approved Logs', data: leaveRaw.map(d => d.approved), backgroundColor: '#10b981' },
                { label: 'Pending Processing', data: leaveRaw.map(d => d.pending), backgroundColor: '#f59e0b' },
                { label: 'Rejected Applications', data: leaveRaw.map(d => d.rejected), backgroundColor: '#ef4444' }
            ]
        },
        options: chartConfig
    });

    // Comprehensive CSV Data Extraction Layer
    function exportCSV() {
        let csv = 'Report Category,Metric Name,Value/Status\n';
        csv += `Core Stats,Total Students,${<?= (int)($stats['total_students'] ?? 0) ?>}\n`;
        csv += `Core Stats,Total Active Rooms,${<?= (int)($stats['total_rooms'] ?? 0) ?>}\n`;
        csv += `Core Stats,Occupied Beds Structural,${<?= (int)($stats['occupied_beds'] ?? 0) ?>}\n`;
        csv += `Financials,Gross Cash Inflow,৳${<?= (float)($stats['total_collection'] ?? 0) ?>}\n`;
        csv += `Financials,Net Project Pipeline Dues,৳${<?= (float)($stats['total_due'] ?? 0) ?>}\n`;
        
        let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.setAttribute("download", `Hostel_Production_Analytics_${new Date().toISOString().slice(0,10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>

<?php 
require '../components/footer.php'; 
?>
</body>
</html>
