<?php
session_start();
require_once '../includes/db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_SESSION['employee_id'])) {
    // Try to recover it
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $emp = $stmt->fetch();

    if ($emp) {
        $_SESSION['employee_id'] = $emp['id'];
    } else {
        // Still invalid
        header("Location: ../login.php");
        exit;
    }
}

$employee_id = $_SESSION['employee_id'];
$action = $_POST['action'] ?? '';
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

try {
    if ($action === 'clock_in') {
        // Check if already clocked in today
        $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employee_id, $today]);
        if ($stmt->fetch()) {
            $_SESSION['msg'] = "Already clocked in today.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, clock_in, date) VALUES (?, ?, ?)");
            $stmt->execute([$employee_id, $now, $today]);
        }
    } elseif ($action === 'clock_out') {
        // Get the clock_in record
        $stmt = $pdo->prepare("SELECT id, clock_in FROM attendance WHERE employee_id = ? AND date = ? AND clock_out IS NULL");
        $stmt->execute([$employee_id, $today]);
        $record = $stmt->fetch();

        if ($record) {
            $clock_in = new DateTime($record['clock_in']);
            $clock_out = new DateTime($now);
            $interval = $clock_in->diff($clock_out);
            $hours = $interval->h + ($interval->i / 60);

            $stmt = $pdo->prepare("UPDATE attendance SET clock_out = ?, work_hours = ? WHERE id = ?");
            $stmt->execute([$now, round($hours, 2), $record['id']]);
        } else {
            $_SESSION['msg'] = "No active clock-in session found.";
        }
    }
} catch (PDOException $e) {
    $_SESSION['msg'] = "Error: " . $e->getMessage();
}

header("Location: dashboard.php");
exit;
?>