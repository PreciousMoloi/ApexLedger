<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$is_client = isset($_SESSION['client_id']);
$user_name = $_SESSION['full_name'];
$user_id = $is_client ? $_SESSION['client_id'] : $_SESSION['user_id'];
$active_page = 'dashboard';

// Fetch data based on user type
if ($is_client) {
    $cid = $_SESSION['client_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM documents WHERE client_id = ?");
    $stmt->execute([$cid]);
    $doc_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages m JOIN message_threads t ON m.thread_id = t.thread_id WHERE t.client_id = ? AND m.read_timestamp IS NULL AND m.sender_type != 'client'");
    $stmt->execute([$cid]);
    $msg_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_tickets WHERE client_id = ? AND status NOT IN ('completed','cancelled')");
    $stmt->execute([$cid]);
    $ticket_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_tickets WHERE client_id = ? AND due_date < CURDATE() AND status NOT IN ('completed','cancelled')");
    $stmt->execute([$cid]);
    $overdue_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT ticket_number, service_type, status, priority, due_date FROM service_tickets WHERE client_id = ? ORDER BY created_date DESC LIMIT 5");
    $stmt->execute([$cid]);
    $recent_tickets = $stmt->fetchAll();
} elseif ($_SESSION['role'] == 'accountant') {
    $uid = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE assigned_accountant_id = ? AND status = 'active'");
    $stmt->execute([$uid]);
    $client_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_tickets WHERE assigned_accountant_id = ? AND status NOT IN ('completed','cancelled')");
    $stmt->execute([$uid]);
    $ticket_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_tickets WHERE assigned_accountant_id = ? AND due_date < CURDATE() AND status NOT IN ('completed','cancelled')");
    $stmt->execute([$uid]);
    $overdue_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages m JOIN message_threads t ON m.thread_id = t.thread_id WHERE t.accountant_id = ? AND m.read_timestamp IS NULL AND m.sender_type != 'user'");
    $stmt->execute([$uid]);
    $msg_count = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT t.ticket_number, t.service_type, t.status, t.priority, t.due_date, c.full_name as client_name FROM service_tickets t JOIN clients c ON t.client_id = c.client_id WHERE t.assigned_accountant_id = ? ORDER BY t.created_date DESC LIMIT 5");
    $stmt->execute([$uid]);
    $recent_tickets = $stmt->fetchAll();
} else {
    $client_count = $pdo->query("SELECT COUNT(*) FROM clients WHERE status = 'active'")->fetchColumn();
    $ticket_count = $pdo->query("SELECT COUNT(*) FROM service_tickets WHERE status NOT IN ('completed','cancelled')")->fetchColumn();
    $accountant_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'accountant' AND is_active = 1")->fetchColumn();
    $overdue_count = $pdo->query("SELECT COUNT(*) FROM service_tickets WHERE due_date < CURDATE() AND status NOT IN ('completed','cancelled')")->fetchColumn();
    $recent_tickets = $pdo->query("SELECT t.ticket_number, t.service_type, t.status, t.priority, t.due_date, c.full_name as client_name FROM service_tickets t JOIN clients c ON t.client_id = c.client_id ORDER BY t.created_date DESC LIMIT 5")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div>
                <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                <p class="date-line">
                    <i class="fas fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
                    <?php if (!$is_client && $_SESSION['role'] == 'accountant' && !empty($_SESSION['department'])): ?>
                        &nbsp;&middot;&nbsp;<span class="badge badge-blue"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($_SESSION['department']); ?> duty</span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="topbar-actions">
                <button class="btn-icon" onclick="toggleTheme()" title="Toggle dark mode"><i class="fas fa-moon"></i></button>
                <div class="avatar" onclick="location.href='profile.php'"><?php echo strtoupper(substr($user_name, 0, 2)); ?></div>
            </div>
        </div>

        <div class="stats-grid">
            <?php if ($is_client): ?>
                <div class="stat-card c-blue"><div class="stat-label"><i class="fas fa-file-invoice"></i> Documents</div><div class="stat-value"><?php echo $doc_count; ?></div></div>
                <div class="stat-card c-purple"><div class="stat-label"><i class="fas fa-envelope"></i> Unread Messages</div><div class="stat-value"><?php echo $msg_count; ?></div></div>
                <div class="stat-card c-amber"><div class="stat-label"><i class="fas fa-tasks"></i> Active Tickets</div><div class="stat-value"><?php echo $ticket_count; ?></div></div>
                <div class="stat-card c-red"><div class="stat-label"><i class="fas fa-triangle-exclamation"></i> Overdue</div><div class="stat-value"><?php echo $overdue_count; ?></div></div>
            <?php elseif ($_SESSION['role'] == 'accountant'): ?>
                <div class="stat-card c-blue"><div class="stat-label"><i class="fas fa-users"></i> My Clients</div><div class="stat-value"><?php echo $client_count; ?></div></div>
                <div class="stat-card c-amber"><div class="stat-label"><i class="fas fa-tasks"></i> Open Tickets</div><div class="stat-value"><?php echo $ticket_count; ?></div></div>
                <div class="stat-card c-purple"><div class="stat-label"><i class="fas fa-envelope"></i> Unread Messages</div><div class="stat-value"><?php echo $msg_count; ?></div></div>
                <div class="stat-card c-red"><div class="stat-label"><i class="fas fa-triangle-exclamation"></i> Overdue</div><div class="stat-value"><?php echo $overdue_count; ?></div></div>
            <?php else: ?>
                <div class="stat-card c-blue"><div class="stat-label"><i class="fas fa-user-plus"></i> Active Clients</div><div class="stat-value"><?php echo $client_count; ?></div></div>
                <div class="stat-card c-amber"><div class="stat-label"><i class="fas fa-ticket"></i> Open Tickets</div><div class="stat-value"><?php echo $ticket_count; ?></div></div>
                <div class="stat-card c-green"><div class="stat-label"><i class="fas fa-user-tie"></i> Accountants</div><div class="stat-value"><?php echo $accountant_count; ?></div></div>
                <div class="stat-card c-red"><div class="stat-label"><i class="fas fa-triangle-exclamation"></i> Overdue</div><div class="stat-value"><?php echo $overdue_count; ?></div></div>
            <?php endif; ?>
        </div>

        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-clock-rotate-left"></i> Recent Service Tickets</h3><a href="tickets.php" class="btn btn-primary btn-sm">View All <i class="fas fa-arrow-right"></i></a></div>
            <div class="table-scroll">
            <table class="data-table"><thead><tr><th>Ticket #</th><th>Client</th><th>Type</th><th>Priority</th><th>Status</th><th>Due Date</th></tr></thead>
            <tbody>
            <?php if (empty($recent_tickets)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--gray-500); padding:2rem;">No service tickets yet.</td></tr>
            <?php endif; ?>
            <?php foreach($recent_tickets as $t): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($t['ticket_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($t['client_name'] ?? 'You'); ?></td>
                    <td style="text-transform:capitalize;"><?php echo str_replace('_', ' ', $t['service_type']); ?></td>
                    <td><span class="badge priority-<?php echo $t['priority']; ?>"><?php echo ucfirst($t['priority']); ?></span></td>
                    <td><span class="badge status-<?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($t['due_date'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
