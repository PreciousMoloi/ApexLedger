<?php
require_once 'config/database.php';

if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$demo_code = null; // shown on-screen only because this demo build has no live SendGrid/SMS gateway wired up

// ---- STEP 2: verify the 2FA code ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step']) && $_POST['step'] === '2fa') {
    $code = trim($_POST['code'] ?? '');
    if (!isset($_SESSION['pending_2fa'])) {
        $error = "Your session expired. Please log in again.";
    } elseif (time() > $_SESSION['pending_2fa']['expires']) {
        unset($_SESSION['pending_2fa']);
        $error = "Your 2FA code expired after 5 minutes. Please log in again.";
    } elseif ($code !== $_SESSION['pending_2fa']['code']) {
        $error = "Incorrect verification code. Please try again.";
        $demo_code = $_SESSION['pending_2fa']['code'];
    } else {
        $p = $_SESSION['pending_2fa'];
        unset($_SESSION['pending_2fa']);
        if ($p['type'] === 'client') {
            $_SESSION['client_id'] = $p['id'];
        } else {
            $_SESSION['user_id'] = $p['id'];
            $_SESSION['role'] = $p['role'];
            $_SESSION['department'] = $p['department'] ?? null;
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$p['id']]);
        }
        $_SESSION['full_name'] = $p['full_name'];
        $_SESSION['email'] = $p['email'];
        $_SESSION['user_type'] = $p['type'];
        $pdo->prepare("INSERT INTO login_attempts (email, ip_address, was_successful) VALUES (?, ?, 1)")->execute([$p['email'], $_SERVER['REMOTE_ADDR']]);
        logAction($pdo, $p['id'], $p['type'], 'login', '2FA verified');
        header('Location: dashboard.php');
        exit();
    }
}

// ---- STEP 1: email + password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['step']) || $_POST['step'] === 'credentials')) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    if (isAccountLocked($pdo, $email)) {
        $error = "This account has been locked after 5 failed attempts. Please try again in 15 minutes.";
    } else {
        $stmt = $pdo->prepare("SELECT client_id as id, full_name, email, password_hash, status, two_factor_enabled FROM clients WHERE email = ?");
        $stmt->execute([$email]);
        $account = $stmt->fetch();
        $type = 'client';

        if (!$account) {
            $stmt = $pdo->prepare("SELECT user_id as id, full_name, email, password_hash, role, department, is_active, two_factor_enabled FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $account = $stmt->fetch();
            $type = 'user';
        }

        $active = $account && ($type === 'client' ? $account['status'] === 'active' : $account['is_active'] == 1);

        if ($account && $active && password_verify($password, $account['password_hash'])) {
            if (!empty($account['two_factor_enabled'])) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $_SESSION['pending_2fa'] = [
                    'id' => $account['id'], 'type' => $type, 'full_name' => $account['full_name'],
                    'email' => $account['email'], 'role' => $account['role'] ?? null,
                    'department' => $account['department'] ?? null,
                    'code' => $code, 'expires' => time() + 300,
                ];
                $demo_code = $code; // demo-mode display in place of a real SMS dispatch
            } else {
                if ($type === 'client') { $_SESSION['client_id'] = $account['id']; }
                else {
                    $_SESSION['user_id'] = $account['id']; $_SESSION['role'] = $account['role'];
                    $_SESSION['department'] = $account['department'] ?? null;
                    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?")->execute([$account['id']]);
                }
                $_SESSION['full_name'] = $account['full_name'];
                $_SESSION['email'] = $account['email'];
                $_SESSION['user_type'] = $type;
                $pdo->prepare("INSERT INTO login_attempts (email, ip_address, was_successful) VALUES (?, ?, 1)")->execute([$email, $ip]);
                logAction($pdo, $account['id'], $type, 'login', null);
                header('Location: dashboard.php');
                exit();
            }
        } else {
            $pdo->prepare("INSERT INTO login_attempts (email, ip_address, was_successful) VALUES (?, ?, 0)")->execute([$email, $ip]);
            $error = "Invalid email or password.";
        }
    }
}

$awaiting_2fa = isset($_SESSION['pending_2fa']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Secure Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body{ background:linear-gradient(135deg,#0f172a 0%,#1d4ed8 100%); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
        .login-container{ background:#fff; border-radius:1.25rem; padding:2.5rem; width:100%; max-width:440px; box-shadow:0 30px 60px -15px rgba(0,0,0,.45); }
        .login-head{ text-align:center; margin-bottom:1.75rem; }
        .login-head h1{ font-size:1.5rem; font-weight:800; color:var(--navy); margin-top:.9rem; }
        .login-head p{ color:var(--gray-500); font-size:.88rem; margin-top:.2rem; }
        .lock-note{ text-align:center; font-size:.76rem; color:var(--gray-400); margin-top:.9rem; }
        .demo-box{ background:var(--primary-light); border:1px dashed var(--primary); color:var(--primary-dark); padding:.85rem 1rem; border-radius:.6rem; margin-bottom:1.25rem; font-size:.82rem; line-height:1.5; }
        .demo-info{ background:var(--gray-50); padding:1rem; border-radius:.6rem; margin-top:1.25rem; font-size:.8rem; text-align:center; color:var(--gray-600); border:1px solid var(--gray-200); }
        .links-row{ text-align:center; margin-top:1.5rem; font-size:.88rem; }
        .links-row a{ color:var(--primary); font-weight:600; text-decoration:none; }
        .links-row a:hover{ text-decoration:underline; }
        .code-input{ text-align:center; letter-spacing:.6em; font-size:1.4rem; font-weight:700; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-head">
            <span class="brand" style="justify-content:center;"><span class="brand-icon"><i class="fas fa-shield-halved"></i></span><span class="brand-text">APEX<span>Ledger</span></span></span>
            <h1 style="margin-top:1.1rem;"><?php echo $awaiting_2fa ? 'Two-Factor Verification' : 'Secure Login'; ?></h1>
            <p>Ntuli Accountants &amp; Associates &mdash; Client Portal</p>
        </div>

        <?php if($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if(isset($_GET['idle'])): ?><div class="alert alert-warning"><i class="fas fa-clock"></i> You were signed out after 30 minutes of inactivity for your security.</div><?php endif; ?>

        <?php if($awaiting_2fa): ?>
            <?php if($demo_code): ?>
                <div class="demo-box"><i class="fas fa-mobile-screen-button"></i> <strong>Demo mode:</strong> in production this code is sent via SMS. Your code is <strong><?php echo $demo_code; ?></strong> (expires in 5 minutes).</div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="step" value="2fa">
                <div class="form-group">
                    <label>6-digit SMS code</label>
                    <input type="text" name="code" class="code-input" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus placeholder="------">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Verify &amp; Sign In</button>
            </form>
            <div class="lock-note">Code expires 5 minutes after it was sent.</div>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="step" value="credentials">
                <div class="form-group"><label>Email Address</label><input type="email" name="email" required placeholder="your@email.com"></div>
                <div class="form-group"><label>Password</label><input type="password" name="password" required placeholder="••••••••"></div>
                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            <div class="lock-note">5 failed attempts will temporarily lock this account for 15 minutes.</div>
            <div class="demo-info"><strong>Demo Credentials</strong><br>Client: thabo@example.com / Password123!<br>Staff (2FA on): admin@ntuli.co.za / Password123!<br>Staff (no 2FA): acc1@ntuli.co.za / Password123!</div>
            <div class="links-row">New client? <a href="register.php">Create an account</a> &nbsp;|&nbsp; <a href="index.php">Back to Home</a></div>
        <?php endif; ?>
    </div>
</body>
</html>
