<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$is_client = isset($_SESSION['client_id']);
$user_id = $is_client ? $_SESSION['client_id'] : $_SESSION['user_id'];
$user_type = $is_client ? 'client' : 'user';
$active_page = 'messages';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_thread'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if ($is_client) {
        // Route this query directly to the accountant who owns that duty.
        // No admin involvement - the client picks what the query is about
        // and it lands straight in that specialist's inbox.
        $deptMap = serviceDepartmentMap();
        $query_type = $_POST['query_type'] ?? '';
        $department = $deptMap[$query_type] ?? null;
        $accountant_id = findAccountantForDepartment($pdo, $department);

        if (!$accountant_id) {
            // "General" query or no specialist available for that duty yet:
            // falls back to the client's own assigned accountant.
            $stmt = $pdo->prepare("SELECT assigned_accountant_id FROM clients WHERE client_id = ?");
            $stmt->execute([$_SESSION['client_id']]);
            $client_data = $stmt->fetch();
            $accountant_id = $client_data['assigned_accountant_id'] ?? null;
        }
        if (!$accountant_id) {
            $fallback = $pdo->query("SELECT user_id FROM users WHERE role='accountant' AND is_active=1 ORDER BY RAND() LIMIT 1")->fetch();
            $accountant_id = $fallback['user_id'] ?? null;
        }

        $stmt = $pdo->prepare("INSERT INTO message_threads (client_id, accountant_id, subject) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['client_id'], $accountant_id, $subject]);
    } else {
        $client_id = $_POST['client_id'];
        $stmt = $pdo->prepare("INSERT INTO message_threads (client_id, accountant_id, subject) VALUES (?, ?, ?)");
        $stmt->execute([$client_id, $_SESSION['user_id'], $subject]);
    }
    $thread_id = $pdo->lastInsertId();
    $is_urgent = containsUrgentKeyword($subject . ' ' . $message) ? 1 : 0;
    $attachment_id = !empty($_POST['attachment_id']) ? $_POST['attachment_id'] : null;
    $stmt = $pdo->prepare("INSERT INTO messages (thread_id, sender_id, sender_type, message_body, is_urgent, attachment_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$thread_id, $user_id, $user_type, $message, $is_urgent, $attachment_id]);
    logAction($pdo, $user_id, $user_type, 'message_send', "New thread: $subject" . ($is_urgent ? ' [URGENT]' : ''));
    header('Location: messages.php?thread=' . $thread_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $thread_id = $_POST['thread_id'];
    $message = trim($_POST['message']);
    $is_urgent = containsUrgentKeyword($message) ? 1 : 0;
    $attachment_id = !empty($_POST['attachment_id']) ? $_POST['attachment_id'] : null;
    $stmt = $pdo->prepare("INSERT INTO messages (thread_id, sender_id, sender_type, message_body, is_urgent, attachment_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$thread_id, $user_id, $user_type, $message, $is_urgent, $attachment_id]);
    logAction($pdo, $user_id, $user_type, 'message_send', "Reply in thread #$thread_id" . ($is_urgent ? ' [URGENT]' : ''));
    header('Location: messages.php?thread=' . $thread_id);
    exit();
}

if ($is_client) {
    $stmt = $pdo->prepare("SELECT t.*, u.full_name as accountant_name, (SELECT COUNT(*) FROM messages WHERE thread_id = t.thread_id AND read_timestamp IS NULL AND sender_type != 'client') as unread FROM message_threads t JOIN users u ON t.accountant_id = u.user_id WHERE t.client_id = ? ORDER BY t.created_date DESC");
    $stmt->execute([$_SESSION['client_id']]);
} elseif ($_SESSION['role'] == 'accountant') {
    $stmt = $pdo->prepare("SELECT t.*, c.full_name as client_name, (SELECT COUNT(*) FROM messages WHERE thread_id = t.thread_id AND read_timestamp IS NULL AND sender_type != 'user') as unread FROM message_threads t JOIN clients c ON t.client_id = c.client_id WHERE t.accountant_id = ? ORDER BY t.created_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("SELECT t.*, c.full_name as client_name, u.full_name as accountant_name FROM message_threads t JOIN clients c ON t.client_id = c.client_id JOIN users u ON t.accountant_id = u.user_id ORDER BY t.created_date DESC");
}
$all_threads = $stmt->fetchAll();

$current_thread = null;
$messages = [];
if (isset($_GET['thread'])) {
    $thread_id = $_GET['thread'];
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE thread_id = ? ORDER BY sent_timestamp ASC");
    $stmt->execute([$thread_id]);
    $messages = $stmt->fetchAll();
    $pdo->prepare("UPDATE messages SET read_timestamp = NOW() WHERE thread_id = ? AND sender_type != ? AND read_timestamp IS NULL")->execute([$thread_id, $user_type]);

    if ($is_client) {
        $stmt = $pdo->prepare("SELECT t.*, u.full_name as other_party FROM message_threads t JOIN users u ON t.accountant_id = u.user_id WHERE t.thread_id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT t.*, c.full_name as other_party FROM message_threads t JOIN clients c ON t.client_id = c.client_id WHERE t.thread_id = ?");
    }
    $stmt->execute([$thread_id]);
    $current_thread = $stmt->fetch();
}

$recipients = [];
if (!$is_client && $_SESSION['role'] == 'accountant') {
    $stmt = $pdo->prepare("SELECT client_id, full_name FROM clients WHERE assigned_accountant_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $recipients = $stmt->fetchAll();
}

// Duty-holders shown to clients so they know exactly who will pick up each query type.
$duty_accountants = [];
if ($is_client) {
    $stmt = $pdo->query("SELECT department, full_name FROM users WHERE role='accountant' AND is_active=1 AND department IS NOT NULL ORDER BY department");
    foreach ($stmt->fetchAll() as $row) {
        $duty_accountants[$row['department']] = $row['full_name'];
    }
}

// documents available to attach
if ($is_client) {
    $attach_docs = $pdo->prepare("SELECT document_id, file_name FROM documents WHERE client_id = ? ORDER BY upload_timestamp DESC LIMIT 20");
    $attach_docs->execute([$_SESSION['client_id']]);
    $attach_docs = $attach_docs->fetchAll();
} else {
    $attach_docs = $pdo->query("SELECT document_id, file_name FROM documents ORDER BY upload_timestamp DESC LIMIT 20")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Messages</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div><h2>Messages</h2><p class="date-line">Secure, encrypted conversations with automatic urgent flagging</p></div>
            <span class="pill pill-encrypted"><i class="fas fa-lock"></i> Encrypted</span>
        </div>

        <div class="messaging-container">
            <div class="thread-list">
                <div class="new-thread-form">
                    <form method="POST">
                        <input type="text" name="subject" placeholder="Subject" required value="<?php echo htmlspecialchars($_GET['subject'] ?? ''); ?>" style="margin-bottom:.5rem;">
                        <?php if($is_client): ?>
                            <select name="query_type" style="margin-bottom:.25rem;">
                                <option value="">General query (your assigned accountant)</option>
                                <option value="taxation">Taxation<?php echo isset($duty_accountants['Taxation']) ? ' — ' . htmlspecialchars($duty_accountants['Taxation']) : ''; ?></option>
                                <option value="bookkeeping">Bookkeeping<?php echo isset($duty_accountants['Bookkeeping']) ? ' — ' . htmlspecialchars($duty_accountants['Bookkeeping']) : ''; ?></option>
                                <option value="payroll">Payroll<?php echo isset($duty_accountants['Payroll']) ? ' — ' . htmlspecialchars($duty_accountants['Payroll']) : ''; ?></option>
                                <option value="advisory">Advisory<?php echo isset($duty_accountants['Advisory']) ? ' — ' . htmlspecialchars($duty_accountants['Advisory']) : ''; ?></option>
                                <option value="audit">Audit<?php echo isset($duty_accountants['Audit']) ? ' — ' . htmlspecialchars($duty_accountants['Audit']) : ''; ?></option>
                            </select>
                            <small style="color:var(--gray-500); display:block; margin-bottom:.5rem;">Goes straight to that accountant - no approval needed.</small>
                        <?php endif; ?>
                        <?php if(!$is_client && !empty($recipients)): ?>
                            <select name="client_id" required style="margin-bottom:.5rem;"><option value="">Select Client</option><?php foreach($recipients as $r): ?><option value="<?php echo $r['client_id']; ?>"><?php echo htmlspecialchars($r['full_name']); ?></option><?php endforeach; ?></select>
                        <?php endif; ?>
                        <textarea name="message" placeholder="Message... (mentioning 'urgent' or 'deadline' auto-flags this)" rows="2" required style="margin-bottom:.5rem;"></textarea>
                        <button type="submit" name="new_thread" class="btn btn-primary btn-block btn-sm">Start Conversation</button>
                    </form>
                </div>
                <?php foreach($all_threads as $thread): ?>
                    <a href="?thread=<?php echo $thread['thread_id']; ?>" class="thread-item <?php echo (isset($_GET['thread']) && $_GET['thread'] == $thread['thread_id']) ? 'active' : ''; ?>">
                        <div class="thread-title"><?php echo htmlspecialchars($thread['subject']); ?><?php if(($thread['unread'] ?? 0) > 0): ?><span class="unread-badge"><?php echo $thread['unread']; ?></span><?php endif; ?></div>
                        <div class="thread-preview">with <?php echo htmlspecialchars($thread['client_name'] ?? $thread['accountant_name'] ?? ''); ?></div>
                        <div class="thread-date"><?php echo date('d M Y', strtotime($thread['created_date'])); ?></div>
                    </a>
                <?php endforeach; ?>
                <?php if(empty($all_threads)): ?><p style="padding:1rem; color:var(--gray-500); font-size:.85rem;">No conversations yet.</p><?php endif; ?>
            </div>

            <div class="chat-area">
                <?php if($current_thread): ?>
                    <div class="chat-header"><i class="fas fa-circle-user" style="color:var(--primary);"></i> <?php echo htmlspecialchars($current_thread['subject']); ?> with <?php echo htmlspecialchars($current_thread['other_party']); ?> <span class="pill pill-encrypted" style="margin-left:auto;"><i class="fas fa-lock"></i> Encrypted</span></div>
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach($messages as $msg): ?>
                            <div class="message <?php echo ($msg['sender_type'] == $user_type) ? 'message-sent' : 'message-received'; ?> <?php echo $msg['is_urgent'] ? 'is-urgent' : ''; ?>">
                                <?php if($msg['is_urgent']): ?><span class="pill pill-urgent" style="margin-bottom:.4rem;"><i class="fas fa-triangle-exclamation"></i> Urgent</span><br><?php endif; ?>
                                <div><?php echo nl2br(htmlspecialchars($msg['message_body'])); ?></div>
                                <?php if(!empty($msg['attachment_id'])): ?><div style="font-size:.75rem; opacity:.85; margin-top:.3rem;"><i class="fas fa-paperclip"></i> Document attached</div><?php endif; ?>
                                <div class="message-meta"><span class="message-time"><?php echo date('d M H:i', strtotime($msg['sent_timestamp'])); ?></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="POST" class="chat-input">
                        <input type="hidden" name="thread_id" value="<?php echo $current_thread['thread_id']; ?>">
                        <?php if(!empty($attach_docs)): ?>
                        <select name="attachment_id" style="max-width:140px;" title="Attach a document">
                            <option value="">📎 Attach</option>
                            <?php foreach($attach_docs as $d): ?><option value="<?php echo $d['document_id']; ?>"><?php echo htmlspecialchars(mb_strimwidth($d['file_name'],0,18,'…')); ?></option><?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                        <textarea name="message" placeholder="Type your message..." rows="2" required></textarea>
                        <button type="submit" name="reply" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                    </form>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; color:var(--gray-400); gap:.6rem;">
                        <i class="fas fa-comments" style="font-size:2.5rem;"></i>
                        <p>Select a conversation or start a new one</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
<script>document.getElementById('chatMessages')?.scrollTo(0, document.getElementById('chatMessages').scrollHeight);</script>
</body>
</html>
