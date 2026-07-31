<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Edit Student Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = intval($_POST['student_id'] ?? 0);
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    /* Validation */
    if ($student_id <= 0) {
        $error = 'Invalid student ID.';
    } else if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($gender)) {
        $error = 'All required fields must be filled.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {

        /* Update Student */
        $sql = "UPDATE students 
                SET first_name = ?, last_name = ?, email = ?, phone = ?, gender = ?, status = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ssssssi', $first_name, $last_name, $email, $phone, $gender, $status, $student_id);
            
            if ($stmt->execute()) {
                $success = true;
                /* Log activity */
                $action = "Updated student: $first_name $last_name";
                $ip = $_SERVER['REMOTE_ADDR'];
                $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } else {
                $error = 'Failed to update student: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

/* Return JSON Response */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Student updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'error' => $error]);
    }
    exit();
}

?>