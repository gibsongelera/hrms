<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

require_once '../includes/db_connection.php';

$msg = "";
$error = "";

// Handle Add Department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $description]);
        $msg = "Department added successfully!";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Department name already exists.";
        } else {
            $error = "Failed to add department: " . $e->getMessage();
        }
    }
}

// Handle Edit Department
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'edit') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];

    try {
        $stmt = $pdo->prepare("UPDATE departments SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $description, $id]);
        $msg = "Department updated successfully!";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "Department name already exists.";
        } else {
            $error = "Failed to update department: " . $e->getMessage();
        }
    }
}

// Handle Delete Department
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        $msg = "Department deleted successfully.";
    } catch (PDOException $e) {
        $error = "Cannot delete department. It may have employees assigned.";
    }
}

// Fetch Departments
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name ASC");
$departments = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <header style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="font-size: 1.5rem; font-weight: 700;">Departments</h2>
                <p style="color: var(--text-muted);">Manage company organizational structure.</p>
            </div>
            <button onclick="document.getElementById('add-modal').style.display='flex'" class="btn btn-primary">
                <i data-lucide="plus-circle"></i> Add Department
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

        <!-- Departments Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($departments as $dept): ?>
                <div class="glass-card" style="padding: 2rem; display: flex; flex-direction: column; height: 100%;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                        <h3 style="font-size: 1.25rem; font-weight: 700; color: white;">
                            <?= htmlspecialchars($dept['name']) ?>
                        </h3>
                        <div style="display: flex; gap: 8px;">
                            <button onclick='openEditModal(<?= json_encode($dept) ?>)'
                                class="btn-icon-action btn-icon-action-edit" title="Edit">
                                <i data-lucide="edit-3"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $dept['id'] ?>)"
                                class="btn-icon-action btn-icon-action-danger" title="Delete">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </div>
                    </div>
                    <p style="color: var(--text-muted); flex-grow: 1; margin-bottom: 1.5rem; line-height: 1.6;">
                        <?= htmlspecialchars($dept['description']) ?>
                    </p>
                    <div
                        style="display: flex; align-items: center; gap: 0.5rem; color: var(--accent); font-size: 0.875rem; font-weight: 500;">
                        <i data-lucide="users" style="width: 16px;"></i>
                        <span>
                            <?php
                            // Count employees in this dept (simple approximation by name for now)
                            $count = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE department = ?");
                            $count->execute([$dept['name']]);
                            echo $count->fetchColumn() . " Employees";
                            ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>
</div>

<!-- Add Department Modal -->
<div id="add-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 500px; width: 90%; animation: slideUp 0.3s ease;">
        <h3 style="margin-bottom: 1.5rem;">Add New Department</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Department Name</label>
                <input type="text" name="name" class="input-field" required placeholder="e.g. Research & Development">
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Description</label>
                <textarea name="description" class="input-field" rows="3" required
                    placeholder="Describe the department's function..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="document.getElementById('add-modal').style.display='none'"
                    class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Department</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Department Modal -->
<div id="edit-modal"
    style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); z-index: 50; align-items: center; justify-content: center;">
    <div class="glass-card" style="max-width: 500px; width: 90%; animation: slideUp 0.3s ease;">
        <h3 style="margin-bottom: 1.5rem;">Edit Department</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Department Name</label>
                <input type="text" name="name" id="edit-name" class="input-field" required>
            </div>
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem;">Description</label>
                <textarea name="description" id="edit-description" class="input-field" rows="3" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="document.getElementById('edit-modal').style.display='none'"
                    class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Department</button>
            </div>
        </form>
    </div>
</div>


<script>
    function openEditModal(dept) {
        document.getElementById('edit-id').value = dept.id;
        document.getElementById('edit-name').value = dept.name;
        document.getElementById('edit-description').value = dept.description;
        document.getElementById('edit-modal').style.display = 'flex';
    }

    function confirmDelete(id) {
        showConfirm({
            title: 'Delete Department?',
            message: 'Are you sure you want to permanently delete this department? This action cannot be undone and will only succeed if no employees are assigned.',
            confirmText: 'Delete Now',
            type: 'danger',
            link: '?delete=' + id
        });
    }

    lucide.createIcons();
</script>

<?php include 'includes/footer.php'; ?>