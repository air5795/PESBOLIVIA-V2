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
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #111111;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: flex;
        }
        
        .login-left {
            background: #004d00;
            color: white;
            padding: 60px 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .login-left h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .login-left .icon {
            font-size: 8rem;
            opacity: 0.3;
            margin-top: 30px;
        }
        
        .login-right {
            padding: 60px 50px;
            flex: 1;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-header h3 {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
        
        .form-control {
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #004d00;
            box-shadow: 0 0 0 0.2rem rgba(0, 77, 0, 0.1);
        }
        
        .btn-login {
            background: #004d00;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 77, 0, 0.4);
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-password a {
            color: #004d00;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .forgot-password a:hover {
            color: #003300;
        }
        
        .back-home {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-home a {
            color: #666;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .back-home a:hover {
            color: #004d00;
        }
        
        .back-home i {
            margin-right: 8px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }
        
        .password-toggle:hover {
            color: #004d00;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 15px;
                background: linear-gradient(135deg, #111111 0%, #002200 100%);
            }
            .login-container {
                flex-direction: column;
                border-radius: 15px;
            }
            .login-left {
                padding: 30px 20px;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
            }
            .login-left-text {
                width: 100%;
            }
            .login-left h2 {
                font-size: 1.8rem;
                margin-bottom: 5px;
            }
            .login-left p {
                display: block;
                font-size: 0.95rem;
                margin-bottom: 0;
            }
            .login-left .icon {
                display: none; /* Ocultamos el ícono gigante para ahorrar espacio valioso en el cel */
            }
            .login-right {
                padding: 35px 20px;
            }
            .login-header {
                margin-bottom: 25px;
            }
            .login-header h3 {
                font-size: 1.5rem;
            }
            .form-control {
                padding: 14px 15px 14px 45px; /* Inputs ligeramente más grandes para touch */
            }
            .btn-login {
                padding: 14px;
            }
            .d-flex.flex-column.gap-3 {
                gap: 1.5rem !important; /* Más espacio en botones de abajo para que no haya miss-clicks */
            }
        }
    </style>
</head>
<body>
    
    <div class="login-container">
        <!-- Lado izquierdo -->
        <div class="login-left">
            <div class="login-left-text">
                <h2><?php echo NOMBRE_SISTEMA; ?></h2>
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
                        confirmButtonColor: '#004d00'
                    });
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                    Swal.fire({
                        icon: 'success',
                        title: '¡Excelente!',
                        text: '<?php echo addslashes($success); ?>',
                        confirmButtonColor: '#004d00'
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
            
            <div class="d-flex flex-column align-items-center gap-3 mt-4">
                <a href="registro.php" class="text-decoration-none fw-bold" style="color: #004d00; font-size: 1.05rem; transition: color 0.2s;" onmouseover="this.style.color='#006600'" onmouseout="this.style.color='#004d00'">
                    <i class="fas fa-user-plus me-1"></i> ¿No tienes cuenta? Registrate aquí
                </a>
                
                <a href="recuperar_password.php" class="text-decoration-none text-secondary" style="font-size: 0.95rem; font-weight: 500; transition: color 0.2s;" onmouseover="this.style.color='#333'" onmouseout="this.style.color='#6c757d'">
                    <i class="fas fa-key me-1"></i> ¿Olvidaste tu contraseña?
                </a>
                
                <a href="index.php" class="text-decoration-none text-muted" style="font-size: 0.9rem; transition: color 0.2s;" onmouseover="this.style.color='#004d00'" onmouseout="this.style.color='#6c757d'">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Toggle password visibility
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
        
        // Validación del formulario
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;
            
            if (usuario === '' || password === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos vacíos',
                    text: 'Por favor, complete todos los campos',
                    confirmButtonColor: '#004d00'
                });
            }
        });
    </script>
</body>
</html>