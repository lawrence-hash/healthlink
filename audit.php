<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
$page_title = 'Audit Logs';

if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$logs = $conn->query("
    SELECT l.*, u.full_name
    FROM audit_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 200
");

require_once 'includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clipboard-list"></i> Audit Logs</h3>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr><th>Date/Time</th><th>User</th><th>Action</th><th>Table</th><th>Record ID</th><th>Details</th><th>IP</th></tr>
            </thead>
            <tbody>
                <?php while ($l = $logs->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d, Y h:i A', strtotime($l['created_at'])) ?></td>
                    <td><?= htmlspecialchars($l['full_name'] ?? 'System') ?></td>
                    <td><code><?= htmlspecialchars($l['action']) ?></code></td>
                    <td><?= htmlspecialchars($l['table_name']) ?></td>
                    <td><?= $l['record_id'] ?></td>
                    <td><?= htmlspecialchars($l['details']) ?></td>
                    <td><?= htmlspecialchars($l['ip_address']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
