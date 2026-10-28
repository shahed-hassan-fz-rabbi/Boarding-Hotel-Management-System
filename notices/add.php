<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Publish New Institutional Notice Circular
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Role Guard: Only Admin and Hostel Supervisor can issue new notice logs
if (!in_array($user_role, ['Admin', 'Hostel Supervisor'])) {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $publish_date = $_POST['publish_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'Active';

    if (empty($title) || empty($description)) {
        $error = 'Both Notice Title and Circular Description are required parameters.';
    } else {
        $insertSql = "INSERT INTO notices (title, description, publish_date, status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSql);
        
        if ($stmt) {
            $stmt->bind_param('ssss', $title, $description, $publish_date, $status);
            if ($stmt->execute()) {
                $message = ' Institutional announcement successfully published to the live dashboard system feed!';
            } else {
                $error = 'Database Failure: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<div class="max-w-[900px] mx-auto">
    
    <div class="glass rounded-2xl p-6 mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800"><?php echo icon('plus', 'w-4 h-4'); ?> Broadcast New Circular</h2>
            <p class="text-sm text-slate-500">Dispatch live updates, academic memos, or emergency hostel schedules</p>
        </div>
        <a href="list.php" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-xl font-semibold hover:bg-slate-300 transition text-sm"><?php echo icon('arrow-left', 'w-4 h-4'); ?> Notice Board</a>
    </div>

    <?php if ($message): ?>
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-4 rounded-xl mb-6 font-medium"> <?= $message ?> </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-100 border border-rose-300 text-rose-800 p-4 rounded-xl mb-6 font-medium"> <?= $error ?> </div>
    <?php endif; ?>

    <form method="POST" class="glass rounded-2xl p-8 bg-white shadow-xl">
        <?= csrf_field() ?>
        <div class="mb-5">
            <label class="block text-sm font-bold text-slate-700 mb-2">Notice Title / Circular Topic *</label>
            <input type="text" name="title" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium" placeholder="e.g., Extended Academic Summer Vacation Notice or Holy Ramadan Meal Schedules" required>
        </div>

        <div class="mb-5">
            <label class="block text-sm font-bold text-slate-700 mb-2">Notice Body / Content Specifications *</label>
            <textarea name="description" rows="6" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-sm" placeholder="Write full analytical descriptions, rules, timelines, or operational remarks here..." required></textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Release Date</label>
                <input type="date" name="publish_date" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-sm" value="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Broadcast State Status</label>
                <select name="status" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-sm">
                    <option value="Active">Active / Publicly Visible</option>
                    <option value="Expired">Archived / Expired</option>
                </select>
            </div>
        </div>

        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:opacity-90 transition">
            <?php echo icon('notices', 'w-4 h-4'); ?> Deploy Notice Circular
        </button>
    </form>
</div>

<!-- JavaScript Dynamic Mobile Controller Handler Linkage -->
<script>
    function toggleSidebar() {
        document.getElementById('projectSidebar').classList.toggle('active');
        document.getElementById('mobileOverlay').classList.toggle('hidden');
    }
</script>

<?php require '../components/footer.php'; ?>
