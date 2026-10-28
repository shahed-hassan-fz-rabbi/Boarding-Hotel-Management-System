<?php
/**
 * ==========================================
 * ADD NEW STUDENT PAGE
 * ==========================================
 */

require '../db.php';
require '../includes/auth_check.php';

requireRoles(['Admin','Teacher','Hostel Supervisor']);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $blood_group = trim($_POST['blood_group'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $admission_date = trim($_POST['admission_date'] ?? '');
    
    // Guardian info
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_phone = trim($_POST['guardian_phone'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = '❌ Please fill all required fields';
    } else {
        // Check if email exists
        $check_email = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $error = '❌ Email already exists';
        } else {
            // Generate student code
            $student_code = 'STU' . date('Ymd') . mt_rand(1000, 9999);

            // Insert guardian first (if provided)
            $guardian_id = NULL;
            if (!empty($father_name) || !empty($mother_name)) {
                $stmt_guardian = $conn->prepare("INSERT INTO guardians (father_name, mother_name, guardian_name, phone) VALUES (?, ?, ?, ?)");
                $stmt_guardian->bind_param("ssss", $father_name, $mother_name, $guardian_name, $guardian_phone);
                
                if ($stmt_guardian->execute()) {
                    $guardian_id = $conn->insert_id;
                }
                $stmt_guardian->close();
            }

            // Handle photo upload
            $photo = NULL;
            if (!empty($_FILES['photo']['name'])) {
                $photo_name = uniqid('student_') . '_' . $_FILES['photo']['name'];
                $photo_path = '../uploads/students/' . $photo_name;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
                    $photo = 'uploads/students/' . $photo_name;
                }
            }

            // Insert student
            $stmt = $conn->prepare("INSERT INTO students (student_code, guardian_id, first_name, last_name, email, phone, gender, dob, blood_group, address, admission_date, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssssssss", $student_code, $guardian_id, $first_name, $last_name, $email, $phone, $gender, $dob, $blood_group, $address, $admission_date, $photo);

            if ($stmt->execute()) {
                $student_id = $conn->insert_id;
                
                // Log activity
                $conn->query("INSERT INTO activity_logs (user_id, action, ip_address) VALUES ({$user_id}, 'Added Student: {$student_code}', '{$_SERVER['REMOTE_ADDR']}')");

                $success = '✅ Student added successfully!';
                header('refresh:2;url=list.php');
            } else {
                $error = '❌ Error adding student: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - Hostel Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .input-field {
            border: 1px solid rgba(102, 126, 234, 0.3);
            background: rgba(102, 126, 234, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .input-field:focus {
            outline: none;
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
        }

        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
    </style>
</head>
<body>

<div class="p-4 lg:p-8 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="glass rounded-2xl p-6 mb-8">
        <h1 class="text-3xl font-bold text-slate-800">➕ Add New Student</h1>
        <p class="text-slate-600 mt-2">Fill in the form to add a new student to the system</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" enctype="multipart/form-data" class="glass rounded-2xl p-8">
        <?= csrf_field() ?>

        <!-- Personal Information -->
        <h2 class="text-2xl font-bold text-slate-800 mb-6">📋 Personal Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">First Name *</label>
                <input type="text" name="first_name" class="input-field w-full" required>
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Last Name *</label>
                <input type="text" name="last_name" class="input-field w-full" required>
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Email *</label>
                <input type="email" name="email" class="input-field w-full" required>
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Phone *</label>
                <input type="tel" name="phone" class="input-field w-full" required>
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Gender *</label>
                <select name="gender" class="input-field w-full" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Date of Birth</label>
                <input type="date" name="dob" class="input-field w-full">
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Blood Group</label>
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
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Admission Date *</label>
                <input type="date" name="admission_date" class="input-field w-full" required value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <!-- Address -->
        <div class="mb-8">
            <label class="block text-sm font-600 text-slate-700 mb-2">Address</label>
            <textarea name="address" class="input-field w-full" rows="3"></textarea>
        </div>

        <!-- Photo Upload -->
        <div class="mb-8">
            <label class="block text-sm font-600 text-slate-700 mb-2">📸 Student Photo</label>
            <input type="file" name="photo" class="input-field w-full" accept="image/*">
            <p class="text-sm text-slate-500 mt-2">Accepted formats: JPG, PNG (Max 5MB)</p>
        </div>

        <!-- Guardian Information -->
        <h2 class="text-2xl font-bold text-slate-800 mb-6">👨‍👩‍👧 Guardian Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Father's Name</label>
                <input type="text" name="father_name" class="input-field w-full">
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Mother's Name</label>
                <input type="text" name="mother_name" class="input-field w-full">
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Guardian Name (if other)</label>
                <input type="text" name="guardian_name" class="input-field w-full">
            </div>
            <div>
                <label class="block text-sm font-600 text-slate-700 mb-2">Guardian Phone</label>
                <input type="tel" name="guardian_phone" class="input-field w-full">
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex gap-4">
            <button type="submit" class="btn-submit">✅ Add Student</button>
            <a href="list.php" class="px-6 py-3 bg-slate-400 text-white rounded-12px text-center font-600 hover:bg-slate-500 transition">Cancel</a>
        </div>

    </form>

</div>

</body>
</html>
