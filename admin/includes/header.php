<?php
if (isset($pdo)) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $sys_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal | <?= htmlspecialchars($sys_settings['company_name'] ?? 'HRMS') ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?= time() ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        function checkNotifications() {
            fetch('api_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const badge = document.querySelector('.nav-badge');
                        const dashboardValue = document.getElementById('pending-requests-count');

                        if (data.pending_leaves > 0) {
                            if (badge) {
                                badge.innerText = data.pending_leaves;
                                badge.style.display = 'flex';
                            }
                        } else if (badge) {
                            badge.style.display = 'none';
                        }

                        if (dashboardValue) {
                            dashboardValue.innerText = data.pending_leaves;
                        }
                    }
                })
                .catch(err => console.error('Notification check failed:', err));
        }

        setInterval(checkNotifications, 30000);

        // Global Confirmation Handler
        function showConfirm(options) {
            const modal = document.getElementById('global-confirm-modal');
            const titleEl = document.getElementById('global-confirm-title');
            const msgEl = document.getElementById('global-confirm-msg');
            const confirmBtn = document.getElementById('global-confirm-btn');

            titleEl.innerText = options.title || 'Are you sure?';
            msgEl.innerText = options.message || 'This action cannot be undone.';
            confirmBtn.innerText = options.confirmText || 'Confirm';

            // Remove previous classes and set based on type
            confirmBtn.className = 'btn ' + (options.type === 'danger' ? 'btn-danger' : 'btn-primary');
            confirmBtn.style.flex = '1';
            confirmBtn.style.textDecoration = 'none';
            confirmBtn.style.textAlign = 'center';

            if (options.link) {
                confirmBtn.onclick = null;
                confirmBtn.href = options.link;
            } else if (options.onConfirm) {
                confirmBtn.href = 'javascript:void(0)';
                confirmBtn.onclick = () => {
                    options.onConfirm();
                    closeConfirm();
                };
            }

            modal.style.display = 'flex';
        }

        function closeConfirm() {
            document.getElementById('global-confirm-modal').style.display = 'none';
        }
    </script>
</head>

<body>
    <div class="admin-bg"></div>

    <!-- Centralized Premium Confirmation Modal -->
    <div id="global-confirm-modal"
        style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(10px); z-index: 10000; align-items: center; justify-content: center; padding: 1rem;">
        <div class="glass-card"
            style="max-width: 450px; width: 100%; text-align: center; padding: 2.5rem; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); animation: modalZoom 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div
                style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                <i data-lucide="alert-triangle" style="width: 32px; height: 32px; color: #ef4444;"></i>
            </div>
            <h3 id="global-confirm-title"
                style="margin-bottom: 0.75rem; color: white; font-size: 1.5rem; font-weight: 700;">Critical Action</h3>
            <p id="global-confirm-msg" style="color: rgba(255,255,255,0.6); margin-bottom: 2rem; line-height: 1.6;">Are
                you sure you want to proceed?</p>
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button onclick="closeConfirm()" class="btn btn-secondary"
                    style="flex: 1; height: 48px;">Cancel</button>
                <a id="global-confirm-btn" href="#" class="btn btn-danger"
                    style="flex: 1; height: 48px; display: flex; align-items: center; justify-content: center;">Confirm</a>
            </div>
        </div>
    </div>