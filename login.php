<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

// Si ya está logueado, redirigir
if (Session::is_logged_in()) {
    $rol = Session::get_user_role();
    redirect(BASE_URL . "/modules/$rol/dashboard.php");
}

$error = '';
$success = '';

// Mensaje de registro exitoso (comprador)
if (isset($_SESSION['registro_exitoso'])) {
    $success = '¡Cuenta creada exitosamente! Ya puedes iniciar sesión.';
    unset($_SESSION['registro_exitoso']);
}

// Mensaje de solicitud enviada (editor)
if (isset($_SESSION['solicitud_enviada'])) {
    $success = '¡Solicitud enviada! Revisaremos tu comprobante y activaremos tu cuenta en 24-48 horas. Te enviaremos un email cuando esté lista.';
    unset($_SESSION['solicitud_enviada']);
}

// Generar token CSRF para el formulario de login si no existe
$csrf_token = Session::generate_csrf_token();

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    
    // Validar CSRF Token
    if (!isset($_POST['csrf_token']) || !Session::validate_csrf_token($_POST['csrf_token'])) {
        $error = 'Petición inválida o expirada. Por favor, intenta de nuevo.';
    }
    // Verificar si el usuario está bloqueado temporalmente por intentos fallidos
    elseif (isset($_SESSION['bloqueo_hasta']) && $_SESSION['bloqueo_hasta'] > time()) {
        $tiempo_restante = ceil(($_SESSION['bloqueo_hasta'] - time()) / 60);
        $error = "Demasiados intentos fallidos. Por favor espera $tiempo_restante minuto(s) antes de intentar nuevamente.";
    } 
    else {
        $usuario = limpiar_texto($_POST['usuario']);
        $password = $_POST['password'];

        if (empty($usuario) || empty($password)) {
            $error = 'Por favor, complete todos los campos';
        }
        else {
            // Buscar usuario
            $query = "SELECT * FROM usuarios WHERE (usuario = ? OR email = ?) AND estado = 'activo'";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, "ss", $usuario, $usuario);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                // Verificar contraseña
                if (verificar_password($password, $user['password'])) {
                    // Login exitoso, borrar la info de fallos previos (Se hace internamente en Session::login)
                    Session::login($user);

                    // Redirigir según rol
                    redirect(BASE_URL . "/modules/{$user['rol']}/dashboard.php");
                }
                else {
                    $error = 'Contraseña o usuario incorrectos'; // Mensaje general para no dar pistas
                }
            }
            else {
                $error = 'Contraseña o usuario incorrectos'; // Mensaje general para no dar pistas
            }

            // Registrar intento fallido
            if ($error == 'Contraseña o usuario incorrectos') {
                if (!isset($_SESSION['intentos_fallidos'])) $_SESSION['intentos_fallidos'] = 0;
                $_SESSION['intentos_fallidos']++;
                
                if ($_SESSION['intentos_fallidos'] >= 5) {
                    $_SESSION['bloqueo_hasta'] = time() + (15 * 60); // Bloqueo de 15 minutos
                    $error = "Demasiados intentos fallidos. Por seguridad, ha sido bloqueado por 15 minutos.";
                } else {
                    $intentos_restantes = 5 - $_SESSION['intentos_fallidos'];
                    $error .= ". Te quedan $intentos_restantes intentos.";
                }
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo NOMBRE_SISTEMA; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
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
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-full: 50px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            max-width: 950px;
            width: 100%;
            display: flex;
            border: 1px solid var(--border);
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: #0a0a0f;
            padding: 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .login-left h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 15px;
        }
        
        .login-left p {
            font-size: 1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .login-left .icon {
            font-size: 6rem;
            opacity: 0.3;
            margin-top: 20px;
        }
        
        .login-right {
            padding: 50px 45px;
            flex: 1;
            background: var(--bg-card);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        
        .login-header h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: var(--text-secondary);
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-group label {
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: block;
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .input-group-custom > i:not(.password-toggle i) {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 1;
        }
        
        .input-group-custom .form-control {
            padding: 14px 45px 14px 45px;
            border: 2px solid var(--border);
            border-radius: var(--radius-full);
            font-size: 1rem;
            background: var(--surface);
            color: var(--text-primary);
            transition: all 0.3s;
        }
        
        .input-group-custom .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: var(--surface);
            color: var(--text-primary);
            outline: none;
        }
        
        .input-group-custom .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .btn-login {
            background: var(--primary-color);
            color: #0a0a0f;
            padding: 14px;
            border: none;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 1.05rem;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--primary-glow);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--text-muted);
            z-index: 2;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .login-links {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
        }
        
        .login-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        
        .login-links a:hover {
            color: var(--primary-color);
        }
        
        .login-links a.primary-link {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
                background: var(--bg-dark);
            }
            .login-container {
                flex-direction: column;
                border-radius: var(--radius-md);
            }
            .login-left {
                padding: 30px 20px;
            }
            .login-left h2 {
                font-size: 1.6rem;
            }
            .login-left p {
                font-size: 0.9rem;
            }
            .login-left .icon {
                display: none;
            }
            .login-right {
                padding: 30px 20px;
            }
            .login-header h3 {
                font-size: 1.5rem;
            }
            .form-control {
                padding: 16px 15px 16px 45px;
            }
            .btn-login {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <!-- Lado izquierdo -->
        <div class="login-left">
            <div class="login-left-text">
                <h2>PES BOLIVIA</h2>
                <p>Bienvenido de vuelta. Ingresa tus credenciales para acceder a tu cuenta.</p>
            </div>
            <div class="icon">
                <i class="fas fa-futbol"></i>
            </div>
        </div>
        
        <!-- Lado derecho - Formulario -->
        <div class="login-right">
            <div class="login-header">
                <h3>Iniciar Sesión</h3>
                <p>Ingresa a tu cuenta</p>
            </div>
            
            <?php if (!empty($error) || !empty($success)): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    <?php if (!empty($error)): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Ups...',
                        text: '<?php echo addslashes($error); ?>',
                        confirmButtonColor: '#27CCA0'
                    });
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Excelente!',
                        text: '<?php echo addslashes($success); ?>',
                        confirmButtonColor: '#27CCA0'
                    });
                    <?php endif; ?>
                });
            </script>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-group">
                    <label for="usuario">Usuario o Email</label>
                    <div class="input-group-custom">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="usuario" name="usuario" 
                               placeholder="Ingresa tu usuario o email" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-group-custom">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Ingresa tu contraseña" required>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Iniciar Sesión
                </button>
            </form>
            
            <div class="login-links">
                <a href="registro.php" class="primary-link">
                    <i class="fas fa-user-plus me-1"></i> ¿No tienes cuenta? Regístrate aquí
                </a>
                
                <a href="recuperar_password.php">
                    <i class="fas fa-key me-1"></i> ¿Olvidaste tu contraseña?
                </a>
                
                <a href="index.php">
                    <i class="fas fa-arrow-left me-1"></i> Volver al inicio
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;
            
            if (usuario === '' || password === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos vacíos',
                    text: 'Por favor, complete todos los campos',
                    confirmButtonColor: '#27CCA0'
                });
            }
        });
    </script>
</body>
</html>