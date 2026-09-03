<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
    $uid = $_SESSION['user_id'] ?? $_SESSION['client_id'];
    $utype = isset($_SESSION['client_id']) ? 'client' : 'user';
    $reason = isset($_GET['reason']) && $_GET['reason'] === 'idle' ? 'Session ended (30 min inactivity)' : 'Manual logout';
    logAction($pdo, $uid, $utype, 'logout', $reason);
}

$idle = isset($_GET['reason']) && $_GET['reason'] === 'idle';
session_unset();
session_destroy();
header('Location: login.php' . ($idle ? '?idle=1' : ''));
exit();
