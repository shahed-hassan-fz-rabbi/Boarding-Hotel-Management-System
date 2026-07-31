<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Delete Payment Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $payment_id = intval($_POST['payment_id'] ?? 0);

    if ($payment_id <= 0) {
        header('Location: list.php?error=Invalid payment ID');
        exit();
    }

    /* Delete payment */
    $sql = "DELETE FROM payments WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $payment_id);
        
        if ($stmt->execute()) {
            /* Log activity */
            $action = "Deleted payment " . $payment_id;
            $ip = $_SERVER['REMOTE_ADDR'];
            $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $logStmt = $conn->prepare($logSql);
            if ($logStmt) {
                $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                $logStmt->execute();
                $logStmt->close();
            }

            header('Location: list.php?success=Payment deleted successfully');
        } else {
            header('Location: list.php?error=Failed to delete payment');
        }
        $stmt->close();
    } else {
        header('Location: list.php?error=Database error');
    }
    exit();
}

?>