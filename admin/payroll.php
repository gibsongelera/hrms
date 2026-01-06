<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

$msg = "";
$error = "";

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM payroll WHERE id = ?");
    $stmt->execute([$id]);
    $msg = "Payroll record deleted.";
}

// Handle Process Payroll (Form Submission)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create') {
    $employee_id = $_POST['employee_id'];
    $month_year = $_POST['month_year'];
    $base_salary = $_POST['base_salary'];
    $deductions = $_POST['deductions'];
    $bonus = $_POST['bonus']; // We need to add this column to DB later if we want to persist it, or just add to net pay

    // Simple Calculation
    $net_pay = $base_salary + $bonus - $deductions;
    $payment_date = date('Y-m-d');

    try {
        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM payroll WHERE employee_id = ? AND month_year = ?");
        $check->execute([$employee_id, $month_year]);
        if ($check->fetch()) {
            $error = "Payroll for this employee and month already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, month_year, base_salary, deductions, net_pay, payment_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$employee_id, $month_year, $base_salary, $deductions, $net_pay, $payment_date]);
            $msg = "Payroll processed and payslip generated successfully.";
        }
    } catch (PDOException $e) {
        $error = "Error processing payroll: " . $e->getMessage();
    }
}

// Fetch Payroll History
$sql = "SELECT p.*, e.first_name, e.last_name, e.profile_pic 
        FROM payroll p 
        JOIN employees e ON p.employee_id = e.id 
        ORDER BY p.payment_date DESC, p.id DESC";
$payroll_history = $pdo->query($sql)->fetchAll();

// Fetch Employees for Modal
$employees = $pdo->query("SELECT id, first_name, last_name, base_salary FROM employees WHERE status='Active'")->fetchAll();

include 'includes/header.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700;">Payroll Management</h2>
                <p style="color: var(--text-muted);">Process salaries and manage payslips.</p>
            </div>
            <button onclick="openProcessModal()" class="btn btn-primary">
                <i data-lucide="banknote"></i> Process New Payroll
            </button>
        </header>

        <?php if ($msg): ?>
            <div
                style="padding: 1rem; margin-bottom: 1.5rem; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; border-radius: 8px;">
                <i data-lucide="check-circle" style="vertical-align: middle; margin-right: 8px; width: 18px;"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div
                style="padding: 1rem; margin-bottom: 1.5rem; background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; border-radius: 8px;">
                <i data-lucide="alert-circle" style="vertical-align: middle; margin-right: 8px; width: 18px;"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Payroll History Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Month</th>
                        <th>Base Salary</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Date Processed</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payroll_history)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No payroll
                                records found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payroll_history as $p): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if ($p['profile_pic']): ?>
                                            <img src="../uploads/<?= $p['profile_pic'] ?>"
                                                style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div
                                                style="width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                                <?= substr($p['first_name'], 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?= $p['first_name'] . ' ' . $p['last_name'] ?>
                                    </div>
                                </td>
                                <td><?= date('F Y', strtotime($p['month_year'])) ?></td>
                                <td>₱<?= number_format($p['base_salary'], 2) ?></td>
                                <td style="color: #ef4444;">-₱<?= number_format($p['deductions'], 2) ?></td>
                                <td style="font-weight: 700; color: #10b981;">₱<?= number_format($p['net_pay'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                                <td>
                                    <button onclick="showConfirm({
                                        title: 'Delete Payroll Record?',
                                        message: 'Are you sure you want to permanently delete this payroll entry? This cannot be undone.',
                                        confirmText: 'Delete Record',
                                        type: 'danger',
                                        link: '?delete=<?= $p['id'] ?>'
                                    })" class="btn"
                                        style="padding: 0.5rem; background: transparent; color: #ef4444; cursor: pointer; border: none;">
                                        <i data-lucide="trash-2" style="width: 18px; height: 18px;"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>
</div>

<!-- Process Payroll Modal -->
<div id="process-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 500px; width: 90%; animation: slideUp 0.3s ease;">
        <h3 style="margin-bottom: 1.5rem;">Process Payroll</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Select Employee</label>
                <select name="employee_id" id="employee-select" class="input-field" required onchange="updateSalary()">
                    <option value="">-- Choose Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" data-salary="<?= $emp['base_salary'] ?>">
                            <?= $emp['first_name'] . ' ' . $emp['last_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Month</label>
                    <input type="month" name="month_year" class="input-field" value="<?= date('Y-m') ?>" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Base Salary (₱)</label>
                    <input type="number" name="base_salary" id="base_salary" class="input-field" step="0.01" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Bonus (₱ +)</label>
                    <input type="number" name="bonus" class="input-field" value="0.00" step="0.01">
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Deductions (₱ -)</label>
                    <input type="number" name="deductions" class="input-field" value="0.00" step="0.01">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="document.getElementById('process-modal').style.display='none'"
                    class="btn"
                    style="background: transparent; border: 1px solid var(--border-color); color: var(--text-muted);">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Payslip</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openProcessModal() {
        document.getElementById('process-modal').style.display = 'flex';
    }

    function updateSalary() {
        const select = document.getElementById('employee-select');
        const salary = select.options[select.selectedIndex].getAttribute('data-salary');
        document.getElementById('base_salary').value = salary || '';
    }
</script>