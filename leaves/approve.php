<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Approve/Reject Leave Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

// Only Admin/Hostel Supervisor can approve/reject
if ($user_role !== 'Admin' && $user_role !== 'Hostel Supervisor') {
    header('Location: list.php?error=Permission denied');
    exit();
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $leave_id = intval($_POST['leave_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');
    $approval_notes = trim($_POST['approval_notes'] ?? null);
    $approved_by = $_SESSION['user_id'];

    /* Validation */
    if ($leave_id <= 0) {
        $error = 'Invalid leave ID.';
    } else if (!in_array($action, ['approve', 'reject'])) {
        $error = 'Invalid action.';
    } else {

        /* Determine new status */
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';

        /* Update leave application */
        $sql = "UPDATE leave_applications 
                SET status = ?, approved_by = ?, approval_notes = ?, approved_on = NOW(), updated_at = NOW()
                WHERE id = ? AND status = 'Pending'";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sisi', $new_status, $approved_by, $approval_notes, $leave_id);
            
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success = true;

                    /* If approved, update leave balance */
                    if ($action === 'approve') {
                        // Get leave details
                        $getLeaveSql = "SELECT la.student_id, la.leave_type_id, la.number_of_days
                                       FROM leave_applications la
                                       WHERE la.id = ?";
                        $getLeaveStmt = $conn->prepare($getLeaveSql);
                        if ($getLeaveStmt) {
                            $getLeaveStmt->bind_param('i', $leave_id);
                            $getLeaveStmt->execute();
                            $getLeaveResult = $getLeaveStmt->get_result();
                            $leaveData = $getLeaveResult->fetch_assoc();
                            $getLeaveStmt->close();

                            if ($leaveData) {
                                // Update leave balance
                                $updateBalanceSql = "UPDATE student_leave_balance 
                                                    SET used_days = used_days + ?, 
                                                        remaining_days = total_days - (used_days + ?),
                                                        updated_at = NOW()
                                                    WHERE student_id = ? AND leave_type_id = ? AND year = YEAR(CURDATE())";
                                $updateBalanceStmt = $conn->prepare($updateBalanceSql);
                                if ($updateBalanceStmt) {
                                    $updateBalanceStmt->bind_param('iiii', $leaveData['number_of_days'], $leaveData['number_of_days'], $leaveData['student_id'], $leaveData['leave_type_id']);
                                    $updateBalanceStmt->execute();
                                    $updateBalanceStmt->close();
                                }
                            }
                        }
                    }

                    /* Log activity */
                    $action_text = ($action === 'approve') ? 'Approved' : 'Rejected';
                    $action_log = "Leave application $leave_id " . $action_text;
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                    $logStmt = $conn->prepare($logSql);
                    if ($logStmt) {
                        $logStmt->bind_param('iss', $_SESSION['user_id'], $action_log, $ip);
                        $logStmt->execute();
                        $logStmt->close();
                    }
                } else {
                    $error = 'Leave application not found or already processed.';
                }
            } else {
                $error = 'Failed to update leave application: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

/* Redirect after processing */
if ($success) {
    header('Location: list.php?success=Leave application ' . (isset($action) && $action === 'approve' ? 'approved' : 'rejected') . ' successfully');
} else if (!empty($error)) {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>