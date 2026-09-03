<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$is_client = isset($_SESSION['client_id']);
$user_id = $is_client ? $_SESSION['client_id'] : $_SESSION['user_id'];
$active_page = 'profile';
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($is_client) {
        $stmt = $pdo->prepare("UPDATE clients SET full_name = ?, phone = ?, physical_address = ? WHERE client_id = ?");
        $stmt->execute([$full_name, $phone, $address, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $user_id]);
    }
    $_SESSION['full_name'] = $full_name;
    logAction($pdo, $user_id, $is_client ? 'client' : 'user', 'profile_update', 'Profile details updated');
    $success = "Profile updated!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notifications'])) {
    $email_n = isset($_POST['notify_email']) ? 1 : 0;
    $sms_n = isset($_POST['notify_sms']) ? 1 : 0;
    $push_n = isset($_POST['notify_push']) ? 1 : 0;
    $table = $is_client ? 'clients' : 'users';
    $idcol = $is_client ? 'client_id' : 'user_id';
    $pdo->prepare("UPDATE $table SET notify_email=?, notify_sms=?, notify_push=? WHERE $idcol=?")->execute([$email_n, $sms_n, $push_n, $user_id]);
    logAction($pdo, $user_id, $is_client ? 'client' : 'user', 'notification_prefs_update', "email=$email_n sms=$sms_n push=$push_n");
    $success = "Notification preferences saved!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    $table = $is_client ? 'clients' : 'users';
    $idcol = $is_client ? 'client_id' : 'user_id';
    $stmt = $pdo->prepare("SELECT password_hash FROM $table WHERE $idcol = ?");
    $stmt->execute([$user_id]);
    $hash = $stmt->fetch()['password_hash'];

    if (!password_verify($current, $hash)) {
        $error = "Current password is incorrect.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
        $error = "Password must be at least 8 characters with an uppercase letter and a number.";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE $table SET password_hash = ? WHERE $idcol = ?")->execute([$new_hash, $user_id]);
        logAction($pdo, $user_id, $is_client ? 'client' : 'user', 'password_change', null);
        $success = "Password updated successfully!";
    }
}

if ($is_client) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
}
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | My Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .profile-container{ max-width:760px; }
        .section-title{ font-size:1.2rem; font-weight:700; margin-bottom:1.4rem; padding-bottom:.75rem; border-bottom:2px solid var(--gray-100); display:flex; align-items:center; gap:.6rem; color:var(--navy); }
        .section-title i{ color:var(--primary); }
        .profile-avatar{ width:84px; height:84px; border-radius:50%; background:linear-gradient(135deg,var(--primary),var(--cyan)); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:1.6rem; margin:0 auto 1rem; }
    </style>
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar"><div><h2>My Profile</h2><p class="date-line">Manage your account information and preferences</p></div></div>

        <div class="profile-container">
            <?php if($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div><?php endif; ?>
            <?php if($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div><?php endif; ?>

            <div class="section-card">
                <div class="profile-avatar"><?php echo strtoupper(substr($user['full_name'],0,2)); ?></div>
                <h2 class="section-title"><i class="fas fa-circle-user"></i> Personal Information</h2>
                <form method="POST">
                    <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                    <div class="form-group"><label>Email</label><input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled></div>
                    <?php if($is_client): ?>
                        <div class="form-row">
                            <div class="form-group"><label>Phone</label><input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required></div>
                            <div class="form-group"><label>Tax Number</label><input type="text" value="<?php echo htmlspecialchars($user['tax_number']); ?>" disabled></div>
                        </div>
                        <div class="form-group"><label>Address</label><textarea name="address" rows="2" required><?php echo htmlspecialchars($user['physical_address']); ?></textarea></div>
                        <div class="form-group"><label>Service Preference</label><input type="text" value="<?php echo $user['service_preference'] ? ucfirst($user['service_preference']) : 'Not specified'; ?>" disabled></div>
                    <?php else: ?>
                        <div class="form-row">
                            <div class="form-group"><label>Role</label><input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled></div>
                            <div class="form-group"><label>Department</label><input type="text" value="<?php echo htmlspecialchars($user['department'] ?? '—'); ?>" disabled></div>
                        </div>
                    <?php endif; ?>
                    <button type="submit" name="save_profile" class="btn btn-success">Save Changes</button>
                </form>
            </div>

            <div class="section-card">
                <h2 class="section-title"><i class="fas fa-bell"></i> Notification Preferences</h2>
                <form method="POST">
                    <div class="toggle-row">
                        <div class="toggle-label"><strong>Email Notifications</strong><span>Receive updates via email</span></div>
                        <label class="switch"><input type="checkbox" name="notify_email" <?php echo $user['notify_email'] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-label"><strong>SMS Notifications</strong><span>Receive alerts via text (used for 2FA codes)</span></div>
                        <label class="switch"><input type="checkbox" name="notify_sms" <?php echo $user['notify_sms'] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <div class="toggle-row">
                        <div class="toggle-label"><strong>Push Notifications</strong><span>Receive notifications on the mobile app</span></div>
                        <label class="switch"><input type="checkbox" name="notify_push" <?php echo $user['notify_push'] ? 'checked' : ''; ?>><span class="slider"></span></label>
                    </div>
                    <button type="submit" name="save_notifications" class="btn btn-primary" style="margin-top:1rem;">Save Preferences</button>
                </form>
            </div>

            <div class="section-card">
                <h2 class="section-title"><i class="fas fa-lock"></i> Change Password</h2>
                <form method="POST">
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" required></div>
                    <div class="form-row">
                        <div class="form-group"><label>New Password</label><input type="password" name="new_password" required placeholder="Min 8 chars, 1 uppercase, 1 number"></div>
                        <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
                    </div>
                    <button type="submit" name="save_password" class="btn btn-primary">Update Password</button>
                </form>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
