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
    <title><?= htmlspecialchars($sys_settings['company_name'] ?? 'HRMS') ?> - Enterprise HR Management</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="fade-in">