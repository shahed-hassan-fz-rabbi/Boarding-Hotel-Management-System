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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Allocations - Hostel System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .glass:hover {
            box-shadow: 0 25px 75px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
        }

        .nav-link {
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-link:hover {
            background: rgba(102, 126, 234, 0.2);
            transform: translateX(4px);
        }

        .nav-link.active {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card {
            text-align: center;
            padding: 1.5rem;
            border-radius: 16px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .input-field {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #1e293b;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .input-field:focus {
            outline: none;
            background: white;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-transfer {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-transfer:hover {
            background: #2563eb;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .room-type-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            display: inline-block;
        }

        .room-type-boys {
            background: #dbeafe;
            color: #1e40af;
        }

        .room-type-girls {
            background: #fce7f3;
            color: #be185d;
        }

        .room-type-staff {
            background: #fef3c7;
            color: #b45309;
        }

        .allocation-card {
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .allocation-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .icon-svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .allocation-row {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .allocation-row:hover {
            background: #f8fafc;
        }

        .hidden {
            display: none !important;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .fade-in {
            animation: fadeInUp 0.4s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <aside class="sidebar w-80 hidden lg:flex flex-col">
            <div class="p-8 border-b border-slate-700">
                <h1 class="text-3xl font-bold text-white">🏫 Hostel</h1>
                <p class="text-slate-400 text-sm mt-2">Management System</p>
            </div>

            <nav class="flex-1 p-6 space-y-2">
                <a href="../index.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M3 12l9-9 9 9M5 10v11a1 1 0 001 1h3v-5a1 1 0 011-1h4a1 1 0 011 1v5h3a1 1 0 001-1V10"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="../students/list.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 4.354a4 4 0 110 8.008 4 4 0 010-8.008M3 20.333V19a6 6 0 0112 0v1.333"></path>
                    </svg>
                    <span>Students</span>
                </a>
                <a href="list.php" class="nav-link active px-4 py-3 text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M20 13H4a1 1 0 00-1 1v6a1 1 0 001 1h16a1 1 0 001-1v-6a1 1 0 00-1-1zM7 17a1 1 0 11-2 0 1 1 0 012 0zM13 17a1 1 0 11-2 0 1 1 0 012 0zM19 17a1 1 0 11-2 0 1 1 0 012 0z"></path>
                    </svg>
                    <span>Allocations</span>
                </a>
            </nav>

            <div class="p-6 border-t border-slate-700">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                        <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                    </div>
                    <div>
                        <p class="text-white font-600"><?php echo htmlspecialchars($user_name); ?></p>
                        <p class="text-slate-400 text-sm"><?php echo htmlspecialchars($user_role); ?></p>
                    </div>
                </div>
                <a href="../auth/logout.php" class="w-full py-2 px-4 bg-red-500/20 text-red-400 rounded-10px hover:bg-red-500/30 transition font-500 text-sm text-center block">
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            <div class="p-4 lg:p-8">

                <!-- Header -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row justify-between items-start lg:items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-800">📋 Room Allocations</h2>
                        <p class="text-slate-600 mt-2">Manage student bed assignments</p>
                    </div>
                    <button id="allocateBtn" class="btn-primary mt-4 lg:mt-0">
                        ➕ New Allocation
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
                        <h3 class="stat-number" style="color: #3b82f6;"><?php echo $boysBeds; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Girls</p>
                        <h3 class="stat-number" style="color: #ec4899;"><?php echo $girlsBeds; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Staff</p>
                        <h3 class="stat-number" style="color: #f59e0b;"><?php echo $staffBeds; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="🔍 Search by student name, code, or room..."
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
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: #cbd5e1;">
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
                        ✓ Allocate Bed
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
                <div class="text-6xl mb-4">⚠️</div>
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
                            🗑️ Remove
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
</body>
</html>