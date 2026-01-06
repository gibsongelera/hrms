<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

$msg = "";
$error = "";

// Handle Add Employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $department = $_POST['department'];
    $job_role = $_POST['job_role'];
    $role_name = $_POST['role_name']; // Admin or Employee
    $base_salary = $_POST['base_salary'];

    try {
        $pdo->beginTransaction();

        // 1. Create User
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
        $stmt->execute([$role_name]);
        $role_id = $stmt->fetchColumn();

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role_id) VALUES (?, ?, ?)");
        $stmt->execute([$email, $hash, $role_id]);
        $user_id = $pdo->lastInsertId();

        // 2. Create Employee Linked to User
        $stmt = $pdo->prepare("INSERT INTO employees (user_id, first_name, last_name, job_role, department, base_salary, status) VALUES (?, ?, ?, ?, ?, ?, 'Active')");
        $stmt->execute([$user_id, $first_name, $last_name, $job_role, $department, $base_salary]);

        $pdo->commit();
        $msg = "Employee added successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            $error = "Email already exists.";
        } else {
            $error = "Error adding employee: " . $e->getMessage();
        }
    }
}

// Handle Edit Employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $job_role = $_POST['job_role'];
    $role_name = $_POST['role_name'];
    $base_salary = $_POST['base_salary'];

    try {
        $pdo->beginTransaction();

        // 1. Update Employee
        $stmt = $pdo->prepare("UPDATE employees SET first_name = ?, last_name = ?, job_role = ?, department = ?, base_salary = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $job_role, $department, $base_salary, $id]);

        // 2. Update User Link
        $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $user_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT id FROM roles WHERE role_name = ?");
        $stmt->execute([$role_name]);
        $role_id = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $user_id]);

        // Only update role if it exists
        if ($role_id) {
            $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$role_id, $user_id]);
        }

        $pdo->commit();
        $msg = "Employee updated successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            $error = "Email already exists.";
        } else {
            $error = "Error updating employee: " . $e->getMessage();
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Delete the user record, which cascades to employee and all other related tables
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM employees WHERE id = ?)");
        $stmt->execute([$id]);
        $msg = "Employee deleted successfully.";
    } catch (PDOException $e) {
        $error = "Failed to delete employee.";
    }
}

// Fetch Employees
$employees = $pdo->query("
    SELECT e.*, u.email, r.role_name 
    FROM employees e 
    JOIN users u ON e.user_id = u.id 
    JOIN roles r ON u.role_id = r.id 
    WHERE r.role_name IN ('Employee', 'Admin')
    ORDER BY e.first_name ASC
")->fetchAll();

// Fetch Departments & Roles for Form
$departments = $pdo->query("SELECT name FROM departments ORDER BY name ASC")->fetchAll();
$roles = $pdo->query("SELECT role_name FROM roles WHERE role_name IN ('Employee', 'Admin') ORDER BY role_name ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 1.5rem; font-weight: 700;">Employee Directory</h2>
            <button class="btn btn-primary" onclick="document.getElementById('add-modal').style.display='flex'">
                <i data-lucide="user-plus"></i> Add New Employee
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

        <section class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee Name</th>
                        <th>Email</th>
                        <th>Role / Dept</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if ($emp['profile_pic']): ?>
                                        <img src="../uploads/<?= $emp['profile_pic'] ?>"
                                            style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div
                                            style="width: 32px; height: 32px; background: #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: var(--primary-color);">
                                            <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="font-weight: 600;"><?= $emp['first_name'] ?>     <?= $emp['last_name'] ?></div>
                                </div>
                            </td>
                            <td style="font-size: 0.875rem; color: var(--text-muted);"><?= $emp['email'] ?></td>
                            <td>
                                <div style="font-size: 0.875rem; font-weight: 500;"><?= $emp['job_role'] ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $emp['department'] ?></div>
                            </td>
                            <td>
                                <span class="badge"
                                    style="background: <?= $emp['status'] == 'Active' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(239, 68, 68, 0.2)' ?>; color: <?= $emp['status'] == 'Active' ? '#10b981' : '#ef4444' ?>;">
                                    <?= $emp['status'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button onclick='openEditModal(<?= json_encode($emp) ?>)'
                                        class="btn-icon-action btn-icon-action-edit" title="Edit">
                                        <i data-lucide="edit-3"></i>
                                    </button>
                                    <button onclick="confirmDelete(<?= $emp['id'] ?>)"
                                        class="btn-icon-action btn-icon-action-danger" title="Delete">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>


    </main>
</div>

<!-- Add Employee Modal -->
<div id="add-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 600px; width: 90%; animation: slideUp 0.3s ease;">
        <h3 style="margin-bottom: 1.5rem;">Add New Employee</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">First Name</label>
                    <input type="text" name="first_name" class="input-field" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Last Name</label>
                    <input type="text" name="last_name" class="input-field" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Email</label>
                    <input type="email" name="email" class="input-field" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Password</label>
                    <input type="password" name="password" class="input-field" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Department</label>
                    <select name="department" class="input-field" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['name'] ?>"><?= $dept['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Job Role</label>
                    <input type="text" name="job_role" class="input-field" placeholder="e.g. Software Engineer"
                        required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">System Role</label>
                    <select name="role_name" class="input-field" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['role_name'] ?>"><?= $r['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem;">Base Salary (₱)</label>
                    <input type="number" name="base_salary" class="input-field" step="0.01" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'"
                    class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Employee Modal -->
<div id="edit-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 600px; width: 90%; animation: slideUp 0.3s ease;">
        <h3 style="margin-bottom: 1.5rem;">Edit Employee</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">First Name</label>
                    <input type="text" name="first_name" id="edit-first-name" class="input-field" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Last Name</label>
                    <input type="text" name="last_name" id="edit-last-name" class="input-field" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Email</label>
                    <input type="email" name="email" id="edit-email" class="input-field" required>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; opacity: 0.5;">Password (Not editable
                        here)</label>
                    <input type="text" class="input-field" disabled value="********">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Department</label>
                    <select name="department" id="edit-department" class="input-field" required>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['name'] ?>"><?= $dept['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">Job Role</label>
                    <input type="text" name="job_role" id="edit-job-role" class="input-field" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem;">System Role</label>
                    <select name="role_name" id="edit-role-name" class="input-field" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['role_name'] ?>"><?= $r['role_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem;">Base Salary (₱)</label>
                    <input type="number" name="base_salary" id="edit-base-salary" class="input-field" step="0.01"
                        required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                    class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Employee</button>
            </div>
        </form>
    </div>
</div>


<script>
    function openEditModal(emp) {
        document.getElementById('edit-id').value = emp.id;
        document.getElementById('edit-first-name').value = emp.first_name;
        document.getElementById('edit-last-name').value = emp.last_name;
        document.getElementById('edit-email').value = emp.email;
        document.getElementById('edit-department').value = emp.department;
        document.getElementById('edit-job-role').value = emp.job_role;
        document.getElementById('edit-role-name').value = emp.role_name;
        document.getElementById('edit-base-salary').value = emp.base_salary;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function confirmDelete(id) {
        showConfirm({
            title: 'Delete Employee?',
            message: 'Are you sure you want to permanently delete this employee? This will also remove their system account and all associated records.',
            confirmText: 'Delete Now',
            type: 'danger',
            link: '?delete=' + id
        });
    }

    // Handle automatic modal opening if ?add=1 is present
    window.addEventListener('load', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('add')) {
            document.getElementById('add-modal').style.display = 'flex';
        }
    });

    lucide.createIcons();
</script>

<?php include 'includes/footer.php'; ?>