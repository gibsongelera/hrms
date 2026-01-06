<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

// Handle Action (Approve/Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $status = $_GET['action'] == 'approve' ? 'Approved' : 'Rejected';

    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    // Update employee status if approved
    if ($status === 'Approved') {
        $stmt_emp = $pdo->prepare("UPDATE employees SET status = 'On Leave' WHERE id = (SELECT employee_id FROM leave_requests WHERE id = ?)");
        $stmt_emp->execute([$id]);
    }

    header("Location: leave_requests.php");
    exit;
}

// Fetch Requests
$requests = $pdo->query("
    SELECT lr.*, e.first_name, e.last_name, e.job_role 
    FROM leave_requests lr 
    JOIN employees e ON lr.employee_id = e.id 
    ORDER BY lr.applied_at DESC
")->fetchAll();

include 'includes/header.php';
?>
<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">Manage Leave Requests</h2>
        </header>

        <section class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= $r['first_name'] ?>     <?= $r['last_name'] ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $r['job_role'] ?></div>
                            </td>
                            <td><?= $r['type'] ?></td>
                            <td><?= $r['start_date'] ?> - <?= $r['end_date'] ?></td>
                            <td style="max-width: 200px; font-size: 0.875rem; color: var(--text-muted);"><?= $r['reason'] ?>
                            </td>
                            <td><span class="badge badge-<?= strtolower($r['status']) ?>"><?= $r['status'] ?></span></td>
                            <td>
                                <button onclick='openReviewModal(<?= json_encode($r) ?>)'
                                    class="btn-icon-action btn-icon-action-edit" title="Review Request">
                                    <i data-lucide="eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>

<!-- Review Leave Request Modal -->
<div id="review-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); z-index: 1000; align-items: center; justify-content: center;">
    <div class="glass-card"
        style="max-width: 550px; width: 90%; padding: 2.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem;">
            <div>
                <h3 id="rev-name" style="margin-bottom: 0.25rem; color: white; font-size: 1.5rem;">Employee Name</h3>
                <p id="rev-role" style="color: var(--text-muted); font-size: 0.9rem;">Job Role</p>
            </div>
            <div id="rev-status-badge"></div>
        </div>

        <div
            style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
            <div>
                <label
                    style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 600;">Leave
                    Type</label>
                <div id="rev-type" style="color: white; font-weight: 500;">Annual Leave</div>
            </div>
            <div>
                <label
                    style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 600;">Duration</label>
                <div id="rev-period" style="color: white; font-weight: 500;">Jan 01 - Jan 05</div>
            </div>
        </div>

        <div style="margin-bottom: 2.5rem;">
            <label
                style="display: block; font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.75rem; font-weight: 600;">Reason
                for Leave</label>
            <div id="rev-reason"
                style="color: #cbd5e1; line-height: 1.6; font-size: 0.95rem; background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 8px; border-left: 3px solid var(--primary-color);">
                -
            </div>
        </div>

        <div id="rev-actions" style="display: flex; gap: 1rem;">
            <button onclick="document.getElementById('review-modal').style.display='none'" class="btn btn-secondary"
                style="flex: 1;">Close</button>
            <div id="rev-decision-btns" style="display: flex; gap: 1rem; flex: 2;">
                <a id="rev-reject-link" href="#" class="btn btn-danger"
                    style="flex: 1; text-decoration: none;">Reject</a>
                <a id="rev-approve-link" href="#" class="btn btn-primary"
                    style="flex: 1; text-decoration: none; background: #10b981; border-color: #10b981;">Approve</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function openReviewModal(r) {
        document.getElementById('rev-name').innerText = r.first_name + ' ' + r.last_name;
        document.getElementById('rev-role').innerText = r.job_role;
        document.getElementById('rev-type').innerText = r.type + ' Leave';
        document.getElementById('rev-period').innerText = r.start_date + ' to ' + r.end_date;
        document.getElementById('rev-reason').innerText = r.reason;
        
        const badge = document.getElementById('rev-status-badge');
        badge.innerHTML = `<span class="badge badge-${r.status.toLowerCase()}">${r.status}</span>`;

        const decisionBtns = document.getElementById('rev-decision-btns');
        if (r.status === 'Pending') {
            decisionBtns.style.display = 'flex';
            document.getElementById('rev-approve-link').href = '?action=approve&id=' + r.id;
            document.getElementById('rev-reject-link').href = '?action=reject&id=' + r.id;
        } else {
            decisionBtns.style.display = 'none';
        }

        document.getElementById('review-modal').style.display = 'flex';
    }

    lucide.createIcons();
</script>
</body>

</html>