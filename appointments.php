<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
$page_title = 'Appointments';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $appt_no   = 'APT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $patient_id = (int)$_POST['patient_id'];
        $date       = sanitize($conn, $_POST['appointment_date']);
        $time       = sanitize($conn, $_POST['appointment_time']);
        $purpose    = sanitize($conn, $_POST['purpose']);
        $notes      = sanitize($conn, $_POST['notes']);
        $assigned   = (int)($_POST['assigned_to'] ?? $_SESSION['user_id']);

        $stmt = $conn->prepare("INSERT INTO appointments (appointment_no, patient_id, appointment_date, appointment_time, purpose, notes, assigned_to) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sisssssi", $appt_no, $patient_id, $date, $time, $purpose, $notes, $assigned);
        // fix: 7 params
        $stmt = $conn->prepare("INSERT INTO appointments (appointment_no, patient_id, appointment_date, appointment_time, purpose, notes, assigned_to) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sisssssi", $appt_no, $patient_id, $date, $time, $purpose, $notes, $assigned);
        if ($stmt->execute()) {
            logAudit($conn, $_SESSION['user_id'], 'ADD_APPOINTMENT', 'appointments', $conn->insert_id, "Appointment $appt_no added");
            $message = "Appointment scheduled successfully.";
        } else {
            $error = "Failed to schedule appointment.";
        }
    }

    if ($_POST['action'] === 'update_status') {
        $id     = (int)$_POST['id'];
        $status = sanitize($conn, $_POST['status']);
        $conn->query("UPDATE appointments SET status = '$status' WHERE id = $id");
        logAudit($conn, $_SESSION['user_id'], 'UPDATE_APPOINTMENT', 'appointments', $id, "Status changed to $status");
        $message = "Appointment status updated.";
    }
}

$filter_patient = (int)($_GET['patient_id'] ?? 0);
$filter_date    = sanitize($conn, $_GET['date'] ?? '');
$where_parts    = [];
if ($filter_patient) $where_parts[] = "a.patient_id = $filter_patient";
if ($filter_date)    $where_parts[] = "a.appointment_date = '$filter_date'";
$where = $where_parts ? 'WHERE ' . implode(' AND ', $where_parts) : '';

$appointments = $conn->query("
    SELECT a.*, p.full_name as patient_name, p.patient_no
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    $where
    ORDER BY a.appointment_date DESC, a.appointment_time ASC
");

$patients = $conn->query("SELECT id, full_name, patient_no FROM patients ORDER BY full_name");
$workers  = $conn->query("SELECT id, full_name FROM users ORDER BY full_name");

require_once 'includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= $error ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-check"></i> Appointments</h3>
        <button class="btn btn-primary" onclick="openModal('addApptModal')">
            <i class="fas fa-plus"></i> New Appointment
        </button>
    </div>
    <div class="card-body">
        <form method="GET" class="filter-bar">
            <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
            <button type="submit" class="btn btn-outline"><i class="fas fa-filter"></i> Filter</button>
            <a href="appointments.php" class="btn btn-outline">Clear</a>
        </form>
        <table class="table">
            <thead>
                <tr><th>Appt No.</th><th>Patient</th><th>Date</th><th>Time</th><th>Purpose</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if ($appointments->num_rows > 0): ?>
                    <?php while ($a = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($a['appointment_no']) ?></code></td>
                        <td><?= htmlspecialchars($a['patient_name']) ?></td>
                        <td><?= date('M d, Y', strtotime($a['appointment_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
                        <td><?= htmlspecialchars($a['purpose']) ?></td>
                        <td><span class="badge badge-<?= strtolower($a['status']) ?>"><?= $a['status'] ?></span></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-outline" onclick="updateStatus(<?= $a['id'] ?>, '<?= $a['status'] ?>')" title="Update Status">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="fas fa-calendar-times"></i><p>No appointments found.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Appointment Modal -->
<div class="modal" id="addApptModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-plus"></i> New Appointment</h3>
            <button onclick="closeModal('addApptModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
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
                <div class="form-row">
                    <div class="form-group">
                        <label>Date *</label>
                        <input type="date" name="appointment_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Time *</label>
                        <input type="time" name="appointment_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Purpose *</label>
                    <input type="text" name="purpose" placeholder="e.g. General Checkup, Vaccination" required>
                </div>
                <div class="form-group">
                    <label>Assigned To</label>
                    <select name="assigned_to">
                        <?php $workers->data_seek(0); while ($w = $workers->fetch_assoc()): ?>
                        <option value="<?= $w['id'] ?>" <?= $w['id'] == $_SESSION['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($w['full_name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addApptModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal" id="statusModal">
    <div class="modal-dialog modal-sm">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Update Status</h3>
            <button onclick="closeModal('statusModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="id" id="statusId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="statusSelect">
                        <option>Pending</option>
                        <option>Confirmed</option>
                        <option>Completed</option>
                        <option>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
