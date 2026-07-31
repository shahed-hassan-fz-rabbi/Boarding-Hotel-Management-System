<?php
/**
 * ==========================================
 * LOGIN PAGE
 * ==========================================
 */

session_start();
require '../db.php';

// Already Logged In
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: ../index.php");
    exit();
}

$error = "";
$success = "";

if (isset($_GET['registered'])) {
    $success = "Registration successful! Waiting for admin approval.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($email === "" || $password === "") {
        $error = "Please fill all fields.";
    } else {
        // Safe query fetch including role and status check
        $sql = "SELECT id, full_name, email, password, role, status FROM users WHERE email=? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Prepare Failed : " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // FIXED SECURITY: Block login if account status is 0 (Pending Admin Approval)
            if (intval($user["status"]) !== 1) {
                $error = "🔒 Your account is pending admin approval or has been suspended.";
            } else {
                if (password_verify($password, $user["password"])) {
                    // Secure Session
                    session_regenerate_id(true);

                    $_SESSION["logged_in"] = true;
                    $_SESSION["user_id"] = $user["id"];
                    $_SESSION["user_name"] = $user["full_name"];
                    $_SESSION["user_email"] = $user["email"];
                    $_SESSION["user_role"] = $user["role"];
                    $_SESSION["LAST_ACTIVITY"] = time();

                    // Update last login
                    $update = $conn->prepare("UPDATE users SET last_login=NOW() WHERE id=?");
                    $update->bind_param("i", $user["id"]);
                    $update->execute();
                    $update->close();

                    header("Location: ../index.php");
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            }
        } else {
            $error = "Invalid email or password.";
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
    <title>Login - Boarding School System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
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
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            border-radius: 24px;
            padding: 2.5rem 3rem;
            width: 100%;
            max-width: 450px;
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
        <div class="text-5xl mb-2">🏢</div>
        <h1 class="text-3xl font-black text-slate-800 tracking-tight">Portal Login</h1>
        <p class="text-slate-500 text-sm mt-1">Boarding School Management System</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-rose-100 border border-rose-200 text-rose-800 p-3.5 rounded-xl mb-4 text-xs font-bold"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl mb-4 text-xs font-bold"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
            <input type="email" name="email" class="input-field" placeholder="admin@boarding.com" required>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
            <input type="password" name="password" class="input-field" placeholder="••••••••" required>
        </div>

        <button type="submit" class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:opacity-95 transition text-sm">
            Login Now
        </button>
    </form>

    <div class="mt-6 text-center text-xs font-semibold text-slate-500">
        New staff member? <a href="register.php" class="text-indigo-600 font-bold hover:underline">Request account here</a>
    </div>

    <div class="mt-5 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
        <p class="font-bold text-slate-700 mb-1">📌 Super Admin Credentials:</p>
        <p class="text-slate-500"><strong>Email:</strong> admin@boarding.com</p>
        <p class="text-slate-500"><strong>Password:</strong> admin123</p>
    </div>
</div>

</body>
</html>