<?php
/**
 * ==========================================
 * UPDATE COMPLAINT STATUS PAGE
 * ==========================================
 */

require '../db.php';
require '../includes/auth_check.php';

$error = '';
$success = '';
$complaint = null;

// Get complaint ID from URL
$complaint_id = $_GET['id'] ?? 0;

if ($complaint_id <= 0) {
    header('Location: list.php?error=invalid_id');
    exit();
}

// Fetch complaint details
$stmt = $conn->prepare("SELECT c.*, s.first_name, s.last_name, s.student_code, u.full_name as assigned_to_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id LEFT JOIN users u ON c.assigned_to = u.id WHERE c.id = ?");
$stmt->bind_param("i", $complaint_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: list.php?error=not_found');
    exit();
}

$complaint = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = trim($_POST['status'] ?? '');
    $assigned_to = trim($_POST['assigned_to'] ?? null);
    $resolution_notes = trim($_POST['resolution_notes'] ?? '');

    // Validation
    if (empty($new_status)) {
        $error = '❌ Please select a status';
    } else {
        // Update complaint
        $resolved_at = NULL;
        if ($new_status == 'Resolved') {
            $resolved_at = date('Y-m-d H:i:s');
        }

        $assigned_to_val = (!empty($assigned_to)) ? (int)$assigned_to : null;

        $stmt = $conn->prepare("UPDATE complaints SET status = ?, assigned_to = ?, resolution_notes = ?, resolved_at = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssssi", $new_status, $assigned_to_val, $resolution_notes, $resolved_at, $complaint_id);

        if ($stmt->execute()) {
            $success = '✅ Complaint status updated successfully!';
            $conn->query("INSERT INTO activity_logs (user_id, action, ip_address) VALUES ({$user_id}, 'Updated Complaint ID: {$complaint_id} Status to {$new_status}', '{$_SERVER['REMOTE_ADDR']}')");
            
            // Refresh complaint data
            $stmt2 = $conn->prepare("SELECT c.*, s.first_name, s.last_name, s.student_code, u.full_name as assigned_to_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id LEFT JOIN users u ON c.assigned_to = u.id WHERE c.id = ?");
            $stmt2->bind_param("i", $complaint_id);
            $stmt2->execute();
            $complaint = $stmt2->fetch_assoc();
            $stmt2->close();
        } else {
            $error = '❌ Error updating complaint: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get all staff members for assignment
$staff_result = $conn->query("SELECT id, full_name FROM users WHERE role IN ('Admin', 'Warden') ORDER BY full_name ASC");
$staff_members = [];
if ($staff_result) {
    while ($row = $staff_result->fetch_assoc()) {
        $staff_members[] = $row;
    }
}

// Status color mapping
$status_colors = array(
    'Open' => 'bg-blue-100 text-blue-800',
    'In Progress' => 'bg-yellow-100 text-yellow-800',
    'Resolved' => 'bg-green-100 text-green-800',
    'Rejected' => 'bg-red-100 text-red-800'
);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Complaint - Hostel Management</title>
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
        .info-card {
            background: #f8fafc;
            border-left: 4px solid #667eea;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-4xl mx-auto">

    <div class="glass rounded-2xl p-6 mb-8">
        <h1 class="text-3xl font-bold text-slate-800">🔧 Update Complaint</h1>
        <p class="text-slate-600 mt-2">Update complaint status and add resolution notes</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Complaint Details Display -->
    <div class="glass rounded-2xl p-8 mb-8">
        <h2 class="text-2xl font-bold text-slate-800 mb-6">📋 Complaint Information</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="info-card">
                <div class="text-sm text-slate-600 font-600 mb-1">Complaint ID</div>
                <div class="text-2xl font-bold text-slate-800">#<?php echo $complaint['id']; ?></div>
            </div>
            <div class="info-card">
                <div class="text-sm text-slate-600 font-600 mb-1">Student</div>
                <div class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($complaint['first_name'] . ' ' . $complaint['last_name']); ?></div>
                <div class="text-sm text-slate-500"><?php echo htmlspecialchars($complaint['student_code']); ?></div>
            </div>
            <div class="info-card">
                <div class="text-sm text-slate-600 font-600 mb-1">Current Status</div>
                <div class="status-badge <?php echo $status_colors[$complaint['status']]; ?>"><?php echo $complaint['status']; ?></div>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-6">
            <div class="font-bold text-blue-900 mb-2">📌 <?php echo htmlspecialchars($complaint['complaint_title']); ?></div>
            <div class="text-blue-800"><?php echo nl2br(htmlspecialchars($complaint['complaint_description'])); ?></div>
            <div class="mt-3 pt-3 border-t border-blue-200 text-sm text-blue-700">
                <span class="mr-4">📂 Category: <strong><?php echo htmlspecialchars($complaint['category']); ?></strong></span>
                <span>📅 Filed: <strong><?php echo date('d M Y, H:i', strtotime($complaint['created_at'])); ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Update Form -->
    <form method="POST" class="glass rounded-2xl p-8">

        <h2 class="text-2xl font-bold text-slate-800 mb-6">⚙️ Update Status</h2>

        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">New Status *</label>
            <select name="status" class="input-field w-full" required>
                <option value="">-- Select new status --</option>
                <option value="Open" <?php echo ($complaint['status'] == 'Open') ? 'selected' : ''; ?>>🔵 Open</option>
                <option value="In Progress" <?php echo ($complaint['status'] == 'In Progress') ? 'selected' : ''; ?>>🟡 In Progress</option>
                <option value="Resolved" <?php echo ($complaint['status'] == 'Resolved') ? 'selected' : ''; ?>>🟢 Resolved</option>
                <option value="Rejected" <?php echo ($complaint['status'] == 'Rejected') ? 'selected' : ''; ?>>🔴 Rejected</option>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Assign To Staff Member</label>
            <select name="assigned_to" class="input-field w-full">
                <option value="">-- Select staff member (optional) --</option>
                <?php foreach ($staff_members as $member): ?>
                    <option value="<?php echo $member['id']; ?>" <?php echo ($complaint['assigned_to'] == $member['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($member['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <h2 class="text-2xl font-bold text-slate-800 mb-6">📝 Resolution Notes</h2>

        <div class="mb-6">
            <label class="block text-sm font-600 text-slate-700 mb-2">Add Resolution or Action Taken</label>
            <textarea name="resolution_notes" class="input-field w-full" rows="5" placeholder="Describe what action has been taken or what the resolution is..."></textarea>
        </div>

        <div class="flex gap-4 pt-6 border-t border-slate-200">
            <button type="submit" class="btn-submit">✅ Update Complaint</button>
            <a href="list.php" class="px-6 py-3 bg-slate-400 text-white rounded-12px text-center font-600 hover:bg-slate-500 transition">← Back to List</a>
        </div>

    </form>

</div>

</body>
</html>