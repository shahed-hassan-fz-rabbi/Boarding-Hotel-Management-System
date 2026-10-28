<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Secure Notice Edit & Processing Panel (POST Delete & PRG Pattern)
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Role Guard: Only Admin and Hostel Supervisor can modify notices
if (!in_array($user_role, ['Admin', 'Hostel Supervisor'])) {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

$message = '';
$error = '';

// PRG Success/Error Notification Capture via Session or GET
if (isset($_GET['success']) && $_GET['success'] === 'updated') {
    $message = ' Notice successfully updated and published!';
}

/* Fetch Current Notice Data safely by ID (form preserves ?id; POST id accepted as fallback) */
$notice_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
$fetchSql = "SELECT title, description, publish_date, status FROM notices WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($fetchSql);
$stmt->bind_param('i', $notice_id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$notice) {
    header('Location: list.php?error=Notice not found');
    exit();
}

/* ============================
   1. SECURE POST METHOD FOR DELETION (Soft Delete Approach)
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'secure_delete') {
    $delete_id = intval($_POST['id'] ?? 0);
    
    // Soft Delete: Changing status to 'Expired' instead of hard truncating rows
    $deleteSql = "UPDATE notices SET status = 'Expired' WHERE id = ?";
    $delStmt = $conn->prepare($deleteSql);
    $delStmt->bind_param('i', $delete_id);
    
    if ($delStmt->execute()) {
        // Logging the delete activity into system log table
        $log_action = "Archived Notice ID: " . $delete_id;
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param('iss', $user_id, $log_action, $user_ip);
        $logStmt->execute();
        $logStmt->close();

        header('Location: list.php?success=archived');
        exit();
    } else {
        $error = 'Failed to archive notice circular.';
    }
    $delStmt->close();
}

/* ============================
   2. HANDLE FORM UPDATE SUBMISSION (PRG Pattern)
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'secure_update') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publish_date = $_POST['publish_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'Active';

    // Validation
    if (empty($title) || empty($description)) {
        $error = 'Both Notice Title and Content fields are required.';
    } else {
        $updateSql = "UPDATE notices SET title = ?, description = ?, publish_date = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        
        if ($stmt) {
            $stmt->bind_param('ssssi', $title, $description, $publish_date, $status, $notice_id);
            
            if ($stmt->execute()) {
                // Log Update Operation into Activity Logs
                $log_action = "Updated Notice Title: " . $title;
                $user_ip = $_SERVER['REMOTE_ADDR'];
                $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param('iss', $user_id, $log_action, $user_ip);
                $logStmt->execute();
                $logStmt->close();

                // PRG Pattern Redirect to clear POST states
                header("Location: edit.php?id={$notice_id}&success=updated");
                exit();
            } else {
                $error = 'Execution Error: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<div class="max-w-[900px] mx-auto space-y-6">
    
    <!-- Header Controls Grid Panel -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-800"><?php echo icon('attendance', 'w-4 h-4'); ?> Edit Notice</h2>
            <p class="text-sm text-slate-500 mt-0.5">Modify announcement text or safely archive expired circulars</p>
        </div>
        <div class="flex items-center gap-3 no-print">
            <a href="list.php" class="px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition text-xs"><?php echo icon('arrow-left', 'w-4 h-4'); ?> Back to List</a>
            
            <!-- FIXED: Secure POST Method Form for Deletion instead of GET injection -->
            <form method="POST" action="" onsubmit="return confirm(' Archive this notice? It will be removed from the active ticker stream.')" class="inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $notice_id ?>">
                <input type="hidden" name="action" value="secure_delete">
                <button type="submit" class="px-4 py-2.5 bg-rose-50 text-rose-600 rounded-xl font-semibold hover:bg-rose-100 transition text-xs">
                    <?php echo icon('trash', 'w-3.5 h-3.5'); ?> Archive Notice
                </button>
            </form>
        </div>
    </div>

    <!-- System Response Prompts -->
    <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl font-medium shadow-sm"> <?= $message ?> </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl font-medium shadow-sm"> <?= $error ?> </div>
    <?php endif; ?>

    <!-- Master Editing Form Interface Layout -->
    <form method="POST" action="" class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm space-y-5">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="secure_update">

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Notice Title *</label>
            <input type="text" name="title" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-semibold text-slate-800 text-sm" value="<?= htmlspecialchars($notice['title']) ?>" required>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Notice Content *</label>
            <textarea name="description" rows="7" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-slate-700 text-sm leading-relaxed" required><?= htmlspecialchars($notice['description']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Publish Date</label>
                <input type="date" name="publish_date" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-slate-700 text-sm" value="<?= htmlspecialchars($notice['publish_date']) ?>">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Visibility Status</label>
                <select name="status" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-semibold text-slate-700 text-sm cursor-pointer">
                    <option value="Active" <?= $notice['status'] === 'Active' ? 'selected' : '' ?>>Active / Publicly Visible</option>
                    <option value="Expired" <?= $notice['status'] === 'Expired' ? 'selected' : '' ?>>Archived / Expired</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-between items-center">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-md hover:opacity-90 transition text-xs">
                <?php echo icon('save', 'w-4 h-4'); ?> Update Notice
            </button>
            <span class="text-[11px] text-slate-400 font-medium italic">ℹ Updates automatically refresh the global marquee stream ticker.</span>
        </div>
    </form>
</div>

<!-- JavaScript Dynamic Mobile Controller Handler -->
<script>
    function toggleSidebar() {
        document.getElementById('projectSidebar').classList.toggle('active');
        document.getElementById('mobileOverlay').classList.toggle('hidden');
    }
</script>

<?php require '../components/footer.php'; ?>
