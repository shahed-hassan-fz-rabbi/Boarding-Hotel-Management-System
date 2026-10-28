<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Staff Directory & Management Console
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';

// Access Control: Admin has full access, others can only view
$can_modify = ($user_role === 'Admin');

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

/* Fetch Staff Records with Search Filter (full_name alias = first + last) */
$staffSql = "SELECT *, CONCAT(first_name, ' ', last_name) AS full_name FROM staff";
if (!empty($search_query)) {
    $staffSql .= " WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR staff_code LIKE ? OR designation LIKE ? OR department LIKE ?";
}
$staffSql .= " ORDER BY staff_code ASC";

$stmt = $conn->prepare($staffSql);

if (!empty($search_query)) {
    $like = "%{$search_query}%";
    $stmt->bind_param('ssss', $like, $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="p-4 lg:p-8 max-w-[1600px] mx-auto">
    
    <!-- Top University Banner Header Component -->
    <div class="glass mb-6 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800"><?php echo icon('building', 'w-4 h-4'); ?> Comilla University</h2>
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
            <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('staff', 'w-4 h-4'); ?> Staff & Human Resources</h2>
            <p class="text-slate-600 mt-1">Manage institutional human resource registries and profiles</p>
        </div>
        <?php if ($can_modify): ?>
        <div>
            <a href="add.php" class="px-5 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-md hover:opacity-90 transition block text-center">
                <?php echo icon('plus', 'w-4 h-4'); ?> Add New Staff
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Search Tool Panel -->
    <div class="glass rounded-2xl p-4 mb-6">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" class="input-field flex-1" placeholder="Search by name, staff code, designation, department..." value="<?= htmlspecialchars($search_query) ?>">
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
                        <th>Staff Code</th>
                        <th>Full Name</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Contact info</th>
                        <th>Joining Date</th>
                        <th>Status</th>
                        <?php if ($can_modify): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): 
                            $statusBadge = $row['status'] === 'Active' ? 'badge-active' : 'badge-inactive';
                        ?>
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="font-bold text-slate-900 font-mono"><?= htmlspecialchars($row['staff_code']) ?></td>
                                <td class="font-semibold text-slate-700"><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['designation']) ?></td>
                                <td><span class="text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-700 font-medium"><?= htmlspecialchars($row['department']) ?></span></td>
                                <td>
                                    <div class="text-sm"><?php echo icon('phone', 'w-4 h-4'); ?> <?= htmlspecialchars($row['phone']) ?></div>
                                    <div class="text-xs text-slate-400"><?= htmlspecialchars($row['email']) ?></div>
                                </td>
                                <td><?= date('d M, Y', strtotime($row['joining_date'])) ?></td>
                                <td><span class="badge <?= $statusBadge ?>"><?= $row['status'] ?></span></td>
                                <?php if ($can_modify): ?>
                                    <td>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="text-xs font-bold text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2 py-1 rounded">Edit</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= $can_modify ? 8 : 7 ?>" class="text-center text-slate-400 py-8 font-medium">No registered staff records found inside data logs.</td>
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
