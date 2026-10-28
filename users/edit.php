<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Edit / Update User Profile & Authorization States
 * ==========================================
 */

// ১. সেশন ও ডাটাবেজ আথেন্টিকেশন সবার আগে (যাতে কোনো HTML এর আগেই রিডাইরেক্ট সম্পন্ন হতে পারে)
require_once '../includes/auth_check.php';

// Access Control: Only Admin can access this panel
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

$message = '';
$error = '';

/* Handle Secure Form Submission (FIXED: Moved BEFORE header rendering to stop "Headers Already Sent") */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_user') {
    $target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $role = $_POST['role'] ?? 'Teacher';
    $status = intval($_POST['status'] ?? 0);
    $password = $_POST['password'] ?? '';

    if (empty($role)) {
        $error = 'Role privilege selection is mandatory.';
    } else {
        if (!empty($password)) {
            // Admin is resetting the user's password
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters long.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $updateSql = "UPDATE users SET role = ?, status = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('sisi', $role, $status, $hashed_password, $target_id);
            }
        } else {
            // Updating only role and status without changing password
            $updateSql = "UPDATE users SET role = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param('sii', $role, $status, $target_id);
        }

        // Execute query safely
        if (empty($error) && $stmt->execute()) {
            $stmt->close();
            
            // Log Update Action into Activity Logs
            $log_action = "Synchronized User ID: " . $target_id . " to Role: " . $role . " | Status: " . $status;
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param('iss', $user_id, $log_action, $user_ip);
            $logStmt->execute();
            $logStmt->close();

            // SUCCESSFUL PRG REDIRECT: Executed before any HTML output, 0% warning chance!
            header("Location: edit.php?id=" . $target_id . "&success=synchronized");
            exit();
        } elseif (empty($error)) {
            $error = 'Failed to synchronize record updates: ' . $conn->error;
        }
    }
}

// PRG Alert Capturing from URL
if (isset($_GET['success']) && $_GET['success'] === 'synchronized') {
    $message = '✓ User profile records successfully synchronized and activated!';
}

/* Fetch Target User Data safely by ID */
$target_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fetchSql = "SELECT full_name, email, role, status FROM users WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($fetchSql);
$stmt->bind_param('i', $target_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user_data) {
    header('Location: list.php?error=not_found');
    exit();
}

// ২. এবার মেইন লেআউট লোড করা (এর আগে সমস্ত হেডার/রিডাইরেক্ট অপারেশন শেষ)
require '../components/header.php';
?>

<div class="max-w-[1000px] mx-auto space-y-6">
    
    <!-- Title Header Dashboard Section -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex justify-between items-center shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">⚙️ Configure Profile Node</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage credentials, elevate security roles, or alter operational states for <span class="font-bold text-indigo-600"><?= htmlspecialchars($user_data['full_name']) ?></span></p>
        </div>
        <a href="list.php" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition text-xs">← Back to List</a>
    </div>

    <!-- Real-time Notification Banners -->
    <?php if ($message): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-xl font-bold text-sm shadow-sm transition-all duration-300">
            <?= $message ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl font-bold text-sm shadow-sm">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Master Form Interface Grid -->
    <form method="POST" action="" class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="sync_user">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">System Role Assignment (RBAC Authorization) *</label>
                <select name="role" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-semibold text-slate-700 text-sm cursor-pointer" required>
                    <option value="Teacher" <?= $user_data['role'] === 'Teacher' ? 'selected' : '' ?>>Teacher / Academic Staff</option>
                    <option value="Hostel Supervisor" <?= $user_data['role'] === 'Hostel Supervisor' ? 'selected' : '' ?>>Hostel Supervisor / Warden</option>
                    <option value="Accountant" <?= $user_data['role'] === 'Accountant' ? 'selected' : '' ?>>Accountant / Financial Officer</option>
                    <option value="Admin" <?= $user_data['role'] === 'Admin' ? 'selected' : '' ?>>System Administrator</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Account Status Matrix</label>
                <select name="status" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-semibold text-slate-700 text-sm cursor-pointer">
                    <option value="1" <?= intval($user_data['status']) === 1 ? 'selected' : '' ?>>Active / Fully Authorized</option>
                    <option value="0" <?= intval($user_data['status']) === 0 ? 'selected' : '' ?>>Suspended / Revoke Access</option>
                </select>
            </div>
        </div>

        <!-- Password Reset Tunnel Container -->
        <div class="bg-indigo-50/40 border border-indigo-100 rounded-xl p-5 space-y-3">
            <h4 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">🔑 Cryptographic Password Reset Tunnel</h4>
            <p class="text-xs text-slate-500">Leave this field completely blank if you do not wish to modify the user's current password configuration.</p>
            <input type="password" name="password" class="w-full border border-slate-200 bg-white p-3 rounded-xl outline-none focus:border-indigo-500 transition text-sm font-medium" placeholder="Enter new password node to override...">
        </div>

        <div class="pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-md hover:opacity-95 transition text-xs">
                💾 Synchronize User Profile Records
            </button>
        </div>
    </form>
</div>

<script>
    function toggleSidebar() {
        document.getElementById('projectSidebar').classList.toggle('active');
        document.getElementById('mobileOverlay').classList.toggle('hidden');
    }
</script>

<?php require '../components/footer.php'; ?>