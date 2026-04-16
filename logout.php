<?php
session_start();
require_once 'config/db.php';
if (isset($_SESSION['user_id'])) {
    logAudit($conn, $_SESSION['user_id'], 'LOGOUT', 'users', $_SESSION['user_id'], 'User logged out');
}
session_destroy();
header('Location: login.php');
exit;
?>
