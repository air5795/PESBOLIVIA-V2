<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('usuarios.php');
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre = limpiar_texto($_POST['nombre']);
$apellido = limpiar_texto($_POST['apellido']);
$usuario = limpiar_texto($_POST['usuario']);
$email = limpiar_texto($_POST['email']);
$rol = limpiar_texto($_POST['rol']);
$estado = limpiar_texto($_POST['estado']);
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

$errores = [];

// Validaciones
if (empty($nombre)) $errores[] = "El nombre es requerido";
if (empty($apellido)) $errores[] = "El apellido es requerido";
if (empty($usuario)) $errores[] = "El usuario es requerido";
if (empty($email)) $errores[] = "El email es requerido";
if (!validar_email($email)) $errores[] = "Email no válido";
if (!in_array($rol, ['editor', 'comprador'])) $errores[] = "Rol no válido";
if (!in_array($estado, ['activo', 'inactivo'])) $errores[] = "Estado no válido";

// Validar usuario único
if ($id > 0) {
    if (usuario_existe($usuario, $id)) {
        $errores[] = "El usuario ya existe";
    }
    if (email_existe($email, $id)) {
        $errores[] = "El email ya está registrado";
    }
} else {
    if (usuario_existe($usuario)) {
        $errores[] = "El usuario ya existe";
    }
    if (email_existe($email)) {
        $errores[] = "El email ya está registrado";
    }
}

// Validar contraseña (solo si es nuevo o si se está cambiando)
if ($id == 0 || !empty($password)) {
    if (strlen($password) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres";
    }
    if ($password !== $password_confirm) {
        $errores[] = "Las contraseñas no coinciden";
    }
}

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect('usuarios.php');
}

// Preparar query
if ($id > 0) {
    // Editar
    if (!empty($password)) {
        $password_hash = hash_password($password);
        $query = "UPDATE usuarios SET nombre = ?, apellido = ?, usuario = ?, email = ?, rol = ?, estado = ?, password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sssssssi", $nombre, $apellido, $usuario, $email, $rol, $estado, $password_hash, $id);
    } else {
        $query = "UPDATE usuarios SET nombre = ?, apellido = ?, usuario = ?, email = ?, rol = ?, estado = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $nombre, $apellido, $usuario, $email, $rol, $estado, $id);
    }
    
    if (mysqli_stmt_execute($stmt)) {
        Session::registrar_actividad(Session::get_user_id(), 'actualizar', 'usuarios', $id, "Usuario actualizado: $usuario");
        $_SESSION['success'] = "Usuario actualizado correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar el usuario";
    }
    
} else {
    // Crear
    $password_hash = hash_password($password);
    
    $query = "INSERT INTO usuarios (nombre, apellido, usuario, email, rol, estado, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "sssssss", $nombre, $apellido, $usuario, $email, $rol, $estado, $password_hash);
    
    if (mysqli_stmt_execute($stmt)) {
        $nuevo_id = mysqli_insert_id($conexion);
        
        // Enviar email de bienvenida (DESHABILITADO TEMPORALMENTE)
        /*
        try {
            $emailSender = new EmailSender();
            $emailSender->email_bienvenida([
                'nombre' => $nombre,
                'usuario' => $usuario,
                'email' => $email,
                'rol' => $rol
            ]);
        } catch (Exception $e) {
            // Continuar aunque falle el email
        }
        */
        
        Session::registrar_actividad(Session::get_user_id(), 'crear', 'usuarios', $nuevo_id, "Usuario creado: $usuario");
        $_SESSION['success'] = "Usuario creado correctamente.";
    } else {
        $_SESSION['error'] = "Error al crear el usuario";
    }
}

mysqli_stmt_close($stmt);
redirect('usuarios.php');
?>