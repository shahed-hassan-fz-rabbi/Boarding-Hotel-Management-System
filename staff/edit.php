<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Edit/Update Staff Profile Console
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Access Control: Only Admin can edit staff records
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

$message = '';
$error = '';

/* Fetch Current Staff Data by ID (full_name alias = first + last) */
$staff_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$fetchSql = "SELECT *, CONCAT(first_name, ' ', last_name) AS full_name FROM staff WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($fetchSql);
$stmt->bind_param('i', $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$staff) {
    header('Location: list.php?error=Record not found');
    exit();
}

/* Handle Form Submission & Updates */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $joining_date = $_POST['joining_date'] ?? '';
    $status = $_POST['status'] ?? 'Active';

    /* Data Validation */
    if (empty($full_name) || empty($designation) || empty($department) || empty($phone) || empty($email)) {
        $error = 'All primary input parameters are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address configuration formatting.';
    } else {
        // Check for duplicate email in OTHER records
        $checkSql = "SELECT id FROM staff WHERE email = ? AND id != ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('si', $email, $staff_id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $checkStmt->close();

        if ($checkRes->num_rows > 0) {
            $error = 'Another staff entry is already using this email address.';
        } else {
            /* Secure Parameterized SQL Update Execution
               (staff table stores first_name/last_name — split the full name) */
            $nameParts = preg_split('/\s+/', $full_name, 2);
            $first_name = array_shift($nameParts);
            $last_name = trim(implode(' ', $nameParts)) ?: '-';

            $updateSql = "UPDATE staff SET first_name = ?, last_name = ?, designation = ?, department = ?, phone = ?, email = ?, joining_date = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            
            if ($stmt) {
                $stmt->bind_param('ssssssssi', $first_name, $last_name, $designation, $department, $phone, $email, $joining_date, $status, $staff_id);
                
                if ($stmt->execute()) {
                    $message = ' Staff profile records successfully updated inside directories!';
                    // Refresh data in view
                    $staff = compact('full_name', 'designation', 'department', 'phone', 'email', 'joining_date', 'status');
                    $staff['staff_code'] = $_POST['staff_code']; // Preserve code for view consistency
                } else {
                    $error = 'Execution Fail: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<div class="p-4 lg:p-8 max-w-[1000px] mx-auto">
    
    <!-- Top University Profile Branding Components -->
    <div class="glass mb-6 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800"><?php echo icon('building', 'w-4 h-4'); ?> Comilla University</h2>
            <p class="text-xs font-semibold text-indigo-600 tracking-wider uppercase">Department of Computer Science and Engineering</p>
        </div>
        <div class="bg-indigo-50 px-4 py-2 rounded-xl text-center md:text-right">
            <p class="text-xs font-bold text-slate-700">Project: <span class="text-indigo-600">Boarding School Management System</span></p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Student: Mahmuda Sultana (ID: 12108010)</p>
        </div>
    </div>

    <!-- Interface Context Row -->
    <div class="glass rounded-2xl p-6 mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('attendance', 'w-4 h-4'); ?> Modify Staff Profile</h2>
            <p class="text-slate-600 mt-1">Update designation, administrative node, and active work configurations</p>
        </div>
        <a href="list.php" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-xl font-semibold hover:bg-slate-300 transition"><?php echo icon('arrow-left', 'w-4 h-4'); ?> Cancel</a>
    </div>

    <!-- System Response Prompts -->
    <?php if ($message): ?>
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-4 rounded-xl mb-6 font-medium"> <?= $message ?> </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-100 border border-rose-300 text-rose-800 p-4 rounded-xl mb-6 font-medium"> <?= $error ?> </div>
    <?php endif; ?>

    <!-- Master Form Infrastructure Layer -->
    <form method="POST" class="glass rounded-2xl p-8">
        <?= csrf_field() ?>
        <input type="hidden" name="staff_code" value="<?= htmlspecialchars($staff['staff_code']) ?>">
        
        <div class="form-row">
            <div>
                <label class="form-label">Static Staff Code Mapping</label>
                <input type="text" class="input-field bg-slate-100 font-mono font-bold text-slate-500 cursor-not-allowed" value="<?= htmlspecialchars($staff['staff_code']) ?>" readonly>
            </div>
            <div>
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="input-field" value="<?= htmlspecialchars($staff['full_name']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label">Official Designation *</label>
                <input type="text" name="designation" class="input-field" value="<?= htmlspecialchars($staff['designation']) ?>" required>
            </div>
            <div>
                <label class="form-label">Department / Wing Node *</label>
                <select name="department" class="input-field" required>
                    <option value="CSE" <?= $staff['department']=='CSE'?'selected':'' ?>>Computer Science & Engineering</option>
                    <option value="Hostel Administration" <?= $staff['department']=='Hostel Administration'?'selected':'' ?>>Hostel Administration</option>
                    <option value="Accounts & Finance" <?= $staff['department']=='Accounts & Finance'?'selected':'' ?>>Accounts & Finance</option>
                    <option value="General Academic" <?= $staff['department']=='General Academic'?'selected':'' ?>>General Academic Support</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label">Active Mobile Number *</label>
                <input type="tel" name="phone" class="input-field" value="<?= htmlspecialchars($staff['phone']) ?>" required>
            </div>
            <div>
                <label class="form-label">Institutional Email Address *</label>
                <input type="email" name="email" class="input-field" value="<?= htmlspecialchars($staff['email']) ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="input-field" value="<?= htmlspecialchars($staff['joining_date']) ?>">
            </div>
            <div>
                <label class="form-label">Operational Status</label>
                <select name="status" class="input-field">
                    <option value="Active" <?= $staff['status']=='Active'?'selected':'' ?>>Active / Fully Commissioned</option>
                    <option value="Inactive" <?= $staff['status']=='Inactive'?'selected':'' ?>>Inactive / Suspended</option>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:opacity-90 transition">
                <?php echo icon('save', 'w-4 h-4'); ?> Synchronize Changes
            </button>
        </div>

    </form>

</div>

<?php require '../components/footer.php'; ?>
