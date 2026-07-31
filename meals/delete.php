<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Delete Meal Record Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $meal_id = intval($_POST['meal_id'] ?? 0);

    if ($meal_id <= 0) {
        header('Location: list.php?error=Invalid meal ID');
        exit();
    }

    /* Delete meal record */
    $sql = "DELETE FROM meal_records WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $meal_id);
        
        if ($stmt->execute()) {
            /* Log activity */
            $action = "Deleted meal record " . $meal_id;
            $ip = $_SERVER['REMOTE_ADDR'];
            $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $logStmt = $conn->prepare($logSql);
            if ($logStmt) {
                $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                $logStmt->execute();
                $logStmt->close();
            }

            header('Location: list.php?success=Meal record deleted successfully');
        } else {
            header('Location: list.php?error=Failed to delete meal record');
        }
        $stmt->close();
    } else {
        header('Location: list.php?error=Database error');
    }
    exit();
}

?>