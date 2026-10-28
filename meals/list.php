<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Meal Records List Page
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';
/* ============================
   Fetch All Meal Records
============================ */

$sql = "SELECT 
            mr.id,
            mr.student_id,
            mr.meal_date,
            mr.meal_type,
            mr.served,
            mr.quantity,
            mr.notes,
            mr.created_at,
            s.student_code,
            s.first_name,
            s.last_name,
            s.gender,
            s.email,
            hr.room_number,
            hr.room_type
        FROM meal_records mr
        INNER JOIN students s ON mr.student_id = s.id
        LEFT JOIN room_allocations ra ON s.id = ra.student_id AND ra.status = 'Active'
        LEFT JOIN room_beds rb ON ra.bed_id = rb.id
        LEFT JOIN hostel_rooms hr ON rb.room_id = hr.id
        ORDER BY mr.meal_date DESC, mr.meal_type DESC";

$result = $conn->query($sql);

$meals = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $meals[] = $row;
    }
}

/* Statistics */
$totalMeals = count($meals);

/* Count meals by type */
$breakfastCount = count(array_filter($meals, fn($m) => $m['meal_type'] === 'Breakfast'));
$lunchCount = count(array_filter($meals, fn($m) => $m['meal_type'] === 'Lunch'));
$dinnerCount = count(array_filter($meals, fn($m) => $m['meal_type'] === 'Dinner'));

/* Count served meals */
$servedCount = count(array_filter($meals, fn($m) => $m['served'] === 1));

/* Fetch all students for dropdown */
$studentSql = "SELECT id, student_code, first_name, last_name FROM students WHERE status = 'Active' ORDER BY first_name ASC";
$studentResult = $conn->query($studentSql);
$students = [];
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

$mealsJSON = json_encode($meals);
$studentsJSON = json_encode($students);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Records - Hostel System</title>
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

        .btn-edit {
            background: #3b82f6;
            color: white;
            border: none;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .meal-type-breakfast {
            background: #fef3c7;
            color: #92400e;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .meal-type-lunch {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .meal-type-dinner {
            background: #f3e8ff;
            color: #6b21a8;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-served {
            background: #dcfce7;
            color: #166534;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-notserved {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
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

        .meal-row {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .meal-row:hover {
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
                <a href="list.php" class="nav-link active px-4 py-3 text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2z"></path>
                    </svg>
                    <span>Meals</span>
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
                        <h2 class="text-3xl font-bold text-slate-800">🍽️ Meal Records</h2>
                        <p class="text-slate-600 mt-2">Track student meal consumption</p>
                    </div>
                    <button id="addMealBtn" class="btn-primary mt-4 lg:mt-0">
                        ➕ Add Meal Record
                    </button>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-6 mb-8">
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Meals</p>
                        <h3 class="stat-number"><?php echo $totalMeals; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Breakfast</p>
                        <h3 class="stat-number" style="color: #f59e0b;"><?php echo $breakfastCount; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Lunch</p>
                        <h3 class="stat-number" style="color: #3b82f6;"><?php echo $lunchCount; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Dinner</p>
                        <h3 class="stat-number" style="color: #8b5cf6;"><?php echo $dinnerCount; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Served</p>
                        <h3 class="stat-number" style="color: #10b981;"><?php echo $servedCount; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="🔍 Search by student name..."
                        class="input-field flex-1">

                    <select id="filterMealType" class="input-field">
                        <option value="">All Meal Types</option>
                        <option value="Breakfast">Breakfast</option>
                        <option value="Lunch">Lunch</option>
                        <option value="Dinner">Dinner</option>
                    </select>

                    <select id="filterServed" class="input-field">
                        <option value="">All Status</option>
                        <option value="1">Served</option>
                        <option value="0">Not Served</option>
                    </select>

                    <input type="date" id="filterDate" class="input-field">
                </div>

                <!-- Meals Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-100 border-b border-slate-300">
                            <tr>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Student</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Date</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Meal Type</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Room</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Served</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Quantity</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="mealTableBody">
                            <!-- Meals will be inserted by JavaScript -->
                        </tbody>
                    </table>

                    <div id="noResults" class="no-results hidden">
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: #cbd5e1;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h3 class="text-xl font-600 text-slate-600">No meal records found</h3>
                        <p class="text-slate-500 mt-2">Try adjusting your search or filters</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Add/Edit Meal Modal -->
    <div id="mealModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-slate-800">Add Meal Record</h3>
                <button id="closeModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <form id="mealForm" method="POST" action="add.php">
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

                <!-- Date & Meal Type -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Meal Date *</label>
                        <input type="date" name="meal_date" class="input-field w-full" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meal Type *</label>
                        <select name="meal_type" class="input-field w-full" required>
                            <option value="">Select Meal Type</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                        </select>
                    </div>
                </div>

                <!-- Served & Quantity -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Served</label>
                        <select name="served" class="input-field w-full">
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity (kg)</label>
                        <input type="number" name="quantity" class="input-field w-full" min="0" step="0.1" placeholder="2.5">
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="input-field w-full" rows="3" placeholder="Any special notes..."></textarea>
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        ✓ Save Record
                    </button>
                    <button type="button" id="cancelBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>

                <input type="hidden" name="meal_id" id="mealId">
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="text-center">
                <div class="text-6xl mb-4">⚠️</div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Delete Meal Record?</h3>
                <p class="text-slate-600 mb-6">This action cannot be undone.</p>
                <div id="deleteInfo" class="text-lg font-600 text-slate-700 mb-6"></div>
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
        const allMeals = <?php echo $mealsJSON; ?>;
        const students = <?php echo $studentsJSON; ?>;
        let filteredMeals = [...allMeals];
        let deleteMealId = null;

        // ============================
        // DOM Elements
        // ============================

        const searchInput = document.getElementById('searchInput');
        const filterMealType = document.getElementById('filterMealType');
        const filterServed = document.getElementById('filterServed');
        const filterDate = document.getElementById('filterDate');
        const mealTableBody = document.getElementById('mealTableBody');
        const noResults = document.getElementById('noResults');
        const addMealBtn = document.getElementById('addMealBtn');
        const mealModal = document.getElementById('mealModal');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const mealForm = document.getElementById('mealForm');
        const deleteModal = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        // ============================
        // Filter & Search
        // ============================

        function applyFilters() {
            let result = [...allMeals];

            // Search
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(meal =>
                    meal.first_name.toLowerCase().includes(searchTerm) ||
                    meal.last_name.toLowerCase().includes(searchTerm) ||
                    meal.student_code.toLowerCase().includes(searchTerm)
                );
            }

            // Filter Meal Type
            const mealType = filterMealType.value;
            if (mealType) {
                result = result.filter(meal => meal.meal_type === mealType);
            }

            // Filter Served
            const served = filterServed.value;
            if (served !== '') {
                result = result.filter(meal => meal.served == served);
            }

            // Filter Date
            const date = filterDate.value;
            if (date) {
                result = result.filter(meal => meal.meal_date === date);
            }

            filteredMeals = result;
            renderMeals(result);
        }

        // ============================
        // Render Meals Table
        // ============================

        function renderMeals(meals) {
            mealTableBody.innerHTML = '';

            if (meals.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            meals.forEach((meal, index) => {
                const mealTypeClass = 
                    meal.meal_type === 'Breakfast' ? 'meal-type-breakfast' :
                    meal.meal_type === 'Lunch' ? 'meal-type-lunch' :
                    'meal-type-dinner';
                
                const servedClass = meal.served === 1 ? 'status-served' : 'status-notserved';
                const servedText = meal.served === 1 ? '✓ Served' : '✗ Not Served';
                const mealDate = new Date(meal.meal_date).toLocaleDateString();

                const row = document.createElement('tr');
                row.className = 'meal-row fade-in';
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-600 text-slate-800">${meal.first_name} ${meal.last_name}</p>
                            <p class="text-sm text-slate-500">${meal.student_code}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${mealDate}</td>
                    <td class="px-6 py-4">
                        <span class="${mealTypeClass}">${meal.meal_type}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${meal.room_number || 'N/A'}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="${servedClass}">${servedText}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${meal.quantity ? meal.quantity + ' kg' : '-'}</td>
                    <td class="px-6 py-4 text-center">
                        <button class="btn-edit btn-sm" onclick="editMeal(${meal.id})">✏️ Edit</button>
                        <button class="btn-danger btn-sm" onclick="openDeleteModal(${meal.id}, '${meal.first_name} ${meal.last_name}')">🗑️ Delete</button>
                    </td>
                `;
                mealTableBody.appendChild(row);
            });
        }

        // ============================
        // Modal Functions
        // ============================

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Meal Record';
            document.getElementById('mealId').value = '';
            mealForm.action = 'add.php';
            mealForm.reset();
            document.querySelector('input[name="meal_date"]').value = new Date().toISOString().split('T')[0];
            document.getElementById('formError').classList.add('hidden');
            mealModal.classList.add('active');
        }

        function closeMealModal() {
            mealModal.classList.remove('active');
            mealForm.reset();
            document.getElementById('formError').classList.add('hidden');
        }

        function openDeleteModal(id, name) {
            deleteMealId = id;
            document.getElementById('deleteInfo').textContent = `${name}'s Meal Record`;
            deleteModal.classList.add('active');
        }

        function closeDeleteModal() {
            deleteModal.classList.remove('active');
            deleteMealId = null;
        }

        // ============================
        // Edit Meal
        // ============================

        function editMeal(id) {
            const meal = allMeals.find(m => m.id === id);
            if (!meal) return;

            document.getElementById('modalTitle').textContent = 'Edit Meal Record';
            document.getElementById('mealId').value = id;
            mealForm.action = 'edit.php';

            document.querySelector('select[name="student_id"]').value = meal.student_id;
            document.querySelector('input[name="meal_date"]').value = meal.meal_date;
            document.querySelector('select[name="meal_type"]').value = meal.meal_type;
            document.querySelector('select[name="served"]').value = meal.served;
            document.querySelector('input[name="quantity"]').value = meal.quantity || '';
            document.querySelector('textarea[name="notes"]').value = meal.notes || '';

            mealModal.classList.add('active');
        }

        // ============================
        // Delete Meal
        // ============================

        confirmDeleteBtn.addEventListener('click', function() {
            if (!deleteMealId) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete.php';
            form.innerHTML = `<input type="hidden" name="meal_id" value="${deleteMealId}">`;
            document.body.appendChild(form);
            form.submit();
        });

        // ============================
        // Event Listeners
        // ============================

        addMealBtn.addEventListener('click', openAddModal);
        closeModal.addEventListener('click', closeMealModal);
        cancelBtn.addEventListener('click', closeMealModal);
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        searchInput.addEventListener('input', applyFilters);
        filterMealType.addEventListener('change', applyFilters);
        filterServed.addEventListener('change', applyFilters);
        filterDate.addEventListener('change', applyFilters);

        // Close modals on background click
        mealModal.addEventListener('click', function(e) {
            if (e.target === mealModal) closeMealModal();
        });

        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) closeDeleteModal();
        });

        // Initial render
        renderMeals(allMeals);
    </script>
<?php
require '../components/footer.php'; 
?>
</body>
</html>