<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($conn, $_POST['full_name']);
    $username  = sanitize($conn, $_POST['username']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $role      = sanitize($conn, $_POST['role']);
    $email     = sanitize($conn, $_POST['email']);
    $phone     = sanitize($conn, $_POST['phone']);
    $sec_q     = sanitize($conn, $_POST['security_question']);
    $sec_a     = sanitize($conn, strtolower(trim($_POST['security_answer'])));

    if (!$full_name || !$username || !$password) {
        $error = 'Full name, username, and password are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (full_name, username, password, role, email, phone, security_question, security_answer) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssssssss", $full_name, $username, $hashed, $role, $email, $phone, $sec_q, $sec_a);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                logAudit($conn, $new_id, 'REGISTER', 'users', $new_id, "New account registered: $username");
                $success = 'Account created successfully! You can now log in.';
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink — Create Account</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-page">
<div class="login-container" style="max-width:500px;">
    <div class="login-brand">
        <i class="fas fa-heartbeat"></i>
        <h1>HealthLink</h1>
        <p>Create New Account</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <a href="login.php" class="btn btn-primary btn-block">
            <i class="fas fa-sign-in-alt"></i> Go to Login
        </a>
    <?php else: ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name *</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-id-badge"></i> Username *</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password *</label>
                    <div class="input-eye">
                        <input type="password" name="password" id="pwd1" placeholder="Min. 6 characters" required>
                        <span onclick="togglePwd('pwd1','eye1')"><i class="fas fa-eye" id="eye1"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password *</label>
                    <div class="input-eye">
                        <input type="password" name="confirm_password" id="pwd2" placeholder="Repeat password" required>
                        <span onclick="togglePwd('pwd2','eye2')"><i class="fas fa-eye" id="eye2"></i></span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Role *</label>
                    <select name="role" required>
                        <option value="health_worker" <?= ($_POST['role'] ?? '') === 'health_worker' ? 'selected' : '' ?>>Health Worker</option>
                        <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-phone"></i> Phone</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-envelope"></i> Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="register-divider">Security Question <small>(for password recovery)</small></div>

            <div class="form-group">
                <label><i class="fas fa-question-circle"></i> Security Question</label>
                <select name="security_question">
                    <option value="">— Select a question —</option>
                    <?php
                    $questions = [
                        "What is your mother's maiden name?",
                        "What was the name of your first pet?",
                        "What is the name of the barangay where you grew up?",
                        "What is your favorite childhood nickname?",
                        "What was the name of your elementary school?",
                    ];
                    foreach ($questions as $q):
                    ?>
                    <option value="<?= $q ?>" <?= ($_POST['security_question'] ?? '') === $q ? 'selected' : '' ?>>
                        <?= $q ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-key"></i> Your Answer</label>
                <input type="text" name="security_answer" placeholder="Answer (case-insensitive)" value="<?= htmlspecialchars($_POST['security_answer'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                <i class="fas fa-user-plus"></i> Create Account
            </button>
        </form>

    <?php endif; ?>

    <p style="text-align:center; margin-top:16px;">
        <a href="login.php" style="color:var(--primary); font-size:13px;">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </p>
    <p class="login-footer">HealthLink &copy; <?= date('Y') ?> — Secure Clinic System</p>
</div>

<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
