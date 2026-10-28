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

$mealsJSON = json_encode($meals, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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
                        <h2 class="text-3xl font-bold text-slate-800"><?php echo icon('meals', 'w-4 h-4'); ?> Meal Records</h2>
                        <p class="text-slate-600 mt-2">Track student meal consumption</p>
                    </div>
                    <button id="addMealBtn" class="btn-primary mt-4 lg:mt-0">
                        <?php echo icon('plus', 'w-4 h-4'); ?> Add Meal Record
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
                        <h3 class="stat-number" style="color: var(--warning);"><?php echo $breakfastCount; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Lunch</p>
                        <h3 class="stat-number" style="color: var(--terracotta);"><?php echo $lunchCount; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Dinner</p>
                        <h3 class="stat-number" style="color: var(--coral);"><?php echo $dinnerCount; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Served</p>
                        <h3 class="stat-number" style="color: var(--success);"><?php echo $servedCount; ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by student name..."
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
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: var(--border-strong);">
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
                        <?php echo icon('check', 'w-3.5 h-3.5'); ?> Save Record
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
                <div class="text-6xl mb-4"><?php echo icon('complaints', 'w-4 h-4'); ?></div>
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

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

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
                const servedText = meal.served === 1 ? ' Served' : ' Not Served';
                const mealDate = new Date(meal.meal_date).toLocaleDateString();

                const row = document.createElement('tr');
                row.className = 'meal-row fade-in';
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-600 text-slate-800">${esc(meal.first_name)} ${esc(meal.last_name)}</p>
                            <p class="text-sm text-slate-500">${esc(meal.student_code)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${mealDate}</td>
                    <td class="px-6 py-4">
                        <span class="${mealTypeClass}">${esc(meal.meal_type)}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${esc(meal.room_number || 'N/A')}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="${servedClass}">${servedText}</span>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${meal.quantity ? meal.quantity + ' kg' : '-'}</td>
                    <td class="px-6 py-4 text-center">
                        <button class="btn-edit btn-sm" onclick="editMeal(${meal.id})"><?php echo icon('edit', 'w-3.5 h-3.5'); ?> Edit</button>
                        <button class="btn-danger btn-sm" onclick="openDeleteModal(${meal.id})"><?php echo icon('trash', 'w-3.5 h-3.5'); ?> Delete</button>
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

        function openDeleteModal(id) {
            deleteMealId = id;
            const m = allMeals.find(x => x.id === id);
            document.getElementById('deleteInfo').textContent = m ? `${m.first_name} ${m.last_name}'s Meal Record` : 'This meal record';
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
