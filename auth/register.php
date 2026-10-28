<?php
/**
 * ==========================================
 * STAFF REGISTRATION PORTAL (With RBAC Dropdown)
 * Warm boutique-hotel design
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
        $error = 'Please fill all required fields';
    } else if (strlen($full_name) < 3) {
        $error = 'Full name must be at least 3 characters';
    } else if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else if ($role === 'Admin') {
        $error = 'Unauthorized role injection attempt blocked.';
    } else {
        // Check if email already exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'Email already exists in system';
        } else {
            // Hash password using native secure BCRYPT
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // status = 0 means 'Pending Admin Approval'
            $status = 0;

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $full_name, $email, $hashed_password, $role, $status);

            if ($stmt->execute()) {
                $success = 'Registration successful! Your account is pending Admin approval.';
                $_POST = array();
            } else {
                error_log('register.php insert failed: ' . $stmt->error);
                $error = 'Registration failed. Please try again.';
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
    <title>Register - Boarding Hotel Management</title>

    <script>
    (function () {
        try {
            var t = localStorage.getItem('bhm-theme');
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        terracotta: { DEFAULT: '#C94F1B', dark: '#A93C16', soft: '#FF875F' },
                        peach: { DEFAULT: '#FFB18A', soft: '#FFD0B8' },
                        ivory: '#FFF8F3', cream: '#F7EDE5', warmgray: '#E7DDD5',
                        charcoal: '#292521', cocoabrown: '#786B63'
                    },
                    fontFamily: { sans: ['Manrope','Inter','system-ui','Segoe UI','sans-serif'] }
                }
            }
        };
    </script>

    <link rel="stylesheet" href="../assets/theme.css">
    <script src="../assets/app.js" defer></script>

    <style>
        body {
            background:
                radial-gradient(900px 480px at 90% -10%, rgba(255, 177, 138, 0.20) 0%, transparent 58%),
                radial-gradient(800px 460px at -5% 105%, rgba(201, 79, 27, 0.12) 0%, transparent 55%),
                var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        [data-theme="dark"] body {
            background:
                radial-gradient(900px 480px at 90% -10%, rgba(255, 135, 95, 0.08) 0%, transparent 58%),
                radial-gradient(800px 460px at -5% 105%, rgba(201, 79, 27, 0.06) 0%, transparent 55%),
                var(--bg);
        }

        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 2.6rem 2.75rem 2.2rem;
            width: 100%;
            max-width: 500px;
        }
    </style>
</head>
<body>

<div class="auth-card" style="position: relative;">
    <button type="button" class="theme-toggle" style="position: absolute; top: 1.25rem; right: 1.25rem;" aria-label="Switch to dark theme" aria-pressed="false" title="Toggle theme">
        <svg class="icon-sun w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        <svg class="icon-moon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
    </button>

    <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-white shadow-lg" style="background: var(--grad-primary);">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold mt-4 tracking-tight" style="color: var(--text);">Create account</h1>
        <p class="text-sm mt-1 font-semibold" style="color: var(--text-2);">Join the Boarding Hotel portal</p>
    </div>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-3.5">
        <?= csrf_field() ?>
        <div>
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="input-field" placeholder="e.g. Rahim Ali" value="<?= htmlspecialchars($_POST['full_name'] ?? ''); ?>" required minlength="3">
        </div>

        <div>
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="input-field" placeholder="name@gmail.com" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="form-label">Password</label>
                <input type="password" name="password" class="input-field" placeholder="Minimum 6 characters" required minlength="6">
            </div>
            <div>
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="input-field" placeholder="Repeat password" required minlength="6">
            </div>
        </div>

        <div>
            <label class="form-label">Requested Role</label>
            <select name="role" class="input-field" required>
                <option value="Teacher">Teacher / Academic Staff</option>
                <option value="Hostel Supervisor">Hostel Supervisor</option>
                <option value="Accountant">Accountant / Financial Officer</option>
            </select>
        </div>

        <button type="submit" class="btn-primary mt-2" style="width: 100%;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5v14"/></svg>
            Create Portal Request
        </button>
    </form>

    <div class="text-center font-semibold text-xs my-4" style="color: var(--text-3);">— Already registered? —</div>
    <div class="text-center text-sm">
        <a href="login.php" class="font-bold hover:underline" style="color: var(--terracotta);">Return to Login</a>
    </div>
</div>

</body>
</html>
