<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * User Accounts & RBAC Access Directory
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';

// Access Control: Only Admin can manage system user accounts
if ($user_role !== 'Admin') {
    header('Location: ../index.php?error=Access denied');
    exit();
}

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

/* Fetch System Users with Search Filter */
$usersSql = "SELECT id, full_name, email, role, status, last_login, created_at FROM users";
if (!empty($search_query)) {
    $usersSql .= " WHERE full_name LIKE ? OR email LIKE ? OR role LIKE ?";
}
$usersSql .= " ORDER BY role ASC, full_name ASC";

$stmt = $conn->prepare($usersSql);

if (!empty($search_query)) {
    $like = "%{$search_query}%";
    $stmt->bind_param('sss', $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Boarding School</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15); }
        .input-field { border: 1px solid #e2e8f0; background: #f8fafc; padding: 0.65rem 1rem; border-radius: 12px; outline: none; transition: all 0.3s; color: #1e293b; }
        .input-field:focus { background: white; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { padding: 1rem; background: #f1f5f9; text-align: left; font-weight: 600; color: #1e293b; border-bottom: 2px solid #e2e8f0; }
        .data-table td { padding: 1rem; border-bottom: 1px solid #e2e8f0; color: #475569; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-teacher { background: #dbeafe; color: #1e40af; }
        .badge-supervisor { background: #fef3c7; color: #92400e; }
        .badge-accountant { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    
    <!-- Top University Banner Header Component -->
    <div class="glass mb-6 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">🏛️ Comilla University</h2>
            <p class="text-xs font-semibold text-indigo-600 tracking-wider uppercase">Department of Computer Science and Engineering</p>
        </div>
        <div class="bg-indigo-50 px-4 py-2 rounded-xl text-center md:text-right">
            <p class="text-xs font-bold text-slate-700">Project: <span class="text-indigo-600">Boarding School Management System</span></p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Student: Mahmuda Sultana (ID: 12108010)</p>
        </div>
    </div>

    <!-- Interface Header Area -->
    <div class="glass rounded-2xl p-6 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-slate-800">🔐 User Control Panel (RBAC)</h2>
            <p class="text-slate-600 mt-1">Provision login credentials and restrict role-based view parameters</p>
        </div>
        <div>
            <a href="add.php" class="px-5 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-md hover:opacity-90 transition block text-center">
                ➕ Provision New Account
            </a>
        </div>
    </div>

    <!-- Search Tool Panel -->
    <div class="glass rounded-2xl p-4 mb-6">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" class="input-field flex-1" placeholder="Search by name, email, role assignment..." value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="px-6 py-2 bg-slate-800 text-white rounded-xl font-semibold hover:bg-slate-700 transition">Search</button>
            <?php if (!empty($search_query)): ?>
                <a href="list.php" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-xl font-semibold hover:bg-slate-300 transition flex items-center">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Data Table Container -->
    <div class="glass rounded-2xl p-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Full Name</th>
                        <th>Email Routing Address</th>
                        <th>Role Assignment</th>
                        <th>Account Status</th>
                        <th>Last Login Session</th>
                        <th>Created On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            // Match badges dynamically to role
                            $roleBadge = 'badge-teacher';
                            if($row['role'] === 'Admin') $roleBadge = 'badge-admin';
                            elseif($row['role'] === 'Hostel Supervisor') $roleBadge = 'badge-supervisor';
                            elseif($row['role'] === 'Accountant') $roleBadge = 'badge-accountant';

                            $statusText = $row['status'] == 1 ? 'Active' : 'Suspended';
                            $statusClass = $row['status'] == 1 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
                        ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="font-bold text-slate-400 font-mono">#<?= $row['id'] ?></td>
                                <td class="font-semibold text-slate-800"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td class="font-medium text-slate-600"><?= htmlspecialchars($row['email']) ?></td>
                                <td><span class="badge <?= $roleBadge ?>"><?= $row['role'] ?></span></td>
                                <td><span class="text-xs font-bold px-2 py-1 rounded <?= $statusClass ?>"><?= $statusText ?></span></td>
                                <td class="text-xs font-mono text-slate-500"><?= $row['last_login'] ? date('d M, Y H:i', strtotime($row['last_login'])) : 'Never logged in' ?></td>
                                <td class="text-sm"><?= date('d M, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2 py-1 rounded">Configure</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-slate-400 py-8 font-medium">No registered system user logins found inside tracking matrices.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php 
$stmt->close();
require '../components/footer.php'; 
?>
</body>
</html>