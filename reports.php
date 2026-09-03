<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['manager', 'admin'])) {
    header('Location: dashboard.php');
    exit();
}
$is_client = false;
$active_page = 'reports';

// ---- CSV export (FR-07: management reports, exportable) ----
if (isset($_GET['export']) && $_GET['export'] === 'tickets') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="apexledger_tickets_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Ticket #', 'Client', 'Accountant', 'Service Type', 'Priority', 'Status', 'Created', 'Due Date', 'Completed']);
    $rows = $pdo->query("SELECT t.*, c.full_name as client_name, u.full_name as accountant_name FROM service_tickets t JOIN clients c ON t.client_id=c.client_id JOIN users u ON t.assigned_accountant_id=u.user_id ORDER BY t.created_date DESC")->fetchAll();
    foreach ($rows as $r) {
        fputcsv($out, [$r['ticket_number'], $r['client_name'], $r['accountant_name'], $r['service_type'], $r['priority'], $r['status'], $r['created_date'], $r['due_date'], $r['completed_date']]);
    }
    fclose($out);
    exit();
}

// ---- Stat cards ----
$total_tickets = $pdo->query("SELECT COUNT(*) FROM service_tickets")->fetchColumn();
$completed_tickets = $pdo->query("SELECT COUNT(*) FROM service_tickets WHERE status='completed'")->fetchColumn();
$completion_rate = $total_tickets > 0 ? round(($completed_tickets / $total_tickets) * 100) : 0;
$overdue_tickets = $pdo->query("SELECT t.*, c.full_name as client_name, u.full_name as accountant_name, DATEDIFF(CURDATE(), t.due_date) as days_over FROM service_tickets t JOIN clients c ON t.client_id=c.client_id JOIN users u ON t.assigned_accountant_id=u.user_id WHERE t.due_date < CURDATE() AND t.status NOT IN ('completed','cancelled') ORDER BY days_over DESC")->fetchAll();

$avg_response = $pdo->query("
    SELECT AVG(diff_minutes) as avg_min FROM (
        SELECT m1.message_id, MIN(TIMESTAMPDIFF(MINUTE, m1.sent_timestamp, m2.sent_timestamp)) as diff_minutes
        FROM messages m1
        JOIN messages m2 ON m2.thread_id = m1.thread_id AND m2.sender_type = 'user' AND m2.sent_timestamp > m1.sent_timestamp
        WHERE m1.sender_type = 'client'
        GROUP BY m1.message_id
    ) x")->fetchColumn();
$avg_response_label = $avg_response ? (round($avg_response / 60, 1) . 'h') : 'N/A';

// ---- Workload by accountant ----
$workload = $pdo->query("
    SELECT u.full_name,
        SUM(CASE WHEN t.status NOT IN ('completed','cancelled') THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM users u LEFT JOIN service_tickets t ON t.assigned_accountant_id = u.user_id
    WHERE u.role = 'accountant'
    GROUP BY u.user_id, u.full_name")->fetchAll();

// ---- Status breakdown (for donut) ----
$status_breakdown = $pdo->query("SELECT status, COUNT(*) c FROM service_tickets GROUP BY status")->fetchAll();

// ---- Security audit summary ----
$failed_attempts_30d = $pdo->query("SELECT COUNT(*) FROM login_attempts WHERE was_successful=0 AND attempt_time > (NOW() - INTERVAL 30 DAY)")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Manager Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        .charts-grid{ display:grid; grid-template-columns:1fr 1.4fr; gap:1.5rem; margin-bottom:1.5rem; }
        .chart-card{ height:300px; }
        @media (max-width:900px){ .charts-grid{ grid-template-columns:1fr; } }
    </style>
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div><h2>Manager Reports</h2><p class="date-line">Workload, performance &amp; compliance overview</p></div>
            <div class="topbar-actions">
                <a href="reports.php?export=tickets" class="btn btn-outline-light btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
                <button onclick="window.print()" class="btn btn-outline-light btn-sm"><i class="fas fa-print"></i> Print / PDF</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card c-blue"><div class="stat-label"><i class="fas fa-ticket"></i> Total Tickets</div><div class="stat-value"><?php echo $total_tickets; ?></div></div>
            <div class="stat-card c-green"><div class="stat-label"><i class="fas fa-circle-check"></i> Completion Rate</div><div class="stat-value"><?php echo $completion_rate; ?>%</div></div>
            <div class="stat-card c-amber"><div class="stat-label"><i class="fas fa-stopwatch"></i> Avg Response Time</div><div class="stat-value"><?php echo $avg_response_label; ?></div></div>
            <div class="stat-card c-red"><div class="stat-label"><i class="fas fa-triangle-exclamation"></i> Overdue Tickets</div><div class="stat-value"><?php echo count($overdue_tickets); ?></div></div>
        </div>

        <div class="charts-grid">
            <div class="section-card chart-card"><h3 style="margin-bottom:1rem;"><i class="fas fa-chart-pie" style="color:var(--primary);"></i> Ticket Completion</h3><canvas id="completionChart"></canvas></div>
            <div class="section-card chart-card"><h3 style="margin-bottom:1rem;"><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Workload by Accountant</h3><canvas id="workloadChart"></canvas></div>
        </div>

        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-shield-halved"></i> Security Snapshot</h3></div>
            <p style="color:var(--gray-600); font-size:.9rem;">Failed login attempts in the last 30 days: <strong><?php echo $failed_attempts_30d; ?></strong>. Full event-level detail is available to Admins under Admin Panel &rarr; Audit Log.</p>
        </div>

        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-triangle-exclamation"></i> Overdue Tickets</h3><span style="color:var(--gray-500); font-size:.85rem;"><?php echo count($overdue_tickets); ?> item(s)</span></div>
            <div class="table-scroll">
            <table class="data-table"><thead><tr><th>Ticket #</th><th>Client</th><th>Accountant</th><th>Due Date</th><th>Days Overdue</th></tr></thead>
            <tbody>
            <?php if(empty($overdue_tickets)): ?><tr><td colspan="5" style="text-align:center; color:var(--gray-500); padding:2rem;">No overdue tickets &mdash; great work!</td></tr><?php endif; ?>
            <?php foreach($overdue_tickets as $t): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($t['ticket_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($t['client_name']); ?></td>
                    <td><?php echo htmlspecialchars($t['accountant_name']); ?></td>
                    <td><?php echo date('d M Y', strtotime($t['due_date'])); ?></td>
                    <td><span class="badge badge-red"><?php echo $t['days_over']; ?>d overdue</span></td>
                </tr>
            <?php endforeach; ?>
            </tbody></table>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
<script>
const completionData = <?php echo json_encode($status_breakdown); ?>;
const statusColors = {open:'#0ea5e9', in_progress:'#f59e0b', awaiting_documents:'#8b5cf6', under_review:'#8b5cf6', completed:'#16a34a', cancelled:'#94a3b8'};
new Chart(document.getElementById('completionChart'), {
    type: 'doughnut',
    data: {
        labels: completionData.map(d => d.status.replace('_',' ')),
        datasets: [{ data: completionData.map(d => d.c), backgroundColor: completionData.map(d => statusColors[d.status] || '#cbd5e1') }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } }
});

const workloadData = <?php echo json_encode($workload); ?>;
new Chart(document.getElementById('workloadChart'), {
    type: 'bar',
    data: {
        labels: workloadData.map(w => w.full_name),
        datasets: [
            { label: 'Open', data: workloadData.map(w => w.open_count), backgroundColor: '#f59e0b', borderRadius: 6 },
            { label: 'Completed', data: workloadData.map(w => w.completed_count), backgroundColor: '#16a34a', borderRadius: 6 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
</body>
</html>
