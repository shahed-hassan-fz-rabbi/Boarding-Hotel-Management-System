<?php
/**
 * ==========================================
 * FILE NEW COMPLAINT PAGE
 * ==========================================
 */

require '../db.php';
require '../includes/auth_check.php';

requireRoles(['Admin','Hostel Supervisor']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $complaint_title = trim($_POST['complaint_title'] ?? '');
    $complaint_description = trim($_POST['complaint_description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Medium');

    // Validation
    if (empty($student_id) || empty($complaint_title) || empty($complaint_description) || empty($category)) {
        $error = '❌ Please fill all required fields';
    } else if (strlen($complaint_title) < 5) {
        $error = '❌ Complaint title must be at least 5 characters';
    } else if (strlen($complaint_description) < 10) {
        $error = '❌ Complaint description must be at least 10 characters';
    } else {
        // Check if student exists
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE id = ?");
        $check_stmt->bind_param("i", $student_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            $error = '❌ Student not found';
        } else {
            // Insert complaint
            $stmt = $conn->prepare("INSERT INTO complaints (student_id, complaint_title, complaint_description, category, priority, status) VALUES (?, ?, ?, ?, ?, 'Open')");
            $stmt->bind_param("issss", $student_id, $complaint_title, $complaint_description, $category, $priority);

            if ($stmt->execute()) {
                $complaint_id = $conn->insert_id;
                $success = '✅ Complaint filed successfully! Complaint ID: #' . $complaint_id;
                $conn->query("INSERT INTO activity_logs (user_id, action, ip_address) VALUES ({$user_id}, 'Filed Complaint ID: {$complaint_id}', '{$_SERVER['REMOTE_ADDR']}')");
                header('refresh:2;url=list.php');
            } else {
                $error = '❌ Error filing complaint: ' . $conn->error;
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

// Complaint categories
$categories = array(
    'Maintenance' => '🔧 Maintenance',
    'Food Quality' => '🍲 Food Quality',
    'Cleanliness' => '🧹 Cleanliness',
    'Noise' => '🔊 Noise/Disturbance',
    'Behavioral' => '👥 Behavioral Issue',
    'Security' => '🔐 Security Concern',
    'Water/Power' => '💧 Water/Power Issue',
    'Other' => '📋 Other'
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Complaint - Hostel Management</title>
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
        .priority-selector {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .priority-btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            background: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .priority-btn.high {
            border-color: #ef4444;
            color: #ef4444;
        }
        .priority-btn.high.active {
            background: #ef4444;
            color: white;
        }
        .priority-btn.medium {
            border-color: #f59e0b;
            color: #f59e0b;
        }
        .priority-btn.medium.active {
            background: #f59e0b;
            color: white;
        }
        .priority-btn.low {
            border-color: #10b981;
            color: #10b981;
        }
        .priority-btn.low.active {
            background: #10b981;
            color: white;
        }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-4xl mx-auto">

    <div class="glass rounded-2xl p-6 mb-8">
        <h1 class="text-3xl font-bold text-slate-800">📝 File a Complaint</h1>
        <p class="text-slate-600 mt-2">Report any issues or concerns to the management</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" class="glass rounded-2xl p-8">
        <?= csrf_field() ?>

        <h2 class="text-2xl font-bold text-slate-800 mb-6">🎓 Your Information</h2>
        
        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Your Name / Student ID *</label>
            <select name="student_id" class="input-field w-full" required>
                <option value="">-- Select your name --</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['student_code'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h2 class="text-2xl font-bold text-slate-800 mb-6">⚠️ Complaint Details</h2>
        
        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Complaint Title *</label>
            <input type="text" name="complaint_title" class="input-field w-full" placeholder="Brief title of your complaint" minlength="5" maxlength="255" required>
            <p class="text-xs text-slate-500 mt-1">Minimum 5 characters required</p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Detailed Description *</label>
            <textarea name="complaint_description" class="input-field w-full" rows="5" placeholder="Please provide detailed information about your complaint..." minlength="10" required></textarea>
            <p class="text-xs text-slate-500 mt-1">Minimum 10 characters required</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Category *</label>
                <select name="category" class="input-field w-full" required>
                    <option value="">-- Select category --</option>
                    <?php foreach ($categories as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Priority Level</label>
                <input type="hidden" name="priority" id="priority_input" value="Medium">
                <div class="priority-selector">
                    <button type="button" class="priority-btn low" onclick="setPriority('Low')">🟢 Low</button>
                    <button type="button" class="priority-btn medium active" onclick="setPriority('Medium')">🟡 Medium</button>
                    <button type="button" class="priority-btn high" onclick="setPriority('High')">🔴 High</button>
                </div>
            </div>
        </div>

        <div class="flex gap-4 pt-6 mt-8 border-t border-slate-200">
            <button type="submit" class="btn-submit">✅ Submit Complaint</button>
            <a href="list.php" class="px-6 py-3 bg-slate-400 text-white rounded-12px text-center font-600 hover:bg-slate-500 transition">← Back to List</a>
        </div>

    </form>

</div>

<script>
    function setPriority(level) {
        document.getElementById('priority_input').value = level;
        const buttons = document.querySelectorAll('.priority-btn');
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.closest('.priority-btn').classList.add('active');
    }
</script>

</body>
</html>