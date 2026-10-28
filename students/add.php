<?php
/**
 * ==========================================
 * ADD NEW STUDENT PAGE
 * ==========================================
 */

require '../db.php';
require '../includes/auth_check.php';
require_once __DIR__ . '/../components/icon.php';

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
        $error = ' Please fill all required fields';
    } else {
        // Check if email exists
        $check_email = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        
        if ($check_email->get_result()->num_rows > 0) {
            $error = ' Email already exists';
        } else {
            // Generate student code
            $student_code = 'STU' . date('Ymd') . mt_rand(1000, 9999);

            // Photo validation & storage — runs BEFORE any DB write, so a rejected
            // upload leaves no partial guardian/student rows behind.
            // Only real images of whitelisted types ≤ 2 MB are accepted; filenames are
            // randomised (original name is never used) to prevent traversal/abuse.
            $photo = NULL;
            $photoError = NULL;
            if (!empty($_FILES['photo']['name'])) {
                $ext = strtolower(pathinfo((string)$_FILES['photo']['name'], PATHINFO_EXTENSION));
                $tmpFile = (string)($_FILES['photo']['tmp_name'] ?? '');
                $errCode = (int)($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE);

                if ($errCode !== UPLOAD_ERR_OK || !is_uploaded_file($tmpFile)) {
                    $photoError = ' Photo upload failed. Please try again.';
                } else if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $photoError = ' Photo must be a JPG, PNG, WEBP or GIF image.';
                } else if ((int)@filesize($tmpFile) > 2 * 1024 * 1024) {
                    $photoError = ' Photo must be 2 MB or smaller.';
                } else if (@getimagesize($tmpFile) === false) {
                    $photoError = ' The uploaded file is not a valid image.';
                } else {
                    $photoDir = __DIR__ . '/../uploads/students';
                    if (!is_dir($photoDir)) {
                        @mkdir($photoDir, 0775, true);
                    }
                    $photoName = uniqid('student_', true) . '.' . $ext;
                    if (move_uploaded_file($tmpFile, $photoDir . '/' . $photoName)) {
                        $photo = 'uploads/students/' . $photoName;
                    } else {
                        $photoError = ' Could not save the uploaded photo. Please try again.';
                    }
                }
            }

            if ($photoError !== NULL) {
                $error = $photoError;
            } else {

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

            // Insert student
            $stmt = $conn->prepare("INSERT INTO students (student_code, guardian_id, first_name, last_name, email, phone, gender, dob, blood_group, address, admission_date, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissssssssss", $student_code, $guardian_id, $first_name, $last_name, $email, $phone, $gender, $dob, $blood_group, $address, $admission_date, $photo);

            if ($stmt->execute()) {
                $student_id = $conn->insert_id;
                
                // Log activity
                $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
                if ($log) {
                    $action = "Added Student: " . $student_code;
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log->bind_param('iss', $user_id, $action, $ip);
                    $log->execute();
                    $log->close();
                }

                $success = ' Student added successfully!';
                header('refresh:2;url=list.php');
            } else {
                error_log('students/add.php insert failed: ' . $stmt->error);
                $error = ' Error adding student. Please try again.';
            }
            $stmt->close();
            }
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
    <!-- Early theme resolution: runs before first paint to prevent FOUC -->
    <script>
    (function () {
        try {
            var t = localStorage.getItem('bhm-theme');
            if (!t) t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        } catch (e) { document.documentElement.setAttribute('data-theme', 'light'); }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/theme.css">
    <script src="../assets/app.js" defer></script>
    
    <style>
        body {
            background: linear-gradient(135deg, var(--terracotta) 0%, var(--terracotta-dark) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .input-field {
            border: 1px solid rgba(201, 79, 27, 0.3);
            background: rgba(201, 79, 27, 0.05);
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: var(--text);
            font-size: 0.95rem;
        }

        .input-field:focus {
            outline: none;
            background: rgba(201, 79, 27, 0.1);
            border-color: var(--terracotta);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--terracotta) 0%, var(--terracotta-dark) 100%);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            box-shadow: 0 10px 30px rgba(201, 79, 27, 0.4);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .alert-error {
            background: var(--error-soft);
            color: var(--error);
            border: 1px solid color-mix(in srgb, var(--error) 30%, transparent);
        }

        .alert-success {
            background: var(--success-soft);
            color: var(--success);
            border: 1px solid color-mix(in srgb, var(--success) 40%, transparent);
        }
    </style>
</head>
<body>
    <button type="button" class="theme-toggle theme-toggle--float" aria-label="Switch to dark theme" aria-pressed="false" title="Toggle theme">
        <?php echo icon('sun', 'icon-sun w-[18px] h-[18px]'); ?>
        <?php echo icon('moon', 'icon-moon w-[18px] h-[18px]'); ?>
    </button>

<div class="p-4 lg:p-8 max-w-4xl mx-auto">

    <!-- Header -->
    <div class="glass rounded-2xl p-6 mb-8">
        <h1 class="text-3xl font-bold text-slate-800"><?php echo icon('plus', 'w-4 h-4'); ?> Add New Student</h1>
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
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?php echo icon('file', 'w-4 h-4'); ?> Personal Information</h2>
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
            <label class="block text-sm font-600 text-slate-700 mb-2"><?php echo icon('camera', 'w-4 h-4'); ?> Student Photo</label>
            <input type="file" name="photo" class="input-field w-full" accept="image/*">
            <p class="text-sm text-slate-500 mt-2">Accepted formats: JPG, PNG (Max 5MB)</p>
        </div>

        <!-- Guardian Information -->
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><?php echo icon('user', 'w-4 h-4'); ?>‍<?php echo icon('user', 'w-4 h-4'); ?>‍<?php echo icon('user', 'w-4 h-4'); ?> Guardian Information</h2>
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
            <button type="submit" class="btn-submit"><?php echo icon('check-circle', 'w-4 h-4'); ?> Add Student</button>
            <a href="list.php" class="px-6 py-3 bg-slate-400 text-white rounded-12px text-center font-600 hover:bg-slate-500 transition">Cancel</a>
        </div>

    </form>

</div>

</body>
</html>
