<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}
$active_page = 'admin';
$is_client = false;
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $role = $_POST['role'];
    $department = trim($_POST['department']);
    $password_hash = password_hash('Password123!', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, department) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$email, $password_hash, $full_name, $role, $department])) {
        logAction($pdo, $_SESSION['user_id'], 'user', 'admin_add_user', "$email ($role)");
        $success = "Staff member added! Default password: Password123!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $user_id = $_POST['user_id'];
    $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?")->execute([$user_id]);
    logAction($pdo, $_SESSION['user_id'], 'user', 'admin_toggle_user', "user #$user_id");
    header('Location: admin.php?tab=users');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_client_status'])) {
    $client_id = $_POST['client_id'];
    $new_status = $_POST['new_status'];
    $pdo->prepare("UPDATE clients SET status = ? WHERE client_id = ?")->execute([$new_status, $client_id]);
    logAction($pdo, $_SESSION['user_id'], 'user', 'admin_toggle_client', "client #$client_id -> $new_status");
    header('Location: admin.php?tab=users');
    exit();
}

// Admin can reassign which accountant a client's queries route to (staffing,
// not a message approval step - queries themselves still go straight to the
// accountant, this just changes who that accountant is).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_accountant'])) {
    $client_id = $_POST['client_id'];
    $new_accountant_id = $_POST['accountant_id'];
    $pdo->prepare("UPDATE clients SET assigned_accountant_id = ? WHERE client_id = ?")->execute([$new_accountant_id, $client_id]);
    logAction($pdo, $_SESSION['user_id'], 'user', 'admin_reassign_client', "client #$client_id -> accountant #$new_accountant_id");
    header('Location: admin.php?tab=users');
    exit();
}

$staff = $pdo->query("SELECT * FROM users ORDER BY role, full_name")->fetchAll();
$duty_accountants = $pdo->query("SELECT user_id, full_name, department FROM users WHERE role='accountant' AND is_active=1 ORDER BY department")->fetchAll();
$clients = $pdo->query("SELECT c.*, u.full_name as accountant_name FROM clients c LEFT JOIN users u ON c.assigned_accountant_id = u.user_id ORDER BY c.registration_date DESC")->fetchAll();
$audit_logs = $pdo->query("SELECT * FROM audit_log ORDER BY timestamp DESC LIMIT 40")->fetchAll();

// ---- System Health (derived from real data, no fake numbers) ----
$total_storage = $pdo->query("SELECT COALESCE(SUM(file_size_bytes),0) FROM documents")->fetchColumn();
$storage_mb = round($total_storage / 1048576, 2);
$active_clients = $pdo->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn();
$active_staff = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active=1")->fetchColumn();
$pending_docs = $pdo->query("SELECT COUNT(*) FROM documents WHERE review_status IN ('pending','under_review')")->fetchColumn();
$events_today = $pdo->query("SELECT COUNT(*) FROM audit_log WHERE DATE(timestamp) = CURDATE()")->fetchColumn();
$failed_logins_today = $pdo->query("SELECT COUNT(*) FROM login_attempts WHERE was_successful=0 AND DATE(attempt_time)=CURDATE()")->fetchColumn();
$open_tickets = $pdo->query("SELECT COUNT(*) FROM service_tickets WHERE status NOT IN ('completed','cancelled')")->fetchColumn();
$tab = $_GET['tab'] ?? 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Admin Control Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .health-dot{ width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:.4rem; }
        .matrix-table td, .matrix-table th{ text-align:center; }
        .matrix-table td:first-child, .matrix-table th:first-child{ text-align:left; }
        .matrix-check{ color:var(--success); font-size:1.1rem; }
        .matrix-cross{ color:var(--gray-300); font-size:1.1rem; }
    </style>
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div><h2>Admin Control Panel</h2><p class="date-line">System administration &amp; user management</p></div>
            <span class="badge badge-green"><i class="fas fa-circle" style="font-size:.5rem;"></i> System Online</span>
        </div>

        <?php if($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div><?php endif; ?>

        <div class="tabs">
            <a href="?tab=users" class="tab <?php echo $tab=='users'?'active':''; ?>" style="text-decoration:none;">User Management</a>
            <a href="?tab=permissions" class="tab <?php echo $tab=='permissions'?'active':''; ?>" style="text-decoration:none;">Permissions Matrix</a>
            <a href="?tab=audit" class="tab <?php echo $tab=='audit'?'active':''; ?>" style="text-decoration:none;">Audit Log</a>
            <a href="?tab=health" class="tab <?php echo $tab=='health'?'active':''; ?>" style="text-decoration:none;">System Health</a>
        </div>

        <?php if($tab=='users'): ?>
            <div class="section-card">
                <div class="section-header"><h3><i class="fas fa-user-tie"></i> Staff Accounts</h3></div>
                <form method="POST" style="margin-bottom:1.5rem;">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Role</label><select name="role"><option value="accountant">Accountant</option><option value="manager">Manager</option><option value="admin">Admin</option></select></div>
                        <div class="form-group"><label>Duty / Department</label>
                            <select name="department">
                                <option value="Taxation">Taxation (accountant)</option>
                                <option value="Bookkeeping">Bookkeeping (accountant)</option>
                                <option value="Payroll">Payroll (accountant)</option>
                                <option value="Advisory">Advisory (accountant)</option>
                                <option value="Audit">Audit (accountant)</option>
                                <option value="Operations">Operations (manager)</option>
                                <option value="Administration">Administration (admin)</option>
                            </select>
                        </div>
                    </div>
                    <p style="color:var(--gray-500); font-size:.8rem; margin:-.5rem 0 1rem;">Every accountant owns one duty. Client queries about that duty are routed to them directly — clients never need admin sign-off to reach the right person.</p>
                    <button type="submit" name="add_user" class="btn btn-success">Create User</button>
                </form>
                <div class="table-scroll">
                <table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>2FA</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($staff as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                    <td><span class="badge badge-blue"><?php echo ucfirst($s['role']); ?></span></td>
                    <td><?php echo htmlspecialchars($s['department'] ?? '—'); ?></td>
                    <td><?php echo $s['two_factor_enabled'] ? '<span class="badge badge-green">On</span>' : '<span class="badge badge-gray">Off</span>'; ?></td>
                    <td><?php echo $s['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Inactive</span>'; ?></td>
                    <td><form method="POST"><input type="hidden" name="user_id" value="<?php echo $s['user_id']; ?>"><button type="submit" name="toggle_status" class="btn btn-outline-light btn-sm"><?php echo $s['is_active'] ? 'Deactivate' : 'Reactivate'; ?></button></form></td>
                </tr>
                <?php endforeach; ?>
                </tbody></table>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3><i class="fas fa-users"></i> Clients</h3></div>
                <div class="table-scroll">
                <table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>Tax #</th><th>Duty</th><th>Accountant</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach($clients as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['email']); ?></td>
                    <td><?php echo htmlspecialchars($c['tax_number']); ?></td>
                    <td><?php echo $c['service_preference'] ? '<span class="badge badge-blue">' . ucfirst($c['service_preference']) . '</span>' : '—'; ?></td>
                    <td>
                        <form method="POST" style="display:flex; gap:.4rem; align-items:center;">
                            <input type="hidden" name="client_id" value="<?php echo $c['client_id']; ?>">
                            <select name="accountant_id" style="min-width:150px;">
                                <?php foreach($duty_accountants as $a): ?>
                                    <option value="<?php echo $a['user_id']; ?>" <?php echo ($c['assigned_accountant_id'] == $a['user_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($a['full_name']) . ' (' . htmlspecialchars($a['department']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="reassign_accountant" class="btn btn-outline-light btn-sm">Save</button>
                        </form>
                    </td>
                    <td><span class="badge status-<?php echo $c['status']=='active'?'completed':($c['status']=='archived'?'cancelled':'in_progress'); ?>"><?php echo ucfirst($c['status']); ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="client_id" value="<?php echo $c['client_id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo $c['status']=='active' ? 'archived' : 'active'; ?>">
                            <button type="submit" name="toggle_client_status" class="btn btn-outline-light btn-sm"><?php echo $c['status']=='active' ? 'Archive' : 'Activate'; ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody></table>
                </div>
            </div>

        <?php elseif($tab=='permissions'): ?>
            <div class="section-card">
                <div class="section-header"><h3><i class="fas fa-table-cells"></i> Permissions Matrix</h3></div>
                <p style="color:var(--gray-500); font-size:.88rem; margin-bottom:1.25rem;">Role-based access control as defined in the Authentication &amp; Security subsystem (UC-18). Client queries route straight to the responsible accountant by duty — Admin is not a required step in that path.</p>
                <div class="table-scroll">
                <table class="data-table matrix-table">
                    <thead><tr><th>Permission</th><th>Admin</th><th>Manager</th><th>Accountant</th><th>Client</th></tr></thead>
                    <tbody>
                    <?php
                    $perms = [
                        ['View own tickets / documents', true, true, true, true],
                        ['Create service tickets', true, false, true, false],
                        ['Update ticket status', true, true, true, false],
                        ['Upload documents', true, false, true, true],
                        ['Review / approve documents', true, true, true, false],
                        ['Send secure messages', true, true, true, true],
                        ['View management reports', true, true, false, false],
                        ['Manage staff users', true, false, false, false],
                        ['View audit log', true, false, false, false],
                        ['System settings', true, false, false, false],
                    ];
                    foreach($perms as $p): ?>
                        <tr>
                            <td><?php echo $p[0]; ?></td>
                            <td><i class="fas <?php echo $p[1]?'fa-circle-check matrix-check':'fa-circle-xmark matrix-cross'; ?>"></i></td>
                            <td><i class="fas <?php echo $p[2]?'fa-circle-check matrix-check':'fa-circle-xmark matrix-cross'; ?>"></i></td>
                            <td><i class="fas <?php echo $p[3]?'fa-circle-check matrix-check':'fa-circle-xmark matrix-cross'; ?>"></i></td>
                            <td><i class="fas <?php echo $p[4]?'fa-circle-check matrix-check':'fa-circle-xmark matrix-cross'; ?>"></i></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

        <?php elseif($tab=='audit'): ?>
            <div class="section-card">
                <div class="section-header"><h3><i class="fas fa-clipboard-list"></i> Audit Log</h3><span style="color:var(--gray-500); font-size:.85rem;">Last 40 events &middot; NFR-Security compliance</span></div>
                <div class="table-scroll">
                <table class="data-table"><thead><tr><th>Timestamp</th><th>User Type</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
                <tbody>
                <?php if(empty($audit_logs)): ?><tr><td colspan="5" style="text-align:center; color:var(--gray-500); padding:2rem;">No audit events recorded yet.</td></tr><?php endif; ?>
                <?php foreach($audit_logs as $log): ?>
                <tr>
                    <td><?php echo date('d M Y H:i:s', strtotime($log['timestamp'])); ?></td>
                    <td><span class="badge badge-blue"><?php echo ucfirst($log['user_type']); ?></span></td>
                    <td style="text-transform:capitalize;"><?php echo str_replace('_',' ',$log['action']); ?></td>
                    <td><?php echo htmlspecialchars($log['details'] ?? '—'); ?></td>
                    <td style="font-family:monospace; font-size:.78rem;"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody></table>
                </div>
            </div>

        <?php else: ?>
            <div class="stats-grid">
                <div class="stat-card c-purple"><div class="stat-label"><i class="fas fa-database"></i> Storage Used</div><div class="stat-value"><?php echo $storage_mb; ?> MB</div></div>
                <div class="stat-card c-blue"><div class="stat-label"><i class="fas fa-user-check"></i> Active Clients</div><div class="stat-value"><?php echo $active_clients; ?></div></div>
                <div class="stat-card c-green"><div class="stat-label"><i class="fas fa-user-tie"></i> Active Staff</div><div class="stat-value"><?php echo $active_staff; ?></div></div>
                <div class="stat-card c-amber"><div class="stat-label"><i class="fas fa-file-circle-question"></i> Docs Pending Review</div><div class="stat-value"><?php echo $pending_docs; ?></div></div>
                <div class="stat-card c-red"><div class="stat-label"><i class="fas fa-triangle-exclamation"></i> Failed Logins Today</div><div class="stat-value"><?php echo $failed_logins_today; ?></div></div>
                <div class="stat-card c-blue"><div class="stat-label"><i class="fas fa-clock-rotate-left"></i> Audit Events Today</div><div class="stat-value"><?php echo $events_today; ?></div></div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3><i class="fas fa-server"></i> Service Status</h3></div>
                <table class="data-table">
                    <tbody>
                        <tr><td><span class="health-dot" style="background:var(--success);"></span> Database (MySQL / WAMP)</td><td style="text-align:right;"><span class="badge badge-green">Operational</span></td></tr>
                        <tr><td><span class="health-dot" style="background:var(--success);"></span> Document Storage</td><td style="text-align:right;"><span class="badge badge-green">Operational</span></td></tr>
                        <tr><td><span class="health-dot" style="background:var(--success);"></span> Authentication Service</td><td style="text-align:right;"><span class="badge badge-green">Operational</span></td></tr>
                        <tr><td><span class="health-dot" style="background:<?php echo $open_tickets > 20 ? 'var(--warning)' : 'var(--success)'; ?>;"></span> Service Ticket Queue (<?php echo $open_tickets; ?> open)</td><td style="text-align:right;"><span class="badge <?php echo $open_tickets > 20 ? 'badge-amber' : 'badge-green'; ?>"><?php echo $open_tickets > 20 ? 'High Load' : 'Normal'; ?></span></td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
