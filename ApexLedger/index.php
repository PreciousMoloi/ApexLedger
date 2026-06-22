<?php
session_start();
if (isset($_SESSION['user_id']) || isset($_SESSION['client_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Ntuli Accountants Professional Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .hero{ padding:9.5rem 5% 5rem; background:linear-gradient(160deg,var(--primary-light) 0%,#ffffff 60%); display:flex; align-items:center; gap:4rem; flex-wrap:wrap; }
        .hero-content{ flex:1; min-width:300px; }
        .hero-eyebrow{ display:inline-flex; align-items:center; gap:.4rem; background:var(--primary-100); color:var(--primary-dark); font-weight:700; font-size:.78rem; padding:.4rem .8rem; border-radius:999px; margin-bottom:1.1rem; }
        .hero-content h1{ font-size:clamp(2.1rem,4.5vw,3.4rem); font-weight:800; line-height:1.15; margin-bottom:1.25rem; color:var(--navy); }
        .hero-content h1 span{ color:var(--primary); }
        .hero-content p{ font-size:1.1rem; color:var(--gray-500); margin-bottom:2rem; line-height:1.65; max-width:540px; }
        .hero-actions{ display:flex; gap:1rem; flex-wrap:wrap; align-items:center; }
        .hero-actions a.text-link{ color:var(--primary); font-weight:600; text-decoration:none; }
        .hero-image{ flex:1; min-width:280px; text-align:center; }
        .hero-card{ background:#fff; border:1px solid var(--gray-200); border-radius:1.5rem; padding:2rem; box-shadow:var(--shadow-lg); display:inline-block; text-align:left; max-width:360px; }
        .hero-card .row{ display:flex; align-items:center; gap:.8rem; padding:.7rem 0; border-bottom:1px solid var(--gray-100); }
        .hero-card .row:last-child{ border-bottom:none; }
        .hero-card .row i{ width:34px; height:34px; border-radius:.6rem; display:flex; align-items:center; justify-content:center; color:#fff; }
        .features{ padding:5rem 5%; background:#fff; text-align:center; }
        .features h2{ font-size:2.2rem; font-weight:800; color:var(--navy); margin-bottom:.5rem; }
        .features-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1.75rem; max-width:1200px; margin:3rem auto 0; }
        .feature-card{ padding:2rem; border-radius:1.1rem; background:var(--gray-50); border:1px solid var(--gray-200); transition:all .25s; text-align:left; }
        .feature-card:hover{ transform:translateY(-6px); box-shadow:var(--shadow-md); border-color:var(--primary); }
        .feature-icon{ width:54px; height:54px; border-radius:.85rem; display:flex; align-items:center; justify-content:center; margin-bottom:1rem; color:#fff; font-size:1.3rem; }
        .stats-banner{ background:linear-gradient(135deg,var(--navy) 0%,var(--primary-dark) 100%); padding:4rem 5%; text-align:center; color:#fff; }
        .stats-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:2rem; max-width:1000px; margin:0 auto; }
        .stat-item h3{ font-size:2.3rem; font-weight:800; }
        .stat-item p{ color:#cbd5e1; margin-top:.3rem; font-size:.9rem; }
        .footer-content{ display:grid; grid-template-columns:repeat(auto-fit,minmax(230px,1fr)); gap:2rem; max-width:1200px; margin:0 auto; }
        @media (max-width:768px){ .hero-content h1{ font-size:2rem; } .nav-links{ display:none; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand"><span class="brand-icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="brand-text">APEX<span>Ledger</span></span></a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>
            <a href="register.php" class="btn btn-outline" style="margin-left:1rem;">Register</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <span class="hero-eyebrow"><i class="fas fa-lock"></i> POPIA-Compliant Client Portal</span>
            <h1>Secure Client Portal for <span>Modern Accounting</span></h1>
            <p>APEXLedger replaces scattered email and WhatsApp threads with one centralised, secure platform for document sharing, real-time messaging, and service tracking between Ntuli Accountants and their clients.</p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-success">Get Started Free</a>
                <a href="login.php" class="btn btn-outline">Client / Staff Login</a>
                <a href="about.php" class="text-link">Learn more <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <div class="hero-image">
            <div class="hero-card">
                <div class="row"><i style="background:var(--primary);"><i class="fas fa-folder-open"></i></i><div><strong>Document uploaded</strong><div style="font-size:.8rem;color:var(--gray-500);">Tax Return 2025 &middot; AES-256 encrypted</div></div></div>
                <div class="row"><i style="background:var(--success);"><i class="fas fa-circle-check"></i></i><div><strong>Ticket completed</strong><div style="font-size:.8rem;color:var(--gray-500);">TAX-2026-0001 &middot; SLA met</div></div></div>
                <div class="row"><i style="background:var(--danger);"><i class="fas fa-bell"></i></i><div><strong>Urgent message flagged</strong><div style="font-size:.8rem;color:var(--gray-500);">Deadline approaching &middot; 2 min ago</div></div></div>
            </div>
        </div>
    </section>

    <section class="features">
        <h2>Everything You Need</h2>
        <p style="color:var(--gray-500);">Built specifically for accounting professionals and their clients</p>
        <div class="features-grid">
            <div class="feature-card"><div class="feature-icon" style="background:var(--primary);"><i class="fas fa-lock"></i></div><h3>Bank-Grade Security</h3><p>AES-256 encryption, TLS 1.3, optional SMS two-factor authentication, and full POPIA compliance.</p></div>
            <div class="feature-card"><div class="feature-icon" style="background:var(--info);"><i class="fas fa-comments"></i></div><h3>Secure Messaging</h3><p>Threaded, encrypted conversations with automatic urgent-keyword flagging.</p></div>
            <div class="feature-card"><div class="feature-icon" style="background:var(--success);"><i class="fas fa-file-alt"></i></div><h3>Document Management</h3><p>Upload, review, and track tax returns, statements and invoices with version control.</p></div>
            <div class="feature-card"><div class="feature-icon" style="background:var(--warning);"><i class="fas fa-tasks"></i></div><h3>Service Tracking</h3><p>Monitor tax returns, payroll, advisory and audit work with live status &amp; priority.</p></div>
        </div>
    </section>

    <section class="stats-banner">
        <div class="stats-grid">
            <div class="stat-item"><h3>500+</h3><p>Active Clients</p></div>
            <div class="stat-item"><h3>99.5%</h3><p>Uptime Guarantee</p></div>
            <div class="stat-item"><h3>5.0 ★</h3><p>Client Rating</p></div>
            <div class="stat-item"><h3>POPIA</h3><p>Compliant</p></div>
        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-content">
            <div><span class="brand brand-sm" style="margin-bottom:.75rem;"><span class="brand-icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="brand-text" style="color:#fff;">APEX<span>Ledger</span></span></span><p>Secure client portal for modern accounting practices.</p></div>
            <div><h4>Ntuli Accountants</h4><p>C/O Mandela &amp; Financial Square<br>Woltemade St, eMalahleni, 1034<br>Mpumalanga</p></div>
            <div><h4>Contact</h4><p><i class="fas fa-phone"></i> 079 400 5315<br><i class="fas fa-envelope"></i> accounts@ntuli.co.za<br><i class="fas fa-clock"></i> Mon&ndash;Fri: 8am&ndash;5pm</p></div>
            <div><h4>Quick Links</h4><a href="index.php">Home</a><a href="about.php">About</a><a href="contact.php">Contact</a><a href="login.php">Login</a><a href="register.php">Register</a></div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 APEXLedger &middot; Ntuli Accountants and Associates &middot; POPIA Compliant</p>
            <p style="margin-top:.4rem;font-size:.75rem;">eMalahleni, Mpumalanga &middot; 079 400 5315 &middot; ⭐ 5.0 Client Rating</p>
        </div>
    </footer>
</body>
</html>
