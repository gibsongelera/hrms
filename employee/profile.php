<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employee') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';
$employee_id = $_SESSION['employee_id'];
$user_id = $_SESSION['user_id'];
$msg = "";
$error = "";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updates_made = false;

    // 1. Handle Profile Picture Upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $fileTmp = $_FILES['profile_pic']['tmp_name'];
        $fileName = time() . '_' . $_FILES['profile_pic']['name'];

        if (move_uploaded_file($fileTmp, $uploadDir . $fileName)) {
            try {
                $stmt = $pdo->prepare("UPDATE employees SET profile_pic = ? WHERE id = ?");
                $stmt->execute([$fileName, $employee_id]);
                $updates_made = true;
            } catch (PDOException $e) {
                $error = "Image update failed: " . $e->getMessage();
            }
        } else {
            $error = "Failed to upload image.";
        }
    }

    // Fetch current data first to compare
    $stmt = $pdo->prepare("SELECT e.*, u.email FROM employees e JOIN users u ON e.user_id = u.id WHERE e.id = ?");
    $stmt->execute([$employee_id]);
    $em_current = $stmt->fetch();

    // 2. Handle Details Update (Name & Email)
    $first_name = $_POST['first_name'] ?? $em_current['first_name'];
    $last_name = $_POST['last_name'] ?? $em_current['last_name'];
    $email = $_POST['email'] ?? $em_current['email'];

    // Only update if changes detected
    if ($first_name !== $em_current['first_name'] || $last_name !== $em_current['last_name']) {
        try {
            $stmt = $pdo->prepare("UPDATE employees SET first_name = ?, last_name = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $employee_id]);
            $updates_made = true;
        } catch (PDOException $e) {
            $error = "Name update failed: " . $e->getMessage();
        }
    }

    if ($email !== $em_current['email']) {
        try {
            // Check if email taken
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $user_id]);
            if ($check->rowCount() > 0) {
                $error = "Email address is already in use.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $user_id]);
                $updates_made = true;
            }
        } catch (PDOException $e) {
            $error = "Email update failed.";
        }
    }

    // 3. Handle Password Change
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!empty($new_password) || !empty($confirm_password)) {
        if ($new_password === $confirm_password) {
            try {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt_user->execute([$hash, $user_id]);
                $updates_made = true;
            } catch (PDOException $e) {
                $error = "Password update failed.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    }

    if ($updates_made && empty($error)) {
        $msg = "Profile updated successfully!";
    } elseif (!$updates_made && empty($error) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Case where upload might have failed silently or no intended changes
    }
}

// Fetch (or Refetch) Current Data
$stmt = $pdo->prepare("
    SELECT e.*, u.email 
    FROM employees e 
    JOIN users u ON e.user_id = u.id 
    WHERE e.id = ?
");
$stmt->execute([$employee_id]);
$em = $stmt->fetch();

include '../includes/header.php';
?>

<div class="dashboard-container">
    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">
        <header style="margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">My Profile</h2>
            <p style="color: var(--text-muted);">Manage your personal account.</p>
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

        <!-- Merged Single Card Layout -->
        <section class="glass-card" style="max-width: 800px; margin: 0 auto;">
            <form method="POST" enctype="multipart/form-data">

                <!-- Header / Avatar Section -->
                <div
                    style="display: flex; flex-wrap: wrap; align-items: center; gap: 2rem; margin-bottom: 3rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 2rem;">
                    <div style="position: relative;">
                        <?php if (!empty($em['profile_pic'])): ?>
                            <img src="../uploads/<?= $em['profile_pic'] ?>" alt="Profile"
                                style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 4px solid var(--primary-color); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);">
                        <?php else: ?>
                            <div
                                style="width: 120px; height: 120px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 700; color: white; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3);">
                                <?= substr($em['first_name'], 0, 1) . substr($em['last_name'], 0, 1) ?>
                            </div>
                        <?php endif; ?>

                        <label for="profile_pic"
                            style="position: absolute; bottom: 5px; right: 5px; background: var(--surface); border: 1px solid var(--border-color); border-radius: 50%; padding: 8px; cursor: pointer; transition: all 0.2s;">
                            <i data-lucide="camera" style="width: 20px; height: 20px; color: var(--text-color);"></i>
                        </label>
                        <input type="file" name="profile_pic" id="profile_pic" style="display: none;"
                            onchange="this.form.submit()">
                    </div>

                    <div>
                        <h3 style="margin: 0 0 0.5rem 0; font-size: 1.75rem; font-weight: 700;">
                            <?= $em['first_name'] . ' ' . $em['last_name'] ?>
                        </h3>
                        <p style="margin: 0 0 0.5rem 0; color: var(--text-muted); font-size: 1rem;">
                            <?= htmlspecialchars($em['job_role']) ?>
                        </p>
                        <div style="display: flex; gap: 0.75rem; align-items: center;">
                            <span class="badge badge-approved"><?= $em['status'] ?></span>
                            <span
                                style="font-size: 0.85rem; color: var(--text-muted); background: rgba(255,255,255,0.05); padding: 2px 8px; border-radius: 4px;">ID:
                                <?= $em['id'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Personal Details Form -->
                <h4
                    style="margin-bottom: 1.5rem; color: var(--primary-color); padding-bottom: 0.5rem; font-size: 1.1rem;">
                    Account Details</h4>
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">First Name</label>
                        <input type="text" name="first_name" class="input-field" value="<?= $em['first_name'] ?>"
                            required>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Last Name</label>
                        <input type="text" name="last_name" class="input-field" value="<?= $em['last_name'] ?>"
                            required>
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Email Address</label>
                        <input type="email" name="email" class="input-field" value="<?= $em['email'] ?>" required>
                    </div>

                    <div>
                        <label
                            style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-muted);">Department
                            <span style="font-size: 0.8em; opacity: 0.7;">(Read Only)</span></label>
                        <div
                            style="padding: 0.75rem 1rem; background: rgba(255,255,255,0.03); border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); opacity: 0.7;">
                            <?= $em['department'] ?>
                        </div>
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label
                            style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: var(--text-muted);">Date
                            Hired <span style="font-size: 0.8em; opacity: 0.7;">(Read Only)</span></label>
                        <div
                            style="padding: 0.75rem 1rem; background: rgba(255,255,255,0.03); border-radius: 6px; border: 1px solid rgba(255,255,255,0.05); opacity: 0.7;">
                            <?= date('M d, Y', strtotime($em['hire_date'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <h4
                    style="margin-bottom: 1.5rem; color: var(--secondary-color); padding-bottom: 0.5rem; font-size: 1.1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 2rem;">
                    Security Settings</h4>

                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">New Password</label>
                        <input type="password" name="new_password" class="input-field"
                            placeholder="Enter only to change">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Confirm Password</label>
                        <input type="password" name="confirm_password" class="input-field"
                            placeholder="Confirm new password">
                    </div>
                </div>

                <div style="text-align: right;">
                    <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem;">Save Profile</button>
                </div>
            </form>
        </section>

        <?php include '../includes/footer.php'; ?>
    </main>
</div>