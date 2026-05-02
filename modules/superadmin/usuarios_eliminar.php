<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('usuarios.php');
}

$id = intval($_GET['id']);

// No permitir eliminar al usuario logueado
if ($id == Session::get_user_id()) {
    $_SESSION['error'] = "No puedes eliminarte a ti mismo";
    redirect('usuarios.php');
}

// Verificar que el usuario existe
$query = "SELECT usuario FROM usuarios WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($usuario_data = mysqli_fetch_assoc($result)) {
    
    // Verificar si tiene productos asignados (si es editor)
    $query_productos = "SELECT COUNT(*) as total FROM producto_editores WHERE id_editor = ?";
    $stmt_productos = mysqli_prepare($conexion, $query_productos);
    mysqli_stmt_bind_param($stmt_productos, "i", $id);
    mysqli_stmt_execute($stmt_productos);
    $result_productos = mysqli_stmt_get_result($stmt_productos);
    $productos = mysqli_fetch_assoc($result_productos);
    
    if ($productos['total'] > 0) {
        $_SESSION['error'] = "No se puede eliminar el usuario porque tiene productos asignados. Primero elimina las asignaciones.";
        mysqli_stmt_close($stmt_productos);
        mysqli_stmt_close($stmt);
        redirect('usuarios.php');
    }
    
    mysqli_stmt_close($stmt_productos);
    
    // Eliminar usuario
    $query_delete = "DELETE FROM usuarios WHERE id = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        Session::registrar_actividad(Session::get_user_id(), 'eliminar', 'usuarios', $id, "Usuario eliminado: " . $usuario_data['usuario']);
        $_SESSION['success'] = "Usuario eliminado correctamente";
    } else {
        $_SESSION['error'] = "Error al eliminar el usuario";
    }
    
    mysqli_stmt_close($stmt_delete);
    
} else {
    $_SESSION['error'] = "Usuario no encontrado";
}

mysqli_stmt_close($stmt);
redirect('usuarios.php');
?>