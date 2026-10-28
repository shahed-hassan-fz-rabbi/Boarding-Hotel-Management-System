<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Add Payment Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

requireRoles(['Admin','Accountant']);
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
    if ($student_id <= 0 || $month <= 0 || $month > 12 || $year < 2000 || $year > 2200) {
        $error = 'Invalid student, month, or year.';
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

        /* Prepare payment date */
        if (empty($payment_date)) {
            $payment_date = null;
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
            $payment_date = null;
        }

        /* Insert payment */
        $sql = "INSERT INTO payments 
                (student_id, month, year, room_fee, meal_fee, fine, discount, paid_amount, due_amount, payment_status, payment_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iiiddddddss', 
                $student_id, $month, $year, 
                $room_fee, $meal_fee, $fine, $discount, 
                $paid_amount, $due_amount, 
                $payment_status, $payment_date
            );
            
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
                    
                    $action = "Added payment for " . ($student['first_name'] ?? '') . " - " . $month . "/" . $year;
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
            } elseif ($conn->errno === 1062) {
                $error = 'A payment record already exists for this student, month, and year.';
            } else {
                error_log('payments/add.php insert failed: ' . $stmt->error);
                $error = 'Failed to add payment. Please try again.';
            }
            $stmt->close();
        } else {
            $error = 'Database error. Please try again.';
        }
    }
}

/* Redirect after processing */
if ($success) {
    header('Location: list.php?success=Payment added successfully');
} else if (!empty($error)) {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>