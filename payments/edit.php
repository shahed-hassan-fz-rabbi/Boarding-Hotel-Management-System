<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Edit Payment Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

requireRoles(['Admin','Accountant']);
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payment_id = intval($_POST['payment_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $month = intval($_POST['month'] ?? 0);
    $year = intval($_POST['year'] ?? 0);
    $room_fee = floatval($_POST['room_fee'] ?? 0);
    $meal_fee = floatval($_POST['meal_fee'] ?? 0);
    $fine = floatval($_POST['fine'] ?? 0);
    $discount = floatval($_POST['discount'] ?? 0);
    $paid_amount = floatval($_POST['paid_amount'] ?? 0);
    $payment_status = trim($_POST['payment_status'] ?? '');
    $payment_date = trim($_POST['payment_date'] ?? null);

    /* Validation */
    if ($payment_id <= 0 || $student_id <= 0 || $month <= 0 || $month > 12 || $year <= 0) {
        $error = 'Invalid payment ID, student, month, or year.';
    } else if ($room_fee < 0 || $meal_fee < 0) {
        $error = 'Fees cannot be negative.';
    } else if (empty($payment_status)) {
        $error = 'Payment status is required.';
    } else {

        /* Calculate total and due amount */
        $total_amount = $room_fee + $meal_fee + $fine - $discount;
        $due_amount = max(0, $total_amount - $paid_amount);

        /* Prepare payment date */
        if (empty($payment_date)) {
            $payment_date = null;
        }

        /* Update payment */
        $sql = "UPDATE payments 
                SET student_id = ?, month = ?, year = ?, room_fee = ?, meal_fee = ?, fine = ?, discount = ?, paid_amount = ?, due_amount = ?, payment_status = ?, payment_date = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iiiidddddssi', 
                $student_id, $month, $year, 
                $room_fee, $meal_fee, $fine, $discount, 
                $paid_amount, $due_amount, 
                $payment_status, $payment_date, $payment_id
            );
            
            if ($stmt->execute()) {
                $success = true;

                /* Log activity */
                $action = "Updated payment " . $payment_id;
                $ip = $_SERVER['REMOTE_ADDR'];
                $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } else {
                $error = 'Failed to update payment: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

/* Redirect after processing */
if ($success) {
    header('Location: list.php?success=Payment updated successfully');
} else if (!empty($error)) {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>