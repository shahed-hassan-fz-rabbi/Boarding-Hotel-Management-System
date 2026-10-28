<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Settings & Configuration Panel
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';

// Only Admin can access
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

$message = '';
$error = '';

/* Fetch current settings */
$settingsSql = "SELECT * FROM system_settings LIMIT 1";
$settingsResult = $conn->query($settingsSql);
$settings = $settingsResult ? $settingsResult->fetch_assoc() : [];

/* Handle settings update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $school_name = trim($_POST['school_name'] ?? '');
    $school_email = trim($_POST['school_email'] ?? '');
    $school_phone = trim($_POST['school_phone'] ?? '');
    $school_address = trim($_POST['school_address'] ?? '');
    $room_fee = floatval($_POST['room_fee'] ?? 0);
    $meal_fee = floatval($_POST['meal_fee'] ?? 0);
    $session_timeout = intval($_POST['session_timeout'] ?? 1800);
    $backup_enabled = intval($_POST['backup_enabled'] ?? 0);
    $email_notifications = intval($_POST['email_notifications'] ?? 1);
    $academic_year = trim($_POST['academic_year'] ?? '');

    /* Validation */
    if (empty($school_name) || empty($school_email) || empty($school_phone)) {
        $error = 'School name, email, and phone are required.';
    } else if (!filter_var($school_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {

        /* Check if settings exist */
        $checkSql = "SELECT id FROM system_settings LIMIT 1";
        $checkResult = $conn->query($checkSql);

        if ($checkResult && $checkResult->num_rows > 0) {
            /* Update existing settings */
            $updateSql = "UPDATE system_settings 
                          SET school_name = ?, school_email = ?, school_phone = ?, 
                              school_address = ?, room_fee = ?, meal_fee = ?,
                              session_timeout = ?, backup_enabled = ?, 
                              email_notifications = ?, academic_year = ?,
                              updated_at = NOW()
                          WHERE id = 1";
            
            $stmt = $conn->prepare($updateSql);
            if ($stmt) {
                // FIXED: Changed type definition string to 'ssssddiiis' to match the 10 variables perfectly
                $stmt->bind_param('ssssddiiis', 
                    $school_name, $school_email, $school_phone, 
                    $school_address, $room_fee, $meal_fee,
                    $session_timeout, $backup_enabled, 
                    $email_notifications, $academic_year
                );
                
                if ($stmt->execute()) {
                    $message = '✓ Settings updated successfully!';
                    $settings = compact('school_name', 'school_email', 'school_phone', 
                                       'school_address', 'room_fee', 'meal_fee',
                                       'session_timeout', 'backup_enabled', 
                                       'email_notifications', 'academic_year');
                } else {
                    $error = 'Failed to update settings: ' . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            /* Insert new settings */
            $insertSql = "INSERT INTO system_settings 
                          (school_name, school_email, school_phone, school_address, 
                           room_fee, meal_fee, session_timeout, backup_enabled, 
                           email_notifications, academic_year)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insertSql);
            if ($stmt) {
                // FIXED: Changed type definition string here as well to 'ssssddiiis'
                $stmt->bind_param('ssssddiiis', 
                    $school_name, $school_email, $school_phone, 
                    $school_address, $room_fee, $meal_fee,
                    $session_timeout, $backup_enabled, 
                    $email_notifications, $academic_year
                );
                
                if ($stmt->execute()) {
                    $message = '✓ Settings saved successfully!';
                    $settings = compact('school_name', 'school_email', 'school_phone', 
                                       'school_address', 'room_fee', 'meal_fee',
                                       'session_timeout', 'backup_enabled', 
                                       'email_notifications', 'academic_year');
                } else {
                    $error = 'Failed to save settings: ' . $stmt->error;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Hostel System</title>
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
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
        }

        .nav-link {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .input-field {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1e293b;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            width: 100%;
        }

        .input-field:focus {
            outline: none;
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .icon-svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .success-message {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #667eea;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .settings-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .settings-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        @media (max-width: 1024px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="sidebar w-80 hidden lg:flex flex-col">
            <div class="p-8 border-b border-slate-700">
                <h1 class="text-3xl font-bold text-white">🏫 Hostel</h1>
                <p class="text-slate-400 text-sm mt-2">Management System</p>
            </div>

            <nav class="flex-1 p-6 space-y-2 overflow-y-auto">
                <a href="../index.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M3 12l9-9 9 9M5 10v11a1 1 0 001 1h3v-5a1 1 0 011-1h4a1 1 0 011 1v5h3a1 1 0 001-1V10"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="../reports/analytics.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M3 3v18a2 2 0 002 2h18M3 9h18M3 15h18M9 3v18M15 3v18"></path>
                    </svg>
                    <span>Analytics</span>
                </a>
                <a href="index.php" class="nav-link active px-4 py-3 text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2zm0 2c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zm1 5h-2v5l4.3 2.6.8-1.3-3.1-1.9V9z"></path>
                    </svg>
                    <span>Settings</span>
                </a>
                <a href="logs.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    <span>Activity Logs</span>
                </a>
            </nav>

            <div class="p-6 border-t border-slate-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <div>
                        <p class="text-white font-600"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-slate-400 text-sm"><?php echo htmlspecialchars($user_role); ?></p>
                    </div>
                </div>
                <a href="../auth/logout.php" class="w-full py-2 px-4 bg-red-500/20 text-red-400 rounded-10px hover:bg-red-500/30 transition font-500 text-sm text-center block">
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-4 lg:p-8">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <h2 class="text-3xl font-bold text-slate-800">⚙️ Settings & Configuration</h2>
                    <p class="text-slate-600 mt-2">Manage system settings and preferences</p>
                </div>

                <!-- Messages -->
                <?php if (!empty($message)): ?>
                    <div class="success-message">
                        <span>✓</span>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <span>✗</span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Settings Form -->
                <form method="POST" class="glass rounded-2xl p-8">
                <?= csrf_field() ?>

                    <!-- School Information -->
                    <div class="settings-section">
                        <h3 class="settings-title">🏫 School Information</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">School Name *</label>
                                <input type="text" name="school_name" class="input-field" 
                                       value="<?php echo htmlspecialchars($settings['school_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Academic Year</label>
                                <input type="text" name="academic_year" class="input-field" 
                                       placeholder="2024-2025"
                                       value="<?php echo htmlspecialchars($settings['academic_year'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="school_email" class="input-field" 
                                       value="<?php echo htmlspecialchars($settings['school_email'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="school_phone" class="input-field" 
                                       value="<?php echo htmlspecialchars($settings['school_phone'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Address</label>
                            <textarea name="school_address" class="input-field" rows="3"><?php echo htmlspecialchars($settings['school_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Fee Configuration -->
                    <div class="settings-section">
                        <h3 class="settings-title">💰 Fee Configuration</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Room Fee (৳) per Month</label>
                                <input type="number" name="room_fee" class="input-field" step="0.01"
                                       value="<?php echo htmlspecialchars($settings['room_fee'] ?? 0); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Meal Fee (৳) per Month</label>
                                <input type="number" name="meal_fee" class="input-field" step="0.01"
                                       value="<?php echo htmlspecialchars($settings['meal_fee'] ?? 0); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- System Configuration -->
                    <div class="settings-section">
                        <h3 class="settings-title">🔧 System Configuration</h3>

                        <div class="form-group">
                            <label class="form-label">Session Timeout (seconds)</label>
                            <input type="number" name="session_timeout" class="input-field"
                                   value="<?php echo htmlspecialchars($settings['session_timeout'] ?? 1800); ?>">
                            <small class="text-slate-500">Default: 1800 (30 minutes)</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label flex items-center gap-3 cursor-pointer">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="email_notifications" value="1"
                                           <?php echo ($settings['email_notifications'] ?? 1) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span>Enable Email Notifications</span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="form-label flex items-center gap-3 cursor-pointer">
                                <label class="toggle-switch">
                                    <input type="checkbox" name="backup_enabled" value="1"
                                           <?php echo ($settings['backup_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                                <span>Enable Automatic Backups</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex gap-3">
                        <button type="submit" class="btn-primary">
                            💾 Save Settings
                        </button>
                        <a href="../index.php" class="flex items-center gap-2 px-6 py-3 bg-slate-200 text-slate-800 rounded-12px font-600 hover:bg-slate-300 transition">
                            ← Back to Dashboard
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
<?php
require '../components/footer.php'; 
?>
</body>
</html>