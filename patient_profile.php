<?php
require_once 'includes/auth.php';
require_once 'config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: patients.php'); exit; }

// Get patient
$stmt = $conn->prepare("SELECT p.*, u.full_name as added_by FROM patients p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
if (!$patient) { header('Location: patients.php'); exit; }

// Get medical records
$records = $conn->query("
    SELECT r.*, u.full_name as recorded_by_name
    FROM medical_records r
    LEFT JOIN users u ON r.recorded_by = u.id
    WHERE r.patient_id = $id
    ORDER BY r.visit_date DESC
");

// Get appointments
$appointments = $conn->query("
    SELECT * FROM appointments
    WHERE patient_id = $id
    ORDER BY appointment_date DESC
");

$page_title = 'Patient Profile';
require_once 'includes/header.php';
?>

<div class="profile-back">
    <a href="patients.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Patients</a>
</div>

<!-- Patient Info Card -->
<div class="profile-grid">
    <div class="card profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-meta">
                <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                <span class="patient-no-badge"><i class="fas fa-id-card"></i> <?= htmlspecialchars($patient['patient_no']) ?></span>
                <span class="badge badge-<?= strtolower($patient['gender']) === 'male' ? 'confirmed' : 'pending' ?>">
                    <?= $patient['gender'] ?>
                </span>
            </div>
        </div>
        <div class="profile-details">
            <div class="detail-row">
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-birthday-cake"></i> Birthdate</span>
                    <span class="detail-value"><?= date('F d, Y', strtotime($patient['birthdate'])) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-user"></i> Age</span>
                    <span class="detail-value"><?= $patient['age'] ?> years old</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-tint"></i> Blood Type</span>
                    <span class="detail-value"><?= $patient['blood_type'] ?: '—' ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-phone"></i> Phone</span>
                    <span class="detail-value"><?= htmlspecialchars($patient['phone'] ?: '—') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-map-marker-alt"></i> Address</span>
                    <span class="detail-value"><?= htmlspecialchars($patient['address']) ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-allergies"></i> Allergies</span>
                    <span class="detail-value"><?= htmlspecialchars($patient['allergies'] ?: 'None') ?></span>
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-user-friends"></i> Emergency Contact</span>
                    <span class="detail-value"><?= htmlspecialchars($patient['emergency_contact'] ?: '—') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-phone-alt"></i> Emergency Phone</span>
                    <span class="detail-value"><?= htmlspecialchars($patient['emergency_phone'] ?: '—') ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label"><i class="fas fa-calendar-plus"></i> Registered</span>
                    <span class="detail-value"><?= date('M d, Y', strtotime($patient['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="profile-stats">
        <div class="stat-card blue">
            <div class="stat-icon"><i class="fas fa-file-medical"></i></div>
            <div class="stat-info">
                <h3><?= $records->num_rows ?></h3>
                <p>Medical Records</p>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <h3><?= $appointments->num_rows ?></h3>
                <p>Appointments</p>
            </div>
        </div>
    </div>
</div>

<!-- Medical Records -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-medical"></i> Medical Records</h3>
        <a href="records.php?patient_id=<?= $id ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add Record
        </a>
    </div>
    <div class="card-body">
        <?php if ($records->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Complaint</th><th>Diagnosis</th><th>Treatment</th><th>BP</th><th>Temp</th><th>Weight</th><th>Recorded By</th></tr>
            </thead>
            <tbody>
                <?php while ($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($r['visit_date'])) ?></td>
                    <td><?= htmlspecialchars($r['chief_complaint'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['diagnosis'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($r['treatment'] ?: '—') ?></td>
                    <td><?= $r['blood_pressure'] ?: '—' ?></td>
                    <td><?= $r['temperature'] ? $r['temperature'] . '°C' : '—' ?></td>
                    <td><?= $r['weight'] ? $r['weight'] . ' kg' : '—' ?></td>
                    <td><?= htmlspecialchars($r['recorded_by_name'] ?? '—') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-file-medical-alt"></i><p>No medical records yet.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Appointments -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check"></i> Appointment History</h3>
        <a href="appointments.php?patient_id=<?= $id ?>" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> New Appointment
        </a>
    </div>
    <div class="card-body">
        <?php if ($appointments->num_rows > 0): ?>
        <table class="table">
            <thead>
                <tr><th>Appt No.</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while ($a = $appointments->fetch_assoc()): ?>
                <tr>
                    <td><code><?= htmlspecialchars($a['appointment_no']) ?></code></td>
                    <td><?= date('M d, Y', strtotime($a['appointment_date'])) ?></td>
                    <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                    <td><?= htmlspecialchars($a['purpose']) ?></td>
                    <td><span class="badge badge-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state"><i class="fas fa-calendar-times"></i><p>No appointments yet.</p></div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
