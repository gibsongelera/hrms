<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

// Fetch Summary Stats
$stats = [
    'total_employees' => $pdo->query("SELECT COUNT(e.id) FROM employees e JOIN users u ON e.user_id = u.id JOIN roles r ON u.role_id = r.id WHERE e.status = 'Active' AND r.role_name IN ('Employee', 'Admin')")->fetchColumn(),
    'on_leave' => $pdo->query("SELECT COUNT(e.id) FROM employees e JOIN users u ON e.user_id = u.id JOIN roles r ON u.role_id = r.id WHERE e.status = 'On Leave' AND r.role_name IN ('Employee', 'Admin')")->fetchColumn(),
    'pending_requests' => $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn(),
    'new_this_month' => $pdo->query("SELECT COUNT(e.id) FROM employees e JOIN users u ON e.user_id = u.id JOIN roles r ON u.role_id = r.id WHERE e.hire_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND r.role_name IN ('Employee', 'Admin')")->fetchColumn(),
    'upcoming_tomorrow' => $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE start_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) AND status = 'Approved'")->fetchColumn()
];

// Fetch Recent Leave Requests
$leave_requests = $pdo->query("
    SELECT lr.*, e.first_name, e.last_name 
    FROM leave_requests lr 
    JOIN employees e ON lr.employee_id = e.id 
    ORDER BY lr.applied_at DESC LIMIT 5
")->fetchAll();

include 'includes/header.php';
?>
<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700;">Admin Dashboard</h2>
                <p style="color: var(--text-muted);">Welcome back, <?= explode('@', $_SESSION['email'])[0] ?></p>
            </div>
            <div class="header-actions">
                <a href="employees.php?add=1" class="btn btn-primary" style="text-decoration: none;">
                    <i data-lucide="plus" style="width: 16px; height: 16px; vertical-align: middle;"></i> Add Employee
                </a>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-title">Total Employees</div>
                <div class="stat-value"><?= $stats['total_employees'] ?></div>
                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #10b981;">+<?= $stats['new_this_month'] ?>
                    this month</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">On Leave Today</div>
                <div class="stat-value"><?= $stats['on_leave'] ?></div>
                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #f59e0b;">Upcoming:
                    <?= $stats['upcoming_tomorrow'] ?> tomorrow
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Pending Requests</div>
                <div class="stat-value" id="pending-requests-count"><?= $stats['pending_requests'] ?></div>
                <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #ef4444;">
                    <?= $stats['pending_requests'] > 0 ? 'Action required' : 'All caught up' ?>
                </div>
            </div>
        </section>

        <section class="table-container">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 1.1rem; font-weight: 600;">Recent Leave Requests</h3>
                <a href="leave_requests.php"
                    style="color: var(--primary-color); font-size: 0.875rem; text-decoration: none;">View All</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leave_requests)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">No pending
                                requests</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leave_requests as $lr): ?>
                            <tr>
                                <td><?= $lr['first_name'] . ' ' . $lr['last_name'] ?></td>
                                <td><?= $lr['type'] ?></td>
                                <td><?= date('M d', strtotime($lr['start_date'])) ?> -
                                    <?= date('M d', strtotime($lr['end_date'])) ?>
                                </td>
                                <td><span class="badge badge-<?= strtolower($lr['status']) ?>"><?= $lr['status'] ?></span></td>
                                <td>
                                    <a href="leave_requests.php" class="btn btn-primary"
                                        style="padding: 0.4rem 0.8rem; font-size: 0.75rem; text-decoration: none;">Review</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>