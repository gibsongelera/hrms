<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';
$employee_id = $_SESSION['employee_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = $_POST['reason'];

    $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_id, type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$employee_id, $type, $start_date, $end_date, $reason])) {
        $_SESSION['msg'] = "Leave request submitted successfully!";
        header("Location: leave_request.php");
        exit;
    } else {
        $msg = "Failed to submit request.";
    }
}

$msg = isset($_SESSION['msg']) ? $_SESSION['msg'] : '';
unset($_SESSION['msg']);

// Fetch Status tracker
$requests = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY applied_at DESC");
$requests->execute([$employee_id]);
$all_requests = $requests->fetchAll();

include '../includes/header.php';
?>
<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">Request Leave</h2>
        </header>

        <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2rem;">
            <!-- Form -->
            <section class="stat-card" style="height: fit-content;">
                <h3 style="margin-bottom: 1.5rem;">New Request</h3>
                <?php if ($msg): ?>
                    <p style="margin-bottom: 1rem; color: #10b981;"><?= $msg ?></p>
                <?php endif; ?>
                <form method="POST" onsubmit="return validateDates()">
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Leave Type</label>
                        <select name="type" class="input-field" required>
                            <option value="Annual">Annual Leave (Vacation)</option>
                            <option value="Sick">Sick Leave</option>
                            <option value="Casual">Casual / Personal</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">From Date</label>
                            <input type="date" name="start_date" id="start_date" class="input-field" required onchange="calculateDays()">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">To Date</label>
                            <input type="date" name="end_date" id="end_date" class="input-field" required onchange="calculateDays()">
                        </div>
                    </div>

                    <p id="duration-display" style="margin-top: -1rem; margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--primary-color); font-weight: 600; display: none;">
                        Duration: <span id="days-count">0</span> day(s)
                    </p>

                    <div style="margin-bottom: 2rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Leave</label>
                        <textarea name="reason" class="input-field" style="height: 120px; resize: none;" 
                            placeholder="Please provide a brief reason..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 1rem;">
                        Submit Request
                    </button>
                </form>

                <script>
                    function calculateDays() {
                        const start = new Date(document.getElementById('start_date').value);
                        const end = new Date(document.getElementById('end_date').value);
                        const display = document.getElementById('duration-display');
                        const countSpan = document.getElementById('days-count');

                        if (!isNaN(start) && !isNaN(end)) {
                            // Calculate difference in time
                            const diffTime = end - start;
                            // Calculate difference in days (add 1 to include the start day)
                            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; 

                            if (diffDays > 0) {
                                countSpan.innerText = diffDays;
                                display.style.display = 'block';
                                display.style.color = 'var(--primary-color)';
                            } else {
                                countSpan.innerText = "Invalid Range";
                                display.style.display = 'block';
                                display.style.color = '#ef4444';
                            }
                        } else {
                            display.style.display = 'none';
                        }
                    }

                    function validateDates() {
                        const start = new Date(document.getElementById('start_date').value);
                        const end = new Date(document.getElementById('end_date').value);
                        if (end < start) {
                            alert("End Date cannot be before Start Date.");
                            return false;
                        }
                        return true;
                    }
                </script>
            </section>

            <!-- Tracker -->
            <section class="table-container">
                <div style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9;">
                    <h3 style="font-size: 1.1rem; font-weight: 600;">Status Tracker</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Dates</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($all_requests as $req): ?>
                        <tr>
                            <td><?= $req['type'] ?></td>
                            <td style="font-size: 0.875rem;"><?= $req['start_date'] ?> to <?= $req['end_date'] ?></td>
                            <td><span class="badge badge-<?= strtolower($req['status']) ?>"><?= $req['status'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($all_requests)): ?>
                            <tr><td colspan="3" style="text-align: center; padding: 1.5rem; color: var(--text-muted);">No history found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
