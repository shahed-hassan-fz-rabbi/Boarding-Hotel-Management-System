<?php
/**
 * ==========================================
 * BOARDING SCHOOL MANAGEMENT SYSTEM
 * Allocate Student to Bed (Transaction-Safe)
 * ==========================================
 */

require_once '../includes/auth_check.php';

requireRoles(['Admin','Hostel Supervisor']);

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $student_id = intval($_POST['student_id'] ?? 0);
    $bed_id = intval($_POST['bed_id'] ?? 0);
    $check_in = trim($_POST['check_in'] ?? '');

    /* Basic validation */
    if ($student_id <= 0 || $bed_id <= 0 || $check_in === '') {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in)) {
        $error = 'Check-in date must be a valid date (YYYY-MM-DD).';
    } else {

        /* Run the whole allocation atomically with row locks so two
           simultaneous submissions can never take the same bed. */
        $conn->begin_transaction();

        try {
            /* Lock the student's allocations for the transaction */
            $checkStmt = $conn->prepare("SELECT id FROM room_allocations 
                                         WHERE student_id = ? AND status = 'Active'
                                         FOR UPDATE");
            if (!$checkStmt) throw new RuntimeException('Database error.');
            $checkStmt->bind_param('i', $student_id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $checkStmt->close();
                throw new RuntimeException('Student is already allocated to a bed.');
            }
            $checkStmt->close();

            /* Lock the bed row and confirm it is still available */
            $bedStmt = $conn->prepare("SELECT id, room_id, status FROM room_beds 
                                       WHERE id = ? FOR UPDATE");
            if (!$bedStmt) throw new RuntimeException('Database error.');
            $bedStmt->bind_param('i', $bed_id);
            $bedStmt->execute();
            $bed = $bedStmt->get_result()->fetch_assoc();
            $bedStmt->close();

            if (!$bed) throw new RuntimeException('Bed not found.');
            if ($bed['status'] !== 'Available') throw new RuntimeException('This bed is not available.');

            /* Confirm the student exists and is active */
            $stuStmt = $conn->prepare("SELECT first_name, last_name FROM students WHERE id = ? AND status = 'Active'");
            if (!$stuStmt) throw new RuntimeException('Database error.');
            $stuStmt->bind_param('i', $student_id);
            $stuStmt->execute();
            $student = $stuStmt->get_result()->fetch_assoc();
            $stuStmt->close();

            if (!$student) throw new RuntimeException('Student not found or inactive.');

            /* Insert the allocation */
            $insStmt = $conn->prepare("INSERT INTO room_allocations (student_id, bed_id, check_in, status) 
                                       VALUES (?, ?, ?, 'Active')");
            if (!$insStmt) throw new RuntimeException('Database error.');
            $insStmt->bind_param('iis', $student_id, $bed_id, $check_in);
            if (!$insStmt->execute()) {
                $insStmt->close();
                throw new RuntimeException('Failed to allocate bed.');
            }
            $insStmt->close();

            /* Mark the bed occupied */
            $updBed = $conn->prepare("UPDATE room_beds SET status = 'Occupied' WHERE id = ?");
            if (!$updBed) throw new RuntimeException('Database error.');
            $updBed->bind_param('i', $bed_id);
            $updBed->execute();
            $updBed->close();

            /* Re-sync the room's allocated_beds counter and Full/Available flag */
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
            $syncRoom->bind_param('i', $bed['room_id']);
            $syncRoom->execute();
            $syncRoom->close();

            /* All good — commit */
            $conn->commit();
            $success = true;

            /* Log activity (best effort, outside the transaction) */
            $action = "Allocated student " . ($student['first_name'] ?? '') . " " . ($student['last_name'] ?? '') . " to bed #" . $bed_id;
            $ip = $_SERVER['REMOTE_ADDR'];
            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, ip_address) VALUES (?, ?, ?)");
            if ($logStmt) {
                $logStmt->bind_param('iss', $_SESSION['user_id'], $action, $ip);
                $logStmt->execute();
                $logStmt->close();
            }

        } catch (RuntimeException $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

/* Redirect after processing */
if ($success) {
    header('Location: list.php?success=Student allocated successfully');
} else if ($error !== '') {
    header('Location: list.php?error=' . urlencode($error));
} else {
    header('Location: list.php');
}
exit();

?>
