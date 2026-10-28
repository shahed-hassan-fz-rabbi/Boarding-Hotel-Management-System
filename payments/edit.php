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
    $payment_date = trim($_POST['payment_date'] ?? '');

    /* Validation */
    if ($payment_id <= 0 || $student_id <= 0 || $month <= 0 || $month > 12 || $year < 2000 || $year > 2200) {
        $error = 'Invalid payment ID, student, month, or year.';
    } else if ($room_fee < 0 || $meal_fee < 0 || $fine < 0 || $discount < 0 || $paid_amount < 0) {
        $error = 'Fee and payment amounts cannot be negative.';
    } else if ($room_fee + $meal_fee + $fine < $discount) {
        $error = 'Discount cannot exceed the total fee amount.';
    } else {

        /* Server-side status calculation (client value is ignored) */
        $total_amount = $room_fee + $meal_fee + $fine - $discount;
        $due_amount = round(max(0, $total_amount - $paid_amount), 2);

        if ($paid_amount <= 0) {
            $payment_status = 'Due';
        } elseif ($due_amount < 0.01) {
            $payment_status = 'Paid';
        } else {
            $payment_status = 'Partial';
        }

        /* Validate payment date */
        if (empty($payment_date)) {
            $payment_date = null;
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
            $payment_date = null;
        }

        /* Update payment */
        $sql = "UPDATE payments 
                SET student_id = ?, month = ?, year = ?, room_fee = ?, meal_fee = ?, fine = ?, discount = ?, paid_amount = ?, due_amount = ?, payment_status = ?, payment_date = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iiiddddddssi', 
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
            } elseif ($conn->errno === 1062) {
                $error = 'Another payment record already exists for this student, month, and year.';
            } else {
                error_log('payments/edit.php update failed: ' . $stmt->error);
                $error = 'Failed to update payment. Please try again.';
            }
            $stmt->close();
        } else {
            $error = 'Database error. Please try again.';
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