<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Provision New User Account Form (Secure RBAC)
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Access Control: Only Admin can create new user accounts
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

$message = '';
$error = '';

/* Handle User Provisioning Submission (PRG Pattern Compatible) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'provision_user') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Teacher';
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    /* Secure Validation Constraints */
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All primary form parameters are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email formatting configuration caught.';
    } elseif (strlen($password) < 6) {
        $error = 'Security Alert: Password must be at least 6 characters long.';
    } else {
        // Safe check if email already exists in system database
        $checkSql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $checkStmt->close();

        if ($checkRes->num_rows > 0) {
            $error = 'A user login node already exists with this email address.';
        } else {
            /* Secure Password Hashing via Bcrypt */
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            /* Parameterized SQL Insert Statement */
            $insertSql = "INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertSql);
            
            if ($stmt) {
                $stmt->bind_param('ssssi', $full_name, $email, $hashed_password, $role, $status);
                
                if ($stmt->execute()) {
                    // Log the account creation event into Audit Trail
                    $log_action = "Provisioned new user account: " . $email . " as " . $role;
                    $user_ip = $_SERVER['REMOTE_ADDR'];
                    $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                    $logStmt = $conn->prepare($logSql);
                    $logStmt->bind_param('iss', $user_id, $log_action, $user_ip);
                    $logStmt->execute();
                    $logStmt->close();

                    header("Location: list.php?success=provisioned");
                    exit();
                } else {
                    $error = 'Database Insertion Error: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<div class="max-w-[1000px] mx-auto space-y-6">
    
    <!-- Navigation Breadcrumb & Header Title -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 flex justify-between items-center shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-800"><?php echo icon('plus', 'w-4 h-4'); ?> Provision User Account</h2>
            <p class="text-sm text-slate-500 mt-0.5">Configure system credentials and cryptographically hash initial passwords</p>
        </div>
        <a href="list.php" class="px-4 py-2 bg-slate-100 text-slate-800 rounded-xl font-semibold hover:bg-slate-200 transition text-xs flex items-center no-print"><?php echo icon('arrow-left', 'w-4 h-4'); ?> Back to Users</a>
    </div>

    <!-- Alert Status Box Prompts -->
    <?php if ($error): ?>
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-xl font-medium shadow-sm"> <?= $error ?> </div>
    <?php endif; ?>

    <!-- User Provisioning Form Core Layout -->
    <form method="POST" action="" class="bg-white border border-slate-200 rounded-2xl p-8 shadow-sm space-y-5">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="provision_user">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Account User's Full Name *</label>
                <input type="text" name="full_name" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-sm" placeholder="e.g. Mahmuda Sultana" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Email Address (Login ID) *</label>
                <input type="email" name="email" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-sm" placeholder="e.g. user@boarding.com" required>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Authentication Password *</label>
                <input type="password" name="password" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-medium text-sm" placeholder="Minimum 6 characters" required>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">System Role Assignment (RBAC) *</label>
                <select name="role" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-semibold text-slate-700 text-sm cursor-pointer" required>
                    <option value="Teacher">Teacher / Academic Staff</option>
                    <option value="Hostel Supervisor">Hostel Supervisor / Warden</option>
                    <option value="Accountant">Accountant / Financial Officer</option>
                    <option value="Admin">System Administrator</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Account Initial Status</label>
                <select name="status" class="w-full border border-slate-200 bg-slate-50/50 p-3 rounded-xl outline-none focus:bg-white focus:border-indigo-500 transition font-semibold text-slate-700 text-sm cursor-pointer">
                    <option value="1">Active / Fully Authorized</option>
                    <option value="0">Suspended / Revoke Access</option>
                </select>
            </div>
            <div class="flex items-end text-slate-400 text-xs font-medium pb-3 italic">
                ℹ Passwords are encrypted instantly using secure industry-standard Bcrypt schemas.
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-md hover:opacity-90 transition text-xs">
                <?php echo icon('save', 'w-4 h-4'); ?> Generate System Credentials
            </button>
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
