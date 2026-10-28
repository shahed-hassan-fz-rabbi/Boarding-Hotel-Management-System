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
$staffSql = "SELECT id, full_name FROM users WHERE role IN ('Warden', 'Staff') AND status = 1 ORDER BY full_name ASC";
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

$complaintsJSON = json_encode($complaints);
$staffJSON = json_encode($staff);
$studentsJSON = json_encode($students);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Management - Hostel System</title>
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

        .btn-view {
            background: #10b981;
            color: white;
            border: none;
        }

        .btn-view:hover {
            background: #059669;
        }

        .priority-high {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .priority-medium {
            background: #fef3c7;
            color: #92400e;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .priority-low {
            background: #d1fae5;
            color: #065f46;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-open {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-inprogress {
            background: #fef3c7;
            color: #92400e;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-resolved {
            background: #dcfce7;
            color: #166534;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-rejected {
            background: #f3e8ff;
            color: #6b21a8;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
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
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            max-width: 700px;
            width: 90%;
            margin: auto;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .icon-svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .complaint-card {
            border-left: 4px solid #667eea;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .complaint-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
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

        @media (max-width: 1024px) {
            .form-row {
                grid-template-columns: 1fr;
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

            <nav class="flex-1 p-6 space-y-2 overflow-y-auto">
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
                <a href="../allocations/list.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M20 13H4a1 1 0 00-1 1v6a1 1 0 001 1h16a1 1 0 001-1v-6a1 1 0 00-1-1z"></path>
                    </svg>
                    <span>Allocations</span>
                </a>
                <a href="../payments/list.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"></path>
                    </svg>
                    <span>Payments</span>
                </a>
                <a href="../meals/list.php" class="nav-link px-4 py-3 text-slate-400 hover:text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2z"></path>
                    </svg>
                    <span>Meals</span>
                </a>
                <a href="list.php" class="nav-link active px-4 py-3 text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2z"></path>
                    </svg>
                    <span>Complaints</span>
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
                        <h2 class="text-3xl font-bold text-slate-800">🚨 Complaints Management</h2>
                        <p class="text-slate-600 mt-2">Track and resolve student complaints</p>
                    </div>
                    <button id="fileComplaintBtn" class="btn-primary mt-4 lg:mt-0">
                        ➕ File Complaint
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
                        <h3 class="stat-number" style="color: #3b82f6;"><?php echo $openComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">In Progress</p>
                        <h3 class="stat-number" style="color: #f59e0b;"><?php echo $inProgressComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Resolved</p>
                        <h3 class="stat-number" style="color: #10b981;"><?php echo $resolvedComplaints; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Rejected</p>
                        <h3 class="stat-number" style="color: #8b5cf6;"><?php echo $rejectedComplaints; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="🔍 Search by student name or complaint title..."
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
                    <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: #cbd5e1;">
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
                <?php if ($user_role === 'Admin' || $user_role === 'Warden'): ?>
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
                        ✓ File Complaint
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
                    complaint.priority === 'High' ? '🔴 High' :
                    complaint.priority === 'Medium' ? '🟡 Medium' :
                    '🟢 Low';

                const createdDate = new Date(complaint.created_at).toLocaleDateString();
                const resolvedDate = complaint.resolved_at ? new Date(complaint.resolved_at).toLocaleDateString() : 'Pending';

                const card = document.createElement('div');
                card.className = 'glass complaint-card fade-in';
                card.style.animationDelay = `${index * 0.05}s`;
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-600 text-slate-800">${complaint.complaint_title}</h3>
                            <p class="text-sm text-slate-600 mt-1">By: ${complaint.student_first_name} ${complaint.student_last_name} (${complaint.student_code})</p>
                        </div>
                        <div class="flex gap-2">
                            <span class="${priorityClass}">${priorityText}</span>
                            <span class="${statusClass}">${complaint.status}</span>
                        </div>
                    </div>

                    <p class="text-slate-600 mb-3">${complaint.complaint_description.substring(0, 150)}...</p>

                    <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                        <div>
                            <p class="text-slate-600">Category</p>
                            <p class="font-600 text-slate-800">${complaint.category}</p>
                        </div>
                        <div>
                            <p class="text-slate-600">Assigned To</p>
                            <p class="font-600 text-slate-800">${complaint.assigned_staff_name || 'Unassigned'}</p>
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
                            👁️ View Details
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
                            <h2 class="text-2xl font-bold text-slate-800">${complaint.complaint_title}</h2>
                            <p class="text-slate-600 mt-1">Complaint #${complaint.id}</p>
                        </div>
                        <span class="${statusClass} text-lg">${complaint.status}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Student</p>
                            <p class="font-600 text-slate-800">${complaint.student_first_name} ${complaint.student_last_name}</p>
                            <p class="text-sm text-slate-600">${complaint.student_code}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Room</p>
                            <p class="font-600 text-slate-800">${complaint.room_number || 'N/A'}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Category</p>
                            <p class="font-600 text-slate-800">${complaint.category}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Priority</p>
                            <span class="${priorityClass}">${complaint.priority}</span>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-600 text-slate-800 mb-2">Description</h3>
                        <p class="text-slate-600 leading-relaxed">${complaint.complaint_description}</p>
                    </div>

                    ${complaint.resolution_notes ? `
                    <div class="mb-6 bg-green-50 p-4 rounded-lg border border-green-200">
                        <h3 class="font-600 text-green-800 mb-2">Resolution Notes</h3>
                        <p class="text-green-700">${complaint.resolution_notes}</p>
                    </div>
                    ` : ''}

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Filed On</p>
                            <p class="font-600 text-slate-800">${createdDate}</p>
                        </div>
                        <div class="bg-slate-100 p-4 rounded-lg">
                            <p class="text-slate-600 text-sm">Assigned To</p>
                            <p class="font-600 text-slate-800">${complaint.assigned_staff_name || 'Unassigned'}</p>
                        </div>
                    </div>
            `;

            // Add update form for admin/warden
            if ('<?php echo $user_role; ?>' === 'Admin' || '<?php echo $user_role; ?>' === 'Warden') {
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
                                ${staff.map(s => `<option value="${s.id}" ${complaint.assigned_to === s.id ? 'selected' : ''}>${s.full_name}</option>`).join('')}
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Resolution Notes</label>
                            <textarea name="resolution_notes" class="input-field w-full" rows="3">${complaint.resolution_notes || ''}</textarea>
                        </div>

                        <input type="hidden" name="complaint_id" value="${complaint.id}">
                        <button type="submit" class="btn-primary w-full">
                            💾 Update Complaint
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
</body>
</html>