<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Daily Attendance Management Engine
 * ==========================================
 */

require_once '../../includes/auth_check.php';

// Only Admin and Teacher roles can manage attendance
if (!in_array($user_role, ['Admin', 'Teacher'])) {
    header('Location: ' . BASE_URL . 'index.php?error=Access denied');
    exit();
}

require '../../components/header.php';

$message = '';
$error = '';

// Default date is today if not selected
$attendance_date = $_GET['date'] ?? date('Y-m-d');

/* Handle Attendance Submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statuses = $_POST['status'] ?? [];
    $remarks = $_POST['remarks'] ?? [];
    
    $conn->begin_transaction();
    try {
        foreach ($statuses as $student_id => $status) {
            $remark = trim($remarks[$student_id] ?? '');
            
            // Check if record already exists for the student on this specific day
            $checkSql = "SELECT id FROM attendance WHERE student_id = ? AND attendance_date = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param('is', $student_id, $attendance_date);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows > 0) {
                // Update existing record
                $updateSql = "UPDATE attendance SET status = ?, remarks = ?, marked_by = ? WHERE student_id = ? AND attendance_date = ?";
                $stmt = $conn->prepare($updateSql);
                $stmt->bind_param('ssiis', $status, $remark, $user_id, $student_id, $attendance_date);
            } else {
                // Insert new clean row
                $insertSql = "INSERT INTO attendance (student_id, attendance_date, status, remarks, marked_by) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insertSql);
                $stmt->bind_param('isssi', $student_id, $attendance_date, $status, $remark, $user_id);
            }
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        $message = ' Attendance saved successfully for' . date('d M, Y', strtotime($attendance_date));
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Transaction Failed: ' . $e->getMessage();
    }
}

/* Fetch Active Students and check if they already have logged attendance for this day */
$studentsSql = "SELECT s.id, s.student_code, s.first_name, s.last_name, s.gender,
                       a.status as attendance_status, a.remarks as attendance_remarks
                FROM students s
                LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = ?
                WHERE s.status = 'Active'
                ORDER BY s.student_code ASC";

$stmt = $conn->prepare($studentsSql);
$stmt->bind_param('s', $attendance_date);
$stmt->execute();
$studentsResult = $stmt->get_result();
?>

<div class="p-4 lg:p-8 max-w-[1400px] mx-auto">
    <!-- Top University Banner Header Component -->
    <div class="glass mb-6 p-4 rounded-2xl flex flex-col md:flex-row justify-between items-center text-center md:text-left gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">AMR Comilla University</h2>
            <p class="text-xs font-semibold text-indigo-600 tracking-wider uppercase">Department of Computer Science and Engineering</p>
        </div>
        <div class="bg-indigo-50 px-4 py-2 rounded-xl text-center md:text-right">
            <p class="text-xs font-bold text-slate-700">Project: <span class="text-indigo-600">Boarding School Management System</span></p>
            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Student ID: 12108010 | Session: 2021-2022</p>
        </div>
    </div>

    <!-- Interface Header Area -->
    <div class="glass rounded-2xl p-6 mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('attendance', 'w-4 h-4'); ?> Roll Call & Attendance Sheets</h2>
            <p class="text-slate-600 mt-1">Manage and track secure daily student records</p>
        </div>
        <div>
            <form method="GET" class="flex items-center gap-2">
                <label class="text-sm font-semibold text-slate-700">Select Target Date:</label>
                <input type="date" name="date" class="input-field" value="<?= $attendance_date ?>" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    <!-- Alert Response Prompts -->
    <?php if ($message): ?>
        <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 p-4 rounded-xl mb-6 font-medium flex items-center gap-2"><span><?php echo icon('check', 'w-3.5 h-3.5'); ?></span> <?= $message ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-rose-100 border border-rose-300 text-rose-800 p-4 rounded-xl mb-6 font-medium flex items-center gap-2"><span><?php echo icon('x', 'w-3.5 h-3.5'); ?></span> <?= $error ?></div>
    <?php endif; ?>

    <!-- Master Data Submissions Sheet -->
    <form method="POST" class="glass rounded-2xl overflow-hidden p-6">
        <?= csrf_field() ?>
        <div class="overflow-x-auto mb-6">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Student Code</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th class="text-center"><span class="dot dot-green"></span> Present</th>
                        <th class="text-center"><span class="dot dot-red"></span> Absent</th>
                        <th class="text-center"><span class="dot dot-amber"></span> Late</th>
                        <th>Administrative Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($studentsResult->num_rows > 0): ?>
                        <?php while ($row = $studentsResult->fetch_assoc()): 
                            $status = $row['attendance_status'] ?? 'Present'; // Default checked option
                        ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="font-bold text-slate-900"><?= htmlspecialchars($row['student_code']) ?></td>
                                <td class="font-medium text-slate-700"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-600 font-medium"><?= $row['gender'] ?></span></td>
                                
                                <td class="text-center">
                                    <input type="radio" name="status[<?= $row['id'] ?>]" value="Present" class="radio-btn" <?= $status === 'Present' ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="status[<?= $row['id'] ?>]" value="Absent" class="radio-btn" <?= $status === 'Absent' ? 'checked' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="status[<?= $row['id'] ?>]" value="Late" class="radio-btn" <?= $status === 'Late' ? 'checked' : '' ?>>
                                </td>
                                
                                <td>
                                    <input type="text" name="remarks[<?= $row['id'] ?>]" class="input-field w-full text-xs" 
                                           placeholder="Sick, Leave, No notice..." value="<?= htmlspecialchars($row['attendance_remarks'] ?? '') ?>">
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-slate-400 py-8 font-medium">No active student registration files found inside data logs.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($studentsResult->num_rows > 0): ?>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:opacity-90 transition">
                    <?php echo icon('save', 'w-4 h-4'); ?> Save Attendance Log
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php 
$stmt->close();
require '../../components/footer.php'; 
?>
