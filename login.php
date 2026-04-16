<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            logAudit($conn, $user['id'], 'LOGIN', 'users', $user['id'], 'User logged in');
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink — Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-brand">
            <i class="fas fa-heartbeat"></i>
            <h1>HealthLink</h1>
            <p>Barangay Health Worker System</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" placeholder="Enter username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-eye">
                    <input type="password" name="password" id="password" placeholder="Enter password" required autocomplete="current-password">
                    <span onclick="togglePassword()"><i class="fas fa-eye" id="eyeIcon"></i></span>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
            <p style="text-align:center; margin-top:14px;">
                <a href="forgot_password.php" style="color:var(--primary); font-size:13px;">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </p>
        </form>
        <p style="text-align:center; margin-top:12px;">
            Don't have an account?
            <a href="register.php" style="color:var(--primary); font-weight:600;">
                Create one
            </a>
        </p>
        <p class="login-footer">HealthLink &copy; <?= date('Y') ?> — Secure Clinic System</p>
    </div>
    <script src="assets/js/app.js"></script>
</body>
</html>
