<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Add New Staff Member Registration Form
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';

// Access Control: Only Admin can insert staff records
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

$message = '';
$error = '';

/* Auto-generate unique Staff Code (e.g., STF20260001) */
$codeSql = "SELECT id FROM staff ORDER BY id DESC LIMIT 1";
$codeRes = $conn->query($codeSql);
$nextId = ($codeRes && $codeRes->num_rows > 0) ? $codeRes->fetch_assoc()['id'] + 1 : 1;
$staff_code = "STF" . date('Y') . str_pad($nextId, 4, '0', STR_PAD_LEFT);

/* Handle Post Data Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $joining_date = $_POST['joining_date'] ?? date('Y-m-d');
    $status = $_POST['status'] ?? 'Active';

    /* Prepared Statement Validation Logic */
    if (empty($full_name) || empty($designation) || empty($department) || empty($phone) || empty($email)) {
        $error = 'All primary input parameters are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid institutional email configuration formatting.';
    } else {
        // Check for duplicate email mapping logs
        $checkSql = "SELECT id FROM staff WHERE email = ? LIMIT 1";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $checkStmt->close();

        if ($checkRes->num_rows > 0) {
            $error = 'A staff entry already exists under this email address mapping.';
        } else {
            /* Secure SQL Insertion Architecture */
            $insertSql = "INSERT INTO staff (staff_code, full_name, designation, department, phone, email, joining_date, status) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertSql);
            if ($stmt) {
                $stmt->bind_param('ssssssss', $staff_code, $full_name, $designation, $department, $phone, $email, $joining_date, $status);
                
                if ($stmt->execute()) {
                    $message = '✓ Staff member successfully registered inside human resource network!';
                    // Refresh next code sequence mapping parameters
                    $nextId++;
                    $staff_code = "STF" . date('Y') . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                } else {
                    $error = 'Execution Error: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Staff - Boarding School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
        .input-field { border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.75rem 1rem; border-radius: 12px; outline: none; transition: all 0.3s; color: #1e293b; width: 100%; font-size: 0.95rem; }
        .input-field:focus { background: white; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-label { display: block; margin-bottom: 0.5rem; color: #1e293b; font-weight: 600; font-size: 0.95rem; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-[1000px] mx-auto">
    
    <!-- Academic Layout Header Components Area -->
    <div class="glass mb-6 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">🏛️ Comilla University</h2>
            <p class="text-xs font-semibold text-indigo-600 tracking-wider uppercase">Department of Computer Science and Engineering</p>
        </div>
        <div class="bg-indigo-50 px-4 py-2 rounded-xl text-center md:text-right">
            <p class="text-xs font-bold text-slate-700">Project: <span class="text-indigo-600">Boarding School Management System</span></p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Student: Mahmuda Sultana (ID: 12108010)</p>
        </div>
    </div>

    <!-- Interface Controls Context -->
    <div class="glass rounded-2xl p-6 mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">➕ Add New Staff Record</h2>
            <p class="text-slate-600 mt-1">Register institutional professors, supervisors or management authorities</p>
        </div>
        <a href="list.php" class="px-4 py-2 bg-slate-200 text-slate-800 rounded-xl font-semibold hover:bg-slate-300 transition">← Back to List</a>
    </div>

    <!-- Feedback Banner Components -->
    <?php if ($message): ?>
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-4 rounded-xl mb-6 font-medium"> <?= $message ?> </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-100 border border-rose-300 text-rose-800 p-4 rounded-xl mb-6 font-medium"> <?= $error ?> </div>
    <?php endif; ?>

    <!-- Insertion Form Data Container Layer -->
    <form method="POST" class="glass rounded-2xl p-8">
        
        <div class="form-row">
            <div>
                <label class="form-label">System Generated Staff Code</label>
                <input type="text" class="input-field bg-slate-100 font-mono font-bold text-slate-500 cursor-not-allowed" value="<?= $staff_code ?>" readonly>
            </div>
            <div>
                <label class="form-label">Full Name *</label>
                <input type="text" name="full_name" class="input-field" placeholder="e.g. Atikur Rahman" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label">Official Designation *</label>
                <input type="text" name="designation" class="input-field" placeholder="e.g. Lecturer / Hostel Supervisor" required>
            </div>
            <div>
                <label class="form-label">Department / Wing Node *</label>
                <select name="department" class="input-field" required>
                    <option value="">-- Select Department --</option>
                    <option value="CSE">Computer Science & Engineering</option>
                    <option value="Hostel Administration">Hostel Administration</option>
                    <option value="Accounts & Finance">Accounts & Finance</option>
                    <option value="General Academic">General Academic Support</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label">Active Mobile Number *</label>
                <input type="tel" name="phone" class="input-field" placeholder="e.g. 017XXXXXXXX" required>
            </div>
            <div>
                <label class="form-label">Institutional Email Address *</label>
                <input type="email" name="email" class="input-field" placeholder="name@boarding.com" required>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label">Joining Date</label>
                <input type="date" name="joining_date" class="input-field" value="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label">Operational Security Status</label>
                <select name="status" class="input-field">
                    <option value="Active">Active / Fully Commissioned</option>
                    <option value="Inactive">Inactive / Suspended</option>
                </select>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:opacity-90 transition">
                💾 Execute Registration Data
            </button>
        </div>

    </form>

</div>

<?php require '../components/footer.php'; ?>
</body>
</html>