<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
$page_title = 'Patients';

$message = '';
$error   = '';

// Add Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $patient_no = 'PT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $full_name  = sanitize($conn, $_POST['full_name']);
        $birthdate  = sanitize($conn, $_POST['birthdate']);
        $age        = (int)$_POST['age'];
        $gender     = sanitize($conn, $_POST['gender']);
        $address    = sanitize($conn, $_POST['address']);
        $phone      = sanitize($conn, $_POST['phone']);
        $blood_type = sanitize($conn, $_POST['blood_type']);
        $allergies  = sanitize($conn, $_POST['allergies']);
        $ec_name    = sanitize($conn, $_POST['emergency_contact']);
        $ec_phone   = sanitize($conn, $_POST['emergency_phone']);

        $stmt = $conn->prepare("INSERT INTO patients (patient_no, full_name, birthdate, age, gender, address, phone, blood_type, allergies, emergency_contact, emergency_phone, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssississssi", $patient_no, $full_name, $birthdate, $age, $gender, $address, $phone, $blood_type, $allergies, $ec_name, $ec_phone, $_SESSION['user_id']);
        if ($stmt->execute()) {
            logAudit($conn, $_SESSION['user_id'], 'ADD_PATIENT', 'patients', $conn->insert_id, "Added patient: $full_name");
            $message = "Patient $full_name added successfully.";
        } else {
            $error = "Failed to add patient.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM patients WHERE id = $id");
        logAudit($conn, $_SESSION['user_id'], 'DELETE_PATIENT', 'patients', $id, "Deleted patient ID: $id");
        $message = "Patient deleted.";
    }
}

$search = sanitize($conn, $_GET['search'] ?? '');
$where  = $search ? "WHERE full_name LIKE '%$search%' OR patient_no LIKE '%$search%'" : '';
$patients = $conn->query("SELECT * FROM patients $where ORDER BY created_at DESC");

require_once 'includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= $error ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-users"></i> Patient List</h3>
        <button class="btn btn-primary" onclick="openModal('addPatientModal')">
            <i class="fas fa-plus"></i> Add Patient
        </button>
    </div>
    <div class="card-body">
        <form method="GET" class="search-bar">
            <input type="text" name="search" placeholder="Search by name or patient no..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-outline"><i class="fas fa-search"></i></button>
        </form>
        <table class="table">
            <thead>
                <tr><th>Patient No.</th><th>Name</th><th>Age</th><th>Gender</th><th>Phone</th><th>Blood Type</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if ($patients->num_rows > 0): ?>
                    <?php while ($p = $patients->fetch_assoc()): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($p['patient_no']) ?></code></td>
                        <td><?= htmlspecialchars($p['full_name']) ?></td>
                        <td><?= $p['age'] ?></td>
                        <td><?= $p['gender'] ?></td>
                        <td><?= htmlspecialchars($p['phone']) ?></td>
                        <td><?= $p['blood_type'] ?: '—' ?></td>
                        <td class="actions">
                            <a href="records.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-info" title="View Records"><i class="fas fa-file-medical"></i></a>
                            <a href="appointments.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-green" title="Appointments"><i class="fas fa-calendar"></i></a>
                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $p['id'] ?>, '<?= htmlspecialchars($p['full_name']) ?>')" title="Delete"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No patients found.</p></div></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Patient Modal -->
<div class="modal" id="addPatientModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New Patient</h3>
            <button onclick="closeModal('addPatientModal')"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Birthdate *</label>
                        <input type="date" name="birthdate" id="birthdate" onchange="calcAge()" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Age</label>
                        <input type="number" name="age" id="age_field" min="0" max="150">
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" required>
                            <option value="">Select</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <option value="">Unknown</option>
                            <option>A+</option><option>A-</option>
                            <option>B+</option><option>B-</option>
                            <option>AB+</option><option>AB-</option>
                            <option>O+</option><option>O-</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Address *</label>
                    <textarea name="address" rows="2" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Allergies</label>
                        <input type="text" name="allergies" placeholder="e.g. Penicillin, Aspirin">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact">
                    </div>
                    <div class="form-group">
                        <label>Emergency Phone</label>
                        <input type="text" name="emergency_phone">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addPatientModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Patient</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php require_once 'includes/footer.php'; ?>
