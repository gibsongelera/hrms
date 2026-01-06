<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

$msg = "";
$error = "";

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_settings') {
    try {
        $pdo->beginTransaction();

        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }

        $pdo->commit();
        $msg = "Global configuration synchronized successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Synchronization failed: " . $e->getMessage();
    }
}

// Fetch All Settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$settings_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include 'includes/header.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <span
                        style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 100px; font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em;">SYSTEM
                        v2.0</span>
                </div>
                <h2 style="font-size: 2rem; font-weight: 800; color: white;">Global Configuration</h2>
                <p style="color: var(--text-muted); font-size: 1rem;">Manage enterprise-wide parameters, attendance
                    protocols, and branding.</p>
            </div>
            <div style="text-align: right;">
                <p style="font-size: 0.8rem; color: #64748b; margin-bottom: 0.5rem;">System Health</p>
                <div
                    style="display: flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.1); padding: 0.5rem 1rem; border-radius: 10px; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <div
                        style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;">
                    </div>
                    <span
                        style="color: #10b981; font-weight: 700; font-size: 0.85rem;"><?= htmlspecialchars($settings_raw['system_status'] ?? 'Online') ?></span>
                </div>
            </div>
        </header>

        <?php if ($msg): ?>
            <div
                style="padding: 1.25rem; margin-bottom: 2rem; background: rgba(16, 185, 129, 0.1); border-left: 4px solid #10b981; color: #10b981; border-radius: 8px; display: flex; align-items: center; gap: 1rem; animation: slideIn 0.4s ease-out;">
                <i data-lucide="shield-check" style="width: 24px; height: 24px;"></i>
                <span style="font-weight: 500;"><?= $msg ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div
                style="padding: 1.25rem; margin-bottom: 2rem; background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444; color: #ef4444; border-radius: 8px; display: flex; align-items: center; gap: 1rem; animation: slideIn 0.4s ease-out;">
                <i data-lucide="alert-octagon" style="width: 24px; height: 24px;"></i>
                <span style="font-weight: 500;"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST"
            style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 2rem; align-items: start;">
            <input type="hidden" name="action" value="update_settings">

            <!-- Card 1: Identity & Branding -->
            <div class="glass-card" style="padding: 2.5rem; border: 1px solid rgba(255,255,255,0.05); height: 100%;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <div
                        style="width: 48px; height: 48px; background: linear-gradient(135deg, #4f46e5, #818cf8); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);">
                        <i data-lucide="swatchbook" style="color: white; width: 24px;"></i>
                    </div>
                    <div>
                        <h3 style="color: white; font-size: 1.25rem; font-weight: 700;">Identity & Branding</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Public presence and localization.</p>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #cbd5e1;">Enterprise
                        Name</label>
                    <input type="text" name="settings[company_name]"
                        value="<?= htmlspecialchars($settings_raw['company_name'] ?? 'HRMS') ?>" class="input-field"
                        placeholder="e.g. Acme Corp" required>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">Affects sidebars, headers, and
                        reports globally.</p>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #cbd5e1;">Primary
                        Currency</label>
                    <div style="position: relative;">
                        <input type="text" name="settings[currency]"
                            value="<?= htmlspecialchars($settings_raw['currency'] ?? '₱') ?>" class="input-field"
                            required style="padding-left: 3rem;">
                        <div
                            style="position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--primary);">
                            <i data-lucide="banknote" style="width: 18px;"></i>
                        </div>
                    </div>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #cbd5e1;">System
                        Operational Status</label>
                    <select name="settings[system_status]" class="input-field">
                        <option value="Online" <?= ($settings_raw['system_status'] ?? '') == 'Online' ? 'selected' : '' ?>>
                            🟢 Live / Online</option>
                        <option value="Maintenance" <?= ($settings_raw['system_status'] ?? '') == 'Maintenance' ? 'selected' : '' ?>>🟠 Maintenance Mode</option>
                        <option value="Offline" <?= ($settings_raw['system_status'] ?? '') == 'Offline' ? 'selected' : '' ?>>🔴 Full Lockdown</option>
                    </select>
                </div>
            </div>

            <!-- Card 2: Attendance Protocols -->
            <div class="glass-card" style="padding: 2.5rem; border: 1px solid rgba(255,255,255,0.05); height: 100%;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <div
                        style="width: 48px; height: 48px; background: linear-gradient(135deg, #f59e0b, #fbbf24); border-radius: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);">
                        <i data-lucide="fingerprint" style="color: white; width: 24px;"></i>
                    </div>
                    <div>
                        <h3 style="color: white; font-size: 1.25rem; font-weight: 700;">Attendance Protocols</h3>
                        <p style="color: var(--text-muted); font-size: 0.85rem;">Time tracking and penalty logic.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #cbd5e1;">Shift
                            Entry (Target)</label>
                        <input type="time" name="settings[late_threshold]"
                            value="<?= htmlspecialchars($settings_raw['late_threshold'] ?? '09:00') ?>"
                            class="input-field" required>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #cbd5e1;">Grace
                            Period (Mins)</label>
                        <input type="number" name="settings[grace_period]"
                            value="<?= htmlspecialchars($settings_raw['grace_period'] ?? '15') ?>" class="input-field"
                            min="0" required>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #cbd5e1;">Shift
                        Termination (Automatic Out)</label>
                    <input type="time" name="settings[shift_end]"
                        value="<?= htmlspecialchars($settings_raw['shift_end'] ?? '18:00') ?>" class="input-field"
                        required>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.5rem;">The point beyond which work hours
                        are capped or flagged.</p>
                </div>

                <div
                    style="background: rgba(255,158,11,0.05); padding: 1.25rem; border-radius: 12px; border: 1px solid rgba(245, 158, 11, 0.1);">
                    <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                        <i data-lucide="info" style="color: #f59e0b; width: 20px; flex-shrink: 0;"></i>
                        <p style="font-size: 0.8rem; color: #f59e0b; line-height: 1.5; margin: 0;">
                            <strong>Current Logic:</strong> Employees have until <span
                                style="font-weight: 800;"><?= date('h:i A', strtotime($settings_raw['late_threshold'] ?? '09:00')) ?></span>
                            + <?= $settings_raw['grace_period'] ?? '15' ?> mins to be marked "Present".
                        </p>
                    </div>
                </div>
            </div>

            <!-- Global Footer / Save -->
            <div style="grid-column: span 2; display: flex; justify-content: center; margin-top: 1rem;">
                <button type="submit" class="btn btn-primary"
                    style="padding: 1.25rem 4rem; border-radius: 100px; display: flex; align-items: center; gap: 1rem; font-size: 1.1rem; box-shadow: 0 20px 40px rgba(79, 70, 229, 0.4);">
                    <i data-lucide="save" style="width: 22px;"></i>
                    Sync Global Parameters
                </button>
            </div>
        </form>

    </main>
</div>

<style>
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<?php include 'includes/footer.php'; ?>