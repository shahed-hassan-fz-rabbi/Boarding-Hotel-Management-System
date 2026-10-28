<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Live Institutional Notice Board Directory
 * ==========================================
 */


require_once '../includes/auth_check.php';


$message = '';
$error = '';

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'archived') {
        $message = ' Notice successfully moved to archive.';
    }
}
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'not_found') {
        $error = 'Target notice bulletin was not found.';
    }
}

/* Fetch Notices (Optimized Columns Selector based on strict RBAC privileges) */
// Admin and Supervisor can see everything including Drafts. Teachers/Accountants can see only Active ones.
if (in_array($user_role, ['Admin', 'Hostel Supervisor'])) {
    $sql = "SELECT id, title, description, priority, publish_date, status FROM notices ORDER BY id DESC";
} else {
    $sql = "SELECT id, title, description, priority, publish_date, status FROM notices WHERE status = 'Active' ORDER BY id DESC";
}
$result = $conn->query($sql);


require '../components/header.php';
?>

<div class="max-w-[1400px] mx-auto space-y-6">

    <!-- Header Action Banner -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-800"><?php echo icon('notices', 'w-4 h-4'); ?> Institutional Notice Board</h2>
            <p class="text-sm text-slate-500 mt-0.5">View active announcements, academic memos, and emergency guidelines</p>
        </div>
        <!-- Only Authorized management can post notices -->
        <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
            <a href="add.php" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-md hover:opacity-90 transition text-xs no-print flex items-center gap-1.5">
                <?php echo icon('plus', 'w-4 h-4'); ?> Publish Notice
            </a>
        <?php endif; ?>
    </div>

    <!-- Alert Notifications System Box -->
    <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl font-medium shadow-sm"> <?= $message ?> </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl font-medium shadow-sm"> <?= $error ?> </div>
    <?php endif; ?>

    <!-- Responsive Clean Grid Card Container -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
                // Set semantic colors for priority badge
                $pBadge = $row['priority'] === 'High' 
                    ? 'bg-rose-50 text-rose-700 border-rose-200' 
                    : ($row['priority'] === 'Medium' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-slate-50 text-slate-600 border-slate-200');

                // Status configuration mapping
                $statusBadge = $row['status'] === 'Active' 
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                    : ($row['status'] === 'Draft' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-400 border-slate-200');
            ?>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between hover:shadow-md transition relative overflow-hidden">
                    <div>
                        <!-- Notice Metadata Info strip -->
                        <div class="flex justify-between items-center gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded border <?= $pBadge ?>"><?= $row['priority'] ?> Priority</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded border <?= $statusBadge ?>"><?= $row['status'] ?></span>
                            </div>
                            <span class="text-xs text-slate-400 font-mono font-bold"><?php echo icon('calendar', 'w-4 h-4'); ?> <?= date('d M, Y', strtotime($row['publish_date'])) ?></span>
                        </div>

                        <!-- Title and Description Content Box -->
                        <h3 class="text-lg font-bold text-slate-800 tracking-tight mb-2"><?= htmlspecialchars($row['title']) ?></h3>
                        <p class="text-sm text-slate-600 leading-relaxed font-medium whitespace-pre-line"><?= htmlspecialchars($row['description']) ?></p>
                    </div>

                    <!-- Actions Footer available only to Admin and Supervisors -->
                    <?php if (in_array($user_role, ['Admin', 'Hostel Supervisor'])): ?>
                        <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2 no-print">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="px-3 py-1.5 border border-slate-200 hover:border-indigo-600 text-slate-600 hover:text-indigo-600 rounded-lg text-xs font-bold transition">
                                <?php echo icon('settings', 'w-4 h-4'); ?> Edit Notice
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-1 md:col-span-2 bg-white border border-slate-200 rounded-2xl p-12 text-center text-slate-400 font-medium italic">
                 No operational notice circulars published on board yet.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript Dynamic Mobile Menu Handler -->
<script>
    function toggleSidebar() {
        document.getElementById('projectSidebar').classList.toggle('active');
        document.getElementById('mobileOverlay').classList.toggle('hidden');
    }
</script>

<?php require '../components/footer.php'; ?>