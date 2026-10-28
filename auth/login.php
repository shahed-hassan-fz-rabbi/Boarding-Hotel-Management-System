<?php
/**
 * ==========================================
 * LOGIN PAGE — warm boutique-hotel design
 * ==========================================
 */

session_start();
require '../db.php';
require_once __DIR__ . '/../includes/csrf.php';

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
    if (!csrf_verify()) {
        csrf_reject();
    }
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
                $error = "Your account is pending admin approval or has been suspended.";
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
    <title>Login - Boarding Hotel Management</title>

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
        .auth-shell {
            display: flex;
            min-height: 100vh;
        }

        /* Left visual panel: layered warm gradient composition (no fake imagery) */
        .auth-visual {
            flex: 1.05;
            position: relative;
            background:
                radial-gradient(900px 500px at 20% 15%, rgba(255, 209, 184, 0.35) 0%, transparent 55%),
                radial-gradient(700px 700px at 90% 85%, rgba(255, 135, 95, 0.28) 0%, transparent 55%),
                linear-gradient(150deg, #A93C16 0%, #C94F1B 42%, #B4431A 68%, #78300F 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5rem 3.5rem 2.75rem;
            overflow: hidden;
        }

        .auth-visual::after {
            content: "";
            position: absolute; inset: 0;
            background:
                radial-gradient(circle at 82% 22%, rgba(255,244,236,0.16) 0%, transparent 34%),
                radial-gradient(circle at 15% 80%, rgba(41,37,33,0.28) 0%, transparent 44%);
            pointer-events: none;
        }

        .auth-panel {
            flex: 1;
            display: flex; align-items: center; justify-content: center;
            padding: 2.5rem 1.5rem;
            position: relative;
        }

        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 2.75rem 2.75rem 2.25rem;
            width: 100%;
            max-width: 440px;
        }

        .visual-chip {
            display: inline-flex; align-items: center; gap: 0.6rem;
            background: rgba(255, 248, 243, 0.12);
            border: 1px solid rgba(255, 248, 243, 0.25);
            -webkit-backdrop-filter: blur(12px);
            backdrop-filter: blur(12px);
            color: #FFF4EC;
            border-radius: 999px;
            padding: 0.5rem 1rem;
            font-size: 0.8rem; font-weight: 600;
        }

        @media (max-width: 900px) {
            .auth-visual { display: none; }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    <!-- Visual panel -->
    <aside class="auth-visual">
        <div style="position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 0.8rem;">
                <div style="width: 46px; height: 46px; border-radius: 13px; background: rgba(255,248,243,0.14); border: 1px solid rgba(255,248,243,0.3); display: flex; align-items: center; justify-content: center; color: #FFF4EC;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v22"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4M10 10h4M10 14h4M10 18h4"/></svg>
                </div>
                <div>
                    <p style="color: #FFF4EC; font-weight: 800; font-size: 1.05rem; line-height: 1.2;">Boarding Hotel</p>
                    <p style="color: rgba(255,244,236,0.66); font-size: 0.62rem; letter-spacing: 0.18em; text-transform: uppercase;">Management System</p>
                </div>
            </div>
        </div>

        <div style="position: relative; z-index: 2;">
            <h2 style="color: #FFF4EC; font-size: 2rem; font-weight: 800; line-height: 1.25; letter-spacing: -0.01em; max-width: 26rem;">
                Hospitality, organised with warmth.
            </h2>
            <p style="color: rgba(255,244,236,0.78); font-size: 0.92rem; line-height: 1.65; margin-top: 0.9rem; max-width: 24rem;">
                Rooms, residents, meals, and payments — the entire boarding operation in one calm, elegant dashboard.
            </p>
            <div style="display: flex; gap: 0.6rem; margin-top: 1.6rem; flex-wrap: wrap;">
                <span class="visual-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4v16"/><path d="M2 8h18a2 2 0 0 1 2 2v10"/><path d="M2 17h20"/><path d="M6 8v9"/></svg>
                    Room &amp; bed management
                </span>
                <span class="visual-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/><path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg>
                    Secure payments
                </span>
                <span class="visual-chip">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 7 13.5 15.5 8.5 10.5 2 17"/><path d="M16 7h6v6"/></svg>
                    Live analytics
                </span>
            </div>
        </div>

        <p style="position: relative; z-index: 2; color: rgba(255,244,236,0.5); font-size: 0.75rem;">
            &copy; <?= date('Y') ?> Boarding Hotel Management System
        </p>
    </aside>

    <!-- Form panel -->
    <div class="auth-panel">
        <div class="auth-card">
            <button type="button" class="theme-toggle" style="position: absolute; top: 1.25rem; right: 1.25rem;" aria-label="Switch to dark theme" aria-pressed="false" title="Toggle theme">
                <svg class="icon-sun w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                <svg class="icon-moon w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            </button>

            <div class="text-center mb-7">
                <div class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-white shadow-lg" style="background: var(--grad-primary);">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <h1 class="text-2xl font-extrabold mt-4 tracking-tight" style="color: var(--text);">Welcome back</h1>
                <p class="text-sm mt-1 font-semibold" style="color: var(--text-2);">Sign in to your management portal</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label" for="loginEmail">Email Address</label>
                    <input type="email" id="loginEmail" name="email" class="input-field" placeholder="you@boarding.com" required>
                </div>

                <div>
                    <label class="form-label" for="loginPassword">Password</label>
                    <input type="password" id="loginPassword" name="password" class="input-field" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="w-full py-3 btn-primary text-sm" style="width: 100%;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg>
                    Sign in
                </button>
            </form>

            <div class="mt-6 text-center text-xs font-semibold" style="color: var(--text-2);">
                New staff member? <a href="register.php" class="font-bold hover:underline" style="color: var(--terracotta);">Request account here</a>
            </div>

            <div class="mt-5 p-3.5 rounded-xl border text-xs" style="background: var(--surface-2); border-color: var(--border); color: var(--text-2);">
                <p class="font-bold mb-1" style="color: var(--text);">Demo credentials</p>
                <p><strong>Email:</strong> admin@boarding.com</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>
