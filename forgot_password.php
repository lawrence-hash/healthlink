<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$step    = $_SESSION['fp_step'] ?? 1;
$error   = '';
$success = '';

// ── STEP 1: Enter username ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step1'])) {
    $username = sanitize($conn, $_POST['username']);
    $stmt = $conn->prepare("SELECT id, full_name, security_question FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && $user['security_question']) {
        $_SESSION['fp_step']     = 2;
        $_SESSION['fp_user_id']  = $user['id'];
        $_SESSION['fp_name']     = $user['full_name'];
        $_SESSION['fp_question'] = $user['security_question'];
        $step = 2;
    } elseif ($user && !$user['security_question']) {
        $error = 'No security question set for this account. Please contact the administrator.';
    } else {
        $error = 'Username not found.';
    }
}

// ── STEP 2: Answer security question ───────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step2'])) {
    $answer = sanitize($conn, strtolower(trim($_POST['answer'])));
    $stmt   = $conn->prepare("SELECT security_answer FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['fp_user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && strtolower($row['security_answer']) === $answer) {
        $_SESSION['fp_step'] = 3;
        $step = 3;
    } else {
        $error = 'Incorrect answer. Please try again.';
        $step  = 2;
    }
}

// ── STEP 3: Set new password ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step3'])) {
    $new  = $_POST['new_password'];
    $conf = $_POST['confirm_password'];

    if (strlen($new) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step  = 3;
    } elseif ($new !== $conf) {
        $error = 'Passwords do not match.';
        $step  = 3;
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $_SESSION['fp_user_id']);
        $stmt->execute();
        logAudit($conn, $_SESSION['fp_user_id'], 'PASSWORD_RESET', 'users', $_SESSION['fp_user_id'], 'Password reset via security question');

        // Clear session
        unset($_SESSION['fp_step'], $_SESSION['fp_user_id'], $_SESSION['fp_name'], $_SESSION['fp_question']);
        $success = 'Password changed successfully. You can now log in.';
        $step    = 'done';
    }
}

$fp_question = $_SESSION['fp_question'] ?? '';
$fp_name     = $_SESSION['fp_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthLink — Forgot Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-page">
<div class="login-container">
    <div class="login-brand">
        <i class="fas fa-heartbeat"></i>
        <h1>HealthLink</h1>
        <p>Password Recovery</p>
    </div>

    <!-- Step indicator -->
    <?php if ($step !== 'done'): ?>
    <div class="step-indicator">
        <div class="step <?= $step >= 1 ? 'active' : '' ?>"><span>1</span> Username</div>
        <div class="step-line"></div>
        <div class="step <?= $step >= 2 ? 'active' : '' ?>"><span>2</span> Verify</div>
        <div class="step-line"></div>
        <div class="step <?= $step >= 3 ? 'active' : '' ?>"><span>3</span> Reset</div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <a href="login.php" class="btn btn-primary btn-block" style="margin-top:8px;">
            <i class="fas fa-sign-in-alt"></i> Back to Login
        </a>

    <?php elseif ($step == 1): ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" placeholder="Enter your username" required autofocus>
            </div>
            <button type="submit" name="step1" class="btn btn-primary btn-block">
                <i class="fas fa-arrow-right"></i> Continue
            </button>
        </form>

    <?php elseif ($step == 2): ?>
        <p class="fp-greeting">Hi, <strong><?= htmlspecialchars($fp_name) ?></strong>. Answer your security question:</p>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-question-circle"></i> <?= htmlspecialchars($fp_question) ?></label>
                <input type="text" name="answer" placeholder="Your answer" required autofocus autocomplete="off">
            </div>
            <button type="submit" name="step2" class="btn btn-primary btn-block">
                <i class="fas fa-check"></i> Verify Answer
            </button>
        </form>

    <?php elseif ($step == 3): ?>
        <form method="POST" class="login-form">
            <div class="form-group">
                <label><i class="fas fa-lock"></i> New Password</label>
                <div class="input-eye">
                    <input type="password" name="new_password" id="new_password" placeholder="Min. 6 characters" required>
                    <span onclick="togglePwd('new_password','eye1')"><i class="fas fa-eye" id="eye1"></i></span>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <div class="input-eye">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat password" required>
                    <span onclick="togglePwd('confirm_password','eye2')"><i class="fas fa-eye" id="eye2"></i></span>
                </div>
            </div>
            <button type="submit" name="step3" class="btn btn-primary btn-block">
                <i class="fas fa-save"></i> Save New Password
            </button>
        </form>
    <?php endif; ?>

    <?php if ($step !== 'done'): ?>
    <p style="text-align:center; margin-top:16px;">
        <a href="login.php" style="color:var(--primary); font-size:13px;">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </p>
    <?php endif; ?>
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
