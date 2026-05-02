<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('categorias.php');
}

$id = intval($_GET['id']);

// Verificar que no tenga productos
$query = "SELECT COUNT(*) as total FROM productos WHERE id_categoria = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$productos = mysqli_fetch_assoc($result);

if ($productos['total'] > 0) {
    $_SESSION['error'] = "No se puede eliminar la categoría porque tiene productos asignados";
    mysqli_stmt_close($stmt);
    redirect('categorias.php');
}

mysqli_stmt_close($stmt);

// Obtener nombre para el log
$query = "SELECT nombre FROM categorias WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($categoria = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    
    // Eliminar categoría
    $query_delete = "DELETE FROM categorias WHERE id = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        Session::registrar_actividad(Session::get_user_id(), 'eliminar', 'categorias', $id, "Categoría eliminada: " . $categoria['nombre']);
        $_SESSION['success'] = "Categoría eliminada correctamente";
    } else {
        $_SESSION['error'] = "Error al eliminar la categoría";
    }
    
    mysqli_stmt_close($stmt_delete);
} else {
    mysqli_stmt_close($stmt);
    $_SESSION['error'] = "Categoría no encontrada";
}

redirect('categorias.php');
?>