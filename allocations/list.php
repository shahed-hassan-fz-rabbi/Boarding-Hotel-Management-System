<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Room Allocations List Page
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';
/* ============================
   Fetch All Room Allocations
============================ */

$sql = "SELECT 
            ra.id as allocation_id,
            ra.student_id,
            ra.bed_id,
            ra.check_in,
            ra.check_out,
            ra.status as allocation_status,
            s.student_code,
            s.first_name,
            s.last_name,
            s.email,
            s.phone,
            s.gender,
            hr.id as room_id,
            hr.room_number,
            hr.room_type,
            rb.bed_number,
            rb.bed_type,
            rb.status as bed_status
        FROM room_allocations ra
        INNER JOIN students s ON ra.student_id = s.id
        INNER JOIN room_beds rb ON ra.bed_id = rb.id
        INNER JOIN hostel_rooms hr ON rb.room_id = hr.id
        WHERE ra.status = 'Active'
        ORDER BY hr.room_number, rb.bed_number ASC";

$result = $conn->query($sql);

$allocations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allocations[] = $row;
    }
}

/* Statistics */
$totalAllocations = count($allocations);

/* Count by room type */
$boysBeds = count(array_filter($allocations, fn($a) => $a['room_type'] === 'Boys'));
$girlsBeds = count(array_filter($allocations, fn($a) => $a['room_type'] === 'Girls'));
$staffBeds = count(array_filter($allocations, fn($a) => $a['room_type'] === 'Staff'));

/* Fetch available beds */
$availableSql = "SELECT 
                    hr.id as room_id,
                    hr.room_number,
                    hr.room_type,
                    rb.id as bed_id,
                    rb.bed_number,
                    rb.bed_type,
                    rb.status
                FROM room_beds rb
                INNER JOIN hostel_rooms hr ON rb.room_id = hr.id
                WHERE rb.status = 'Available'
                ORDER BY hr.room_number, rb.bed_number";

$availableResult = $conn->query($availableSql);
$availableBeds = [];
if ($availableResult && $availableResult->num_rows > 0) {
    while ($row = $availableResult->fetch_assoc()) {
        $availableBeds[] = $row;
    }
}

/* Fetch students not allocated */
$studentSql = "SELECT id, student_code, first_name, last_name, gender, email 
               FROM students 
               WHERE status = 'Active' 
               AND id NOT IN (
                   SELECT student_id FROM room_allocations WHERE status = 'Active'
               )
               ORDER BY first_name ASC";

$studentResult = $conn->query($studentSql);
$students = [];
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

$allocationsJSON = json_encode($allocations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$availableBedsJSON = json_encode($availableBeds, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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
                        <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('file', 'w-4 h-4'); ?> Room Allocations</h2>
                        <p class="text-slate-600 mt-2">Manage student bed assignments</p>
                    </div>
                    <button id="allocateBtn" class="btn-primary mt-4 lg:mt-0">
                        <?php echo icon('plus', 'w-4 h-4'); ?> New Allocation
                    </button>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Allocations</p>
                        <h3 class="stat-number"><?php echo $totalAllocations; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Boys</p>
                        <h3 class="stat-number" style="color: var(--terracotta);"><?php echo $boysBeds; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Girls</p>
                        <h3 class="stat-number" style="color: var(--coral);"><?php echo $girlsBeds; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Staff</p>
                        <h3 class="stat-number" style="color: var(--warning);"><?php echo $staffBeds; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by student name, code, or room..."
                        class="input-field flex-1">

                    <select id="filterRoomType" class="input-field">
                        <option value="">Filter by Room Type</option>
                        <option value="Boys">Boys</option>
                        <option value="Girls">Girls</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>

                <!-- Allocations Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-100 border-b border-slate-300">
                            <tr>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Student</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Room</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Bed</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Check-In</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Type</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="allocationTableBody">
                            <!-- Allocations will be inserted by JavaScript -->
                        </tbody>
                    </table>

                    <div id="noResults" class="no-results hidden">
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: var(--border-strong);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h3 class="text-xl font-600 text-slate-600">No allocations found</h3>
                        <p class="text-slate-500 mt-2">Try adjusting your search or filters</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Allocate Student Modal -->
    <div id="allocateModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-slate-800">New Room Allocation</h3>
                <button id="closeModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <form id="allocateForm" method="POST" action="allocate.php">
            <?= csrf_field() ?>
                <!-- Student Selection -->
                <div class="form-group">
                    <label class="form-label">Select Student *</label>
                    <select name="student_id" id="studentSelect" class="input-field w-full" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> 
                                (<?php echo htmlspecialchars($student['student_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Room Selection -->
                <div class="form-group">
                    <label class="form-label">Select Room *</label>
                    <select name="room_id" id="roomSelect" class="input-field w-full" required>
                        <option value="">-- Choose Room --</option>
                        <?php 
                            $uniqueRooms = [];
                            foreach ($availableBeds as $bed) {
                                $key = $bed['room_id'];
                                if (!isset($uniqueRooms[$key])) {
                                    $uniqueRooms[$key] = $bed;
                                }
                            }
                            foreach ($uniqueRooms as $room):
                        ?>
                            <option value="<?php echo $room['room_id']; ?>">
                                <?php echo htmlspecialchars($room['room_number']); ?> 
                                (<?php echo htmlspecialchars($room['room_type']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Bed Selection -->
                <div class="form-group">
                    <label class="form-label">Select Bed *</label>
                    <select name="bed_id" id="bedSelect" class="input-field w-full" required>
                        <option value="">-- Choose Bed --</option>
                    </select>
                </div>

                <!-- Check-In Date -->
                <div class="form-group">
                    <label class="form-label">Check-In Date *</label>
                    <input type="date" name="check_in" class="input-field w-full" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        <?php echo icon('check', 'w-3.5 h-3.5'); ?> Allocate Bed
                    </button>
                    <button type="button" id="cancelBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Deallocate Confirmation Modal -->
    <div id="deallocateModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="text-center">
                <div class="text-6xl mb-4"><?php echo icon('complaints', 'w-4 h-4'); ?></div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Remove Allocation?</h3>
                <p class="text-slate-600 mb-6">This will check out the student from the bed.</p>
                <div id="deallocateInfo" class="text-lg font-600 text-slate-700 mb-6"></div>
                <div class="flex gap-3">
                    <button id="confirmDeallocateBtn" class="btn-danger flex-1">
                        Yes, Remove
                    </button>
                    <button id="cancelDeallocateBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        const allAllocations = <?php echo $allocationsJSON; ?>;
        const availableBeds = <?php echo $availableBedsJSON; ?>;
        let filteredAllocations = [...allAllocations];
        let deallocateId = null;

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        // ============================
        // DOM Elements
        // ============================

        const searchInput = document.getElementById('searchInput');
        const filterRoomType = document.getElementById('filterRoomType');
        const allocationTableBody = document.getElementById('allocationTableBody');
        const noResults = document.getElementById('noResults');
        const allocateBtn = document.getElementById('allocateBtn');
        const allocateModal = document.getElementById('allocateModal');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const allocateForm = document.getElementById('allocateForm');
        const deallocateModal = document.getElementById('deallocateModal');
        const confirmDeallocateBtn = document.getElementById('confirmDeallocateBtn');
        const cancelDeallocateBtn = document.getElementById('cancelDeallocateBtn');
        const roomSelect = document.getElementById('roomSelect');
        const bedSelect = document.getElementById('bedSelect');

        // ============================
        // Filter & Search
        // ============================

        function applyFilters() {
            let result = [...allAllocations];

            // Search
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(alloc =>
                    alloc.first_name.toLowerCase().includes(searchTerm) ||
                    alloc.last_name.toLowerCase().includes(searchTerm) ||
                    alloc.student_code.toLowerCase().includes(searchTerm) ||
                    alloc.room_number.toLowerCase().includes(searchTerm)
                );
            }

            // Filter Room Type
            const roomType = filterRoomType.value;
            if (roomType) {
                result = result.filter(alloc => alloc.room_type === roomType);
            }

            filteredAllocations = result;
            renderAllocations(result);
        }

        // ============================
        // Render Allocations Table
        // ============================

        function renderAllocations(allocations) {
            allocationTableBody.innerHTML = '';

            if (allocations.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            allocations.forEach((alloc, index) => {
                const roomTypeBadgeClass = `room-type-${alloc.room_type?.toLowerCase()}`;
                const checkInDate = new Date(alloc.check_in).toLocaleDateString();
                const days = Math.floor((Date.now() - new Date(alloc.check_in).getTime()) / (1000 * 60 * 60 * 24));

                const row = document.createElement('tr');
                row.className = 'allocation-row fade-in';
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-600 text-slate-800">${esc(alloc.first_name)} ${esc(alloc.last_name)}</p>
                            <p class="text-sm text-slate-500">${esc(alloc.student_code)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${esc(alloc.room_number)}</td>
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-600 text-slate-800">${esc(alloc.bed_number)}</p>
                            <p class="text-sm text-slate-500">${esc(alloc.bed_type)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">
                        <div>
                            <p>${checkInDate}</p>
                            <p class="text-sm text-slate-500">${days} days ago</p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="room-type-badge ${roomTypeBadgeClass}">
                            ${esc(alloc.room_type)}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button class="btn-danger btn-sm" onclick="openDeallocateModal(${alloc.allocation_id})">
                            <?php echo icon('trash', 'w-3.5 h-3.5'); ?> Remove
                        </button>
                    </td>
                `;
                allocationTableBody.appendChild(row);
            });
        }

        // ============================
        // Modal Functions
        // ============================

        function openAllocateModal() {
            allocateModal.classList.add('active');
            updateBedOptions();
        }

        function closeAllocateModal() {
            allocateModal.classList.remove('active');
            allocateForm.reset();
        }

        function openDeallocateModal(id) {
            deallocateId = id;
            const a = allAllocations.find(x => x.allocation_id === id);
            const info = a ? `${a.first_name} ${a.last_name} from Room ${a.room_number}` : 'This allocation';
            document.getElementById('deallocateInfo').textContent = info;
            deallocateModal.classList.add('active');
        }

        function closeDeallocateModal() {
            deallocateModal.classList.remove('active');
            deallocateId = null;
        }

        // ============================
        // Update Bed Options Based on Room
        // ============================

        function updateBedOptions() {
            const roomId = roomSelect.value;
            bedSelect.innerHTML = '<option value="">-- Choose Bed --</option>';

            if (roomId) {
                const bedsForRoom = availableBeds.filter(bed => bed.room_id == roomId);
                bedsForRoom.forEach(bed => {
                    const option = document.createElement('option');
                    option.value = bed.bed_id;
                    option.textContent = `${bed.bed_number} (${bed.bed_type})`;
                    bedSelect.appendChild(option);
                });
            }
        }

        // ============================
        // Event Listeners
        // ============================

        allocateBtn.addEventListener('click', openAllocateModal);
        closeModal.addEventListener('click', closeAllocateModal);
        cancelBtn.addEventListener('click', closeAllocateModal);
        cancelDeallocateBtn.addEventListener('click', closeDeallocateModal);
        roomSelect.addEventListener('change', updateBedOptions);

        searchInput.addEventListener('input', applyFilters);
        filterRoomType.addEventListener('change', applyFilters);

        // Deallocate form submission
        confirmDeallocateBtn.addEventListener('click', function() {
            if (!deallocateId) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'deallocate.php';
            form.innerHTML = `<input type="hidden" name="allocation_id" value="${deallocateId}">`;
            document.body.appendChild(form);
            form.submit();
        });

        // Close modals on background click
        allocateModal.addEventListener('click', function(e) {
            if (e.target === allocateModal) closeAllocateModal();
        });

        deallocateModal.addEventListener('click', function(e) {
            if (e.target === deallocateModal) closeDeallocateModal();
        });

        // Initial render
        renderAllocations(allAllocations);
    </script>
<?php
require '../components/footer.php'; 
?>
