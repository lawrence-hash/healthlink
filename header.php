<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
// Build base URL dynamically so assets resolve correctly on any server setup
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink — <?= ucfirst($page_title ?? 'Dashboard') ?></title>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-heartbeat"></i>
            <span>HealthLink</span>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-item <?= $current_page === 'index' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i><span>Dashboard</span>
            </a>
            <a href="patients.php" class="nav-item <?= $current_page === 'patients' ? 'active' : '' ?>">
                <i class="fas fa-users"></i><span>Patients</span>
            </a>
            <a href="appointments.php" class="nav-item <?= $current_page === 'appointments' ? 'active' : '' ?>">
                <i class="fas fa-calendar-check"></i><span>Appointments</span>
            </a>
            <a href="records.php" class="nav-item <?= $current_page === 'records' ? 'active' : '' ?>">
                <i class="fas fa-file-medical"></i><span>Medical Records</span>
            </a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="users.php" class="nav-item <?= $current_page === 'users' ? 'active' : '' ?>">
                <i class="fas fa-user-shield"></i><span>Users</span>
            </a>
            <a href="audit.php" class="nav-item <?= $current_page === 'audit' ? 'active' : '' ?>">
                <i class="fas fa-clipboard-list"></i><span>Audit Logs</span>
            </a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                    <small><?= ucfirst($_SESSION['role']) ?></small>
                </div>
            </div>
            <a href="logout.php" class="btn-logout" title="Logout"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <h2><?= htmlspecialchars($page_title ?? 'Dashboard') ?></h2>
            <div class="topbar-right">
                <span class="date-display"><i class="fas fa-calendar"></i> <?= date('F d, Y') ?></span>
            </div>
        </header>
        <div class="content-area">
