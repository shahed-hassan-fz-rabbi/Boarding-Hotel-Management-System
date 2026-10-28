<?php
/**
 * ==========================================
 * ADD MEAL RECORD PAGE
 * ==========================================
 */

require '../db.php';
require '../includes/auth_check.php';
require_once __DIR__ . '/../components/icon.php';

requireRoles(['Admin','Hostel Supervisor']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $meal_date = trim($_POST['meal_date'] ?? '');
    $meal_type = trim($_POST['meal_type'] ?? '');
    $served = isset($_POST['served']) ? 1 : 0;
    $quantity = trim($_POST['quantity'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    // Validation
    if (empty($student_id) || empty($meal_date) || empty($meal_type)) {
        $error = ' Please fill all required fields';
    } else if (!in_array($meal_type, ['Breakfast', 'Lunch', 'Dinner'], true)) {
        $error = ' Invalid meal type';
    } else {
        // Check if student exists
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
        $check_stmt->bind_param("i", $student_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            $error = ' Student not found';
        } else {
            // Insert meal record
            $stmt = $conn->prepare("INSERT INTO meal_records (student_id, meal_date, meal_type, served, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issidsi", $student_id, $meal_date, $meal_type, $served, $quantity, $notes, $user_id);

            if ($stmt->execute()) {
                $success = ' Meal record added successfully!';

                $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                if ($log) {
                    $action = "Added Meal Record for Student ID: " . intval($student_id);
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log->bind_param('iss', $user_id, $action, $ip);
                    $log->execute();
                    $log->close();
                }
                header('refresh:2;url=list.php');
            } else {
                error_log('meals/add.php insert failed: ' . $stmt->error);
                $error = ' Error adding meal record. Please try again.';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Get all students for dropdown
$students_result = $conn->query("SELECT id, student_code, first_name, last_name FROM students WHERE status = 'Active' ORDER BY first_name ASC");
$students = [];
if ($students_result) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Meal Record - Hostel Management</title>
    <!-- Early theme resolution: runs before first paint to prevent FOUC -->
    <script>
    (function () {
        try {
            var t = localStorage.getItem('bhm-theme');
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/theme.css">
    <script src="../assets/app.js" defer></script>
    
    <style>
        body {
            background: linear-gradient(135deg, var(--terracotta) 0%, var(--terracotta-dark) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .input-field {
            border: 1px solid rgba(201, 79, 27, 0.3);
            background: rgba(201, 79, 27, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: var(--text);
            font-size: 0.95rem;
        }
        .input-field:focus {
            outline: none;
            background: rgba(201, 79, 27, 0.1);
            border-color: var(--terracotta);
            box-shadow: 0 0 0 3px rgba(201, 79, 27, 0.1);
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--terracotta) 0%, var(--terracotta-dark) 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            box-shadow: 0 10px 30px rgba(201, 79, 27, 0.4);
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-error {
            background: var(--error-soft);
            color: var(--error);
            border: 1px solid color-mix(in srgb, var(--error) 30%, transparent);
        }
        .alert-success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid color-mix(in srgb, var(--success) 40%, transparent);
        }
    </style>
</head>
<body>
    <button type="button" class="theme-toggle theme-toggle--float" aria-label="Switch to dark theme" aria-pressed="false" title="Toggle theme">
        <?php echo icon('sun', 'icon-sun w-[18px] h-[18px]'); ?>
        <?php echo icon('moon', 'icon-moon w-[18px] h-[18px]'); ?>
    </button>

<div class="p-4 lg:p-8 max-w-4xl mx-auto">

    <div class="glass rounded-2xl p-6 mb-8">
        <h1 class="text-3xl font-bold text-slate-800"><?php echo icon('plus', 'w-4 h-4'); ?> Add Meal Record</h1>
        <p class="text-slate-600 mt-2">Record meal consumption for a student</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" class="glass rounded-2xl p-8">
        <?= csrf_field() ?>

        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?php echo icon('students', 'w-4 h-4'); ?> Student Information</h2>
        
        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Select Student *</label>
            <select name="student_id" class="input-field w-full" required>
                <option value="">-- Choose a student --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['student_code'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?php echo icon('meals', 'w-4 h-4'); ?> Meal Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Meal Date *</label>
                <input type="date" name="meal_date" class="input-field w-full" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Meal Type *</label>
                <select name="meal_type" class="input-field w-full" required>
                    <option value="">-- Select meal type --</option>
                    <option value="Breakfast"><?php echo icon('sun', 'w-4 h-4'); ?> Breakfast</option>
                    <option value="Lunch"><?php echo icon('sun', 'w-4 h-4'); ?> Lunch</option>
                    <option value="Dinner"><?php echo icon('moon', 'w-4 h-4'); ?> Dinner</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Quantity</label>
                <input type="number" name="quantity" class="input-field w-full" step="0.1" min="0" placeholder="e.g., 1.0">
            </div>

            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Meal Served</label>
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="served" id="served_checkbox" checked>
                    <label for="served_checkbox" class="text-slate-700 font-500 cursor-pointer"><?php echo icon('check-circle', 'w-4 h-4'); ?> Meal was served</label>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Notes (Optional)</label>
            <textarea name="notes" class="input-field w-full" rows="4" placeholder="Add any additional notes about this meal..."></textarea>
        </div>

        <div class="flex gap-4 pt-6 border-t border-slate-200">
            <button type="submit" class="btn-submit"><?php echo icon('check-circle', 'w-4 h-4'); ?> Add Meal Record</button>
            <a href="list.php" class="px-6 py-3 bg-slate-400 text-white rounded-12px text-center font-600 hover:bg-slate-500 transition"><?php echo icon('arrow-left', 'w-4 h-4'); ?> Back to List</a>
        </div>

    </form>

</div>

</body>
</html>