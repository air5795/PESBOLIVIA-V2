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
$mostrar_formulario_token = false;
$token = '';

// Verificar si hay un token en la URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = limpiar_texto($_GET['token']);
    
    // Verificar si el token es válido
    $query = "SELECT id, nombre, email FROM usuarios 
              WHERE token_recuperacion = ? 
              AND token_expiracion > NOW() 
              AND estado = 'activo'";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $mostrar_formulario_token = true;
    } else {
        $error = 'El enlace de recuperación es inválido o ha expirado';
    }
    
    mysqli_stmt_close($stmt);
}

// Procesar solicitud de recuperación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar'])) {
    $email = limpiar_texto($_POST['email']);
    
    if (empty($email)) {
        $error = 'Por favor, ingrese su email';
    } elseif (!validar_email($email)) {
        $error = 'Email no válido';
    } else {
        // Buscar usuario
        $query = "SELECT id, nombre, email FROM usuarios WHERE email = ? AND estado = 'activo'";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // Generar token
            $token = generar_token();
            $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Actualizar usuario con token
            $update = "UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($conexion, $update);
            mysqli_stmt_bind_param($stmt_update, "ssi", $token, $expiracion, $user['id']);
            mysqli_stmt_execute($stmt_update);
            mysqli_stmt_close($stmt_update);
            
            // Enviar email
            $emailSender = new EmailSender();
            if ($emailSender->email_recuperacion_password($email, $token, $user['nombre'])) {
                $success = 'Se ha enviado un enlace de recuperación a tu email';
            } else {
                $error = 'Error al enviar el email. Intenta nuevamente';
            }
        } else {
            // Por seguridad, mostrar el mismo mensaje aunque no exista
            $success = 'Si el email existe, recibirás un enlace de recuperación';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar'])) {
    $token = limpiar_texto($_POST['token']);
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    if (empty($nueva_password) || empty($confirmar_password)) {
        $error = 'Por favor, complete todos los campos';
    } elseif (strlen($nueva_password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($nueva_password !== $confirmar_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Verificar token nuevamente
        $query = "SELECT id FROM usuarios 
                  WHERE token_recuperacion = ? 
                  AND token_expiracion > NOW() 
                  AND estado = 'activo'";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            // Actualizar contraseña
            $password_hash = hash_password($nueva_password);
            
            $update = "UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE id = ?";
            $stmt_update = mysqli_prepare($conexion, $update);
            mysqli_stmt_bind_param($stmt_update, "si", $password_hash, $user['id']);
            
            if (mysqli_stmt_execute($stmt_update)) {
                mysqli_stmt_close($stmt_update);
                
                // Redirigir al login con mensaje
                $_SESSION['password_changed'] = true;
                redirect(BASE_URL . '/login.php?success=password_changed');
            } else {
                $error = 'Error al actualizar la contraseña';
            }
        } else {
            $error = 'Token inválido o expirado';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Mostrar mensaje de éxito si viene del login
if (isset($_SESSION['password_changed'])) {
    $success = 'Contraseña actualizada correctamente. Ya puedes iniciar sesión';
    unset($_SESSION['password_changed']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - <?php echo NOMBRE_SISTEMA; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
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
        
        .recovery-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px;
            max-width: 500px;
            width: 100%;
        }
        
        .recovery-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .recovery-icon {
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
        
        .recovery-header h3 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        
        .recovery-header p {
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
        
        .btn-submit {
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
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 77, 0, 0.4);
        }
        
        .back-login {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-login a {
            color: #004d00;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s;
        }
        
        .back-login a:hover {
            color: #003300;
        }
        
        .back-login i {
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
            .recovery-container {
                padding: 35px 20px;
                border-radius: 15px;
            }
            .recovery-icon {
                width: 65px;
                height: 65px;
                font-size: 1.5rem;
                margin-bottom: 15px;
            }
            .recovery-header h3 {
                font-size: 1.5rem;
            }
            .recovery-header p {
                font-size: 0.95rem;
            }
            .form-control {
                padding: 14px 15px 14px 45px;
            }
            .btn-submit {
                padding: 14px;
            }
        }
    </style>
</head>
<body>
    
    <div class="recovery-container">
        <div class="recovery-header">
            <div class="recovery-icon">
                <i class="fas fa-key"></i>
            </div>
            <h3><?php echo $mostrar_formulario_token ? 'Nueva Contraseña' : 'Recuperar Contraseña'; ?></h3>
            <p><?php echo $mostrar_formulario_token ? 'Ingresa tu nueva contraseña' : 'Te enviaremos un enlace para recuperar tu contraseña'; ?></p>
        </div>
        
        <?php if (!empty($error) || !empty($success)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                <?php if (!empty($error)): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Hubo un problema',
                    text: '<?php echo addslashes($error); ?>',
                    confirmButtonColor: '#004d00'
                });
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Operación Exitosa',
                    text: '<?php echo addslashes($success); ?>',
                    confirmButtonColor: '#004d00'
                });
                <?php endif; ?>
            });
        </script>
        <?php endif; ?>
        
        <?php if (!$mostrar_formulario_token): ?>
        <!-- Formulario de solicitud de recuperación -->
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group-custom">
                    <i class="fas fa-envelope"></i>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Ingresa tu email registrado" required autofocus>
                </div>
                <small class="text-muted">Te enviaremos un enlace de recuperación a este email</small>
            </div>
            
            <button type="submit" name="solicitar" class="btn btn-submit">
                <i class="fas fa-paper-plane me-2"></i>
                Enviar Enlace de Recuperación
            </button>
        </form>
        
        <?php else: ?>
        <!-- Formulario de cambio de contraseña -->
        <form method="POST" action="" id="changePasswordForm">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="nueva_password">Nueva Contraseña</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" id="nueva_password" name="nueva_password" 
                           placeholder="Mínimo 6 caracteres" required autofocus>
                    <span class="password-toggle" onclick="togglePassword('nueva_password', 'toggleIcon1')">
                        <i class="fas fa-eye" id="toggleIcon1"></i>
                    </span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirmar_password">Confirmar Contraseña</label>
                <div class="input-group-custom">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" 
                           placeholder="Repite tu contraseña" required>
                    <span class="password-toggle" onclick="togglePassword('confirmar_password', 'toggleIcon2')">
                        <i class="fas fa-eye" id="toggleIcon2"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" name="cambiar" class="btn btn-submit">
                <i class="fas fa-check me-2"></i>
                Cambiar Contraseña
            </button>
        </form>
        <?php endif; ?>
        
        <div class="back-login">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i>
                Volver al login
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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
        
        // Validar formulario de cambio de contraseña
        <?php if ($mostrar_formulario_token): ?>
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const nueva = document.getElementById('nueva_password').value;
            const confirmar = document.getElementById('confirmar_password').value;
            
            if (nueva.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña débil',
                    text: 'La contraseña debe tener al menos 6 caracteres',
                    confirmButtonColor: '#004d00'
                });
                return;
            }
            
            if (nueva !== confirmar) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Discrepancia',
                    text: 'Las contraseñas no coinciden',
                    confirmButtonColor: '#004d00'
                });
                return;
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>