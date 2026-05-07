<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

// Si ya está logueado, redirigir a su panel
if (Session::is_logged_in()) {
    $rol = Session::get_user_role();
    redirect(BASE_URL . "/modules/$rol/dashboard.php");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo NOMBRE_SISTEMA; ?> PES BOLIVIA</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #27CCA0;
            --primary-dark: #1fa87a;
            --primary-glow: rgba(39, 204, 160, 0.3);
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --surface: #16161f;
            --surface-alt: #1c1c26;
            --border: #2a2a3a;
            --text-primary: #e8e8ec;
            --text-secondary: #9999a8;
            --text-muted: #666677;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.4);
            --shadow-lg: 0 15px 40px rgba(0,0,0,0.5);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-full: 50px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Navbar Moderno Oscuro */
        .modern-nav {
            background: rgba(18, 18, 26, 0.98);
            backdrop-filter: 20px;
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        .nav-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            gap: 20px;
        }
        .nav-brand-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .mobile-menu-btn {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-primary);
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .mobile-menu-btn:hover {
            background: var(--primary-color);
            color: #0a0a0f;
            border-color: var(--primary-color);
        }

        .modern-brand {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1px;
            letter-spacing: -0.5px;
            flex-shrink: 0;
        }
        .modern-brand:hover { color: var(--text-primary); }
        .modern-brand i { color: var(--primary-color); font-size: 1.2rem; margin-right: 3px; }
        .modern-brand .pes-red { color: #fff; font-size: 0.95rem; }
        .modern-brand .bo-red { color: #e74c3c; font-weight: 900; font-size: 0.95rem; }
        .modern-brand .li-yellow { color: #f1c40f; font-weight: 900; font-size: 0.95rem; }
        .modern-brand .via-green { color: var(--primary-color); font-weight: 900; font-size: 0.95rem; }
        .modern-brand .badge-store {
            background: var(--primary-color);
            color: #0a0a0f;
            font-size: 0.5rem;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: 700;
            margin-left: 3px;
        }

        .nav-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .nav-actions a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-full);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        .btn-login-nav {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--border);
        }
        .btn-login-nav:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .btn-register-nav {
            background: var(--primary-color);
            color: #0a0a0f;
        }
        .btn-register-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
            color: #0a0a0f;
        }
        
        .btn-whatsapp {
            background: var(--primary-color) !important;
            color: #0a0a0f !important;
        }
        .btn-whatsapp:hover {
            background: var(--primary-dark) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
            color: #0a0a0f !important;
        }

        @media (min-width: 768px) {
            .modern-brand { font-size: 1.3rem; gap: 2px; }
            .modern-brand i { font-size: 1.4rem; margin-right: 5px; }
            .modern-brand .pes-red, .modern-brand .bo-red, .modern-brand .li-yellow, .modern-brand .via-green { font-size: 1rem; }
            .modern-brand .badge-store { font-size: 0.6rem; padding: 3px 6px; margin-left: 5px; }
        }
        @media (min-width: 992px) {
            .modern-brand { font-size: 1.4rem; }
            .modern-brand i { font-size: 1.5rem; }
            .mobile-menu-btn { display: none !important; }
        }
        
        /* Hero Section Slider */
        .hero {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            padding: 120px 0 100px;
            overflow: hidden;
            z-index: 1;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(rgba(10,10,15,0.75), rgba(10,10,15,0.9)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%2327CCA0" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
            z-index: 2;
            pointer-events: none;
        }
        .hero-slider-bg {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
        }
        .hero-slider-bg .slide-item {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-size: cover;
            background-position: center center;
            opacity: 0;
            animation: kenburns-slide 30s infinite linear;
        }
        .hero-slider-bg .slide-item:nth-child(1) { background-image: url('uploads/img/pes21.png'); animation-delay: 0s; }
        .hero-slider-bg .slide-item:nth-child(2) { background-image: url('uploads/img/1.jpg'); animation-delay: 6s; }
        .hero-slider-bg .slide-item:nth-child(3) { background-image: url('uploads/img/2.jpg'); animation-delay: 12s; }
        .hero-slider-bg .slide-item:nth-child(4) { background-image: url('uploads/img/3.jpg'); animation-delay: 18s; }
        .hero-slider-bg .slide-item:nth-child(5) { background-image: url('uploads/img/4.jpg'); animation-delay: 24s; }

        @keyframes kenburns-slide {
            0% { opacity: 0; transform: scale(1); }
            10% { opacity: 1; transform: scale(1.02); }
            20% { opacity: 1; transform: scale(1.08); }
            30% { opacity: 0; transform: scale(1.1); }
            100% { opacity: 0; transform: scale(1.1); }
        }
        
        .hero-content {
            position: relative;
            z-index: 3;
            color: white;
        }
        
        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            text-shadow: 2px 4px 8px rgba(0,0,0,0.5);
            background: linear-gradient(135deg, #ffffff 0%, #d4d4d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 35px;
            opacity: 0.95;
            color: var(--text-secondary);
        }
        
        .btn-hero {
            background: var(--primary-color);
            color: #0a0a0f;
            padding: 16px 45px;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            box-shadow: 0 10px 25px var(--primary-glow);
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-hero:hover {
            color: #0a0a0f;
            transform: translateY(-5px);
            box-shadow: 0 15px 35px var(--primary-glow);
        }

        /* Footer */
        .modern-footer {
            background: var(--bg-card);
            color: white;
            padding: 40px 20px 25px;
            border-top: 1px solid var(--border);
        }
        .footer-back-to-top {
            background: var(--primary-color);
            color: #0a0a0f;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .footer-back-to-top:hover { background: var(--primary-dark); }
        .footer-brand {
            text-align: center;
            padding: 25px 0;
        }
        .footer-brand i { font-size: 1.8rem; margin-bottom: 8px; color: var(--primary-color); }
        .footer-brand h4 { color: var(--primary-color); }

        @media (max-width: 768px) {
            .hero { padding: 100px 0 60px; min-height: 80vh; }
            .hero-content { text-align: center; margin-bottom: 30px; }
            .hero h1 { font-size: 2rem; }
            .hero p { font-size: 1rem; }
            .nav-actions { gap: 5px; }
            .nav-actions a { padding: 8px 12px; font-size: 0.85rem; }
            .nav-actions a span { display: none; }
            .nav-actions a i { margin: 0; }
            .btn-hero { padding: 14px 30px; font-size: 1rem; }
            .modern-nav { padding: 10px 12px; }
            .nav-content { gap: 10px; }
        }
    </style>
</head>
<body>
    
    <!-- Navbar Moderno -->
    <nav class="modern-nav">
        <div class="nav-content">
            <div class="nav-brand-menu">
                <button class="mobile-menu-btn" onclick="toggleMenu()">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="index.php" class="modern-brand">
                    <i class="fas fa-gamepad"></i>
                    <span class="pes-red">PES</span>
                    <span class="bo-red">BO</span>
                    <span class="li-yellow">LI</span>
                    <span class="via-green">VIA</span>
                    <span class="badge-store">STORE</span>
                </a>
            </div>
            <div class="nav-actions">
                <a href="tienda.php" class="btn-login-nav">
                    <i class="fas fa-store"></i> <span>Tienda</span>
                </a>
                <a href="https://wa.me/59179441119" target="_blank" class="btn-login-nav btn-whatsapp">
                    <i class="fab fa-whatsapp"></i> <span>Contacto</span>
                </a>
                <a href="login.php" class="btn-login-nav">
                    <i class="fas fa-sign-in-alt"></i> <span>Iniciar Sesión</span>
                </a>
                <a href="registro.php" class="btn-register-nav">
                    <i class="fas fa-user-plus"></i> <span>Registrarse</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <div class="hero-slider-bg">
            <div class="slide-item"></div>
            <div class="slide-item"></div>
            <div class="slide-item"></div>
            <div class="slide-item"></div>
            <div class="slide-item"></div>
        </div>

        <div class="container h-100">
            <div class="row h-100 align-items-center justify-content-center text-center">
                <div class="col-lg-9 hero-content" data-aos="fade-up">
                    <h1 class="mx-auto" style="max-width: 900px;">
                        LA EXPERIENCIA DEFINITIVA
                    </h1>
                    <p class="mx-auto mb-5" style="max-width: 700px;">
                        Parches oficiales, kits exclusivos, módulos avanzados, Estadios, Tutoriales, Herramientas de Edición y mucho más.
                    </p>
                    <a href="login.php" class="btn-hero">
                        Comenzar Ahora <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="modern-footer">
        <div class="footer-back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
            <i class="fas fa-arrow-up me-2"></i> Volver al inicio
        </div>
        <div class="footer-brand">
            <i class="fas fa-gamepad"></i>
            <h4 class="text-white mb-2">PES <span style="color: var(--primary-color);">BOLIVIA</span> STORE</h4>
            <p class="text-white-50 mb-0">&copy; <?php echo date('Y'); ?>. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        AOS.init({ duration: 1000, once: true });
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>