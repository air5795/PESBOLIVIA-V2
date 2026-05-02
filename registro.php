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
$paso = 1; // Paso del formulario

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar'])) {
    $nombre = limpiar_texto($_POST['nombre']);
    $apellido = limpiar_texto($_POST['apellido']);
    $email = limpiar_texto($_POST['email']);
    $usuario = limpiar_texto($_POST['usuario']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $rol = limpiar_texto($_POST['rol']);
    
    $errores = [];
    
    // Validaciones
    if (empty($nombre)) $errores[] = "El nombre es requerido";
    if (empty($apellido)) $errores[] = "El apellido es requerido";
    if (empty($email)) $errores[] = "El email es requerido";
    if (!validar_email($email)) $errores[] = "Email no válido";
    if (empty($usuario)) $errores[] = "El usuario es requerido";
    if (strlen($password) < 6) $errores[] = "La contraseña debe tener al menos 6 caracteres";
    if ($password !== $password_confirm) $errores[] = "Las contraseñas no coinciden";
    if (!in_array($rol, ['editor', 'comprador'])) $errores[] = "Rol no válido";
    
    // Verificar usuario y email únicos
    if (usuario_existe($usuario)) $errores[] = "El usuario ya existe";
    if (email_existe($email)) $errores[] = "El email ya está registrado";
    
    if (!empty($errores)) {
        $error = implode('<br>', $errores);
    } else {
        // Crear usuario
        $password_hash = hash_password($password);
        $estado = ($rol === 'comprador') ? 'activo' : 'inactivo'; // Los editores inician inactivos
        
        $query = "INSERT INTO usuarios (nombre, apellido, usuario, email, password, rol, estado) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sssssss", $nombre, $apellido, $usuario, $email, $password_hash, $rol, $estado);
        
        if (mysqli_stmt_execute($stmt)) {
            $nuevo_id = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            
            if ($rol === 'comprador') {
                // Comprador registrado directamente
                $_SESSION['registro_exitoso'] = true;
                redirect('login.php');
            } else {
                // Editor - pasar al formulario de pago
                $_SESSION['editor_registrado_id'] = $nuevo_id;
                $_SESSION['editor_nombre'] = $nombre . ' ' . $apellido;
                redirect('registro_pago.php');
            }
        } else {
            $error = "Error al crear la cuenta. Intenta nuevamente.";
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
    <title>Registro - <?php echo NOMBRE_SISTEMA; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #111111;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .registro-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        
        .registro-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .registro-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .role-card {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-card:hover {
            border-color: #004d00;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 77, 0, 0.2);
        }
        
        .role-card.selected {
            border-color: #004d00;
            background: #004d00;
            color: white;
        }
        
        .role-card i {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .role-card h5 {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .role-card p {
            font-size: 0.9rem;
            margin: 0;
            opacity: 0.8;
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
        
        .btn-registro {
            background: #004d00;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
        }
        
        .btn-registro:hover {
            opacity: 0.9;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #004d00;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        /* Optimizaciones para Celular */
        @media (max-width: 768px) {
            body {
                padding: 15px;
                background: linear-gradient(135deg, #111111 0%, #002200 100%);
            }
            .registro-container {
                padding: 25px 20px;
                border-radius: 15px;
            }
            .role-selection {
                grid-template-columns: 1fr; /* Apilamos las opciones de rol verticalmente */
                gap: 10px;
            }
            .role-card {
                padding: 15px;
                display: flex;
                align-items: center;
                text-align: left;
            }
            .role-card i {
                font-size: 2rem;
                margin-right: 15px;
                margin-bottom: 0;
            }
            .role-card h5 {
                margin-bottom: 2px;
                font-size: 1.1rem;
            }
            .role-card p {
                font-size: 0.85rem;
            }
            .form-control {
                padding: 14px;
            }
            .btn-registro {
                padding: 14px;
                font-size: 1.1rem;
            }
            .row > div {
                margin-bottom: 15px;
            }
            /* Ocultar margen del ultimo col de la fila si está en formato block */
            .row .mb-3 { margin-bottom: 0 !important; }
            .col-md-6 { margin-bottom: 15px; }
        }
    </style>
</head>
<body>
    
    <div class="registro-container">
        <div class="registro-header">
            <h2>
                <i class="fas fa-user-plus me-2"></i>
                Crear Cuenta
            </h2>
            <p class="text-muted">Únete a <?php echo NOMBRE_SISTEMA; ?></p>
        </div>
        
        <?php if (!empty($error)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hubo un problema',
                    html: '<?php echo $error; ?>',
                    confirmButtonColor: '#004d00'
                });
            });
        </script>
        <?php endif; ?>
        
        <form method="POST" action="" id="formRegistro">
            <!-- Selección de Rol -->
            <div class="role-selection">
                <div class="role-card" onclick="selectRole('comprador')">
                    <i class="fas fa-shopping-cart"></i>
                    <h5>Comprador</h5>
                    <p>Compra parches y módulos</p>
                </div>
                
                <div class="role-card" onclick="selectRole('editor')">
                    <i class="fas fa-user-edit"></i>
                    <h5>Editor</h5>
                    <p>Vende tus creaciones</p>
                </div>
            </div>
            
            <input type="hidden" name="rol" id="rol" required>
            
            <div id="info-editor" class="info-box" style="display: none;">
                <strong><i class="fas fa-info-circle me-2"></i>Registro de Editor:</strong>
                <p class="mb-0 mt-2">
                    Para vender productos debes pagar una membresía de <strong>$10 USD (≈ Bs. 70)</strong>. 
                    Después del registro te pediremos el comprobante de pago.
                </p>
            </div>
            
            <div id="formFields" style="display: none;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nombre *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-user-edit"></i>
                            <input type="text" class="form-control" name="nombre" placeholder="Tu nombre" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Apellido *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-user-edit"></i>
                            <input type="text" class="form-control" name="apellido" placeholder="Tu apellido" required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <div class="input-group-custom">
                        <i class="fas fa-envelope"></i>
                        <input type="email" class="form-control" name="email" placeholder="tucorreo@ejemplo.com" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Usuario *</label>
                    <div class="input-group-custom">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Nombre de usuario" required>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Solo letras, números y guiones bajos</small>
                        <small id="usuarioFeedback" style="display: none; font-weight: 500;"></small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contraseña *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mín. 6 caracteres" required>
                            <span class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Confirmar Contraseña *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite tu contraseña" required>
                            <span class="password-toggle" onclick="togglePassword('password_confirm', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </span>
                        </div>
                        <small id="passwordFeedback" class="mt-1 d-block" style="display: none; font-weight: 500;"></small>
                    </div>
                </div>
                
                <button type="submit" name="registrar" class="btn btn-registro mt-3">
                    <i class="fas fa-user-plus me-2"></i>
                    Crear Cuenta
                </button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="mb-0">¿Ya tienes cuenta? <a href="login.php" style="color: #004d00; font-weight: 600;">Inicia Sesión</a></p>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" style="color: #666; text-decoration: none;">
                <i class="fas fa-arrow-left me-1"></i>
                Volver al inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Ocultar/Mostrar contraseñas
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

        function selectRole(role) {
            // Remover selección anterior
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Seleccionar nuevo rol
            event.currentTarget.classList.add('selected');
            document.getElementById('rol').value = role;
            
            // Mostrar info de editor si aplica
            if (role === 'editor') {
                document.getElementById('info-editor').style.display = 'block';
            } else {
                document.getElementById('info-editor').style.display = 'none';
            }
            
            // Mostrar formulario
            document.getElementById('formFields').style.display = 'block';
        }
        
        // Validar formulario
        document.getElementById('formRegistro').addEventListener('submit', function(e) {
            const rol = document.getElementById('rol').value;
            const password = document.getElementById('password').value;
            const password_confirm = document.getElementById('password_confirm').value;
            
            if (!rol) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta un paso',
                    text: 'Por favor selecciona un tipo de cuenta (Comprador o Editor) antes de continuar.',
                    confirmButtonColor: '#004d00'
                });
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña corta',
                    text: 'La contraseña debe tener al menos 6 caracteres.',
                    confirmButtonColor: '#004d00'
                });
                return false;
            }
            
            if (password !== password_confirm) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No coinciden',
                    text: 'Las contraseñas no coinciden. Por favor, revísalas.',
                    confirmButtonColor: '#004d00'
                });
                return false;
            }
            
            // Validation user check block submit if not valid. Note: This assumes valid input when typing.
            const userValidationStatusMessage = document.getElementById('usuarioFeedback').innerText;
            if(userValidationStatusMessage === 'Usuario no disponible') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Usuario no disponible',
                    text: 'El nombre de usuario que elegiste ya está registrado.',
                    confirmButtonColor: '#004d00'
                });
                return false;
            }
        });
        
        // Validación asíncrona (AJAX) en tiempo real para el nombre de usuario
        const inputUsuario = document.getElementById('usuario');
        const feedbackUsuario = document.getElementById('usuarioFeedback');
        let typingTimer;
        
        inputUsuario.addEventListener('input', function() {
            clearTimeout(typingTimer);
            const userText = inputUsuario.value.trim();
            
            feedbackUsuario.style.display = 'block';
            
            if(userText.length === 0) {
                feedbackUsuario.innerText = '';
                feedbackUsuario.style.display = 'none';
                return;
            }
            
            // Mostrando "Comprobando..."
            feedbackUsuario.innerText = 'Comprobando...';
            feedbackUsuario.style.color = '#888';
            
            // Pausa antes de lanzar request
            typingTimer = setTimeout(async function() {
                try {
                    const req = await fetch('ajax/validar_usuario.php?usuario=' + encodeURIComponent(userText));
                    const res = await req.json();
                    if(res.status === 'exists') {
                        feedbackUsuario.innerText = 'Usuario no disponible';
                        feedbackUsuario.style.color = '#dc3545'; // Rojo Bootstrap
                    } else if (res.status === 'available') {
                        feedbackUsuario.innerText = 'Usuario disponible';
                        feedbackUsuario.style.color = '#198754'; // Verde Bootstrap
                    }
                } catch(err) {
                    console.log('Error validando usuario AJAX', err);
                }
            }, 600); 
        });
        
        // Validación visual en tiempo real de contraseñas idénticas
        const inputPass = document.getElementById('password');
        const inputPassConfirm = document.getElementById('password_confirm');
        const passFeedback = document.getElementById('passwordFeedback');
        
        function checkPasswordsMatch() {
            const p1 = inputPass.value;
            const p2 = inputPassConfirm.value;
            
            if(p2.length > 0) {
                passFeedback.style.display = 'block';
                if(p1 === p2) {
                    passFeedback.innerHTML = '<i class="fas fa-check-circle"></i> Las contraseñas coinciden';
                    passFeedback.style.color = '#198754';
                } else {
                    passFeedback.innerHTML = '<i class="fas fa-times-circle"></i> Las contraseñas no coinciden';
                    passFeedback.style.color = '#dc3545';
                }
            } else {
                passFeedback.style.display = 'none';
            }
        }
        
        inputPass.addEventListener('input', checkPasswordsMatch);
        inputPassConfirm.addEventListener('input', checkPasswordsMatch);
    </script>
</body>
</html>