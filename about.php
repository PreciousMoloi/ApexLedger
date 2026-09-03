<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | About Ntuli Accountants</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .hero{ padding:8.5rem 5% 4rem; background:linear-gradient(135deg,var(--navy) 0%,var(--primary-dark) 100%); color:#fff; text-align:center; }
        .hero h1{ font-size:2.6rem; font-weight:800; margin-bottom:.6rem; }
        .hero p{ color:#cbd5e1; }
        .content{ max-width:1180px; margin:0 auto; padding:3.5rem 5%; }
        .company-profile{ padding:2rem; margin-bottom:2rem; }
        .company-profile h2{ color:var(--navy); margin-bottom:1.25rem; font-weight:800; }
        .info-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1.75rem; }
        .info-grid p{ line-height:1.7; }
        .info-grid i{ color:var(--primary); width:22px; }
        .mission{ display:flex; gap:3rem; margin-bottom:4rem; flex-wrap:wrap; align-items:flex-start; }
        .mission-text{ flex:1.5; min-width:300px; }
        .mission-text h2{ color:var(--navy); margin-bottom:1rem; font-weight:800; }
        .mission-text p{ line-height:1.8; color:var(--gray-600); margin-bottom:1rem; }
        .stats-box{ flex:1; min-width:260px; display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .stats-box .stat-card{ text-align:center; padding:1.5rem; }
        .stats-box .number{ font-size:2rem; font-weight:800; color:var(--primary); }
        .section-title{ text-align:center; margin-bottom:2rem; font-weight:800; color:var(--navy); font-size:1.7rem; }
        .team-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:1.75rem; margin-bottom:1.75rem; }
        .team-card{ text-align:center; padding:2rem; transition:transform .25s; }
        .team-card:hover{ transform:translateY(-6px); }
        .team-avatar{ width:90px; height:90px; background:var(--primary-light); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem; }
        .team-avatar i{ font-size:2.2rem; color:var(--primary); }
        .role{ color:var(--success); font-weight:700; margin:.4rem 0; font-size:.88rem; text-transform:uppercase; letter-spacing:.03em; }
        .compliance{ background:var(--primary-light); padding:2rem; border-radius:1.1rem; text-align:center; margin-top:2rem; }
        .compliance h3{ color:var(--navy); margin-bottom:.75rem; }
        .rating-stars{ color:var(--warning); font-size:1.2rem; }
        @media (max-width:768px){ .hero h1{ font-size:1.9rem; } .nav-links{ display:none; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand"><span class="brand-icon"><i class="fas fa-file-invoice-dollar"></i></span><span class="brand-text">APEX<span>Ledger</span></span></a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
            <?php if(isset($_SESSION['user_id']) || isset($_SESSION['client_id'])): ?>
                <a href="dashboard.php">Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="btn btn-outline" style="margin-left:1rem;">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <section class="hero">
        <h1>About Ntuli Accountants and Associates</h1>
        <p>Trusted accounting services in eMalahleni since 2010</p>
    </section>

    <div class="content">
        <div class="company-profile card">
            <h2><i class="fas fa-building"></i> Ntuli Accountants and Associates</h2>
            <div class="info-grid">
                <div><p><i class="fas fa-map-marker-alt"></i> <strong>Address:</strong><br>C/O Mandela &amp; Financial Square<br>Woltemade St, eMalahleni, 1034<br>Mpumalanga</p></div>
                <div><p><i class="fas fa-phone"></i> <strong>Contact:</strong><br>079 400 5315<br>accounts@ntuli.co.za<br>www.ntuliaccounting.co.za</p></div>
                <div><p><i class="fas fa-clock"></i> <strong>Hours:</strong><br>Mon&ndash;Fri: 8am&ndash;5pm<br>Sat&ndash;Sun: Closed</p></div>
                <div><p><i class="fas fa-star"></i> <strong>Rating:</strong><br><span class="rating-stars">★★★★★</span> 5.0<br><i class="fas fa-circle-check" style="color:var(--success);"></i> POPIA Compliant</p></div>
            </div>
        </div>

        <div class="mission">
            <div class="mission-text">
                <h2>Our Mission</h2>
                <p>Ntuli Accountants and Associates was founded to provide professional accounting services to businesses and individuals in eMalahleni and across Mpumalanga. With over 15 years of experience, we specialise in taxation, bookkeeping, payroll processing, and financial advisory.</p>
                <p>APEXLedger was born from a clear problem: client communication and document management scattered across WhatsApp, email, and paper files. This fragmentation led to delayed responses, lost documents, and compliance risks under South Africa's POPIA legislation.</p>
                <p>Our mission is to provide a centralised, secure, and intuitive platform that streamlines client onboarding, document sharing, secure messaging, and service tracking.</p>
            </div>
            <div class="stats-box">
                <div class="stat-card card"><div class="number">15+</div><div>Years Experience</div></div>
                <div class="stat-card card"><div class="number">500+</div><div>Happy Clients</div></div>
                <div class="stat-card card"><div class="number">5.0 ★</div><div>Client Rating</div></div>
                <div class="stat-card card"><div class="number">POPIA</div><div>Compliant</div></div>
            </div>
        </div>

        <h2 class="section-title">Meet Our Team</h2>
        <div class="team-grid">
            <div class="team-card card"><div class="team-avatar"><i class="fas fa-user-tie"></i></div><h3>Mr. S. Ntuli</h3><div class="role">Managing Director</div><p>Founder with 15+ years in accounting and financial advisory.</p></div>
            <div class="team-card card"><div class="team-avatar"><i class="fas fa-user-circle"></i></div><h3>Ms. T. Dlamini</h3><div class="role">Senior Accountant</div><p>Taxation and compliance specialist.</p></div>
            <div class="team-card card"><div class="team-avatar"><i class="fas fa-user"></i></div><h3>Mr. K. Mthembu</h3><div class="role">Administration Clerk</div><p>Client onboarding and document management.</p></div>
        </div>

        <h2 class="section-title">Built By AWS-Technicians</h2>
        <div class="team-grid" style="margin-bottom:0;">
            <div class="team-card card"><div class="team-avatar"><i class="fas fa-user-tie"></i></div><h3>Precious Moloi</h3><div class="role">Project Manager &amp; Documentation Lead</div><p>Overall coordination, planning, and quality assurance.</p></div>
            <div class="team-card card"><div class="team-avatar"><i class="fas fa-paint-brush"></i></div><h3>Nyiko Matladi</h3><div class="role">Business Analyst / Designer</div><p>Requirements gathering, UI/UX design, system architecture.</p></div>
            <div class="team-card card"><div class="team-avatar"><i class="fas fa-code"></i></div><h3>Luvuyo Yizani</h3><div class="role">Full-Stack Developer</div><p>Backend API, frontend implementation, system integration.</p></div>
        </div>

        <div class="compliance">
            <h3><i class="fas fa-shield-halved"></i> POPIA Compliant &amp; Security First</h3>
            <p>APEXLedger adheres to the Protection of Personal Information Act (POPIA) of South Africa (Act 4 of 2013), ensuring all client data is handled with the highest standards of confidentiality and security. TLS 1.3 encryption, optional two-factor authentication, and comprehensive audit logging protect every interaction.</p>
            <p style="margin-top:1rem;"><strong>Registered with:</strong> Information Regulator of South Africa | SAICA Member</p>
        </div>
    </div>

    <footer class="site-footer" style="text-align:center;">
        <p>&copy; 2026 APEXLedger &middot; Ntuli Accountants and Associates &middot; POPIA Compliant</p>
        <p style="margin-top:.5rem;">eMalahleni, Mpumalanga &middot; 079 400 5315 &middot; ⭐ 5.0 Client Rating</p>
    </footer>
</body>
</html>
