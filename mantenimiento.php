<?php
require_once 'config/config.php';

// Si no está en mantenimiento y no es superadmin, redirigir al index
if (!isset($CONFIG['modo_mantenimiento']) || $CONFIG['modo_mantenimiento'] != '1') {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}

// Opcional: Si el usuario ya inicio sesion y es superadmin, sugerirle que puede ver la página principal
require_once 'config/session.php';
$es_superadmin = (class_exists('Session') && Session::is_logged_in() && Session::is_superadmin());

if ($es_superadmin) {
    header("Location: " . BASE_URL . "/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>En Mantenimiento - <?php echo htmlspecialchars(NOMBRE_SISTEMA); ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #111111 0%, #1a1a1a 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at 50% 50%, rgba(0, 77, 0, 0.15) 0%, transparent 60%);
            z-index: 1;
        }

        .maintenance-container {
            max-width: 600px;
            padding: 3rem;
            background: rgba(25, 25, 25, 0.9);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
        }

        .icon-container {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #004d00 0%, #008000 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto 2rem;
            box-shadow: 0 10px 25px rgba(0, 77, 0, 0.4);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 77, 0, 0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(0, 77, 0, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 77, 0, 0); }
        }

        h1 {
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(to right, #ffffff, #a0aec0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        p.lead {
            font-size: 1.1rem;
            color: #a0aec0;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .btn-admin {
            margin-top: 2rem;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }

        .btn-admin:hover {
            color: white;
            text-decoration: underline;
        }

        .amazon-brand { 
            font-family: 'Russo One', sans-serif;
            color: white; 
            font-size: 1.5rem; 
            display: inline-block;
            margin-bottom: 2rem;
        }
        .amazon-brand span { color: #10b981; }
        .amazon-brand .store { color: #047857; }
        .amazon-brand .danger { color: #ef4444; }
    </style>
</head>
<body>

    <div class="maintenance-container">
        <div class="amazon-brand">
            <i class="fas fa-gamepad me-2 text-white"></i> PES <span class="danger ms-1">BOLIVIA</span> &nbsp; <span class="store">STORE</span>
        </div>

        <div class="icon-container">
            <i class="fas fa-tools text-white"></i>
        </div>
        
        <h1>Estamos en Mantenimiento</h1>
        
        <p class="lead">
            Estamos preparando el lanzamiento oficial de nuestra plataforma. ¡Pronto estaremos listos para ofrecerte la mejor experiencia! Vuelve en unas horas.
        </p>
        
        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.1); border-radius: 10px; overflow: hidden; margin-bottom: 2rem;">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 75%"></div>
        </div>
        
        <div>
            <a href="https://www.facebook.com/profile.php?id=100063614739282" target="_blank" class="btn btn-outline-light rounded-pill px-4 py-2 me-2">
                <i class="fab fa-facebook me-2"></i> Síguenos
            </a>
            <a href="login.php" class="btn btn-admin mt-0">
                <i class="fas fa-lock me-1"></i> Admin Login
            </a>
        </div>
    </div>

</body>
</html>
