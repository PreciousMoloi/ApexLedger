<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$is_client = isset($_SESSION['client_id']);
$user_id = $is_client ? $_SESSION['client_id'] : $_SESSION['user_id'];
$active_page = 'tickets';
$role = $is_client ? 'client' : $_SESSION['role'];
$can_create = ($role === 'accountant'); // UC-12: Accountant creates service tickets

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket']) && $can_create) {
    $service_type = $_POST['service_type'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $description = trim($_POST['description']);

    $prefix = strtoupper(substr($service_type, 0, 3));
    $year = date('Y');
    $count = $pdo->query("SELECT COUNT(*) FROM service_tickets WHERE YEAR(created_date) = $year")->fetchColumn() + 1;
    $ticket_number = $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

    $client_id = $_POST['client_id'];
    $accountant_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO service_tickets (ticket_number, client_id, assigned_accountant_id, service_type, priority, due_date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ticket_number, $client_id, $accountant_id, $service_type, $priority, $due_date, $description]);
    $new_id = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO ticket_history (ticket_id, new_status, changed_by, change_reason) VALUES (?, 'open', ?, 'Ticket created')")->execute([$new_id, $accountant_id]);
    logAction($pdo, $user_id, 'user', 'ticket_create', "$ticket_number created");
    header('Location: tickets.php?success=created');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && !$is_client) {
    $ticket_id = $_POST['ticket_id'];
    $new_status = $_POST['status'];
    $old = $pdo->prepare("SELECT status FROM service_tickets WHERE ticket_id = ?"); $old->execute([$ticket_id]); $old_status = $old->fetchColumn();
    $pdo->prepare("UPDATE service_tickets SET status = ? WHERE ticket_id = ?")->execute([$new_status, $ticket_id]);
    if ($new_status == 'completed') {
        $pdo->prepare("UPDATE service_tickets SET completed_date = NOW() WHERE ticket_id = ?")->execute([$ticket_id]);
    }
    $pdo->prepare("INSERT INTO ticket_history (ticket_id, old_status, new_status, changed_by) VALUES (?, ?, ?, ?)")->execute([$ticket_id, $old_status, $new_status, $user_id]);
    logAction($pdo, $user_id, 'user', 'ticket_status_update', "Ticket #$ticket_id: $old_status -> $new_status");
    header('Location: tickets.php?success=updated');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notes']) && !$is_client) {
    $pdo->prepare("UPDATE service_tickets SET internal_notes = ? WHERE ticket_id = ?")->execute([trim($_POST['internal_notes']), $_POST['ticket_id']]);
    logAction($pdo, $user_id, 'user', 'ticket_note_update', "Ticket #" . $_POST['ticket_id']);
    header('Location: tickets.php?success=updated');
    exit();
}

// Filters
$f_status = $_GET['status'] ?? '';
$f_priority = $_GET['priority'] ?? '';
$where = []; $params = [];

if ($is_client) {
    $where[] = "t.client_id = ?"; $params[] = $_SESSION['client_id'];
} elseif ($role == 'accountant') {
    $where[] = "t.assigned_accountant_id = ?"; $params[] = $_SESSION['user_id'];
}
if ($f_status) { $where[] = "t.status = ?"; $params[] = $f_status; }
if ($f_priority) { $where[] = "t.priority = ?"; $params[] = $f_priority; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT t.*, c.full_name as client_name, u.full_name as accountant_name FROM service_tickets t
        JOIN clients c ON t.client_id = c.client_id
        JOIN users u ON t.assigned_accountant_id = u.user_id
        $whereSql
        ORDER BY FIELD(t.status,'open','in_progress','awaiting_documents','under_review','completed','cancelled'), t.due_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$all_tickets = $stmt->fetchAll();

$clients = [];
if ($can_create) {
    $stmt = $pdo->prepare("SELECT client_id, full_name FROM clients WHERE assigned_accountant_id = ? AND status='active'");
    $stmt->execute([$_SESSION['user_id']]);
    $clients = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Service Tickets</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .filter-bar{ display:flex; gap:.75rem; flex-wrap:wrap; margin-bottom:1.25rem; }
        .filter-bar select{ width:auto; min-width:160px; }
        .notes-panel{ background:var(--gray-50); border:1px solid var(--gray-200); border-radius:var(--radius-sm); padding:.85rem; }
        .notes-panel summary{ cursor:pointer; color:var(--primary); font-weight:600; font-size:.8rem; list-style:none; }
        .notes-panel summary::-webkit-details-marker{ display:none; }
        .empty-state{ text-align:center; padding:3rem 1rem; color:var(--gray-500); }
        .empty-state i{ font-size:2.5rem; color:var(--gray-300); margin-bottom:.75rem; }
    </style>
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div><h2>Service Tickets</h2><p class="date-line">Track tax returns, payroll, advisory &amp; audit work</p></div>
            <?php if($is_client): ?><a href="messages.php?subject=New%20Service%20Request" class="btn btn-success"><i class="fas fa-plus"></i> Request a Service</a><?php endif; ?>
        </div>

        <?php if(isset($_GET['success'])): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> Ticket <?php echo htmlspecialchars($_GET['success']); ?> successfully!</div><?php endif; ?>

        <?php if($can_create): ?>
        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-circle-plus"></i> New Service Request</h3></div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group"><label>Client</label><select name="client_id" required><option value="">Select Client</option><?php foreach($clients as $c): ?><option value="<?php echo $c['client_id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Service Type</label><select name="service_type" required><option value="tax_return">Tax Return</option><option value="payroll">Payroll</option><option value="bookkeeping">Bookkeeping</option><option value="advisory">Advisory</option><option value="audit">Audit</option></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Priority</label><select name="priority" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                    <div class="form-group"><label>Due Date</label><input type="date" name="due_date" required min="<?php echo date('Y-m-d'); ?>"></div>
                </div>
                <div class="form-group"><label>Description</label><textarea name="description" rows="3" required placeholder="Describe the scope of work..."></textarea></div>
                <button type="submit" name="create_ticket" class="btn btn-success"><i class="fas fa-ticket"></i> Create Ticket</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-list-check"></i> <?php echo $is_client ? 'My Tickets' : 'All Tickets'; ?></h3><span style="color:var(--gray-500); font-size:.85rem;"><?php echo count($all_tickets); ?> ticket(s)</span></div>

            <form method="GET" class="filter-bar">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <?php foreach(['open','in_progress','awaiting_documents','under_review','completed','cancelled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $f_status==$s?'selected':''; ?>><?php echo ucwords(str_replace('_',' ',$s)); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="priority" onchange="this.form.submit()">
                    <option value="">All priorities</option>
                    <?php foreach(['low','medium','high','urgent'] as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo $f_priority==$p?'selected':''; ?>><?php echo ucfirst($p); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if($f_status || $f_priority): ?><a href="tickets.php" class="btn btn-outline-light btn-sm">Clear filters</a><?php endif; ?>
            </form>

            <?php if(empty($all_tickets)): ?>
                <div class="empty-state"><i class="fas fa-ticket"></i><p>No tickets match these filters.</p></div>
            <?php else: ?>
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>Ticket #</th><th>Client</th><th>Type</th><th>Priority</th><th>Status</th><th>Due Date</th><?php if(!$is_client): ?><th>Update</th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach($all_tickets as $t):
                    $overdue = strtotime($t['due_date']) < strtotime(date('Y-m-d')) && !in_array($t['status'], ['completed','cancelled']);
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($t['ticket_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($t['client_name']); ?></td>
                        <td style="text-transform:capitalize;"><?php echo str_replace('_', ' ', $t['service_type']); ?></td>
                        <td><span class="badge priority-<?php echo $t['priority']; ?>"><?php echo ucfirst($t['priority']); ?></span></td>
                        <td><span class="badge status-<?php echo $t['status']; ?>"><?php echo str_replace('_', ' ', $t['status']); ?></span></td>
                        <td><?php echo date('d M Y', strtotime($t['due_date'])); ?> <?php if($overdue): ?><span class="badge badge-red" style="margin-left:.3rem;">Overdue</span><?php endif; ?></td>
                        <?php if(!$is_client): ?>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="ticket_id" value="<?php echo $t['ticket_id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="open" <?php echo $t['status']=='open'?'selected':''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $t['status']=='in_progress'?'selected':''; ?>>In Progress</option>
                                    <option value="awaiting_documents" <?php echo $t['status']=='awaiting_documents'?'selected':''; ?>>Awaiting Docs</option>
                                    <option value="under_review" <?php echo $t['status']=='under_review'?'selected':''; ?>>Under Review</option>
                                    <option value="completed" <?php echo $t['status']=='completed'?'selected':''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $t['status']=='cancelled'?'selected':''; ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php if(!$is_client): ?>
                    <tr>
                        <td colspan="7" style="padding-top:0;">
                            <details class="notes-panel">
                                <summary><i class="fas fa-note-sticky"></i> Internal notes</summary>
                                <form method="POST" style="margin-top:.6rem; display:flex; gap:.5rem;">
                                    <input type="hidden" name="ticket_id" value="<?php echo $t['ticket_id']; ?>">
                                    <input type="text" name="internal_notes" value="<?php echo htmlspecialchars($t['internal_notes'] ?? ''); ?>" placeholder="Internal note (not visible to client)">
                                    <button type="submit" name="update_notes" class="btn btn-outline-light btn-sm">Save</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
