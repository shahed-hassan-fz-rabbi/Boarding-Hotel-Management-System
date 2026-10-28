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

<?php require '../components/header.php'; ?>

<div class="max-w-[1400px] mx-auto space-y-6">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('file', 'w-4 h-4'); ?> Activity Logs</h2>
                    <p class="text-slate-600 mt-2">System activity and user actions tracking</p>
                </div>

                <!-- Statistics -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <p class="text-slate-600">Total Activities Logged: <span class="font-bold text-xl text-slate-800"><?php echo $totalLogs; ?></span></p>
                </div>

                <!-- Search Filter -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <input type="text" id="searchInput" placeholder="Search activities..."
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
                logsContainer.innerHTML = '<div style="padding: 2rem; text-align: center; color: var(--text-2);">No activities found</div>';
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

<?php require '../components/footer.php';
