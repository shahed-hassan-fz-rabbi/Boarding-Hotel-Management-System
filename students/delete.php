<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Delete Student Process
 * ==========================================
 */

require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = intval($_POST['student_id'] ?? 0);

    if ($student_id <= 0) {
        header('Location: list.php?error=Invalid student ID');
        exit();
    }

    /* Get student info for logging */
    $sql = "SELECT first_name, last_name FROM students WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $student = $result->fetch_assoc();
        $stmt->close();
    }

    /* Delete Student (will cascade delete related records) */
    $deleteSql = "DELETE FROM students WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    if ($deleteStmt) {
        $deleteStmt->bind_param('i', $student_id);
        if ($deleteStmt->execute()) {
            /* Log activity */
            $action = "Deleted student: " . ($student['first_name'] ?? 'Unknown') . " " . ($student['last_name'] ?? '');
            $ip = $_SERVER['REMOTE_ADDR'];
            $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
            $logStmt = $conn->prepare($logSql);
            if ($logStmt) {
                $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                $logStmt->execute();
                $logStmt->close();
            }
            header('Location: list.php?success=Student deleted successfully');
        } else {
            header('Location: list.php?error=Failed to delete student');
        }
        $deleteStmt->close();
    } else {
        header('Location: list.php?error=Database error');
    }
    exit();
}

?>