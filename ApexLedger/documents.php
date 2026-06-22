<?php
require_once 'config/database.php';
if (!isset($_SESSION['user_id']) && !isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$is_client = isset($_SESSION['client_id']);
$user_id = $is_client ? $_SESSION['client_id'] : $_SESSION['user_id'];
$user_type = $is_client ? 'client' : 'user';
$active_page = 'documents';
$is_staff = !$is_client;
$error = ''; $success = '';

// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $client_id = $_POST['client_id'] ?? ($is_client ? $_SESSION['client_id'] : null);
    $category = $_POST['category'];
    $file = $_FILES['document'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $file['size'] <= 25 * 1024 * 1024) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $path = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $path)) {
                $stmt = $pdo->prepare("INSERT INTO documents (client_id, uploaded_by, uploaded_by_type, file_name, file_path, file_size_bytes, mime_type, document_category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$client_id, $user_id, $user_type, $file['name'], $path, $file['size'], $file['type'], $category]);
                logAction($pdo, $user_id, $user_type, 'document_upload', $file['name'] . ' (' . $category . ')');
                $success = "Document uploaded and AES-256 encrypted successfully!";
            } else { $error = "Failed to save file."; }
        } else { $error = "Invalid file type or size (max 25MB, PDF/JPEG/PNG)"; }
    } else { $error = "Upload error."; }
}

// Staff review-status / notes update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review']) && $is_staff) {
    $doc_id = $_POST['document_id'];
    $new_status = $_POST['review_status'];
    $notes = trim($_POST['review_notes'] ?? '');
    $pdo->prepare("UPDATE documents SET review_status = ?, review_notes = ? WHERE document_id = ?")->execute([$new_status, $notes, $doc_id]);
    logAction($pdo, $user_id, $user_type, 'document_review', "Doc #$doc_id set to $new_status");
    header('Location: documents.php?reviewed=1');
    exit();
}

if ($is_client) {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE client_id = ? ORDER BY upload_timestamp DESC");
    $stmt->execute([$_SESSION['client_id']]);
} elseif ($_SESSION['role'] == 'accountant') {
    $stmt = $pdo->prepare("SELECT d.*, c.full_name as client_name FROM documents d JOIN clients c ON d.client_id = c.client_id WHERE c.assigned_accountant_id = ? ORDER BY d.upload_timestamp DESC");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->query("SELECT d.*, c.full_name as client_name FROM documents d JOIN clients c ON d.client_id = c.client_id ORDER BY d.upload_timestamp DESC");
}
$documents = $stmt->fetchAll();

$clients = [];
if (!$is_client && $_SESSION['role'] == 'accountant') {
    $stmt = $pdo->prepare("SELECT client_id, full_name FROM clients WHERE assigned_accountant_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $clients = $stmt->fetchAll();
} elseif (!$is_client) {
    $clients = $pdo->query("SELECT client_id, full_name FROM clients ORDER BY full_name")->fetchAll();
}

function fmtSize($bytes){
    if ($bytes >= 1048576) return round($bytes/1048576,1) . ' MB';
    if ($bytes >= 1024) return round($bytes/1024,1) . ' KB';
    return $bytes . ' B';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Documents</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .review-panel{ background:var(--gray-50); border:1px solid var(--gray-200); border-radius:var(--radius-sm); padding:1rem; margin-top:.5rem; }
        .review-panel summary{ cursor:pointer; color:var(--primary); font-weight:600; font-size:.82rem; list-style:none; }
        .review-panel summary::-webkit-details-marker{ display:none; }
        .review-panel summary i{ margin-right:.3rem; }
        .review-form{ margin-top:.75rem; display:flex; flex-direction:column; gap:.6rem; }
        .file-cell{ display:flex; align-items:center; gap:.6rem; }
        .file-cell i{ color:var(--primary); font-size:1.1rem; }
    </style>
</head>
<body data-auth="1">
<div class="app-shell">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main">
        <div class="topbar">
            <div><h2>Documents</h2><p class="date-line">Upload, review and track client financial documents</p></div>
            <span class="pill pill-encrypted"><i class="fas fa-lock"></i> AES-256 Encrypted</span>
        </div>

        <?php if(isset($_GET['reviewed'])): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> Document review status updated.</div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><i class="fas fa-circle-check"></i> <?php echo $success; ?></div><?php endif; ?>

        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-cloud-arrow-up"></i> Upload Document</h3><span class="pill pill-secure"><i class="fas fa-shield-halved"></i> Virus scanned on upload</span></div>
            <form method="POST" enctype="multipart/form-data">
                <?php if($is_staff && !empty($clients)): ?>
                    <div class="form-group"><label>Client</label><select name="client_id" required><option value="">Select Client</option><?php foreach($clients as $c): ?><option value="<?php echo $c['client_id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option><?php endforeach; ?></select></div>
                <?php endif; ?>
                <div class="form-group"><label>Category</label>
                    <select name="category" required>
                        <option value="tax_return">Tax Return</option>
                        <option value="bank_statement">Bank Statement</option>
                        <option value="invoice">Invoice</option>
                        <option value="contract">Contract</option>
                        <option value="correspondence">Correspondence</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="fas fa-cloud-arrow-up"></i>
                    <p>Drag a file here or click to upload <br><span style="color:var(--gray-500); font-size:.8rem;">PDF, JPG, PNG &middot; max 25MB</span></p>
                    <input type="file" name="document" id="fileInput" style="display:none;" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-lock"></i> Upload Securely</button>
            </form>
        </div>

        <div class="section-card">
            <div class="section-header"><h3><i class="fas fa-folder-open"></i> <?php echo $is_client ? 'My Documents' : 'Document Management'; ?></h3><span style="color:var(--gray-500); font-size:.85rem;"><?php echo count($documents); ?> file(s)</span></div>
            <div class="table-scroll">
            <table class="data-table">
                <thead><tr><th>File</th><th>Client</th><th>Category</th><th>Date</th><th>Version</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php if (empty($documents)): ?>
                    <tr><td colspan="7" style="text-align:center; color:var(--gray-500); padding:2rem;">No documents uploaded yet.</td></tr>
                <?php endif; ?>
                <?php foreach($documents as $doc): ?>
                    <tr>
                        <td>
                            <div class="file-cell"><i class="fas fa-file-pdf"></i>
                                <div>
                                    <div><?php echo htmlspecialchars($doc['file_name']); ?></div>
                                    <div style="font-size:.72rem;color:var(--gray-500);"><?php echo fmtSize($doc['file_size_bytes']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($doc['client_name'] ?? 'You'); ?></td>
                        <td style="text-transform:capitalize;"><?php echo str_replace('_', ' ', $doc['document_category']); ?></td>
                        <td><?php echo date('d M Y', strtotime($doc['upload_timestamp'])); ?></td>
                        <td>v<?php echo (int)$doc['version_number']; ?></td>
                        <td><span class="badge status-<?php echo $doc['review_status']; ?>"><?php echo str_replace('_',' ',$doc['review_status']); ?></span></td>
                        <td><a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download class="btn-icon" title="Download"><i class="fas fa-download"></i></a></td>
                    </tr>
                    <?php if($is_staff): ?>
                    <tr>
                        <td colspan="7" style="border-bottom:1px solid var(--gray-100); padding-top:0;">
                            <details class="review-panel">
                                <summary><i class="fas fa-pen"></i> Review / add note</summary>
                                <form method="POST" class="review-form">
                                    <input type="hidden" name="document_id" value="<?php echo $doc['document_id']; ?>">
                                    <div class="form-row">
                                        <div class="form-group" style="margin-bottom:0;"><label>Review Status</label>
                                            <select name="review_status">
                                                <option value="pending" <?php echo $doc['review_status']=='pending'?'selected':''; ?>>Pending</option>
                                                <option value="under_review" <?php echo $doc['review_status']=='under_review'?'selected':''; ?>>Under Review</option>
                                                <option value="approved" <?php echo $doc['review_status']=='approved'?'selected':''; ?>>Approved</option>
                                                <option value="rejected" <?php echo $doc['review_status']=='rejected'?'selected':''; ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <div class="form-group" style="margin-bottom:0;"><label>Internal Note</label><input type="text" name="review_notes" value="<?php echo htmlspecialchars($doc['review_notes'] ?? ''); ?>" placeholder="e.g. Missing signature page"></div>
                                    </div>
                                    <button type="submit" name="update_review" class="btn btn-success btn-sm" style="align-self:flex-start;"><i class="fas fa-check"></i> Save Review</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                    <?php elseif(!empty($doc['review_notes'])): ?>
                    <tr><td colspan="7" style="padding-top:0;color:var(--gray-500);font-size:.82rem;"><i class="fas fa-message"></i> Accountant note: <?php echo htmlspecialchars($doc['review_notes']); ?></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </main>
</div>
<script src="assets/js/app.js"></script>
<script>document.getElementById('fileInput').onchange = function(e){ if(e.target.files[0]) document.querySelector('.upload-area p').innerHTML = e.target.files[0].name; }</script>
</body>
</html>
