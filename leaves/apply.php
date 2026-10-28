<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Apply for Leave Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

requireRoles(['Admin', 'Teacher', 'Hostel Supervisor']);

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* Staff roles apply on behalf of the student selected in the UI */
    $student_id = intval($_POST['student_id'] ?? 0);
    $leave_type_id = intval($_POST['leave_type_id'] ?? 0);
    $from_date = trim($_POST['from_date'] ?? '');
    $to_date = trim($_POST['to_date'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    // Apply on behalf of the student selected in the UI (all allowed roles are staff)

    /* Validation */
    if ($student_id <= 0) {
        $error = 'Invalid student.';
    } else if ($leave_type_id <= 0) {
        $error = 'Please select a leave type.';
    } else if (empty($from_date) || empty($to_date)) {
        $error = 'From date and To date are required.';
    } else if ($to_date < $from_date) {
        $error = 'To date must be after From date.';
    } else if (empty($reason)) {
        $error = 'Reason is required.';
    } else {

        /* Calculate number of days */
        $fromDateTime = new DateTime($from_date);
        $toDateTime = new DateTime($to_date);
        $interval = $fromDateTime->diff($toDateTime);
        $number_of_days = $interval->days + 1;

        /* Check for overlapping leave */
        $checkSql = "SELECT id FROM leave_applications 
                    WHERE student_id = ? 
                    AND status IN ('Pending', 'Approved')
                    AND (
                        (from_date <= ? AND to_date >= ?) OR
                        (from_date <= ? AND to_date >= ?) OR
                        (from_date >= ? AND to_date <= ?)
                    )";
        $checkStmt = $conn->prepare($checkSql);
        if ($checkStmt) {
            $checkStmt->bind_param('issssss', $student_id, $to_date, $from_date, $from_date, $from_date, $from_date, $to_date);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $error = 'You already have an overlapping leave application.';
            }
            $checkStmt->close();
        }

        /* Insert leave application */
        if (empty($error)) {
            $sql = "INSERT INTO leave_applications 
                    (student_id, leave_type_id, from_date, to_date, number_of_days, reason, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('isisis', $student_id, $leave_type_id, $from_date, $to_date, $number_of_days, $reason);
                
                if ($stmt->execute()) {
                    $success = true;

                    /* Log activity */
                    $studentSql = "SELECT first_name, last_name FROM students WHERE id = ?";
                    $studentStmt = $conn->prepare($studentSql);
                    if ($studentStmt) {
                        $studentStmt->bind_param('i', $student_id);
                        $studentStmt->execute();
                        $studentResult = $studentStmt->get_result();
                        $student = $studentResult->fetch_assoc();
                        
                        $action = "Applied for leave - " . ($student['first_name'] ?? '') . " (" . $number_of_days . " days)";
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                        $logStmt = $conn->prepare($logSql);
                        if ($logStmt) {
                            $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                            $logStmt->execute();
                            $logStmt->close();
                        }
                        
                        $studentStmt->close();
                    }
                } else {
                    $error = 'Failed to apply for leave: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    }
}

/* Redirect after processing */
if ($success) {
    header('Location: list.php?success=Leave application submitted successfully');
} else if (!empty($error)) {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>