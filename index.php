<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
$page_title = 'Dashboard';

// Stats
$total_patients    = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch_assoc()['c'];
$total_appointments = $conn->query("SELECT COUNT(*) as c FROM appointments")->fetch_assoc()['c'];
$today_appointments = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['c'];
$pending_appointments = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE status = 'Pending'")->fetch_assoc()['c'];

// Today's appointments list
$today_list = $conn->query("
    SELECT a.*, p.full_name as patient_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
    LIMIT 10
");

// Recent patients
$recent_patients = $conn->query("SELECT * FROM patients ORDER BY created_at DESC LIMIT 5");

require_once 'includes/header.php';
?>

<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3><?= $total_patients ?></h3>
            <p>Total Patients</p>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-info">
            <h3><?= $total_appointments ?></h3>
            <p>Total Appointments</p>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?= $today_appointments ?></h3>
            <p>Today's Appointments</p>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <h3><?= $pending_appointments ?></h3>
            <p>Pending</p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-day"></i> Today's Appointments</h3>
            <a href="appointments.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if ($today_list->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr><th>Time</th><th>Patient</th><th>Purpose</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = $today_list->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('h:i A', strtotime($row['appointment_time'])) ?></td>
                        <td><?= htmlspecialchars($row['patient_name']) ?></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td><span class="badge badge-<?= strtolower($row['status']) ?>"><?= $row['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-calendar-times"></i><p>No appointments today.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-plus"></i> Recent Patients</h3>
            <a href="patients.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="card-body">
            <?php if ($recent_patients->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr><th>Patient No.</th><th>Name</th><th>Gender</th><th>Age</th></tr>
                </thead>
                <tbody>
                    <?php while ($row = $recent_patients->fetch_assoc()): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($row['patient_no']) ?></code></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= $row['gender'] ?></td>
                        <td><?= $row['age'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div class="empty-state"><i class="fas fa-user-slash"></i><p>No patients yet.</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
