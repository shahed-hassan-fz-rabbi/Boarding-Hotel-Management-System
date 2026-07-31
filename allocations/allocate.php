<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Allocate Student to Bed
 * ==========================================
 */

require_once '../includes/auth_check.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = intval($_POST['student_id'] ?? 0);
    $bed_id = intval($_POST['bed_id'] ?? 0);
    $check_in = trim($_POST['check_in'] ?? '');

    /* Validation */
    if ($student_id <= 0 || $bed_id <= 0 || empty($check_in)) {
        $error = 'All fields are required.';
    } else {

        /* Check if student already allocated */
        $checkSql = "SELECT id FROM room_allocations 
                    WHERE student_id = ? AND status = 'Active'";
        $checkStmt = $conn->prepare($checkSql);
        if ($checkStmt) {
            $checkStmt->bind_param('i', $student_id);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $error = 'Student is already allocated to a bed.';
            }
            $checkStmt->close();
        }

        /* Check if bed is available */
        if (empty($error)) {
            $bedSql = "SELECT status FROM room_beds WHERE id = ?";
            $bedStmt = $conn->prepare($bedSql);
            if ($bedStmt) {
                $bedStmt->bind_param('i', $bed_id);
                $bedStmt->execute();
                $bedResult = $bedStmt->get_result();
                $bed = $bedResult->fetch_assoc();
                
                if (!$bed || $bed['status'] !== 'Available') {
                    $error = 'This bed is not available.';
                }
                $bedStmt->close();
            }
        }

        /* Insert allocation */
        if (empty($error)) {
            $sql = "INSERT INTO room_allocations (student_id, bed_id, check_in, status) 
                    VALUES (?, ?, ?, 'Active')";
            
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('iis', $student_id, $bed_id, $check_in);
                
                if ($stmt->execute()) {
                    /* Update bed status */
                    $updateBedSql = "UPDATE room_beds SET status = 'Occupied' WHERE id = ?";
                    $updateBedStmt = $conn->prepare($updateBedSql);
                    if ($updateBedStmt) {
                        $updateBedStmt->bind_param('i', $bed_id);
                        $updateBedStmt->execute();
                        $updateBedStmt->close();
                    }

                    $success = true;

                    /* Log activity */
                    $studentSql = "SELECT first_name, last_name FROM students WHERE id = ?";
                    $studentStmt = $conn->prepare($studentSql);
                    if ($studentStmt) {
                        $studentStmt->bind_param('i', $student_id);
                        $studentStmt->execute();
                        $studentResult = $studentStmt->get_result();
                        $student = $studentResult->fetch_assoc();
                        
                        $action = "Allocated student " . ($student['first_name'] ?? '') . " to bed";
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
                    $error = 'Failed to allocate bed: ' . $stmt->error;
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
    header('Location: list.php?success=Student allocated successfully');
} else if (!empty($error)) {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>