<?php
// sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';

// Fetch Notifications if Admin
$pending_leaves = 0;
if ($role === 'Admin' && isset($pdo)) {
    try {
        $pending_leaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
    } catch (PDOException $e) {
        $pending_leaves = 0;
    }
}
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <i data-lucide="shield-check"></i>
        <span><?= htmlspecialchars($sys_settings['company_name'] ?? 'HRMS') ?></span>
    </div>
    <ul class="sidebar-nav">
        <?php if ($role === 'Admin'): ?>
            <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <i data-lucide="layout-dashboard"></i> Dashboard</a>
            </li>
            <li><a href="employees.php" class="<?= $current_page == 'employees.php' ? 'active' : '' ?>">
                    <i data-lucide="users"></i> Employees</a>
            </li>
            <li><a href="attendance.php" class="<?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                    <i data-lucide="clock"></i> Attendance</a>
            </li>
            <li><a href="payroll.php" class="<?= $current_page == 'payroll.php' ? 'active' : '' ?>">
                    <i data-lucide="banknote"></i> Payroll</a>
            </li>
            <li><a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>">
                    <i data-lucide="bar-chart-2"></i> Reports</a>
            </li>
            <li><a href="departments.php" class="<?= $current_page == 'departments.php' ? 'active' : '' ?>">
                    <i data-lucide="building-2"></i> Departments</a>
            </li>
            <li><a href="leave_requests.php" class="<?= $current_page == 'leave_requests.php' ? 'active' : '' ?>">
                    <i data-lucide="calendar-days"></i>
                    <span>Leave Requests</span>
                    <?php if ($pending_leaves > 0): ?>
                        <span class="nav-badge"><?= $pending_leaves ?></span>
                    <?php endif; ?>
                </a></li>
        <?php else: ?>
            <li><a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <i data-lucide="layout-dashboard"></i> My Dashboard</a>
            </li>
            <li><a href="attendance.php" class="<?= $current_page == 'attendance.php' ? 'active' : '' ?>">
                    <i data-lucide="clock"></i> Attendance</a>
            </li>
            <li><a href="leave_request.php" class="<?= $current_page == 'leave_request.php' ? 'active' : '' ?>">
                    <i data-lucide="calendar-plus"></i> Request Leave</a>
            </li>
            <li><a href="payslip.php" class="<?= $current_page == 'payslip.php' ? 'active' : '' ?>">
                    <i data-lucide="file-text"></i> My Payslips</a>
            </li>
            <li><a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <i data-lucide="user"></i> My Profile</a>
            </li>
        <?php endif; ?>
        <li style="margin-top: auto;"><a href="../logout.php"><i data-lucide="log-out"></i> Logout</a></li>
    </ul>
</div>