<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Settings & Configuration Panel
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Only Admin can access
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

require '../components/header.php';

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
                    $message = ' Settings updated successfully!';
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
                    $message = ' Settings saved successfully!';
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

<div class="max-w-[1400px] mx-auto">

        <!-- Sidebar -->
<!-- Main Content -->
        <div>
            <div class="space-y-6">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8">
                    <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('settings', 'w-4 h-4'); ?> Settings & Configuration</h2>
                    <p class="text-slate-600 mt-2">Manage system settings and preferences</p>
                </div>

                <!-- Messages -->
                <?php if (!empty($message)): ?>
                    <div class="success-message">
                        <span><?php echo icon('check', 'w-3.5 h-3.5'); ?></span>
                        <span><?php echo htmlspecialchars($message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <span><?php echo icon('x', 'w-3.5 h-3.5'); ?></span>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Settings Form -->
                <form method="POST" class="glass rounded-2xl p-8">
                <?= csrf_field() ?>

                    <!-- School Information -->
                    <div class="settings-section">
                        <h3 class="settings-title"><?php echo icon('building', 'w-4 h-4'); ?> School Information</h3>

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
                        <h3 class="settings-title"><?php echo icon('payments', 'w-4 h-4'); ?> Fee Configuration</h3>

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
                        <h3 class="settings-title"><?php echo icon('settings', 'w-4 h-4'); ?> System Configuration</h3>

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
                            <?php echo icon('save', 'w-4 h-4'); ?> Save Settings
                        </button>
                        <a href="../index.php" class="flex items-center gap-2 px-6 py-3 bg-slate-200 text-slate-800 rounded-12px font-600 hover:bg-slate-300 transition">
                            <?php echo icon('arrow-left', 'w-4 h-4'); ?> Back to Dashboard
                        </a>
                    </div>

                </form>

            </div>
        </div>

    </div>
<?php
require '../components/footer.php'; 
?>
