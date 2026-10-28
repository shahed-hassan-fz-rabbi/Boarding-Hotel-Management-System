<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Students List Page
 * ==========================================
 */

require_once '../includes/auth_check.php';

require '../components/header.php';
/* ============================
   Fetch All Students
============================ */

$sql = "SELECT 
            s.id, 
            s.student_code, 
            s.first_name, 
            s.last_name, 
            s.gender, 
            s.email, 
            s.phone, 
            s.admission_date, 
            s.status,
            s.photo,
            g.father_name,
            g.phone as guardian_phone
        FROM students s
        LEFT JOIN guardians g ON s.guardian_id = g.id
        ORDER BY s.student_code DESC";

$result = $conn->query($sql);

$students = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$totalStudents = count($students);
$activeStudents = count(array_filter($students, fn($s) => $s['status'] === 'Active'));
$inactiveStudents = $totalStudents - $activeStudents;

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
                        <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('staff', 'w-4 h-4'); ?> Students Management</h2>
                        <p class="text-slate-600 mt-2">Manage all student records and information</p>
                    </div>
                    <button id="addStudentBtn" class="btn-primary mt-4 lg:mt-0">
                        <?php echo icon('plus', 'w-4 h-4'); ?> Add New Student
                    </button>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Students</p>
                        <h3 class="stat-number"><?php echo $totalStudents; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Active Students</p>
                        <h3 class="stat-number" style="color: var(--success);"><?php echo $activeStudents; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Inactive Students</p>
                        <h3 class="stat-number" style="color: var(--error);"><?php echo $inactiveStudents; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by name, email, or code..."
                        class="input-field flex-1">

                    <select id="filterStatus" class="input-field">
                        <option value="">Filter by Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>

                    <select id="filterGender" class="input-field">
                        <option value="">Filter by Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <!-- Students Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-100 border-b border-slate-300">
                            <tr>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Student</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Code</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Email</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Phone</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Admission Date</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Status</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <!-- Students will be inserted by JavaScript -->
                        </tbody>
                    </table>

                    <div id="noResults" class="no-results hidden">
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: var(--border-strong);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h3 class="text-xl font-600 text-slate-600">No students found</h3>
                        <p class="text-slate-500 mt-2">Try adjusting your search or filters</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Add/Edit Student Modal -->
    <div id="studentModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-slate-800">Add New Student</h3>
                <button id="closeModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <form id="studentForm" method="POST" action="add.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
                <div id="formError" class="error-message hidden"></div>

                <!-- First Name & Last Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">First Name *</label>
                        <input type="text" name="first_name" class="input-field w-full" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name *</label>
                        <input type="text" name="last_name" class="input-field w-full" required>
                    </div>
                </div>

                <!-- Email & Phone -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="input-field w-full" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone *</label>
                        <input type="tel" name="phone" class="input-field w-full" required>
                    </div>
                </div>

                <!-- Gender & Date of Birth -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="input-field w-full" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth *</label>
                        <input type="date" name="dob" class="input-field w-full" required>
                    </div>
                </div>

                <!-- Blood Group & Address -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="input-field w-full">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Admission Date *</label>
                        <input type="date" name="admission_date" class="input-field w-full" required>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="input-field w-full" rows="2" placeholder="Enter student address"></textarea>
                </div>

                <!-- Student Code -->
                <div class="form-group">
                    <label class="form-label">Student Code</label>
                    <input type="text" name="student_code" class="input-field w-full" placeholder="Auto-generated if empty">
                </div>

                <!-- Photo -->
                <div class="form-group">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" class="input-field w-full" accept="image/*">
                </div>

                <!-- Guardian Info -->
                <div class="form-group">
                    <label class="form-label font-bold text-base">Guardian Information</label>
                </div>

                <!-- Father & Mother Name -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Father's Name</label>
                        <input type="text" name="father_name" class="input-field w-full">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" name="mother_name" class="input-field w-full">
                    </div>
                </div>

                <!-- Guardian Name & Phone -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Guardian Name</label>
                        <input type="text" name="guardian_name" class="input-field w-full">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Guardian Phone</label>
                        <input type="tel" name="guardian_phone" class="input-field w-full">
                    </div>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="input-field w-full">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        Save Student
                    </button>
                    <button type="button" id="cancelBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>

                <input type="hidden" name="student_id" id="studentId">
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="text-center">
                <div class="text-6xl mb-4"><?php echo icon('complaints', 'w-4 h-4'); ?></div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Delete Student?</h3>
                <p class="text-slate-600 mb-6">This action cannot be undone. All associated data will be deleted.</p>
                <div id="deleteStudentName" class="text-lg font-600 text-slate-700 mb-6"></div>
                <div class="flex gap-3">
                    <button id="confirmDeleteBtn" class="btn-danger flex-1">
                        Delete
                    </button>
                    <button id="cancelDeleteBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        const allStudents = <?php echo $studentsJSON; ?>;

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }
        let filteredStudents = [...allStudents];
        let deleteStudentId = null;

        // ============================
        // DOM Elements
        // ============================

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterGender = document.getElementById('filterGender');
        const studentTableBody = document.getElementById('studentTableBody');
        const noResults = document.getElementById('noResults');
        const addStudentBtn = document.getElementById('addStudentBtn');
        const studentModal = document.getElementById('studentModal');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const studentForm = document.getElementById('studentForm');
        const deleteModal = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        // ============================
        // Filter & Search
        // ============================

        function applyFilters() {
            let result = [...allStudents];

            // Search
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(student =>
                    student.first_name.toLowerCase().includes(searchTerm) ||
                    student.last_name.toLowerCase().includes(searchTerm) ||
                    student.email.toLowerCase().includes(searchTerm) ||
                    student.student_code.toLowerCase().includes(searchTerm)
                );
            }

            // Filter Status
            const status = filterStatus.value;
            if (status) {
                result = result.filter(student => student.status === status);
            }

            // Filter Gender
            const gender = filterGender.value;
            if (gender) {
                result = result.filter(student => student.gender === gender);
            }

            filteredStudents = result;
            renderStudents(result);
        }

        // ============================
        // Render Students Table
        // ============================

        function renderStudents(students) {
            studentTableBody.innerHTML = '';

            if (students.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            students.forEach((student, index) => {
                const statusClass = student.status === 'Active' 
                    ? 'status-active' 
                    : 'status-inactive';
                const statusText = student.status === 'Active' ? ' Active' : ' Inactive';

                const photoSrc = student.photo 
                    ? `uploads/students/${encodeURIComponent(student.photo)}` 
                    : `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect fill='%23667eea' width='100' height='100'/%3E%3Ctext x='50' y='60' font-size='50' fill='white' text-anchor='middle' font-family='Arial'%3E${encodeURIComponent(student.first_name?.charAt(0) + (student.last_name?.charAt(0) ?? ''))}%3C/text%3E%3C/svg%3E`;

                const row = document.createElement('tr');
                row.className = 'student-row fade-in';
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td class="px-6 py-4 flex items-center gap-3">
                        <img src="${photoSrc}" alt="Avatar" class="student-avatar" style="width: 40px; height: 40px; border-radius: 50%;">
                        <div>
                            <p class="font-600 text-slate-800">${esc(student.first_name)} ${esc(student.last_name)}</p>
                            <p class="text-sm text-slate-500">${esc(student.gender)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${esc(student.student_code || 'N/A')}</td>
                    <td class="px-6 py-4 text-slate-700">${esc(student.email)}</td>
                    <td class="px-6 py-4 text-slate-700">${esc(student.phone)}</td>
                    <td class="px-6 py-4 text-slate-700">${new Date(student.admission_date).toLocaleDateString()}</td>
                    <td class="px-6 py-4">
                        <span class="${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button class="btn-edit btn-sm" onclick="editStudent(${student.id})"><?php echo icon('edit', 'w-3.5 h-3.5'); ?> Edit</button>
                        <button class="btn-danger btn-sm" onclick="openDeleteModal(${student.id})"><?php echo icon('trash', 'w-3.5 h-3.5'); ?> Delete</button>
                    </td>
                `;
                studentTableBody.appendChild(row);
            });
        }

        // ============================
        // Modal Functions
        // ============================

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Student';
            document.getElementById('studentId').value = '';
            studentForm.action = 'add.php';
            studentForm.reset();
            document.getElementById('formError').classList.add('hidden');
            studentModal.classList.add('active');
        }

        function openEditModal(id) {
            document.getElementById('modalTitle').textContent = 'Edit Student';
            document.getElementById('studentId').value = id;
            studentForm.action = 'edit.php';
            studentModal.classList.add('active');
            // Form will be filled by editStudent function
        }

        function closeStudentModal() {
            studentModal.classList.remove('active');
            studentForm.reset();
            document.getElementById('formError').classList.add('hidden');
        }

        function openDeleteModal(id, name) {
            deleteStudentId = id;
            const s = allStudents.find(x => x.id === id);
            document.getElementById('deleteStudentName').textContent
                = (name ?? '') || (s ? `${s.first_name} ${s.last_name}` : 'this student');
            deleteModal.classList.add('active');
        }

        function closeDeleteModal() {
            deleteModal.classList.remove('active');
            deleteStudentId = null;
        }

        // ============================
        // Edit Student
        // ============================

        function editStudent(id) {
            const student = allStudents.find(s => s.id === id);
            if (!student) return;

            openEditModal(id);
            document.querySelector('input[name="first_name"]').value = student.first_name;
            document.querySelector('input[name="last_name"]').value = student.last_name;
            document.querySelector('input[name="email"]').value = student.email;
            document.querySelector('input[name="phone"]').value = student.phone;
            document.querySelector('select[name="gender"]').value = student.gender;
            document.querySelector('select[name="status"]').value = student.status;
        }

        // ============================
        // Delete Student
        // ============================

        confirmDeleteBtn.addEventListener('click', function() {
            if (!deleteStudentId) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete.php';
            form.innerHTML = `<input type="hidden" name="student_id" value="${deleteStudentId}">`;
            document.body.appendChild(form);
            form.submit();
        });

        // ============================
        // Event Listeners
        // ============================

        addStudentBtn.addEventListener('click', openAddModal);
        closeModal.addEventListener('click', closeStudentModal);
        cancelBtn.addEventListener('click', closeStudentModal);
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        searchInput.addEventListener('input', applyFilters);
        filterStatus.addEventListener('change', applyFilters);
        filterGender.addEventListener('change', applyFilters);

        // Close modal on background click
        studentModal.addEventListener('click', function(e) {
            if (e.target === studentModal) closeStudentModal();
        });

        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) closeDeleteModal();
        });

        // Initial render
        renderStudents(allStudents);
    </script>

<?php
require '../components/footer.php'; 
?>
