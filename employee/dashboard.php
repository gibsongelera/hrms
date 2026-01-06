<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';
$employee_id = $_SESSION['employee_id'];

// Get Employee Data
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

if (!$employee) {
    // Fallback if employee record is missing
    $employee = ['first_name' => 'Employee', 'last_name' => ''];
}

// Check today's attendance
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->execute([$employee_id, $today]);
$attendance = $stmt->fetch();

include '../includes/header.php';
?>
<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700;">My Dashboard</h2>
                <p style="color: var(--text-muted);">Welcome back, <?= $employee['first_name'] ?></p>
            </div>
            <div id="real-time-clock" style="font-size: 1.25rem; font-weight: 600; color: var(--primary-color);">
                Loading...
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card"
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
                <div class="stat-title">Attendance</div>
                <div id="attendance-status" style="margin: 1rem 0;">
                    <?php if (!$attendance): ?>
                        <span style="color: var(--text-muted);">You haven't clocked in today.</span>
                    <?php elseif (!$attendance['clock_out']): ?>
                        <span style="color: #10b981; font-weight: 600;">Currently Clocked In</span>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Since:
                            <?= date('h:i A', strtotime($attendance['clock_in'])) ?>
                        </div>
                    <?php else: ?>
                        <span style="color: var(--secondary-color);">Clocked Out</span>
                        <div style="font-size: 0.875rem; color: var(--text-muted);">Total: <?= $attendance['work_hours'] ?>
                            hrs</div>
                    <?php endif; ?>
                </div>

                <form id="attendance-form" action="process_attendance.php" method="POST">
                    <?php if (!$attendance): ?>
                        <button type="submit" name="action" value="clock_in" class="btn btn-primary">Clock In</button>
                    <?php elseif (!$attendance['clock_out']): ?>
                        <button type="submit" name="action" value="clock_out" class="btn"
                            style="background: #ef4444; color: white;">Clock Out</button>
                    <?php else: ?>
                        <button class="btn" disabled
                            style="background: #e2e8f0; color: #94a3b8; cursor: not-allowed;">Already Recorded</button>
                    <?php endif; ?>
                </form>
            </div>

            <?php
            // Calculate Remaining Leaves (Assuming 20 days standard allowance)
            $total_allowance = 20;
            $stmt_leaves = $pdo->prepare("SELECT SUM(DATEDIFF(end_date, start_date) + 1) FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND YEAR(start_date) = YEAR(CURDATE())");
            $stmt_leaves->execute([$employee_id]);
            $used_leaves = $stmt_leaves->fetchColumn() ?: 0;
            $remaining_leaves = $total_allowance - $used_leaves;

            // Calculate Total Work Hours for this Month (Completed Sessions)
            $current_month = date('Y-m');
            $stmt_hours = $pdo->prepare("SELECT SUM(work_hours) FROM attendance WHERE employee_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?");
            $stmt_hours->execute([$employee_id, $current_month]);
            $completed_hours = $stmt_hours->fetchColumn() ?: 0;

            // Logic for Real-time Timer
            $is_active = false;
            $current_session_seconds = 0;
            if ($attendance && !$attendance['clock_out']) {
                $is_active = true;
                $start_time = strtotime($attendance['clock_in']);
                $current_session_seconds = time() - $start_time;
            }
            ?>

            <div class="stat-card">
                <div class="stat-title">Hours Worked (This Month)</div>
                <div class="stat-value" id="total-hours-display">
                    <?= floor($completed_hours) ?>h <?= round(($completed_hours - floor($completed_hours)) * 60) ?>m
                </div>
                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                    <?php if ($is_active): ?>
                        <span class="badge badge-approved" style="font-size: 0.7rem;">Live Tracking</span>
                    <?php else: ?>
                        Total Verified
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="table-container">
            <div style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                <h3 style="font-size: 1.1rem; font-weight: 600;">Quick Actions</h3>
            </div>
            <div
                style="padding: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <a href="leave_request.php" class="btn"
                    style="border: 1px solid var(--primary-color); color: var(--primary-color); text-align: center; text-decoration: none;">Request
                    Leave</a>
                <a href="profile.php" class="btn"
                    style="border: 1px solid var(--primary-color); color: var(--primary-color); text-align: center; text-decoration: none;">Update
                    Profile</a>
                <a href="payslip.php" class="btn"
                    style="border: 1px solid var(--primary-color); color: var(--primary-color); text-align: center; text-decoration: none;">Download
                    Payslip</a>
            </div>
        </section>
    </main>
</div>

<script>
    lucide.createIcons();

    // Real-time Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('real-time-clock').innerText = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Live Attendance Timer
    <?php if ($is_active): ?>
        let sessionSeconds = <?= $current_session_seconds ?>;
        let completedHours = <?= $completed_hours ?>;

        function updateTimer() {
            sessionSeconds++;

            // 1. Update "Since: ..." text if it exists
            // (Optional, simpler to just keep the start time static)

            // 2. Update Total Monthly Hours
            // Convert everything to seconds first
            let totalSeconds = (completedHours * 3600) + sessionSeconds;
            let h = Math.floor(totalSeconds / 3600);
            let m = Math.floor((totalSeconds % 3600) / 60);
            let s = totalSeconds % 60;

            document.getElementById('total-hours-display').innerText = `${h}h ${m}m ${s}s`;
        }
        setInterval(updateTimer, 1000);
    <?php endif; ?>
</script>
<?php include '../includes/footer.php'; ?>