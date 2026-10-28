<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Secure Staff Registration Portal (With RBAC Dropdown)
 * ==========================================
 */

require '../db.php';
require_once __DIR__ . '/../includes/csrf.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!csrf_verify()) {
        csrf_reject();
    }
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'] ?? 'Teacher'; 

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = '❌ Please fill all required fields';
    } else if (strlen($full_name) < 3) {
        $error = '❌ Full name must be at least 3 characters';
    } else if (strlen($password) < 6) {
        $error = '❌ Password must be at least 6 characters';
    } else if ($password !== $confirm_password) {
        $error = '❌ Passwords do not match';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '❌ Invalid email address';
    } else if ($role === 'Admin') {
        $error = '❌ Unauthorized role injection attempt blocked.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = '❌ Email already exists in system';
        } else {
            // Hash password using native secure BCRYPT
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // status = 0 means 'Pending Admin Approval'
            $status = 0; 
            
            // FIXED: Typing definition layout syntax sync error
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $full_name, $email, $hashed_password, $role, $status);
            
            if ($stmt->execute()) {
                $success = '✅ Registration successful! Your account is pending Admin approval.';
                $_POST = array();
            } else {
                $error = '❌ Registration failed: ' . $conn->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Boarding School System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            border-radius: 24px;
            padding: 2.5rem 3rem;
            width: 100%;
            max-width: 480px;
            margin: 1rem;
        }
        .input-field {
            border: 1.5px solid rgba(102, 126, 234, 0.3);
            background: rgba(102, 126, 234, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: #1e293b;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            outline: none;
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body>

<div class="glass">
    <div class="text-center mb-6">
        <div class="text-5xl mb-2">🏫</div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Create Account</h1>
        <p class="text-slate-500 text-sm mt-1">Join Boarding School Portal System</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-rose-100 border border-rose-200 text-rose-800 p-3.5 rounded-xl mb-4 text-xs font-bold"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl mb-4 text-xs font-bold"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-3.5">
        <?= csrf_field() ?>
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">👤 Full Name</label>
            <input type="text" name="full_name" class="input-field" placeholder="e.g. Rahim Ali" value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>" required minlength="3">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">📧 Email Address</label>
            <input type="email" name="email" class="input-field" placeholder="name@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">🔐 Password</label>
                <input type="password" name="password" class="input-field" placeholder="••••••••" required minlength="6">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">🔐 Confirm Password</label>
                <input type="password" name="confirm_password" class="input-field" placeholder="••••••••" required minlength="6">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">🎯 Select System Role Privilege</label>
            <select name="role" class="w-full border border-slate-200 bg-white p-3 rounded-xl outline-none focus:border-indigo-500 text-sm font-bold text-slate-700 cursor-pointer" required>
                <option value="Teacher">Teacher / Academic Staff</option>
                <option value="Hostel Supervisor">Hostel Supervisor / Warden</option>
                <option value="Accountant">Accountant / Financial Officer</option>
            </select>
        </div>

        <button type="submit" class="w-full py-3 mt-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:opacity-95 transition text-sm">
            Create Portal Request
        </button>
    </form>

    <div class="text-center text-slate-400 font-semibold text-xs my-4">— Already registered? —</div>
    <div class="text-center text-sm">
        <a href="login.php" class="text-indigo-600 font-bold hover:underline">Return to Login Gateway</a>
    </div>
</div>

</body>
</html>