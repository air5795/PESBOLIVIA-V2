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
        
        .registro-container {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: 35px;
            max-width: 1200px;
            width: 100%;
            border: 1px solid var(--border);
        }

        @media (min-width: 992px) {
            .registro-container {
                padding: 50px 70px;
            }
        }

        @media (min-width: 1200px) {
            .form-row-3 {
                gap: 50px;
            }
            .form-row-2 {
                gap: 50px;
            }
        }
        
        .registro-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .registro-header h2 {
            color: var(--text-primary);
            font-weight: 700;
            margin-bottom: 8px;
        }
        
        .registro-header p {
            color: var(--text-secondary);
        }
        
        .role-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        @media (min-width: 992px) {
            .role-selection {
                gap: 25px;
            }
            .role-card {
                padding: 25px;
            }
            .role-card i {
                font-size: 2.8rem;
            }
            .role-card h5 {
                font-size: 1.2rem;
            }
            .role-card p {
                font-size: 0.9rem;
            }
        }
        
        .role-card {
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: var(--surface);
            color: var(--text-primary);
        }
        
        .role-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px var(--primary-glow);
        }
        
        .role-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #0a0a0f;
        }
        
        .role-card i {
            font-size: 2.2rem;
            margin-bottom: 8px;
            display: block;
        }
        
        .role-card.selected i {
            color: #0a0a0f;
        }
        
        .role-card h5 {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }
        
        .role-card p {
            font-size: 0.8rem;
            margin: 0;
            opacity: 0.8;
        }
        
        .role-card.selected p {
            color: rgba(0,0,0,0.7);
        }
        
        .input-group-custom {
            position: relative;
        }
        
        .input-group-custom i {
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

        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background: var(--border);
            overflow: hidden;
            transition: all 0.3s;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 4px;
            color: var(--text-muted);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-glow);
            background: var(--surface);
            color: var(--text-primary);
            outline: none;
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .form-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .btn-registro {
            background: var(--primary-color);
            color: #0a0a0f;
            padding: 14px;
            border: none;
            border-radius: var(--radius-full);
            font-weight: 700;
            width: 100%;
            margin-top: 12px;
            font-size: 1.05rem;
            transition: all 0.3s;
        }
        
        .btn-registro:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--primary-glow);
        }
        
        .info-box {
            background: var(--surface);
            border-left: 4px solid var(--primary-color);
            padding: 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 18px;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .info-box strong {
            color: var(--primary-color);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-secondary);
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .back-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .back-link a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .registro-container {
                padding: 25px 18px;
                border-radius: var(--radius-md);
            }
            .role-selection {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .role-card {
                padding: 14px;
                display: flex;
                align-items: center;
                text-align: left;
            }
            .role-card i {
                font-size: 1.8rem;
                margin-right: 12px;
                margin-bottom: 0;
            }
            .role-card h5 { font-size: 1rem; }
            .role-card p { font-size: 0.8rem; }
            .form-control { padding: 16px 45px 16px 45px; }
            .btn-registro { padding: 16px; font-size: 1rem; }
            .col-md-6, .col-md-4 { margin-bottom: 12px; }
            .password-toggle { right: 12px; }
        }

        @media (min-width: 992px) {
            .form-row-3 {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 30px;
            }
            .form-row-2 {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 30px;
            }
            .form-row-3 > div,
            .form-row-2 > div {
                margin-bottom: 0;
            }
            .role-selection {
                gap: 40px;
            }
            .role-card {
                padding: 30px;
            }
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
            <p>Únete a PES Bolivia Store</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hubo un problema',
                    html: '<?php echo $error; ?>',
                    confirmButtonColor: '#27CCA0'
                });
            });
        </script>
        <?php endif; ?>
        
        <form method="POST" action="" id="formRegistro">
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
                <div class="form-row-3 mb-3">
                    <div>
                        <label class="form-label">Nombre *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-user-edit"></i>
                            <input type="text" class="form-control" name="nombre" placeholder="Tu nombre" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Apellido *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-user-edit"></i>
                            <input type="text" class="form-control" name="apellido" placeholder="Tu apellido" required>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Email *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" name="email" placeholder="tucorreo@ejemplo.com" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row-3 mb-3">
                    <div>
                        <label class="form-label">Usuario *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-user"></i>
                            <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Nombre de usuario" required>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Solo letras, números y guiones</small>
                            <small id="usuarioFeedback" style="display: none; font-weight: 500;"></small>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Contraseña *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Mín. 6 caracteres" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-strength-text" id="strengthText"></div>
                    </div>
                    <div>
                        <label class="form-label">Confirmar *</label>
                        <div class="input-group-custom">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Repite contraseña" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password_confirm', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </button>
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
        
        <div class="login-link">
            <p class="mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
        </div>
        
        <div class="back-link">
            <a href="index.php">
                <i class="fas fa-arrow-left me-1"></i>
                Volver al inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
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
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            event.currentTarget.classList.add('selected');
            document.getElementById('rol').value = role;
            
            if (role === 'editor') {
                document.getElementById('info-editor').style.display = 'block';
            } else {
                document.getElementById('info-editor').style.display = 'none';
            }
            
            document.getElementById('formFields').style.display = 'block';
        }
        
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
                    confirmButtonColor: '#27CCA0'
                });
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Contraseña corta',
                    text: 'La contraseña debe tener al menos 6 caracteres.',
                    confirmButtonColor: '#27CCA0'
                });
                return false;
            }
            
            if (password !== password_confirm) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No coinciden',
                    text: 'Las contraseñas no coinciden. Por favor, revísalas.',
                    confirmButtonColor: '#27CCA0'
                });
                return false;
            }
            
            const userValidationStatusMessage = document.getElementById('usuarioFeedback').innerText;
            if(userValidationStatusMessage === 'Usuario no disponible') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Usuario no disponible',
                    text: 'El nombre de usuario que elegiste ya está registrado.',
                    confirmButtonColor: '#27CCA0'
                });
                return false;
            }
        });
        
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
            
            feedbackUsuario.innerText = 'Comprobando...';
            feedbackUsuario.style.color = '#888';
            
            typingTimer = setTimeout(async function() {
                try {
                    const req = await fetch('ajax/validar_usuario.php?usuario=' + encodeURIComponent(userText));
                    const res = await req.json();
                    if(res.status === 'exists') {
                        feedbackUsuario.innerText = 'Usuario no disponible';
                        feedbackUsuario.style.color = '#dc3545';
                    } else if (res.status === 'available') {
                        feedbackUsuario.innerText = 'Usuario disponible';
                        feedbackUsuario.style.color = '#27CCA0';
                    }
                } catch(err) {
                    console.log('Error validando usuario AJAX', err);
                }
            }, 600); 
        });
        
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
                    passFeedback.style.color = '#27CCA0';
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

        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        inputPass.addEventListener('input', function() {
            const password = inputPass.value;
            let strength = 0;
            let text = '';
            let color = '';

            if (password.length === 0) {
                strengthBar.style.width = '0%';
                strengthText.innerText = '';
                return;
            }

            if (password.length >= 6) strength += 1;
            if (password.length >= 10) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^A-Za-z0-9]/.test(password)) strength += 1;

            switch(strength) {
                case 0:
                case 1:
                    text = 'Muy débil';
                    color = '#dc3545';
                    break;
                case 2:
                    text = 'Débil';
                    color = '#ffc107';
                    break;
                case 3:
                    text = 'Regular';
                    color = '#17a2b8';
                    break;
                case 4:
                    text = 'Buena';
                    color = '#27CCA0';
                    break;
                case 5:
                    text = 'Muy fuerte';
                    color = '#20c997';
                    break;
            }

            strengthBar.style.width = (strength * 20) + '%';
            strengthBar.style.background = color;
            strengthText.innerText = text;
            strengthText.style.color = color;
        });
    </script>
</body>
</html>