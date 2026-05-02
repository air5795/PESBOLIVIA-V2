<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('categorias.php');
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre = limpiar_texto($_POST['nombre']);
$descripcion = limpiar_texto($_POST['descripcion'] ?? '');
$icono = limpiar_texto($_POST['icono'] ?? '');
$estado = limpiar_texto($_POST['estado']);

$errores = [];

// Validaciones
if (empty($nombre)) $errores[] = "El nombre es requerido";
if (!in_array($estado, ['activo', 'inactivo'])) $errores[] = "Estado no válido";

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect('categorias.php');
}

if ($id > 0) {
    // Editar
    $query = "UPDATE categorias SET nombre = ?, descripcion = ?, icono = ?, estado = ? WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $descripcion, $icono, $estado, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        Session::registrar_actividad(Session::get_user_id(), 'actualizar', 'categorias', $id, "Categoría actualizada: $nombre");
        $_SESSION['success'] = "Categoría actualizada correctamente";
    } else {
        $_SESSION['error'] = "Error al actualizar la categoría";
    }
} else {
    // Crear
    $query = "INSERT INTO categorias (nombre, descripcion, icono, estado) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $nombre, $descripcion, $icono, $estado);
    
    if (mysqli_stmt_execute($stmt)) {
        $nuevo_id = mysqli_insert_id($conexion);
        Session::registrar_actividad(Session::get_user_id(), 'crear', 'categorias', $nuevo_id, "Categoría creada: $nombre");
        $_SESSION['success'] = "Categoría creada correctamente";
    } else {
        $_SESSION['error'] = "Error al crear la categoría";
    }
}

mysqli_stmt_close($stmt);
redirect('categorias.php');
?>