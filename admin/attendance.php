<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

$msg = "";
$error = "";

// Fetch Settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$late_threshold = $settings['late_threshold'] ?? '09:00';
$grace_period = $settings['grace_period'] ?? '15';

// Handle Edit/Update Attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update') {
    $id = $_POST['id'];
    $clock_in = $_POST['clock_in'];
    $clock_out = !empty($_POST['clock_out']) ? $_POST['clock_out'] : NULL;
    $date = $_POST['date']; // Usually shouldn't change, but good to have

    // Calculate Work Hours
    $work_hours = 0;
    if ($clock_in && $clock_out) {
        $t1 = strtotime($clock_in);
        $t2 = strtotime($clock_out);
        $diff = $t2 - $t1;
        $work_hours = round($diff / 3600, 2);
    }

    try {
        $stmt = $pdo->prepare("UPDATE attendance SET clock_in = ?, clock_out = ?, date = ?, work_hours = ? WHERE id = ?");
        $stmt->execute([$clock_in, $clock_out, $date, $work_hours, $id]);
        $msg = "Attendance record updated successfully.";
    } catch (PDOException $e) {
        $error = "Failed to update record: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Record deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting record.";
    }
}

// Filters
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_emp = $_GET['employee_id'] ?? '';

// Build Query
$sql = "SELECT a.*, e.first_name, e.last_name, e.profile_pic 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        WHERE 1=1";
$params = [];

if ($filter_date) {
    $sql .= " AND a.date = ?";
    $params[] = $filter_date;
}
if ($filter_emp) {
    $sql .= " AND a.employee_id = ?";
    $params[] = $filter_emp;
}

$sql .= " ORDER BY a.clock_in DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Fetch Employees for Filter
$employees = $pdo->query("SELECT id, first_name, last_name FROM employees ORDER BY first_name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">Attendance Management</h2>
            <p style="color: var(--text-muted);">Monitor and correct employee attendance.</p>
        </header>

        <?php if ($msg): ?>
            <div
                style="padding: 1rem; margin-bottom: 1.5rem; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; border-radius: 8px;">
                <i data-lucide="check-circle" style="vertical-align: middle; margin-right: 8px; width: 18px;"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <section class="glass-card" style="padding: 1.5rem; margin-bottom: 2rem;">
            <form method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Filter by Date</label>
                    <input type="date" name="date" class="input-field" value="<?= $filter_date ?>"
                        style="margin-bottom: 0;">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem;">Filter by
                        Employee</label>
                    <select name="employee_id" class="input-field" style="margin-bottom: 0;">
                        <option value="">All Employees</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $filter_emp == $emp['id'] ? 'selected' : '' ?>>
                                <?= $emp['first_name'] . ' ' . $emp['last_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height: 52px;"><i data-lucide="filter"></i>
                    Filter</button>
                <a href="attendance.php" class="btn"
                    style="height: 52px; background: rgba(255,255,255,0.1); color: white;">Reset</a>
            </form>
        </section>

        <!-- Records Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Work Hours</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance_records)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-muted);">No records
                                found for this criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if ($record['profile_pic']): ?>
                                            <img src="../uploads/<?= $record['profile_pic'] ?>"
                                                style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                        <?php else: ?>
                                            <div
                                                style="width: 32px; height: 32px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold;">
                                                <?= substr($record['first_name'], 0, 1) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?= $record['first_name'] . ' ' . $record['last_name'] ?>
                                    </div>
                                </td>
                                <td><?= date('M d, Y', strtotime($record['date'])) ?></td>
                                <td style="color: #10b981;"><?= date('h:i A', strtotime($record['clock_in'])) ?></td>
                                <td style="color: #ef4444;">
                                    <?= $record['clock_out'] ? date('h:i A', strtotime($record['clock_out'])) : '<span class="badge badge-pending">Active</span>' ?>
                                </td>
                                <td><?= $record['work_hours'] ? $record['work_hours'] . ' hrs' : '-' ?></td>
                                <td>
                                    <?php
                                    $status = 'Present';
                                    $threshold_time = strtotime($record['date'] . ' ' . $late_threshold);
                                    $grace_threshold = $threshold_time + ($grace_period * 60);
                                    if (strtotime($record['clock_in']) > $grace_threshold)
                                        $status = 'Late';
                                    echo "<span class='badge " . ($status == 'Late' ? 'badge-rejected' : 'badge-approved') . "'>$status</span>";
                                    ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <button onclick="openEditModal(<?= htmlspecialchars(json_encode($record)) ?>)"
                                            class="btn-icon-action btn-icon-action-edit" title="Edit">
                                            <i data-lucide="edit-3"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?= $record['id'] ?>)"
                                            class="btn-icon-action btn-icon-action-danger" title="Delete">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </div>
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

<!-- Edit Modal -->
<div id="edit-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 500px; width: 90%; animation: slideUp 0.3s ease;">
        <h3 style="margin-bottom: 1.5rem;">Edit Attendance Record</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit-id">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Date</label>
                <input type="date" name="date" id="edit-date" class="input-field" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Clock In</label>
                    <input type="datetime-local" name="clock_in" id="edit-clock-in" class="input-field" step="1"
                        required>
                </div>
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Clock Out</label>
                    <input type="datetime-local" name="clock_out" id="edit-clock-out" class="input-field" step="1">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                    class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>


<script>
    function confirmDelete(id) {
        showConfirm({
            title: 'Delete Attendance Record?',
            message: 'Are you sure you want to permanently delete this attendance entry? This action cannot be undone.',
            confirmText: 'Delete Record',
            type: 'danger',
            link: '?delete=' + id
        });
    }

    function openEditModal(record) {
        document.getElementById('edit-id').value = record.id;
        document.getElementById('edit-date').value = record.date;

        // Format datetime-local string (YYYY-MM-DDTHH:MM)
        const formatDateTime = (dateStr) => {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
            return date.toISOString().slice(0, 16);
        };

        // We need to handle the conversion carefully as PHP sends 'YYYY-MM-DD HH:MM:SS'
        // Simplest is to just replace ' ' with 'T' for local inputs provided valid ISO format
        document.getElementById('edit-clock-in').value = record.clock_in.replace(' ', 'T');
        document.getElementById('edit-clock-out').value = record.clock_out ? record.clock_out.replace(' ', 'T') : '';

        document.getElementById('edit-modal').style.display = 'flex';
    }
</script>