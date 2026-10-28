<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Activity Logs Viewer
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Only Admin can access
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

/* ============================
   Fetch Activity Logs
============================ */

$page = intval($_GET['page'] ?? 1);
$limit = 50;
$offset = ($page - 1) * $limit;

$sql = "SELECT 
            al.id,
            al.user_id,
            al.action,
            al.ip_address,
            al.created_at,
            u.full_name,
            u.email
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    $stmt->close();
}

/* Count total logs */
$countSql = "SELECT COUNT(*) as total FROM activity_logs";
$countResult = $conn->query($countSql);
$countRow = $countResult ? $countResult->fetch_assoc() : ['total' => 0];
$totalLogs = $countRow['total'];
$totalPages = ceil($totalLogs / $limit);

$logsJSON = json_encode($logs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Hostel System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
        }

        .nav-link {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .icon-svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .input-field {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1e293b;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .input-field:focus {
            outline: none;
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .log-row {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem;
            transition: all 0.3s ease;
        }

        .log-row:hover {
            background: #f8fafc;
        }

        .log-time {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .log-action {
            font-weight: 600;
            color: #1e293b;
            margin: 0.5rem 0;
        }

        .log-user {
            font-size: 0.9rem;
            color: #64748b;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .pagination {
            display: flex;
            gap: 0.5rem;
            margin-top: 2rem;
            justify-content: center;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            color: #667eea;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
        }

        .pagination .active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .fade-in {
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="sidebar w-80 hidden lg:flex flex-col">
            <div class="p-8 border-b border-slate-700">
                <h1 class="text-3xl font-bold text-white">🏫 Hostel</h1>
                <p class="text-slate-400 text-sm mt-2">Management System</p>
            </div>

            <nav class="flex-1 p-6 space-y-2 overflow-y-auto">
                <a href="../index.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M3 12l9-9 9 9M5 10v11a1 1 0 001 1h3v-5a1 1 0 011-1h4a1 1 0 011 1v5h3a1 1 0 001-1V10"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="../reports/analytics.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M3 3v18a2 2 0 002 2h18M3 9h18M3 15h18M9 3v18M15 3v18"></path>
                    </svg>
                    <span>Analytics</span>
                </a>
                <a href="index.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2zm0 2c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zm1 5h-2v5l4.3 2.6.8-1.3-3.1-1.9V9z"></path>
                    </svg>
                    <span>Settings</span>
                </a>
                <a href="logs.php" class="nav-link active px-4 py-3 text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Activity Logs</span>
                </a>
            </nav>

            <div class="p-6 border-t border-slate-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <div>
                        <p class="text-white font-600"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-slate-400 text-sm"><?php echo htmlspecialchars($user_role); ?></p>
                    </div>
                </div>
                <a href="../auth/logout.php" class="w-full py-2 px-4 bg-red-500/20 text-red-400 rounded-10px hover:bg-red-500/30 transition font-500 text-sm text-center block">
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-4 lg:p-8">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <h2 class="text-3xl font-bold text-slate-800">📋 Activity Logs</h2>
                    <p class="text-slate-600 mt-2">System activity and user actions tracking</p>
                </div>

                <!-- Statistics -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <p class="text-slate-600">Total Activities Logged: <span class="font-bold text-xl text-slate-800"><?php echo $totalLogs; ?></span></p>
                </div>

                <!-- Search Filter -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <input type="text" id="searchInput" placeholder="🔍 Search activities..."
                           class="input-field w-full">
                </div>

                <!-- Logs List -->
                <div class="glass rounded-2xl overflow-hidden">
                    <div id="logsContainer">
                        <!-- Logs will be inserted by JavaScript -->
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="<?php echo ($page == $i) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>

            </div>
        </div>

    </div>

    <!-- JavaScript -->
    <script>
        const allLogs = <?php echo $logsJSON; ?>;
        let filteredLogs = [...allLogs];

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        const searchInput = document.getElementById('searchInput');
        const logsContainer = document.getElementById('logsContainer');

        // ============================
        // Format Date & Time
        // ============================

        function formatDateTime(datetime) {
            const date = new Date(datetime);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }

        // ============================
        // Render Logs
        // ============================

        function renderLogs(logs) {
            logsContainer.innerHTML = '';

            if (logs.length === 0) {
                logsContainer.innerHTML = '<div style="padding: 2rem; text-align: center; color: #64748b;">No activities found</div>';
                return;
            }

            logs.forEach((log, index) => {
                const logDiv = document.createElement('div');
                logDiv.className = 'log-row fade-in';
                logDiv.style.animationDelay = `${index * 0.05}s`;
                logDiv.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <p class="log-time">${formatDateTime(log.created_at)}</p>
                            <p class="log-action">${esc(log.action)}</p>
                            <p class="log-user">
                                By: <strong>${esc(log.full_name || 'System')}</strong>
                                ${log.email ? '(' + esc(log.email) + ')' : ''}
                            </p>
                        </div>
                        <span class="badge badge-info">${esc(log.ip_address || 'N/A')}</span>
                    </div>
                `;
                logsContainer.appendChild(logDiv);
            });
        }

        // ============================
        // Search Filter
        // ============================

        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();

            if (searchTerm === '') {
                filteredLogs = [...allLogs];
            } else {
                filteredLogs = allLogs.filter(log =>
                    log.action.toLowerCase().includes(searchTerm) ||
                    (log.full_name && log.full_name.toLowerCase().includes(searchTerm)) ||
                    (log.email && log.email.toLowerCase().includes(searchTerm))
                );
            }

            renderLogs(filteredLogs);
        });

        // Initial render
        renderLogs(allLogs);
    </script>

</body>
</html>