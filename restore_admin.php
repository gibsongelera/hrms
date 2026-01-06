<?php
/**
 * HRMS - Admin Recovery & Setup Utility
 * Date: December 29, 2025
 */

require_once 'includes/db_connection.php';

$title = "Admin System Health & Recovery";
$status_steps = [];
$error = "";
$success = false;

try {
    // 1. Check Table Existence
    $tables = $pdo->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($tables == 0) {
        throw new Exception("Core database tables are missing. Please import 'sql/database_setup.sql' first.");
    }
    $status_steps[] = ["Check Database Tables", "Ready", "success"];

    // 2. Ensure Admin Role
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = 'Admin'");
    $stmt->execute();
    $admin_role = $stmt->fetch();
    if (!$admin_role) {
        $pdo->exec("INSERT INTO roles (role_name) VALUES ('Admin')");
        $admin_role_id = $pdo->lastInsertId();
        $status_steps[] = ["Role Verification", "Admin role created", "warning"];
    } else {
        $admin_role_id = $admin_role['id'];
        $status_steps[] = ["Role Verification", "Admin role present", "success"];
    }

    // 3. Setup/Reset Admin User
    $admin_email = 'admin@hrms.com';
    $admin_pass = 'admin123';
    $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    $admin_user = $stmt->fetch();

    if (!$admin_user) {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role_id) VALUES (?, ?, ?)");
        $stmt->execute([$admin_email, $hashed_pass, $admin_role_id]);
        $user_id = $pdo->lastInsertId();

        // Match with employee record
        $stmt = $pdo->prepare("INSERT INTO employees (user_id, first_name, last_name, job_role, department, status) VALUES (?, 'System', 'Administrator', 'IT Administrator', 'IT Department', 'Active')");
        $stmt->execute([$user_id]);

        $status_steps[] = ["Admin Account", "Created: $admin_email", "success"];
    } else {
        // Reset password for existing admin
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role_id = ? WHERE email = ?");
        $stmt->execute([$hashed_pass, $admin_role_id, $admin_email]);
        $status_steps[] = ["Admin Account", "Verified & Password Synced", "success"];
    }

    $success = true;

} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg: #020617;
            --card: rgba(30, 41, 59, 0.7);
            --primary: #4f46e5;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --text: #f8fafc;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        .container {
            width: 100%;
            max-width: 500px;
            padding: 2rem;
            animation: fadeIn 0.8s ease-out;
        }

        .card {
            background: var(--card);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .icon-box {
            width: 64px;
            height: 64px;
            background: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 800;
        }

        p {
            color: #94a3b8;
            margin: 0.5rem 0 0;
            font-size: 0.95rem;
        }

        .status-list {
            margin-top: 2rem;
        }

        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .status-label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 100px;
            font-weight: 700;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .badge-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: var(--primary);
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            margin-top: 2rem;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="icon-box">
                    <i data-lucide="shield-check" style="color: white; width: 32px; height: 32px;"></i>
                </div>
                <h1>System Recovery</h1>
                <p>Ensuring Administrative Integrity</p>
            </div>

            <?php if ($error): ?>
                <div
                    style="background: rgba(239, 68, 68, 0.1); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--error); color: var(--error); font-size: 0.9rem; line-height: 1.5;">
                    <strong>Setup Blocked:</strong><br><?= $error ?>
                </div>
            <?php else: ?>
                <div class="status-list">
                    <?php foreach ($status_steps as $step): ?>
                        <div class="status-item">
                            <span class="status-label"><?= $step[0] ?></span>
                            <span class="status-badge badge-<?= $step[2] ?>"><?= $step[1] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div
                    style="margin-top: 2rem; padding: 1.25rem; background: rgba(16, 185, 129, 0.05); border-radius: 12px; border: 1px dashed var(--success); text-align: center;">
                    <p style="color: var(--success); font-weight: 600; margin: 0;">Admin Credentials Configured:</p>
                    <code
                        style="display: block; margin-top: 0.5rem; color: #fff; font-size: 1rem;">admin@hrms.com | admin123</code>
                </div>

                <a href="login.php" class="btn">Proceed to Login</a>
            <?php endif; ?>

            <div
                style="text-align: center; margin-top: 2rem; font-size: 0.7rem; color: #475569; letter-spacing: 0.1em; text-transform: uppercase;">
                HRMS Security Core &bull; 
            </div>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>

</html>
