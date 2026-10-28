<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Leave Applications List Page
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';
/* ============================
   Fetch All Leave Applications
============================ */

$sql = "SELECT 
            la.id,
            la.student_id,
            la.leave_type_id,
            la.from_date,
            la.to_date,
            la.number_of_days,
            la.reason,
            la.status,
            la.approved_by,
            la.approval_notes,
            la.applied_on,
            la.approved_on,
            s.student_code,
            s.first_name,
            s.last_name,
            s.email,
            s.phone,
            lt.leave_type_name,
            u.full_name as approved_by_name
        FROM leave_applications la
        INNER JOIN students s ON la.student_id = s.id
        INNER JOIN leave_types lt ON la.leave_type_id = lt.id
        LEFT JOIN users u ON la.approved_by = u.id
        ORDER BY la.applied_on DESC";

$result = $conn->query($sql);

$leaves = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $leaves[] = $row;
    }
}

/* Statistics */
$totalApplications = count($leaves);
$pendingApplications = count(array_filter($leaves, fn($l) => $l['status'] === 'Pending'));
$approvedApplications = count(array_filter($leaves, fn($l) => $l['status'] === 'Approved'));
$rejectedApplications = count(array_filter($leaves, fn($l) => $l['status'] === 'Rejected'));

/* Fetch all students */
$studentSql = "SELECT id, student_code, first_name, last_name FROM students WHERE status = 'Active' ORDER BY first_name ASC";
$studentResult = $conn->query($studentSql);
$students = [];
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

/* Fetch leave types */
$typeSql = "SELECT id, leave_type_name FROM leave_types ORDER BY leave_type_name ASC";
$typeResult = $conn->query($typeSql);
$leaveTypes = [];
if ($typeResult && $typeResult->num_rows > 0) {
    while ($row = $typeResult->fetch_assoc()) {
        $leaveTypes[] = $row;
    }
}

$leavesJSON = json_encode($leaves, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$studentsJSON = json_encode($students, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$leaveTypesJSON = json_encode($leaveTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>

<div class="max-w-[1400px] mx-auto">

        <!-- Sidebar -->
<!-- Main Content -->
        <div>
            <div class="space-y-6">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('calendar', 'w-4 h-4'); ?> Leave Management</h2>
                        <p class="text-slate-600 mt-2">Track and manage student leave applications</p>
                    </div>
                    <button id="applyLeaveBtn" class="btn-primary mt-4 lg:mt-0">
                        <?php echo icon('plus', 'w-4 h-4'); ?> Apply for Leave
                    </button>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Applications</p>
                        <h3 class="stat-number"><?php echo $totalApplications; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-number" style="color: var(--warning);"><?php echo $pendingApplications; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Approved</p>
                        <h3 class="stat-number" style="color: var(--success);"><?php echo $approvedApplications; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Rejected</p>
                        <h3 class="stat-number" style="color: var(--error);"><?php echo $rejectedApplications; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by student name or code..."
                        class="input-field flex-1">

                    <select id="filterStatus" class="input-field">
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Rejected">Rejected</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <select id="filterLeaveType" class="input-field">
                        <option value="">All Leave Types</option>
                        <?php foreach ($leaveTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['leave_type_name']); ?>">
                                <?php echo htmlspecialchars($type['leave_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Leaves Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-100 border-b border-slate-300">
                            <tr>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Student</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Leave Type</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">From - To</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Days</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Status</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Applied On</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="leaveTableBody">
                            <!-- Leaves will be inserted by JavaScript -->
                        </tbody>
                    </table>

                    <div id="noResults" class="no-results hidden">
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: var(--border-strong);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h3 class="text-xl font-600 text-slate-600">No leave applications found</h3>
                        <p class="text-slate-500 mt-2">Try adjusting your search or filters</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Apply for Leave Modal -->
    <div id="leaveModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800">Apply for Leave</h3>
                <button id="closeModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <form id="leaveForm" method="POST" action="apply.php">
            <?= csrf_field() ?>
                <div id="formError" class="hidden bg-red-100 text-red-700 p-3 rounded-lg mb-4"></div>

                <!-- Leave Type -->
                <div class="form-group">
                    <label class="form-label">Leave Type *</label>
                    <select name="leave_type_id" class="input-field w-full" required>
                        <option value="">-- Select Leave Type --</option>
                        <?php foreach ($leaveTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo htmlspecialchars($type['leave_type_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- From Date & To Date -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">From Date *</label>
                        <input type="date" name="from_date" class="input-field w-full" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">To Date *</label>
                        <input type="date" name="to_date" class="input-field w-full" required>
                    </div>
                </div>

                <!-- Number of Days (Auto-calculated) -->
                <div class="form-group">
                    <label class="form-label">Number of Days *</label>
                    <input type="number" name="number_of_days" id="numberOfDays" class="input-field w-full" readonly>
                    <small class="text-slate-500 mt-1">Auto-calculated from dates</small>
                </div>

                <!-- Reason -->
                <div class="form-group">
                    <label class="form-label">Reason for Leave *</label>
                    <textarea name="reason" class="input-field w-full" rows="4" required placeholder="Explain why you need this leave"></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        <?php echo icon('check', 'w-3.5 h-3.5'); ?> Submit Application
                    </button>
                    <button type="button" id="cancelBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800">Review Leave Application</h3>
                <button id="closeActionModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <div id="reviewContent">
                <!-- Leave details will be inserted by JavaScript -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        const allLeaves = <?php echo $leavesJSON; ?>;
        const leaveTypes = <?php echo $leaveTypesJSON; ?>;
        const userRole = '<?php echo $user_role; ?>';
        let filteredLeaves = [...allLeaves];

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        // ============================
        // DOM Elements
        // ============================

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterLeaveType = document.getElementById('filterLeaveType');
        const leaveTableBody = document.getElementById('leaveTableBody');
        const noResults = document.getElementById('noResults');
        const applyLeaveBtn = document.getElementById('applyLeaveBtn');
        const leaveModal = document.getElementById('leaveModal');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const leaveForm = document.getElementById('leaveForm');
        const numberOfDays = document.getElementById('numberOfDays');
        const actionModal = document.getElementById('actionModal');
        const closeActionModal = document.getElementById('closeActionModal');

        // ============================
        // Calculate Days Between Dates
        // ============================

        function calculateDays() {
            const fromDate = new Date(document.querySelector('input[name="from_date"]').value);
            const toDate = new Date(document.querySelector('input[name="to_date"]').value);

            if (fromDate && toDate && toDate >= fromDate) {
                const diffTime = Math.abs(toDate - fromDate);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                numberOfDays.value = diffDays;
            }
        }

        // ============================
        // Filter & Search
        // ============================

        function applyFilters() {
            let result = [...allLeaves];

            // Search
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(leave =>
                    leave.first_name.toLowerCase().includes(searchTerm) ||
                    leave.last_name.toLowerCase().includes(searchTerm) ||
                    leave.student_code.toLowerCase().includes(searchTerm)
                );
            }

            // Filter Status
            const status = filterStatus.value;
            if (status) {
                result = result.filter(leave => leave.status === status);
            }

            // Filter Leave Type
            const leaveType = filterLeaveType.value;
            if (leaveType) {
                result = result.filter(leave => leave.leave_type_name === leaveType);
            }

            filteredLeaves = result;
            renderLeaves(result);
        }

        // ============================
        // Render Leaves Table
        // ============================

        function renderLeaves(leaves) {
            leaveTableBody.innerHTML = '';

            if (leaves.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            leaves.forEach((leave, index) => {
                const statusClass =
                    leave.status === 'Pending' ? 'status-pending' :
                    leave.status === 'Approved' ? 'status-approved' :
                    leave.status === 'Rejected' ? 'status-rejected' :
                    'status-cancelled';

                const appliedDate = new Date(leave.applied_on).toLocaleDateString();
                const fromDate = new Date(leave.from_date).toLocaleDateString();
                const toDate = new Date(leave.to_date).toLocaleDateString();

                const row = document.createElement('tr');
                row.className = 'leave-row fade-in';
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-600 text-slate-800">${esc(leave.first_name)} ${esc(leave.last_name)}</p>
                            <p class="text-sm text-slate-500">${esc(leave.student_code)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${esc(leave.leave_type_name)}</td>
                    <td class="px-6 py-4 text-slate-700">
                        <div>
                            <p>${fromDate}</p>
                            <p class="text-sm text-slate-500">to ${toDate}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center font-600 text-slate-800">${leave.number_of_days}</td>
                    <td class="px-6 py-4">
                        <span class="${statusClass}">${esc(leave.status)}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${appliedDate}</td>
                    <td class="px-6 py-4 text-center">
                        <button class="btn-primary btn-sm" onclick="viewLeave(${leave.id})">
                            <?php echo icon('eye', 'w-3.5 h-3.5'); ?> View
                        </button>
                    </td>
                `;
                leaveTableBody.appendChild(row);
            });
        }

        // ============================
        // View Leave Details
        // ============================

        function viewLeave(id) {
            const leave = allLeaves.find(l => l.id === id);
            if (!leave) return;

            const fromDate = new Date(leave.from_date).toLocaleDateString();
            const toDate = new Date(leave.to_date).toLocaleDateString();
            const appliedDate = new Date(leave.applied_on).toLocaleDateString();
            const approvedDate = leave.approved_on ? new Date(leave.approved_on).toLocaleDateString() : 'N/A';

            const statusClass =
                leave.status === 'Pending' ? 'status-pending' :
                leave.status === 'Approved' ? 'status-approved' :
                leave.status === 'Rejected' ? 'status-rejected' :
                'status-cancelled';

            let reviewHTML = `
                <div class="mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800">${esc(leave.first_name)} ${esc(leave.last_name)}</h2>
                            <p class="text-slate-600 mt-1">${esc(leave.student_code)}</p>
                        </div>
                        <span class="${statusClass} text-lg">${esc(leave.status)}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Leave Type</p>
                            <p class="font-600 text-slate-800">${esc(leave.leave_type_name)}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Number of Days</p>
                            <p class="font-600 text-slate-800">${leave.number_of_days} Days</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">From Date</p>
                            <p class="font-600 text-slate-800">${fromDate}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">To Date</p>
                            <p class="font-600 text-slate-800">${toDate}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-600 text-slate-800 mb-2">Reason</h3>
                        <p class="text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-lg">${esc(leave.reason)}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Applied On</p>
                            <p class="font-600 text-slate-800">${appliedDate}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Approved By</p>
                            <p class="font-600 text-slate-800">${esc(leave.approved_by_name || 'Pending')}</p>
                        </div>
                    </div>
            `;

            if (leave.approval_notes) {
                reviewHTML += `
                    <div class="mb-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <h3 class="font-600 text-blue-800 mb-2">Approval Notes</h3>
                        <p class="text-blue-700">${esc(leave.approval_notes)}</p>
                    </div>
                `;
            }

            // Add action buttons for admin/hostel supervisor and pending leaves
            if ((userRole === 'Admin' || userRole === 'Hostel Supervisor') && leave.status === 'Pending') {
                reviewHTML += `
                    <div class="border-t pt-6">
                        <h3 class="font-600 text-slate-800 mb-4">Action</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Approval Notes</label>
                            <textarea id="approvalNotes" class="input-field w-full" rows="3" placeholder="Add any notes for approval/rejection"></textarea>
                        </div>

                        <div class="flex gap-3">
                            <button class="btn-approve flex-1 btn-sm" onclick="approveLeavev(${leave.id})">
                                <?php echo icon('check', 'w-3.5 h-3.5'); ?> Approve
                            </button>
                            <button class="btn-reject flex-1 btn-sm" onclick="rejectLeave(${leave.id})">
                                <?php echo icon('x', 'w-3.5 h-3.5'); ?> Reject
                            </button>
                        </div>
                    </div>
                `;
            }

            document.getElementById('reviewContent').innerHTML = reviewHTML;
            actionModal.classList.add('active');
        }

        // ============================
        // Approve/Reject Functions
        // ============================

        function approveLeavev(id) {
            const approvalNotes = document.getElementById('approvalNotes').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'approve.php';
            form.innerHTML = `
                <input type="hidden" name="leave_id" value="${id}">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="approval_notes" value="${esc(approvalNotes)}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        function rejectLeave(id) {
            const approvalNotes = document.getElementById('approvalNotes').value;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'approve.php';
            form.innerHTML = `
                <input type="hidden" name="leave_id" value="${id}">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="approval_notes" value="${esc(approvalNotes)}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // ============================
        // Modal Functions
        // ============================

        function openLeaveModal() {
            leaveModal.classList.add('active');
            document.getElementById('formError').classList.add('hidden');
        }

        function closeLeaveModal() {
            leaveModal.classList.remove('active');
            leaveForm.reset();
            document.getElementById('formError').classList.add('hidden');
        }

        // ============================
        // Event Listeners
        // ============================

        applyLeaveBtn.addEventListener('click', openLeaveModal);
        closeModal.addEventListener('click', closeLeaveModal);
        closeActionModal.addEventListener('click', () => actionModal.classList.remove('active'));
        cancelBtn.addEventListener('click', closeLeaveModal);

        document.querySelector('input[name="from_date"]').addEventListener('change', calculateDays);
        document.querySelector('input[name="to_date"]').addEventListener('change', calculateDays);

        searchInput.addEventListener('input', applyFilters);
        filterStatus.addEventListener('change', applyFilters);
        filterLeaveType.addEventListener('change', applyFilters);

        // Close modals on background click
        leaveModal.addEventListener('click', function(e) {
            if (e.target === leaveModal) closeLeaveModal();
        });

        actionModal.addEventListener('click', function(e) {
            if (e.target === actionModal) actionModal.classList.remove('active');
        });

        // Initial render
        renderLeaves(allLeaves);
    </script>
<?php
require '../components/footer.php'; 
?>
