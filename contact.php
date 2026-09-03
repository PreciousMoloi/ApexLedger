<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APEXLedger | Contact Ntuli Accountants</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .main{ padding-top:6.5rem; margin-left:0; }
        .contact-container{ max-width:1180px; margin:0 auto; padding:2rem 5% 1rem; }
        .contact-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(330px,1fr)); gap:1.75rem; }
        .info-card{ padding:2rem; }
        .info-card h2{ color:var(--navy); margin-bottom:1.5rem; border-bottom:2px solid var(--primary-light); padding-bottom:.6rem; font-weight:800; font-size:1.25rem; }
        .info-item{ margin-bottom:1.5rem; display:flex; align-items:flex-start; gap:1rem; }
        .info-icon{ width:44px; height:44px; flex:0 0 44px; background:var(--primary-light); border-radius:50%; display:flex; align-items:center; justify-content:center; color:var(--primary); }
        .info-content h3{ font-size:.8rem; margin-bottom:.25rem; color:var(--gray-500); text-transform:uppercase; letter-spacing:.03em; }
        .info-content p{ font-weight:500; line-height:1.5; }
        .hours-table{ width:100%; border-collapse:collapse; }
        .hours-table td{ padding:.5rem 0; border-bottom:1px solid var(--gray-100); font-size:.92rem; }
        .rating-stars{ color:var(--warning); font-size:1.2rem; }
        .btn-whatsapp{ display:inline-flex; align-items:center; gap:.5rem; background:#25D366; color:#fff; padding:.7rem 1.4rem; border-radius:.5rem; text-decoration:none; margin-top:.75rem; font-weight:600; }
        .btn-whatsapp:hover{ background:#128C7E; }
        @media (max-width:768px){ .nav-links{ display:none; } }
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

    <div class="main">
        <div class="contact-container">
            <div class="contact-grid">
                <div class="info-card card">
                    <h2><i class="fas fa-map-marker-alt"></i> Visit Our Office</h2>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-building"></i></div>
                        <div class="info-content"><h3>Address</h3><p>C/O Mandela &amp; Financial Square<br>Woltemade St, eMalahleni, 1034<br>Mpumalanga, South Africa</p></div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone"></i></div>
                        <div class="info-content"><h3>Phone / WhatsApp</h3><p>079 400 5315</p></div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-content"><h3>Email</h3><p>accounts@ntuli.co.za</p></div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fab fa-whatsapp"></i></div>
                        <div class="info-content"><h3>WhatsApp Business</h3><p>Chat with us directly</p><a href="https://wa.me/27794005315" class="btn-whatsapp"><i class="fab fa-whatsapp"></i> Chat on WhatsApp</a></div>
                    </div>
                </div>

                <div class="info-card card">
                    <h2><i class="fas fa-clock"></i> Business Hours</h2>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                        <div class="info-content">
                            <h3>Operating Hours</h3>
                            <table class="hours-table">
                                <tr><td>Monday &ndash; Friday</td><td style="text-align:right;font-weight:600;">8:00 AM &ndash; 5:00 PM</td></tr>
                                <tr><td>Saturday</td><td style="text-align:right;">Closed</td></tr>
                                <tr><td>Sunday</td><td style="text-align:right;">Closed</td></tr>
                                <tr><td>Public Holidays</td><td style="text-align:right;">Closed</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-star"></i></div>
                        <div class="info-content">
                            <h3>Client Rating</h3>
                            <p class="rating-stars">★★★★★ 5.0</p>
                            <p style="font-weight:400;color:var(--gray-500);">Based on verified client reviews</p>
                        </div>
                    </div>
                </div>

                <div class="info-card card">
                    <h2 style="color:var(--navy);"><i class="fas fa-map"></i> Location Map</h2>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d14360.123456789!2d29.123456!3d-25.123456!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjXCsDA3JzI0LjQiUyAyOcKwMDcnMjQuNCJF!5e0!3m2!1sen!2sza!4v1234567890123!5m2!1sen!2sza" width="100%" height="260" style="border:0; border-radius:.6rem;" allowfullscreen="" loading="lazy"></iframe>
                    <p style="margin-top:1rem; font-size:.85rem; color:var(--gray-500);"><i class="fas fa-location-dot"></i> C/O Mandela &amp; Financial Square, Woltemade St, eMalahleni</p>
                    <p style="margin-top:.5rem; font-size:.85rem; color:var(--gray-500);"><i class="fas fa-car"></i> Free parking available at Financial Square</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="site-footer" style="text-align:center; margin-top:2rem;">
        <p>&copy; 2026 APEXLedger &middot; Ntuli Accountants and Associates &middot; POPIA Compliant</p>
        <p style="margin-top:.5rem;">eMalahleni, Mpumalanga &middot; 079 400 5315 &middot; accounts@ntuli.co.za &middot; ⭐ 5.0 Client Rating</p>
    </footer>
</body>
</html>
