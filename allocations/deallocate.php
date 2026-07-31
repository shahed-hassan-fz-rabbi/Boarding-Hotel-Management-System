<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Remove Student Allocation (Deallocate)
 * ==========================================
 */

require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allocation_id = intval($_POST['allocation_id'] ?? 0);

    if ($allocation_id <= 0) {
        header('Location: list.php?error=Invalid allocation ID');
        exit();
    }

    /* Get allocation and bed info */
    $sql = "SELECT ra.bed_id, s.first_name, s.last_name 
            FROM room_allocations ra
            INNER JOIN students s ON ra.student_id = s.id
            WHERE ra.id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $allocation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $allocation = $result->fetch_assoc();
        $stmt->close();

        if (!$allocation) {
            header('Location: list.php?error=Allocation not found');
            exit();
        }

        /* Update allocation status to Checked Out */
        $updateSql = "UPDATE room_allocations 
                      SET status = 'Checked Out', check_out = NOW()
                      WHERE id = ?";
        
        $updateStmt = $conn->prepare($updateSql);
        if ($updateStmt) {
            $updateStmt->bind_param('i', $allocation_id);
            
            if ($updateStmt->execute()) {
                /* Update bed status back to Available */
                $bedUpdateSql = "UPDATE room_beds SET status = 'Available' WHERE id = ?";
                $bedUpdateStmt = $conn->prepare($bedUpdateSql);
                if ($bedUpdateStmt) {
                    $bedUpdateStmt->bind_param('i', $allocation['bed_id']);
                    $bedUpdateStmt->execute();
                    $bedUpdateStmt->close();
                }

                /* Log activity */
                $action = "Removed allocation for " . $allocation['first_name'] . " " . $allocation['last_name'];
                $ip = $_SERVER['REMOTE_ADDR'];
                $logSql = "INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)";
                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                    $logStmt->execute();
                    $logStmt->close();
                }

                header('Location: list.php?success=Student removed from bed successfully');
            } else {
                header('Location: list.php?error=Failed to remove allocation');
            }
            $updateStmt->close();
        } else {
            header('Location: list.php?error=Database error');
        }
    } else {
        header('Location: list.php?error=Database error');
    }
    exit();
}

?>