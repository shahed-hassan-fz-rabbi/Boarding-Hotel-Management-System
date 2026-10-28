<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Edit Meal Record Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

requireRoles(['Admin','Hostel Supervisor']);
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $meal_id = intval($_POST['meal_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $meal_date = trim($_POST['meal_date'] ?? '');
    $meal_type = trim($_POST['meal_type'] ?? '');
    $served = intval($_POST['served'] ?? 0);
    $quantity = !empty($_POST['quantity']) ? floatval($_POST['quantity']) : null;
    $notes = trim($_POST['notes'] ?? null);

    /* Validation */
    if ($meal_id <= 0 || $student_id <= 0) {
        $error = 'Invalid meal or student ID.';
    } else if (empty($meal_date)) {
        $error = 'Meal date is required.';
    } else if (empty($meal_type) || !in_array($meal_type, ['Breakfast', 'Lunch', 'Dinner'])) {
        $error = 'Invalid meal type.';
    } else {

        /* Update meal record */
        $sql = "UPDATE meal_records 
                SET student_id = ?, meal_date = ?, meal_type = ?, served = ?, quantity = ?, notes = ?, updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('issidsi', $student_id, $meal_date, $meal_type, $served, $quantity, $notes, $meal_id);
            
            if ($stmt->execute()) {
                $success = true;

                /* Log activity */
                $action = "Updated meal record " . $meal_id;
                $ip = $_SERVER['REMOTE_ADDR'];
                $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                    $logStmt->execute();
                    $logStmt->close();
                }
            } else {
                $error = 'Failed to update meal record: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }
}

/* Redirect after processing */
if ($success) {
    header('Location: list.php?success=Meal record updated successfully');
} else if (!empty($error)) {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>