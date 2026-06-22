<?php
// Expects: $active_page (string), $is_client (bool), and session role info already set.
$role = $is_client ? 'client' : ($_SESSION['role'] ?? '');
function navClass($page, $active_page){ return 'nav-item' . ($page === $active_page ? ' active' : ''); }
?>
<button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
<aside class="sidebar" id="sidebar">
    <div class="logo-block">
        <a href="dashboard.php" class="brand brand-sm">
            <span class="brand-icon"><i class="fas fa-file-invoice-dollar"></i></span>
            <span><span class="brand-text">APEX<span>Ledger</span></span><div class="brand-sub"><?php echo $is_client ? 'Client Portal' : 'Staff Portal'; ?></div></span>
        </a>
    </div>
    <nav class="nav">
        <a href="dashboard.php" class="<?php echo navClass('dashboard', $active_page); ?>"><i class="fas fa-gauge-high"></i> Dashboard</a>
        <a href="documents.php" class="<?php echo navClass('documents', $active_page); ?>"><i class="fas fa-folder-open"></i> Documents</a>
        <a href="messages.php" class="<?php echo navClass('messages', $active_page); ?>"><i class="fas fa-envelope"></i> Messages</a>
        <a href="tickets.php" class="<?php echo navClass('tickets', $active_page); ?>"><i class="fas fa-ticket"></i> Service Tickets</a>
        <?php if ($role === 'manager' || $role === 'admin'): ?>
            <a href="reports.php" class="<?php echo navClass('reports', $active_page); ?>"><i class="fas fa-chart-pie"></i> Reports</a>
        <?php endif; ?>
        <a href="profile.php" class="<?php echo navClass('profile', $active_page); ?>"><i class="fas fa-circle-user"></i> My Profile</a>
        <?php if ($role === 'admin'): ?>
            <div class="nav-divider">Administration</div>
            <a href="admin.php" class="<?php echo navClass('admin', $active_page); ?>"><i class="fas fa-gear"></i> Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php" class="nav-item logout-item"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
    </nav>
</aside>
<div id="idleWarning" style="display:none; position:fixed; bottom:1.25rem; right:1.25rem; z-index:500; background:var(--navy); color:#fff; padding:.9rem 1.2rem; border-radius:.7rem; box-shadow:var(--shadow-lg); font-size:.85rem; align-items:center; gap:.6rem; max-width:280px;">
    <i class="fas fa-clock" style="color:var(--warning);"></i> You'll be signed out soon due to inactivity. Move your mouse or click to stay signed in.
</div>
