<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

// 1. Attendance Stats (Late vs On Time)
// We'll just look at this month
$month_start = date('Y-m-01');
$stmt = $pdo->prepare("SELECT clock_in FROM attendance WHERE date >= ?");
$stmt->execute([$month_start]);
$attendance_data = $stmt->fetchAll();

$late_count = 0;
$on_time_count = 0;

foreach ($attendance_data as $record) {
    if (date('H:i', strtotime($record['clock_in'])) > '09:00') {
        $late_count++;
    } else {
        $on_time_count++;
    }
}

// 2. Department Distribution
$dept_stats = $pdo->query("SELECT department, COUNT(*) as count FROM employees GROUP BY department")->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Leave Stats
$leave_stats = $pdo->query("SELECT status, COUNT(*) as count FROM leave_requests GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">Reports & Analytics</h2>
            <p style="color: var(--text-muted);">Key metrics for <?= date('F Y') ?></p>
        </header>

        <!-- Charts Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">

            <!-- Attendance Chart -->
            <div class="glass-card" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Attendance Punctuality</h3>
                <div style="height: 250px;">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>

            <!-- Departments Chart -->
            <div class="glass-card" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Employees per Department</h3>
                <div style="height: 250px;">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>

            <!-- Leaves Chart -->
            <div class="glass-card" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 1rem; font-size: 1.1rem;">Leave Requests Status</h3>
                <div style="height: 250px;">
                    <canvas id="leaveChart"></canvas>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Common Options
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#94a3b8' } }
        },
        scales: {
            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
        }
    };

    // 1. Attendance Chart
    new Chart(document.getElementById('attendanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['On Time', 'Late'],
            datasets: [{
                data: [<?= $on_time_count ?>, <?= $late_count ?>],
                backgroundColor: ['#10b981', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: { ...commonOptions, scales: {} } // No scales for doughnut
    });

    // 2. Department Chart
    const depts = <?= json_encode(array_keys($dept_stats)) ?>;
    const deptCounts = <?= json_encode(array_values($dept_stats)) ?>;

    new Chart(document.getElementById('deptChart'), {
        type: 'bar',
        data: {
            labels: depts,
            datasets: [{
                label: 'Employees',
                data: deptCounts,
                backgroundColor: '#4f46e5',
                borderRadius: 5
            }]
        },
        options: commonOptions
    });

    // 3. Leave Chart
    const statuses = <?= json_encode(array_keys($leave_stats)) ?>;
    const leaveCounts = <?= json_encode(array_values($leave_stats)) ?>;

    new Chart(document.getElementById('leaveChart'), {
        type: 'pie',
        data: {
            labels: statuses,
            datasets: [{
                data: leaveCounts,
                backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                borderWidth: 0
            }]
        },
        options: { ...commonOptions, scales: {} }
    });
</script>