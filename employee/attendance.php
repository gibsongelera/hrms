<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';
$employee_id = $_SESSION['employee_id'];

// Fetch Attendance History
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC");
$stmt->execute([$employee_id]);
$attendance_records = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">My Attendance History</h2>
            <p style="color: var(--text-muted);">View your daily clock-in and clock-out records.</p>
        </header>

        <section class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Work Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($attendance_records) > 0): ?>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                                <td>
                                    <?php if ($record['clock_in']): ?>
                                        <span style="color: #10b981; font-weight: 500;">
                                            <?= date('h:i A', strtotime($record['clock_in'])) ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['clock_out']): ?>
                                        <span style="color: var(--secondary-color); font-weight: 500;">
                                            <?= date('h:i A', strtotime($record['clock_out'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['work_hours']): ?>
                                        <strong><?= $record['work_hours'] ?> hrs</strong>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    // Calculate Status dynamically
                                    $status = 'Present';
                                    if ($record['clock_in']) {
                                        $clockInTime = date('H:i', strtotime($record['clock_in']));
                                        if ($clockInTime > '09:00') {
                                            $status = 'Late';
                                        }
                                    }
                                    ?>
                                    <?php if ($status == 'Present'): ?>
                                        <span class="badge badge-approved">Present</span>
                                    <?php elseif ($status == 'Late'): ?>
                                        <span class="badge badge-pending">Late</span>
                                    <?php else: ?>
                                        <span class="badge badge-rejected"><?= $status ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No attendance records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    <?php include '../includes/footer.php'; ?>
    </main>
</div>