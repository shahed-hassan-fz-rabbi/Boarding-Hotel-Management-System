<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Complaints List Page
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';
/* ============================
   Fetch All Complaints
============================ */

$sql = "SELECT 
            c.id,
            c.student_id,
            c.complaint_title,
            c.complaint_description,
            c.category,
            c.priority,
            c.status,
            c.assigned_to,
            c.resolution_notes,
            c.created_at,
            c.resolved_at,
            s.student_code,
            s.first_name as student_first_name,
            s.last_name as student_last_name,
            s.email as student_email,
            s.phone as student_phone,
            u.full_name as assigned_staff_name,
            hr.room_number
        FROM complaints c
        INNER JOIN students s ON c.student_id = s.id
        LEFT JOIN users u ON c.assigned_to = u.id
        LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'Active'
        LEFT JOIN room_beds rb ON ra.bed_id = rb.id
        LEFT JOIN hostel_rooms hr ON rb.room_id = hr.id
        ORDER BY 
            CASE 
                WHEN c.priority = 'High' THEN 1
                WHEN c.priority = 'Medium' THEN 2
                WHEN c.priority = 'Low' THEN 3
            END ASC,
            c.created_at DESC";

$result = $conn->query($sql);

$complaints = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $complaints[] = $row;
    }
}

/* Statistics */
$totalComplaints = count($complaints);
$openComplaints = count(array_filter($complaints, fn($c) => $c['status'] === 'Open'));
$inProgressComplaints = count(array_filter($complaints, fn($c) => $c['status'] === 'In Progress'));
$resolvedComplaints = count(array_filter($complaints, fn($c) => $c['status'] === 'Resolved'));
$rejectedComplaints = count(array_filter($complaints, fn($c) => $c['status'] === 'Rejected'));

/* Fetch all staff for assignment */
$staffSql = "SELECT id, full_name FROM users WHERE role IN ('Admin', 'Hostel Supervisor') AND status = 1 ORDER BY full_name ASC";
$staffResult = $conn->query($staffSql);
$staff = [];
if ($staffResult && $staffResult->num_rows > 0) {
    while ($row = $staffResult->fetch_assoc()) {
        $staff[] = $row;
    }
}

/* Fetch all students */
$studentSql = "SELECT id, student_code, first_name, last_name FROM students WHERE status = 'Active' ORDER BY first_name ASC";
$studentResult = $conn->query($studentSql);
$students = [];
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

$complaintsJSON = json_encode($complaints, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$staffJSON = json_encode($staff, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$studentsJSON = json_encode($students, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>

<div class="max-w-[1400px] mx-auto">

        <!-- Sidebar -->
<!-- Main Content -->
        <div>
            <div class="space-y-6">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('alert', 'w-4 h-4'); ?> Complaints Management</h2>
                        <p class="text-slate-600 mt-2">Track and resolve student complaints</p>
                    </div>
                    <button id="fileComplaintBtn" class="btn-primary mt-4 lg:mt-0">
                        <?php echo icon('plus', 'w-4 h-4'); ?> File Complaint
                    </button>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6 mb-8">
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Complaints</p>
                        <h3 class="stat-number"><?php echo $totalComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Open</p>
                        <h3 class="stat-number" style="color: var(--terracotta);"><?php echo $openComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">In Progress</p>
                        <h3 class="stat-number" style="color: var(--warning);"><?php echo $inProgressComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Resolved</p>
                        <h3 class="stat-number" style="color: var(--success);"><?php echo $resolvedComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Rejected</p>
                        <h3 class="stat-number" style="color: var(--coral);"><?php echo $rejectedComplaints; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by student name or complaint title..."
                        class="input-field flex-1">

                    <select id="filterStatus" class="input-field">
                        <option value="">All Status</option>
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Rejected">Rejected</option>
                    </select>

                    <select id="filterPriority" class="input-field">
                        <option value="">All Priority</option>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>
                </div>

                <!-- Complaints List -->
                <div id="complaintsContainer">
                    <!-- Complaints cards will be inserted by JavaScript -->
                </div>

                <div id="noResults" class="glass rounded-2xl no-results hidden">
                    <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: var(--border-strong);">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <h3 class="text-xl font-600 text-slate-600">No complaints found</h3>
                    <p class="text-slate-500 mt-2">Try adjusting your search or filters</p>
                </div>

            </div>
        </div>

    </div>

    <!-- File Complaint Modal -->
    <div id="complaintModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-slate-800">File New Complaint</h3>
                <button id="closeModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <form id="complaintForm" method="POST" action="add.php">
            <?= csrf_field() ?>
                <div id="formError" class="hidden bg-red-100 text-red-700 p-3 rounded-lg mb-4"></div>

                <!-- Student Selection -->
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" id="studentSelect" class="input-field w-full" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                                (<?php echo htmlspecialchars($student['student_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Complaint Title -->
                <div class="form-group">
                    <label class="form-label">Complaint Title *</label>
                    <input type="text" name="complaint_title" class="input-field w-full" required placeholder="Brief title of the complaint">
                </div>

                <!-- Category & Priority -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="category" class="input-field w-full" required>
                            <option value="">Select Category</option>
                            <option value="Cleanliness">Cleanliness</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Food Quality">Food Quality</option>
                            <option value="Noise">Noise</option>
                            <option value="Safety">Safety</option>
                            <option value="Behavior">Behavior</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority *</label>
                        <select name="priority" class="input-field w-full" required>
                            <option value="">Select Priority</option>
                            <option value="High">High</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Low">Low</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div class="form-group">
                    <label class="form-label">Description *</label>
                    <textarea name="complaint_description" class="input-field w-full" rows="4" required placeholder="Detailed description of the complaint"></textarea>
                </div>

                <!-- Status (for admin) -->
                <?php if ($user_role === 'Admin' || $user_role === 'Hostel Supervisor'): ?>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="input-field w-full">
                        <option value="Open">Open</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <!-- Assign To -->
                <div class="form-group">
                    <label class="form-label">Assign To</label>
                    <select name="assigned_to" class="input-field w-full">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        <?php echo icon('check', 'w-3.5 h-3.5'); ?> File Complaint
                    </button>
                    <button type="button" id="cancelBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>

                <input type="hidden" name="complaint_id" id="complaintId">
            </form>
        </div>
    </div>

    <!-- View/Edit Complaint Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800">Complaint Details</h3>
                <button id="closeDetailModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <div id="detailContent">
                <!-- Complaint details will be inserted by JavaScript -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        const allComplaints = <?php echo $complaintsJSON; ?>;
        const staff = <?php echo $staffJSON; ?>;
        let filteredComplaints = [...allComplaints];

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        // ============================
        // DOM Elements
        // ============================

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterPriority = document.getElementById('filterPriority');
        const complaintsContainer = document.getElementById('complaintsContainer');
        const noResults = document.getElementById('noResults');
        const fileComplaintBtn = document.getElementById('fileComplaintBtn');
        const complaintModal = document.getElementById('complaintModal');
        const detailModal = document.getElementById('detailModal');
        const closeModal = document.getElementById('closeModal');
        const closeDetailModal = document.getElementById('closeDetailModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const complaintForm = document.getElementById('complaintForm');

        // ============================
        // Filter & Search
        // ============================

        function applyFilters() {
            let result = [...allComplaints];

            // Search
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(complaint =>
                    complaint.student_first_name.toLowerCase().includes(searchTerm) ||
                    complaint.student_last_name.toLowerCase().includes(searchTerm) ||
                    complaint.complaint_title.toLowerCase().includes(searchTerm) ||
                    complaint.student_code.toLowerCase().includes(searchTerm)
                );
            }

            // Filter Status
            const status = filterStatus.value;
            if (status) {
                result = result.filter(complaint => complaint.status === status);
            }

            // Filter Priority
            const priority = filterPriority.value;
            if (priority) {
                result = result.filter(complaint => complaint.priority === priority);
            }

            filteredComplaints = result;
            renderComplaints(result);
        }

        // ============================
        // Render Complaints Cards
        // ============================

        function renderComplaints(complaints) {
            complaintsContainer.innerHTML = '';

            if (complaints.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            complaints.forEach((complaint, index) => {
                const statusClass = 
                    complaint.status === 'Open' ? 'status-open' :
                    complaint.status === 'In Progress' ? 'status-inprogress' :
                    complaint.status === 'Resolved' ? 'status-resolved' :
                    'status-rejected';

                const priorityClass =
                    complaint.priority === 'High' ? 'priority-high' :
                    complaint.priority === 'Medium' ? 'priority-medium' :
                    'priority-low';

                const priorityText = 
                    complaint.priority === 'High' ? 'High' :
                    complaint.priority === 'Medium' ? 'Medium' :
                    'Low';

                const createdDate = new Date(complaint.created_at).toLocaleDateString();
                const resolvedDate = complaint.resolved_at ? new Date(complaint.resolved_at).toLocaleDateString() : 'Pending';

                const card = document.createElement('div');
                card.className = 'glass complaint-card fade-in';
                card.style.animationDelay = `${index * 0.05}s`;
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-600 text-slate-800">${esc(complaint.complaint_title)}</h3>
                            <p class="text-sm text-slate-600 mt-1">By: ${esc(complaint.student_first_name)} ${esc(complaint.student_last_name)} (${esc(complaint.student_code)})</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="${priorityClass}">${priorityText}</span>
                            <span class="${statusClass}">${esc(complaint.status)}</span>
                        </div>
                    </div>

                    <p class="text-slate-600 mb-3">${esc(complaint.complaint_description?.substring(0, 150))}...</p>

                    <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                        <div>
                            <p class="text-slate-600">Category</p>
                            <p class="font-600 text-slate-800">${esc(complaint.category)}</p>
                        </div>
                        <div>
                            <p class="text-slate-600">Assigned To</p>
                            <p class="font-600 text-slate-800">${esc(complaint.assigned_staff_name || 'Unassigned')}</p>
                        </div>
                        <div>
                            <p class="text-slate-600">Filed On</p>
                            <p class="font-600 text-slate-800">${createdDate}</p>
                        </div>
                        <div>
                            <p class="text-slate-600">Resolved On</p>
                            <p class="font-600 text-slate-800">${resolvedDate}</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button class="btn-view btn-sm flex-1" onclick="viewComplaint(${complaint.id})">
                            <?php echo icon('eye', 'w-3.5 h-3.5'); ?> View Details
                        </button>
                    </div>
                `;
                complaintsContainer.appendChild(card);
            });
        }

        // ============================
        // View Complaint
        // ============================

        function viewComplaint(id) {
            const complaint = allComplaints.find(c => c.id === id);
            if (!complaint) return;

            const createdDate = new Date(complaint.created_at).toLocaleDateString();
            const resolvedDate = complaint.resolved_at ? new Date(complaint.resolved_at).toLocaleDateString() : 'Pending';

            const priorityClass =
                complaint.priority === 'High' ? 'priority-high' :
                complaint.priority === 'Medium' ? 'priority-medium' :
                'priority-low';

            const statusClass = 
                complaint.status === 'Open' ? 'status-open' :
                complaint.status === 'In Progress' ? 'status-inprogress' :
                complaint.status === 'Resolved' ? 'status-resolved' :
                'status-rejected';

            let detailHTML = `
                <div class="mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800">${esc(complaint.complaint_title)}</h2>
                            <p class="text-slate-600 mt-1">Complaint #${complaint.id}</p>
                        </div>
                        <span class="${statusClass} text-lg">${esc(complaint.status)}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Student</p>
                            <p class="font-600 text-slate-800">${esc(complaint.student_first_name)} ${esc(complaint.student_last_name)}</p>
                            <p class="text-sm text-slate-600">${esc(complaint.student_code)}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Room</p>
                            <p class="font-600 text-slate-800">${esc(complaint.room_number || 'N/A')}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Category</p>
                            <p class="font-600 text-slate-800">${esc(complaint.category)}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Priority</p>
                            <span class="${priorityClass}">${esc(complaint.priority)}</span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-600 text-slate-800 mb-2">Description</h3>
                        <p class="text-slate-600 leading-relaxed">${esc(complaint.complaint_description)}</p>
                    </div>

                    ${complaint.resolution_notes ? `
                    <div class="mb-6 bg-green-50 p-4 rounded-lg border border-green-200">
                        <h3 class="font-600 text-green-800 mb-2">Resolution Notes</h3>
                        <p class="text-green-700">${esc(complaint.resolution_notes)}</p>
                    </div>
                    ` : ''}

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Filed On</p>
                            <p class="font-600 text-slate-800">${createdDate}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Assigned To</p>
                            <p class="font-600 text-slate-800">${esc(complaint.assigned_staff_name || 'Unassigned')}</p>
                        </div>
                    </div>
            `;

            // Add update form for admin/hostel supervisor
            if ('<?php echo $user_role; ?>' === 'Admin' || '<?php echo $user_role; ?>' === 'Hostel Supervisor') {
                detailHTML += `
                    <form method="POST" action="update.php" class="border-t pt-6">
                        <?= csrf_field() ?>
                        <h3 class="font-600 text-slate-800 mb-4">Update Status</h3>
                        
                        <div class="mb-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="input-field w-full">
                                <option value="Open" ${complaint.status === 'Open' ? 'selected' : ''}>Open</option>
                                <option value="In Progress" ${complaint.status === 'In Progress' ? 'selected' : ''}>In Progress</option>
                                <option value="Resolved" ${complaint.status === 'Resolved' ? 'selected' : ''}>Resolved</option>
                                <option value="Rejected" ${complaint.status === 'Rejected' ? 'selected' : ''}>Rejected</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="input-field w-full">
                                <option value="">-- Unassigned --</option>
                                ${staff.map(s => `<option value="${s.id}" ${complaint.assigned_to === s.id ? 'selected' : ''}>${esc(s.full_name)}</option>`).join('')}
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Resolution Notes</label>
                            <textarea name="resolution_notes" class="input-field w-full" rows="3">${esc(complaint.resolution_notes || '')}</textarea>
                        </div>

                        <input type="hidden" name="complaint_id" value="${complaint.id}">
                        <button type="submit" class="btn-primary w-full">
                            <?php echo icon('save', 'w-4 h-4'); ?> Update Complaint
                        </button>
                    </form>
                `;
            }

            document.getElementById('detailContent').innerHTML = detailHTML;
            detailModal.classList.add('active');
        }

        // ============================
        // Modal Functions
        // ============================

        function openComplaintModal() {
            document.getElementById('modalTitle').textContent = 'File New Complaint';
            document.getElementById('complaintId').value = '';
            complaintForm.action = 'add.php';
            complaintForm.reset();
            document.getElementById('formError').classList.add('hidden');
            complaintModal.classList.add('active');
        }

        function closeComplaintModal() {
            complaintModal.classList.remove('active');
            complaintForm.reset();
            document.getElementById('formError').classList.add('hidden');
        }

        function closeComplaintDetailModal() {
            detailModal.classList.remove('active');
        }

        // ============================
        // Event Listeners
        // ============================

        fileComplaintBtn.addEventListener('click', openComplaintModal);
        closeModal.addEventListener('click', closeComplaintModal);
        closeDetailModal.addEventListener('click', closeComplaintDetailModal);
        cancelBtn.addEventListener('click', closeComplaintModal);

        searchInput.addEventListener('input', applyFilters);
        filterStatus.addEventListener('change', applyFilters);
        filterPriority.addEventListener('change', applyFilters);

        // Close modals on background click
        complaintModal.addEventListener('click', function(e) {
            if (e.target === complaintModal) closeComplaintModal();
        });

        detailModal.addEventListener('click', function(e) {
            if (e.target === detailModal) closeComplaintDetailModal();
        });

        // Initial render
        renderComplaints(allComplaints);
    </script>
<?php
require '../components/footer.php'; 
?>
