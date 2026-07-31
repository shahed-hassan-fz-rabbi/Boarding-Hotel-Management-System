<?php
/**
 * ==========================================
 * LOGIN PAGE - COMPLETE WITH REGISTRATION LINK
 * ==========================================
 */

require '../db.php';

$error = '';
$success = '';

// Check if session expired
if (isset($_GET['expired'])) {
    $error = '⏱️ Session expired. Please login again.';
}

// Check if coming from registration
if (isset($_GET['registered'])) {
    $success = '✅ Registration successful! You can now login.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = '❌ Please fill all fields';
    } else {
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password using bcrypt
            if (password_verify($password, $user['password'])) {
                // Password correct, start session
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $email;
                $_SESSION['created'] = time();

                // Log activity
                $ip = $_SERVER['REMOTE_ADDR'];
                $action = "Login";
                $conn->query("INSERT INTO activity_logs (user_id, action, ip_address) VALUES ({$user['id']}, '$action', '$ip')");

                // Redirect to dashboard
                header('Location: ../index.php');
                exit();
            } else {
                $error = '❌ Invalid email or password';
            }
        } else {
            $error = '❌ Invalid email or password';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hostel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            margin: 1rem;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .logo-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .logo-subtitle {
            color: #64748b;
            font-size: 0.95rem;
        }

        .input-field {
            border: 1.5px solid rgba(102, 126, 234, 0.3);
            background: rgba(102, 126, 234, 0.05);
            padding: 0.9rem 1.2rem;
            border-radius: 12px;
            color: #1e293b;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .input-field::placeholder {
            color: rgba(30, 41, 59, 0.5);
        }

        .input-field:focus {
            outline: none;
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.9rem 2rem;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .btn-login:hover {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            color: #94a3b8;
            font-weight: 500;
        }

        .divider::before,
        .divider::after {
            content: '';
            display: inline-block;
            width: 35%;
            height: 1px;
            background: #e2e8f0;
            vertical-align: middle;
        }

        .divider::before {
            margin-right: 0.75rem;
        }

        .divider::after {
            margin-left: 0.75rem;
        }

        .demo-box {
            background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
            border: 1px solid #93c5fd;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .demo-title {
            color: #1e40af;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .demo-item {
            color: #1e40af;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .demo-item:last-child {
            margin-bottom: 0;
        }

        .demo-label {
            font-weight: 600;
        }

        .demo-value {
            background: rgba(255, 255, 255, 0.7);
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-family: monospace;
        }

        .footer-text {
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 1.5rem;
        }

        .footer-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #475569;
            font-size: 0.9rem;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .glass {
                padding: 2rem 1.5rem;
                border-radius: 20px;
            }

            .logo-title {
                font-size: 1.8rem;
            }

            .btn-login {
                padding: 0.8rem 1.5rem;
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>

<div class="glass">
    <!-- Logo Section -->
    <div class="logo-section">
        <div class="logo-icon">🏫</div>
        <h1 class="logo-title">Hostel Manager</h1>
        <p class="logo-subtitle">Management System Login</p>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Success Alert -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="">
        <!-- Email Field -->
        <div class="form-group">
            <label class="form-label" for="email">📧 Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                class="input-field"
                placeholder="admin@boarding.com"
                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                required>
        </div>

        <!-- Password Field -->
        <div class="form-group">
            <label class="form-label" for="password">🔐 Password</label>
            <input
                type="password"
                id="password"
                name="password"
                class="input-field"
                placeholder="••••••••"
                required>
        </div>

        <!-- Remember Me -->
        <div class="form-group checkbox-wrapper">
            <input type="checkbox" id="remember" name="remember" value="1">
            <label for="remember" style="margin-bottom: 0; cursor: pointer;">Remember me</label>
        </div>

        <!-- Login Button -->
        <button type="submit" class="btn-login">
            🚀 Login Now
        </button>
    </form>

    <!-- Divider -->
    <div class="divider">Demo Credentials</div>

    <!-- Demo Credentials Box -->
    <div class="demo-box">
        <div class="demo-title">
            📌 Test Account
        </div>
        <div class="demo-item">
            <span class="demo-label">Email:</span>
            <span class="demo-value">admin@boarding.com</span>
        </div>
        <div class="demo-item">
            <span class="demo-label">Password:</span>
            <span class="demo-value">admin123</span>
        </div>
    </div>

    <!-- Registration Link -->
    <div class="footer-text">
        New user? <a href="register.php" class="footer-link">Create account here</a>
    </div>

    <!-- Footer -->
    <p style="text-align: center; color: #94a3b8; font-size: 0.75rem; margin-top: 1rem;">
        © 2024 Hostel Management System v1.0
    </p>
</div>

</body>
</html>