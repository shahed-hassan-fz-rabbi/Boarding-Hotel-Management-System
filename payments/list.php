<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Payments List Page
 * ==========================================
 */

require_once '../includes/auth_check.php';
require '../components/header.php';
/* ============================
   Fetch All Payments
============================ */

$sql = "SELECT 
            p.id,
            p.student_id,
            p.month,
            p.year,
            p.room_fee,
            p.meal_fee,
            p.fine,
            p.discount,
            p.paid_amount,
            p.due_amount,
            p.payment_status,
            p.payment_date,
            s.student_code,
            s.first_name,
            s.last_name,
            s.email,
            s.phone
        FROM payments p
        INNER JOIN students s ON p.student_id = s.id
        ORDER BY p.year DESC, p.month DESC, s.first_name ASC";

$result = $conn->query($sql);

$payments = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
}

/* Statistics */
$totalPayments = count($payments);
$totalAmount = 0;
$paidAmount = 0;
$dueAmount = 0;
$partialAmount = 0;

foreach ($payments as $payment) {
    $totalAmount += ($payment['room_fee'] + $payment['meal_fee'] + $payment['fine'] - $payment['discount']);
    $paidAmount += $payment['paid_amount'];
    $dueAmount += $payment['due_amount'];
    if ($payment['payment_status'] === 'Partial') {
        $partialAmount += ($payment['room_fee'] + $payment['meal_fee'] + $payment['fine'] - $payment['discount']);
    }
}

/* Fetch all students for dropdown */
$studentSql = "SELECT id, student_code, first_name, last_name FROM students WHERE status = 'Active' ORDER BY first_name ASC";
$studentResult = $conn->query($studentSql);
$students = [];
if ($studentResult && $studentResult->num_rows > 0) {
    while ($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

$paymentsJSON = json_encode($payments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$studentsJSON = json_encode($students, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management - Hostel System</title>
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

        .status-paid {
            background: #dcfce7;
            color: #166534;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-partial {
            background: #fef3c7;
            color: #92400e;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-due {
            background: #fee2e2;
            color: #991b1b;
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

        .payment-row {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .payment-row:hover {
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

        .amount-badge {
            background: #f0f9ff;
            color: #0c4a6e;
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
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
                        <path d="M20 13H4a1 1 0 00-1 1v6a1 1 0 001 1h16a1 1 0 001-1v-6a1 1 0 00-1-1zM7 17a1 1 0 11-2 0 1 1 0 012 0zM13 17a1 1 0 11-2 0 1 1 0 012 0zM19 17a1 1 0 11-2 0 1 1 0 012 0z"></path>
                    </svg>
                    <span>Allocations</span>
                </a>
                <a href="list.php" class="nav-link active px-4 py-3 text-white rounded-12px">
                    <svg class="icon-svg" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
                    </svg>
                    <span>Payments</span>
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
                        <h2 class="text-3xl font-bold text-slate-800">💰 Payments Management</h2>
                        <p class="text-slate-600 mt-2">Manage student payment records</p>
                    </div>
                    <button id="addPaymentBtn" class="btn-primary mt-4 lg:mt-0">
                        ➕ New Payment
                    </button>
                </div>

                <!-- Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Payments</p>
                        <h3 class="stat-number"><?php echo $totalPayments; ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Paid</p>
                        <h3 class="stat-number" style="color: #10b981;">৳<?php echo number_format($paidAmount, 0); ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Due</p>
                        <h3 class="stat-number" style="color: #ef4444;">৳<?php echo number_format($dueAmount, 0); ?></h3>
                    </div>
                    <div class="glass stat-card rounded-2xl">
                        <p class="stat-label">Total Amount</p>
                        <h3 class="stat-number" style="color: #f59e0b;">৳<?php echo number_format($totalAmount, 0); ?></h3>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="glass rounded-2xl p-6 mb-8 flex flex-col lg:flex-row gap-4">
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="🔍 Search by student name or code..."
                        class="input-field flex-1">

                    <select id="filterStatus" class="input-field">
                        <option value="">Filter by Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial</option>
                        <option value="Due">Due</option>
                    </select>

                    <select id="filterMonth" class="input-field">
                        <option value="">Filter by Month</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                </div>

                <!-- Payments Table -->
                <div class="glass rounded-2xl overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-slate-100 border-b border-slate-300">
                            <tr>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Student</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Month/Year</th>
                                <th class="px-6 py-4 text-right font-600 text-slate-700">Total</th>
                                <th class="px-6 py-4 text-right font-600 text-slate-700">Paid</th>
                                <th class="px-6 py-4 text-right font-600 text-slate-700">Due</th>
                                <th class="px-6 py-4 text-left font-600 text-slate-700">Status</th>
                                <th class="px-6 py-4 text-center font-600 text-slate-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="paymentTableBody">
                            <!-- Payments will be inserted by JavaScript -->
                        </tbody>
                    </table>

                    <div id="noResults" class="no-results hidden">
                        <svg class="icon-svg mx-auto mb-4" viewBox="0 0 24 24" style="width: 64px; height: 64px; stroke: #cbd5e1;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        <h3 class="text-xl font-600 text-slate-600">No payments found</h3>
                        <p class="text-slate-500 mt-2">Try adjusting your search or filters</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- Add/Edit Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-slate-800">Add Payment</h3>
                <button id="closeModal" class="text-slate-500 hover:text-slate-700 text-2xl cursor-pointer">&times;</button>
            </div>

            <form id="paymentForm" method="POST" action="add.php">
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

                <!-- Month & Year -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Month *</label>
                        <select name="month" class="input-field w-full" required>
                            <option value="">Select Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year *</label>
                        <input type="number" name="year" class="input-field w-full" min="2020" max="2099" required value="<?php echo date('Y'); ?>">
                    </div>
                </div>

                <!-- Room Fee & Meal Fee -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Room Fee (৳) *</label>
                        <input type="number" name="room_fee" class="input-field w-full" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Meal Fee (৳) *</label>
                        <input type="number" name="meal_fee" class="input-field w-full" min="0" step="0.01" required>
                    </div>
                </div>

                <!-- Fine & Discount -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Fine (৳)</label>
                        <input type="number" name="fine" class="input-field w-full" min="0" step="0.01" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Discount (৳)</label>
                        <input type="number" name="discount" class="input-field w-full" min="0" step="0.01" value="0">
                    </div>
                </div>

                <!-- Paid Amount -->
                <div class="form-group">
                    <label class="form-label">Paid Amount (৳) *</label>
                    <input type="number" name="paid_amount" class="input-field w-full" min="0" step="0.01" required>
                </div>

                <!-- Payment Status -->
                <div class="form-group">
                    <label class="form-label">Payment Status *</label>
                    <select name="payment_status" class="input-field w-full" required>
                        <option value="">Select Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial</option>
                        <option value="Due">Due</option>
                    </select>
                </div>

                <!-- Payment Date -->
                <div class="form-group">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" class="input-field w-full">
                </div>

                <!-- Buttons -->
                <div class="flex gap-3">
                    <button type="submit" class="btn-primary flex-1">
                        ✓ Save Payment
                    </button>
                    <button type="button" id="cancelBtn" class="btn-secondary flex-1">
                        Cancel
                    </button>
                </div>

                <input type="hidden" name="payment_id" id="paymentId">
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="text-center">
                <div class="text-6xl mb-4">⚠️</div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">Delete Payment?</h3>
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
        const allPayments = <?php echo $paymentsJSON; ?>;
        const students = <?php echo $studentsJSON; ?>;
        let filteredPayments = [...allPayments];
        let deletePaymentId = null;

        /* HTML-escape DB-sourced values before they are interpolated into innerHTML templates */
        function esc(v) {
            return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        // ============================
        // DOM Elements
        // ============================

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterMonth = document.getElementById('filterMonth');
        const paymentTableBody = document.getElementById('paymentTableBody');
        const noResults = document.getElementById('noResults');
        const addPaymentBtn = document.getElementById('addPaymentBtn');
        const paymentModal = document.getElementById('paymentModal');
        const closeModal = document.getElementById('closeModal');
        const cancelBtn = document.getElementById('cancelBtn');
        const paymentForm = document.getElementById('paymentForm');
        const deleteModal = document.getElementById('deleteModal');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        // ============================
        // Month Name Function
        // ============================

        function getMonthName(month) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[parseInt(month) - 1] || '';
        }

        // ============================
        // Format Currency
        // ============================

        function formatCurrency(amount) {
            return '৳' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // ============================
        // Filter & Search
        // ============================

        function applyFilters() {
            let result = [...allPayments];

            // Search
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                result = result.filter(payment =>
                    payment.first_name.toLowerCase().includes(searchTerm) ||
                    payment.last_name.toLowerCase().includes(searchTerm) ||
                    payment.student_code.toLowerCase().includes(searchTerm)
                );
            }

            // Filter Status
            const status = filterStatus.value;
            if (status) {
                result = result.filter(payment => payment.payment_status === status);
            }

            // Filter Month
            const month = filterMonth.value;
            if (month) {
                result = result.filter(payment => payment.month == month);
            }

            filteredPayments = result;
            renderPayments(result);
        }

        // ============================
        // Render Payments Table
        // ============================

        function renderPayments(payments) {
            paymentTableBody.innerHTML = '';

            if (payments.length === 0) {
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            payments.forEach((payment, index) => {
                const statusClass = 
                    payment.payment_status === 'Paid' ? 'status-paid' :
                    payment.payment_status === 'Partial' ? 'status-partial' :
                    'status-due';
                
                const statusText = 
                    payment.payment_status === 'Paid' ? '✓ Paid' :
                    payment.payment_status === 'Partial' ? '◐ Partial' :
                    '✗ Due';

                const totalAmount = (parseFloat(payment.room_fee) + parseFloat(payment.meal_fee) + parseFloat(payment.fine) - parseFloat(payment.discount));
                const dueAmount = totalAmount - parseFloat(payment.paid_amount);

                const row = document.createElement('tr');
                row.className = 'payment-row fade-in';
                row.style.animationDelay = `${index * 0.05}s`;
                row.innerHTML = `
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-600 text-slate-800">${esc(payment.first_name)} ${esc(payment.last_name)}</p>
                            <p class="text-sm text-slate-500">${esc(payment.student_code)}</p>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-slate-700">${getMonthName(payment.month)} ${payment.year}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="amount-badge">${formatCurrency(totalAmount)}</span>
                    </td>
                    <td class="px-6 py-4 text-right font-600 text-green-600">${formatCurrency(payment.paid_amount)}</td>
                    <td class="px-6 py-4 text-right font-600 text-red-600">${formatCurrency(dueAmount)}</td>
                    <td class="px-6 py-4">
                        <span class="${statusClass}">${statusText}</span>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <button class="btn-edit btn-sm" onclick="editPayment(${payment.id})">✏️ Edit</button>
                        <button class="btn-danger btn-sm" onclick="openDeleteModal(${payment.id})">🗑️ Delete</button>
                    </td>
                `;
                paymentTableBody.appendChild(row);
            });
        }

        // ============================
        // Modal Functions
        // ============================

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Payment';
            document.getElementById('paymentId').value = '';
            paymentForm.action = 'add.php';
            paymentForm.reset();
            document.getElementById('formError').classList.add('hidden');
            paymentModal.classList.add('active');
        }

        function closePaymentModal() {
            paymentModal.classList.remove('active');
            paymentForm.reset();
            document.getElementById('formError').classList.add('hidden');
        }

        function openDeleteModal(id) {
            deletePaymentId = id;
            const p = allPayments.find(x => x.id === id);
            document.getElementById('deleteInfo').textContent = p ? `${p.first_name} ${p.last_name}` : 'This payment';
            deleteModal.classList.add('active');
        }

        function closeDeleteModal() {
            deleteModal.classList.remove('active');
            deletePaymentId = null;
        }

        // ============================
        // Edit Payment
        // ============================

        function editPayment(id) {
            const payment = allPayments.find(p => p.id === id);
            if (!payment) return;

            document.getElementById('modalTitle').textContent = 'Edit Payment';
            document.getElementById('paymentId').value = id;
            paymentForm.action = 'edit.php';

            document.querySelector('select[name="student_id"]').value = payment.student_id;
            document.querySelector('select[name="month"]').value = payment.month;
            document.querySelector('input[name="year"]').value = payment.year;
            document.querySelector('input[name="room_fee"]').value = payment.room_fee;
            document.querySelector('input[name="meal_fee"]').value = payment.meal_fee;
            document.querySelector('input[name="fine"]').value = payment.fine;
            document.querySelector('input[name="discount"]').value = payment.discount;
            document.querySelector('input[name="paid_amount"]').value = payment.paid_amount;
            document.querySelector('select[name="payment_status"]').value = payment.payment_status;
            if (payment.payment_date) {
                document.querySelector('input[name="payment_date"]').value = payment.payment_date;
            }

            paymentModal.classList.add('active');
        }

        // ============================
        // Delete Payment
        // ============================

        confirmDeleteBtn.addEventListener('click', function() {
            if (!deletePaymentId) return;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete.php';
            form.innerHTML = `<input type="hidden" name="payment_id" value="${deletePaymentId}">`;
            document.body.appendChild(form);
            form.submit();
        });

        // ============================
        // Event Listeners
        // ============================

        addPaymentBtn.addEventListener('click', openAddModal);
        closeModal.addEventListener('click', closePaymentModal);
        cancelBtn.addEventListener('click', closePaymentModal);
        cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        searchInput.addEventListener('input', applyFilters);
        filterStatus.addEventListener('change', applyFilters);
        filterMonth.addEventListener('change', applyFilters);

        // Close modals on background click
        paymentModal.addEventListener('click', function(e) {
            if (e.target === paymentModal) closePaymentModal();
        });

        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) closeDeleteModal();
        });

        // Initial render
        renderPayments(allPayments);
    </script>

<?php
require '../components/footer.php'; 
?>
</body>
</html>