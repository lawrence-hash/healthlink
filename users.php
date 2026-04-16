<?php
require_once 'includes/auth.php';
require_once 'config/db.php';
$page_title = 'User Management';

if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $full_name = sanitize($conn, $_POST['full_name']);
        $username  = sanitize($conn, $_POST['username']);
        $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role      = sanitize($conn, $_POST['role']);
        $email     = sanitize($conn, $_POST['email']);
        $phone     = sanitize($conn, $_POST['phone']);
        $sec_q     = sanitize($conn, $_POST['security_question'] ?? '');
        $sec_a     = sanitize($conn, strtolower(trim($_POST['security_answer'] ?? '')));

        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, email, phone, security_question, security_answer) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $full_name, $username, $password, $role, $email, $phone, $sec_q, $sec_a);
        if ($stmt->execute()) {
            logAudit($conn, $_SESSION['user_id'], 'ADD_USER', 'users', $conn->insert_id, "Added user: $username");
            $message = "User $full_name added successfully.";
        } else {
            $error = "Username already exists or failed to add user.";
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)$_POST['id'];
        if ($id !== $_SESSION['user_id']) {
            $conn->query("DELETE FROM users WHERE id = $id");
            logAudit($conn, $_SESSION['user_id'], 'DELETE_USER', 'users', $id, "Deleted user ID: $id");
            $message = "User deleted.";
        } else {
            $error = "You cannot delete your own account.";
        }
    }
}

$users = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
require_once 'includes/header.php';
?>

<?php if ($message): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= $error ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-user-shield"></i> System Users</h3>
        <button class="btn btn-primary" onclick="openModal('addUserModal')">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Phone</th><th>Created</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'confirmed' : 'pending' ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['phone']) ?></td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                        <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $u['id'] ?>, '<?= htmlspecialchars($u['full_name']) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal" id="addUserModal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            <button onclick="closeModal('addUserModal')"><i class="fas fa-times"></i></button>
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
                        <label>Username *</label>
                        <input type="text" name="username" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="health_worker">Health Worker</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone">
                    </div>
                </div>
                <div class="form-group">
                    <label>Security Question</label>
                    <select name="security_question">
                        <option value="">— Select a question —</option>
                        <option>What is your mother's maiden name?</option>
                        <option>What was the name of your first pet?</option>
                        <option>What is the name of the barangay where you grew up?</option>
                        <option>What is your favorite childhood nickname?</option>
                        <option>What was the name of your elementary school?</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Security Answer</label>
                    <input type="text" name="security_answer" placeholder="Answer (case-insensitive)">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save User</button>
            </div>
        </form>
    </div>
</div>

<form method="POST" id="deleteForm">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<?php require_once 'includes/footer.php'; ?>
