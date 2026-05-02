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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap" rel="stylesheet">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        .amazon-brand { 
            font-family: 'Russo One', sans-serif;
            color: white; 
            font-size: 1.5rem; 
            font-weight: 400; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            letter-spacing: 1px;
        }
        .amazon-brand:hover { color: white; }
        .amazon-brand span { color: var(--amazon-yellow); }
        
        /* Navbar */
        .navbar {
            background: #111111;
            border-bottom: 2px solid #004d00;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: white !important;
        }
        
        .navbar-brand i {
            margin-right: 10px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            margin: 0 15px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-login {
            background: white;
            color: #004d00;
            padding: 8px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Hero Section Slider Styles */
        .hero {
            position: relative;
            min-height: 90vh;
            display: flex;
            align-items: center;
            padding: 120px 0 100px;
            overflow: hidden;
            z-index: 1;
        }

        /* Overlay oscuro y curva de abajo SVG */
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(rgba(17,17,17,0.7), rgba(17,17,17,0.9)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,165.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
            z-index: 2;
            pointer-events: none;
        }

        /* Slider Background Container */
        .hero-slider-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .hero-slider-bg .slide-item {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center center;
            opacity: 0;
            animation: kenburns-slide 30s infinite linear; /* Duración total = número imgs * x segs */
        }

        /* Tiempos de cada Slide (Total: 30s) */
        .hero-slider-bg .slide-item:nth-child(1) {
            background-image: url('uploads/img/pes21.png');
            animation-delay: 0s;
        }
        .hero-slider-bg .slide-item:nth-child(2) {
            background-image: url('uploads/img/1.jpg');
            animation-delay: 6s;
        }
        .hero-slider-bg .slide-item:nth-child(3) {
            background-image: url('uploads/img/2.jpg');
            animation-delay: 12s;
        }
        .hero-slider-bg .slide-item:nth-child(4) {
            background-image: url('uploads/img/3.jpg');
            animation-delay: 18s;
        }
        .hero-slider-bg .slide-item:nth-child(5) {
            background-image: url('uploads/img/4.jpg');
            animation-delay: 24s;
        }

        /* Efecto tipo Ken Burns: Desvanecimiento y Zoom simultáneo */
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
            font-size: 4.2rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 25px;
            text-shadow: 2px 4px 8px rgba(0,0,0,0.5);
            background: linear-gradient(135deg, #ffffff 0%, #d4d4d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }
        
        .btn-hero {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 16px 45px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            border: 2px solid transparent;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
            display: inline-block;
            text-decoration: none;
        }
        
        .btn-hero:hover {
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.6);
            border-color: rgba(255,255,255,0.2);
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        
        .hero-image {
            animation: float 4s ease-in-out infinite;
        }
        
        .hero-player-img {
            max-height: 650px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            filter: drop-shadow(0 25px 35px rgba(0,0,0,0.8));
            transform: scale(1.1);
            margin: 0 auto;
            display: block;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            height: 100%;
            border: 2px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            border-color: #004d00;
            box-shadow: 0 10px 30px rgba(0, 77, 0, 0.2);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: #004d00;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 20px;
        }
        
        .feature-card h4 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Stats Section */
        .stats {
            background: #004d00;
            color: white;
            padding: 60px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        /* CTA Section */
        .cta {
            padding: 80px 0;
            background: white;
        }
        
        .cta-box {
            background: #111111;
            border: 2px solid #004d00;
            color: white;
            padding: 60px;
            border-radius: 20px;
            text-align: center;
        }
        
        .cta-box h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .cta-box p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }
        
        /* Footer */
        footer {
            background: #2d3748;
            color: white;
            padding: 40px 0 20px;
        }
        
        footer a {
            color: #a0aec0;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        footer a:hover {
            color: white;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin: 0 5px;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: #004d00;
            transform: translateY(-3px);
        }
        
        @media (max-width: 991px) {
            .navbar-collapse {
                background: #111111;
                padding: 15px;
                border-radius: 8px;
                margin-top: 10px;
                border: 1px solid #333;
            }
            .nav-item {
                margin: 5px 0 !important;
            }
        }
        
        @media (max-width: 768px) {
            .hero {
                padding: 120px 0 80px;
                min-height: 85vh;
            }
            .hero-content {
                text-align: center;
                margin-bottom: 30px;
            }
            .hero h1 {
                font-size: 2.2rem;
            }
            .hero p {
                font-size: 1.1rem;
            }
            .hero-player-img {
                max-height: 320px;
                transform: scale(1);
                margin: 0 auto;
                display: block;
            }
            .amazon-brand {
                font-size: 0.9rem;
            }
            .amazon-brand i {
                font-size: 1.2rem;
            }
            .stat-number {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a href="index.php" class="navbar-brand amazon-brand mb-0">
                <i class="fas fa-gamepad me-2 text-white"></i> PES <span class="ms-1 text-danger">BOLIVIA</span> &nbsp; <span class="text-warning">EDITION</span> &nbsp; <span class="text-success">STORE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tienda.php">Tienda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacto">Contacto</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a class="btn btn-login" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-login" href="registro.php" style="background: white; color: #004d00; border: 2px solid white;">
                            <i class="fas fa-user-plus"></i> Registrarse
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="inicio">
        <!-- Slider Images Background -->
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
                    <h1 class="mx-auto" style="max-width: 900px; text-shadow: 0 5px 15px rgba(0,0,0,0.8);">
                        LA EXPERIENCIA DEFINITIVA
                    </h1>
                    <p class="mx-auto fs-4 fw-light mb-5" style="max-width: 700px; text-shadow: 0 4px 10px rgba(0,0,0,0.9); line-height: 1.6;">
                        Parches oficiales, kits exclusivos, módulos avanzados ,  Estadios , Tutoriales , Herramientas de Edicion y mucho mas.
                    </p>
                    <a href="login.php" class="btn btn-hero">
                        Comenzar Ahora <i class="fas fa-arrow-right ms-2 fs-5 align-middle"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Inicializar AOS
        AOS.init({
            duration: 1000,
            once: true
        });
        
        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>