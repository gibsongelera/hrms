<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch Notifications
$pending_leaves = 0;
try {
    global $pdo;
    if (isset($pdo)) {
        $pending_leaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
    }
} catch (Exception $e) {
    $pending_leaves = 0;
}
?>
<aside class="admin-sidebar">
    <div class="admin-logo">
        <div
            style="width: 32px; height: 32px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
            <i data-lucide="shield" style="color: white; width: 20px;"></i>
        </div>
        <span><?= htmlspecialchars($sys_settings['company_name'] ?? 'HRMS') ?></span>
    </div>

    <div class="admin-nav-group">Overview</div>
    <a href="dashboard.php" class="admin-nav-item <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
        <i data-lucide="layout-grid"></i> Dashboard
    </a>

    <div class="admin-nav-group">Workforce</div>
    <a href="employees.php" class="admin-nav-item <?= $current_page == 'employees.php' ? 'active' : '' ?>">
        <i data-lucide="users"></i> Employees
    </a>
    <a href="departments.php" class="admin-nav-item <?= $current_page == 'departments.php' ? 'active' : '' ?>">
        <i data-lucide="building-2"></i> Departments
    </a>

    <div class="admin-nav-group">Operations</div>
    <a href="attendance.php" class="admin-nav-item <?= $current_page == 'attendance.php' ? 'active' : '' ?>">
        <i data-lucide="clock"></i> Attendance
    </a>
    <a href="leave_requests.php" class="admin-nav-item <?= $current_page == 'leave_requests.php' ? 'active' : '' ?>">
        <i data-lucide="calendar-check"></i>
        <span>Leave Requests</span>
        <span class="nav-badge" title="<?= $pending_leaves ?> pending requests"
            style="background: #ef4444; color: white; min-width: 18px; height: 18px; border-radius: 50%; display: <?= $pending_leaves > 0 ? 'flex' : 'none' ?>; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: bold; margin-left: auto; box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); animation: pulse-red 2s infinite;">
            <?= $pending_leaves ?>
        </span>
    </a>
    <a href="payroll.php" class="admin-nav-item <?= $current_page == 'payroll.php' ? 'active' : '' ?>">
        <i data-lucide="banknote"></i> Payroll
    </a>

    <div class="admin-nav-group">Insights</div>
    <a href="reports.php" class="admin-nav-item <?= $current_page == 'reports.php' ? 'active' : '' ?>">
        <i data-lucide="bar-chart-2"></i> Reports
    </a>

    <div style="margin-top: auto;">
        <a href="settings.php" class="admin-nav-item <?= $current_page == 'settings.php' ? 'active' : '' ?>">
            <i data-lucide="settings"></i> Settings
        </a>
        <a href="../logout.php" class="admin-nav-item" style="color: #ef4444;">
            <i data-lucide="log-out"></i> Logout
        </a>
    </div>
</aside>