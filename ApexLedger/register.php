<?php
require_once 'config/database.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $tax_number = trim($_POST['tax_number']);
    $address = trim($_POST['address']);
    $service_preference = $_POST['service_preference'] ?? null;
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) $errors[] = "Passwords do not match.";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Password must contain an uppercase letter.";
    if (!preg_match('/[0-9]/', $password)) $errors[] = "Password must contain a number.";
    if (!preg_match('/^[0-9]{10}$/', preg_replace('/\s+/', '', $phone))) $errors[] = "Phone number must be 10 digits.";
    if (!preg_match('/^[0-9]{10}$/', $tax_number)) $errors[] = "Tax number must be 10 digits.";

    $check = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE email = ? OR tax_number = ?");
    $check->execute([$email, $tax_number]);
    if ($check->fetchColumn() > 0) $errors[] = "Email or Tax Number already registered.";

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $accountant = $pdo->query("SELECT user_id FROM users WHERE role = 'accountant' AND is_active = 1 ORDER BY RAND() LIMIT 1")->fetch();
        $accountant_id = $accountant ? $accountant['user_id'] : null;

        $stmt = $pdo->prepare("INSERT INTO clients (full_name, email, phone, tax_number, physical_address, password_hash, assigned_accountant_id, service_preference) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$full_name, $email, $phone, $tax_number, $address, $password_hash, $accountant_id, $service_preference])) {
            $newId = $pdo->lastInsertId();
            logAction($pdo, $newId, 'client', 'register', 'New client self-registration');
            $success = "Registration successful! <a href='login.php'>Click here to login</a>";
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Register with Ntuli Accountants</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body{ background:linear-gradient(160deg,var(--gray-50) 0%,var(--primary-light) 100%); }
        .container{ max-width:640px; margin:7.5rem auto 3rem; padding:2.25rem; background:#fff; border-radius:1.25rem; box-shadow:var(--shadow-lg); }
        .form-title{ display:flex; align-items:center; gap:.75rem; margin-bottom:.3rem; }
        .form-title .brand-icon{ width:34px; height:34px; font-size:.9rem; }
        h2{ font-size:1.5rem; color:var(--navy); font-weight:800; }
        .subtitle{ color:var(--gray-500); margin-bottom:1.75rem; font-size:.92rem; }
        .form-actions{ display:flex; gap:.75rem; margin-top:.5rem; }
        .login-link{ text-align:center; margin-top:1.5rem; font-size:.9rem; }
        .login-link a{ color:var(--primary); font-weight:600; text-decoration:none; }
        @media (max-width:600px){ .container{ margin:6rem 1rem 2rem; padding:1.5rem; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand"><span class="brand-icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="brand-text">APEX<span>Ledger</span></span></a>
        <div class="nav-links"><a href="index.php">Home</a><a href="about.php">About</a><a href="contact.php">Contact</a><a href="login.php">Login</a></div>
    </nav>
    <div class="container">
        <div class="form-title"><span class="brand-icon"><i class="fas fa-user-plus"></i></span><h2>New Client Onboarding</h2></div>
        <p class="subtitle">Register with Ntuli Accountants and Associates for secure financial services. Fields marked with <strong>*</strong> are required.</p>

        <?php foreach($errors as $error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></div><?php endforeach; ?>
        <?php if (isset($success)): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required placeholder="e.g. John Smith"></div>
            <div class="form-row">
                <div class="form-group"><label>Email *</label><input type="email" name="email" required placeholder="client@example.com"></div>
                <div class="form-group"><label>Phone *</label><input type="tel" name="phone" required placeholder="082 000 0000"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Tax Number (10 digits) *</label><input type="text" name="tax_number" required placeholder="e.g. 1234567890"></div>
                <div class="form-group"><label>Service Preference</label>
                    <select name="service_preference">
                        <option value="">Select a service...</option>
                        <option value="taxation">Taxation</option>
                        <option value="bookkeeping">Bookkeeping</option>
                        <option value="payroll">Payroll</option>
                        <option value="advisory">Advisory</option>
                        <option value="audit">Audit</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Physical Address *</label><textarea name="address" rows="2" required placeholder="Street, suburb, city, postal code"></textarea></div>
            <div class="form-row">
                <div class="form-group"><label>Password *</label><input type="password" name="password" required placeholder="Min 8 chars, 1 uppercase, 1 number"></div>
                <div class="form-group"><label>Confirm Password *</label><input type="password" name="confirm_password" required></div>
            </div>
            <div class="form-actions">
                <a href="index.php" class="btn btn-outline-light btn-block">Cancel</a>
                <button type="submit" class="btn btn-primary btn-block">Register Client</button>
            </div>
        </form>
        <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
    </div>
</body>
</html>
