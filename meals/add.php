<?php
/**
 * ==========================================
 * ADD MEAL RECORD PAGE
 * ==========================================
 */

require '../db.php';
require '../includes/auth_check.php';

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
        $error = '❌ Please fill all required fields';
    } else {
        // Check if student exists
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
        $check_stmt->bind_param("i", $student_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            $error = '❌ Student not found';
        } else {
            // Insert meal record
            $stmt = $conn->prepare("INSERT INTO meal_records (student_id, meal_date, meal_type, served, quantity, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiids", $student_id, $meal_date, $meal_type, $served, $quantity, $notes, $user_id);

            if ($stmt->execute()) {
                $success = '✅ Meal record added successfully!';
                $conn->query("INSERT INTO activity_logs (user_id, action, ip_address) VALUES ({$user_id}, 'Added Meal Record for Student ID: {$student_id}', '{$_SERVER['REMOTE_ADDR']}')");
                header('refresh:2;url=list.php');
            } else {
                $error = '❌ Error adding meal record: ' . $conn->error;
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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .input-field {
            border: 1px solid rgba(102, 126, 234, 0.3);
            background: rgba(102, 126, 234, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: #1e293b;
            font-size: 0.95rem;
        }
        .input-field:focus {
            outline: none;
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-weight: 500;
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
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-4xl mx-auto">

    <div class="glass rounded-2xl p-6 mb-8">
        <h1 class="text-3xl font-bold text-slate-800">➕ Add Meal Record</h1>
        <p class="text-slate-600 mt-2">Record meal consumption for a student</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" class="glass rounded-2xl p-8">

        <h2 class="text-2xl font-bold text-slate-800 mb-6">🎓 Student Information</h2>
        
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

        <h2 class="text-2xl font-bold text-slate-800 mb-6">🍽️ Meal Details</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Meal Date *</label>
                <input type="date" name="meal_date" class="input-field w-full" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Meal Type *</label>
                <select name="meal_type" class="input-field w-full" required>
                    <option value="">-- Select meal type --</option>
                    <option value="Breakfast">🌅 Breakfast</option>
                    <option value="Lunch">☀️ Lunch</option>
                    <option value="Dinner">🌙 Dinner</option>
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
                    <label for="served_checkbox" class="text-slate-700 font-500 cursor-pointer">✅ Meal was served</label>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Notes (Optional)</label>
            <textarea name="notes" class="input-field w-full" rows="4" placeholder="Add any additional notes about this meal..."></textarea>
        </div>

        <div class="flex gap-4 pt-6 border-t border-slate-200">
            <button type="submit" class="btn-submit">✅ Add Meal Record</button>
            <a href="list.php" class="px-6 py-3 bg-slate-400 text-white rounded-12px text-center font-600 hover:bg-slate-500 transition">← Back to List</a>
        </div>

    </form>

</div>

</body>
</html>