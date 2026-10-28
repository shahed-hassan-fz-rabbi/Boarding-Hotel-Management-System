<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Remove Student Allocation (Transaction-Safe)
 * ==========================================
 */

require_once '../includes/auth_check.php';

requireRoles(['Admin','Hostel Supervisor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $allocation_id = intval($_POST['allocation_id'] ?? 0);

    if ($allocation_id <= 0) {
        header('Location: list.php?error=Invalid allocation ID');
        exit();
    }

    /* Do the checkout + bed release + counter re-sync atomically */
    $conn->begin_transaction();

    try {
        /* Lock the allocation row */
        $stmt = $conn->prepare("SELECT ra.id, ra.bed_id, ra.status, s.first_name, s.last_name 
                                FROM room_allocations ra
                                INNER JOIN students s ON ra.student_id = s.id
                                WHERE ra.id = ?
                                FOR UPDATE");
        if (!$stmt) throw new RuntimeException('Database error.');
        $stmt->bind_param('i', $allocation_id);
        $stmt->execute();
        $allocation = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$allocation) throw new RuntimeException('Allocation not found.');
        if ($allocation['status'] !== 'Active') throw new RuntimeException('Allocation is already checked out.');

        /* Mark the allocation as checked out (date only) */
        $upt = $conn->prepare("UPDATE room_allocations 
                               SET status = 'Checked Out', check_out = CURDATE()
                               WHERE id = ?");
        if (!$upt) throw new RuntimeException('Database error.');
        $upt->bind_param('i', $allocation_id);
        if (!$upt->execute()) { $upt->close(); throw new RuntimeException('Failed to remove allocation.'); }
        $upt->close();

        /* Release the bed */
        $bedUpd = $conn->prepare("UPDATE room_beds SET status = 'Available' WHERE id = ?");
        if (!$bedUpd) throw new RuntimeException('Database error.');
        $bedUpd->bind_param('i', $allocation['bed_id']);
        $bedUpd->execute();
        $bedUpd->close();

        /* Re-sync the room counter and Full/Available flag */
        $roomIdStmt = $conn->prepare("SELECT room_id FROM room_beds WHERE id = ?");
        if (!$roomIdStmt) throw new RuntimeException('Database error.');
        $roomIdStmt->bind_param('i', $allocation['bed_id']);
        $roomIdStmt->execute();
        $bed = $roomIdStmt->get_result()->fetch_assoc();
        $roomIdStmt->close();

        if ($bed) {
            $roomId = (int)$bed['room_id'];
            $syncRoom = $conn->prepare("UPDATE hostel_rooms hr
                                        SET allocated_beds = (
                                                SELECT COUNT(*) FROM room_allocations ra
                                                INNER JOIN room_beds rb ON rb.id = ra.bed_id
                                                WHERE rb.room_id = hr.id AND ra.status = 'Active'
                                            ),
                                            status = IF((SELECT COUNT(*) FROM room_allocations ra
                                                INNER JOIN room_beds rb ON rb.id = ra.bed_id
                                                WHERE rb.room_id = hr.id AND ra.status = 'Active')
                                                >= hr.total_beds, 'Full', 'Available')
                                        WHERE hr.id = ?");
            if (!$syncRoom) throw new RuntimeException('Database error.');
            $syncRoom->bind_param('i', $roomId);
            $syncRoom->execute();
            $syncRoom->close();
        }

        $conn->commit();

        /* Log activity (best effort, outside the transaction) */
        $action = "Removed allocation for " . $allocation['first_name'] . " " . $allocation['last_name'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
        if ($logStmt) {
            $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
            $logStmt->execute();
            $logStmt->close();
        }

        header('Location: list.php?success=Student removed from bed successfully');
        exit();

    } catch (RuntimeException $e) {
        $conn->rollback();
        header('Location: list.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

header('Location: list.php');
exit();

?>
