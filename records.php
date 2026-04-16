<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
$page_title = 'Medical Records';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $patient_id  = (int)$_POST['patient_id'];
        $appt_id     = $_POST['appointment_id'] ? (int)$_POST['appointment_id'] : null;
        $visit_date  = sanitize($conn, $_POST['visit_date']);
        $complaint   = sanitize($conn, $_POST['chief_complaint']);
        $diagnosis   = sanitize($conn, $_POST['diagnosis']);
        $treatment   = sanitize($conn, $_POST['treatment']);
        $prescription = sanitize($conn, $_POST['prescription']);
        $bp          = sanitize($conn, $_POST['blood_pressure']);
        $temp        = sanitize($conn, $_POST['temperature']);
        $weight      = sanitize($conn, $_POST['weight']);
        $height      = sanitize($conn, $_POST['height']);
        $notes       = sanitize($conn, $_POST['notes']);

        $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, appointment_id, visit_date, chief_complaint, diagnosis, treatment, prescription, blood_pressure, temperature, weight, height, notes, recorded_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iissssssssssi", $patient_id, $appt_id, $visit_date, $complaint, $diagnosis, $treatment, $prescription, $bp, $temp, $weight, $height, $notes, $_SESSION['user_id']);
        if ($stmt->execute()) {
            logAudit($conn, $_SESSION['user_id'], 'ADD_RECORD', 'medical_records', $conn->insert_id, "Record added for patient ID $patient_id");
            $message = "Medical record saved successfully.";
        } else {
            $error = "Failed to save record.";
        }
    }
}

$filter_patient = (int)($_GET['patient_id'] ?? 0);
$where = $filter_patient ? "WHERE r.patient_id = $filter_patient" : '';

$records = $conn->query("
    SELECT r.*, p.full_name as patient_name, p.patient_no
    FROM medical_records r
    JOIN patients p ON r.patient_id = p.id
    $where
    ORDER BY r.visit_date DESC
");

$patients = $conn->query("SELECT id, full_name, patient_no FROM patients ORDER BY full_name");

require_once 'includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= $error ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-file-medical"></i> Medical Records</h3>
        <button class="btn btn-primary" onclick="openModal('addRecordModal')">
            <i class="fas fa-plus"></i> Add Record
        </button>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <select name="patient_id" onchange="this.form.submit()">
                <option value="">All Patients</option>
                <?php $patients->data_seek(0); while ($p = $patients->fetch_assoc()): ?>
                <option value="<?= $p['id'] ?>" <?= $filter_patient == $p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['patient_no'] . ' — ' . $p['full_name']) ?>
                </option>
                <?php endwhile; ?>
            </select>
        </form>
        <table class="table">
            <thead>
                <tr><th>Date</th><th>Patient</th><th>Complaint</th><th>Diagnosis</th><th>BP</th><th>Temp</th><th>Weight</th></tr>
            </thead>
            <tbody>
                <?php if ($records->num_rows > 0): ?>
                    <?php while ($r = $records->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($r['visit_date'])) ?></td>
                        <td><a href="patient_profile.php?id=<?= $r['patient_id'] ?>" class="profile-link"><?= htmlspecialchars($r['patient_name']) ?></a></td>
                        <td><?= htmlspecialchars($r['chief_complaint']) ?></td>
                        <td><?= htmlspecialchars($r['diagnosis']) ?></td>
                        <td><?= $r['blood_pressure'] ?: '—' ?></td>
                        <td><?= $r['temperature'] ? $r['temperature'] . '°C' : '—' ?></td>
                        <td><?= $r['weight'] ? $r['weight'] . ' kg' : '—' ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="fas fa-file-medical-alt"></i><p>No records found.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Record Modal -->
<div class="modal" id="addRecordModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-notes-medical"></i> Add Medical Record</h3>
            <button onclick="closeModal('addRecordModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Patient *</label>
                        <select name="patient_id" required>
                            <option value="">Select Patient</option>
                            <?php $patients->data_seek(0); while ($p = $patients->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" <?= $filter_patient == $p['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['patient_no'] . ' — ' . $p['full_name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Visit Date *</label>
                        <input type="date" name="visit_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Chief Complaint</label>
                    <textarea name="chief_complaint" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Diagnosis</label>
                    <textarea name="diagnosis" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Treatment</label>
                    <textarea name="treatment" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Prescription</label>
                    <textarea name="prescription" rows="2"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Pressure</label>
                        <input type="text" name="blood_pressure" placeholder="e.g. 120/80">
                    </div>
                    <div class="form-group">
                        <label>Temperature (°C)</label>
                        <input type="text" name="temperature" placeholder="e.g. 36.5">
                    </div>
                    <div class="form-group">
                        <label>Weight (kg)</label>
                        <input type="text" name="weight" placeholder="e.g. 65">
                    </div>
                    <div class="form-group">
                        <label>Height (cm)</label>
                        <input type="text" name="height" placeholder="e.g. 165">
                    </div>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="2"></textarea>
                </div>
                <input type="hidden" name="appointment_id" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addRecordModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Record</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
