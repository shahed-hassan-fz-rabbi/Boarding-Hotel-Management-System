<?php
/**
 * ==========================================
 * PASSWORD HASH UTILITY (ADMIN ONLY)
 * Generate bcrypt hashes to place into
 * the users table via SQL, when needed.
 * ==========================================
 */

require_once 'includes/auth_check.php';

if ($user_role !== 'Admin') {
    header('Location: ' . BASE_URL . 'index.php?error=Access denied');
    exit();
}

$password = trim($_POST['password'] ?? '');
$hash = '';
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strlen($password) < 6) {
        $notice = 'Password must be at least 6 characters.';
    } elseif (!csrf_verify()) {
        http_response_code(403);
        die('Request blocked (CSRF token invalid).');
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hash Tool - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 2rem; width: 100%; max-width: 620px; margin: 1rem; }
        .input { border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.7rem 1rem; border-radius: 10px; width: 100%; outline: none; }
        .input:focus { border-color: #667eea; }
        .btn { background: #4f46e5; color: #fff; padding: 0.7rem 1.25rem; border-radius: 10px; font-weight: 700; border: none; cursor: pointer; }
    </style>
</head>
<body>
<div class="card">
    <h1 class="text-xl font-black text-slate-800 mb-1">🔐 Password Hash Tool</h1>
    <p class="text-sm text-slate-500 mb-4">Generate a bcrypt hash, then run the UPDATE SQL yourself in phpMyAdmin / MySQL CLI.</p>

    <?php if ($notice): ?>
        <div class="bg-rose-100 border border-rose-200 text-rose-800 p-3 rounded-xl text-sm mb-4"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php require 'includes/csrf.php'; echo csrf_field(); ?>
        <input class="input mb-3" type="password" name="password" placeholder="New password (min 6 chars)" required>
        <button class="btn" type="submit">Generate Hash</button>
    </form>

    <?php if ($hash): ?>
        <div class="mt-6">
            <p class="text-xs font-bold text-slate-500 uppercase mb-1">SQL to run (replaces the password of the entered email)</p>
            <textarea rows="2" cols="100" class="input font-mono text-xs">UPDATE users SET password = '<?= htmlspecialchars($hash) ?>' WHERE email = 'user@example.com';</textarea>
        </div>
    <?php endif; ?>

    <a href="<?= BASE_URL ?>settings/index.php" class="inline-block mt-6 text-sm text-indigo-600 font-bold hover:underline">← Back to settings</a>
</div>
</body>
</html>
