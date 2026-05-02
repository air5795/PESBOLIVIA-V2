<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id = intval($_GET['id']);

// Verificar que no tenga ventas
$query = "SELECT COUNT(*) as total FROM compras WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ventas = mysqli_fetch_assoc($result);

if ($ventas['total'] > 0) {
    $_SESSION['error'] = "No se puede eliminar el producto porque tiene ventas asociadas";
    mysqli_stmt_close($stmt);
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

// Obtener producto
$query = "SELECT nombre, imagen FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($producto = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    
    // Eliminar editores asignados
    $query_editores = "DELETE FROM producto_editores WHERE id_producto = ?";
    $stmt_editores = mysqli_prepare($conexion, $query_editores);
    mysqli_stmt_bind_param($stmt_editores, "i", $id);
    mysqli_stmt_execute($stmt_editores);
    mysqli_stmt_close($stmt_editores);
    
    // Eliminar producto
    $query_delete = "DELETE FROM productos WHERE id = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        // Eliminar imagen si existe
        if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])) {
            unlink('../../' . $producto['imagen']);
        }
        
        Session::registrar_actividad(Session::get_user_id(), 'eliminar', 'productos', $id, "Producto eliminado: " . $producto['nombre']);
        $_SESSION['success'] = "Producto eliminado correctamente";
    } else {
        $_SESSION['error'] = "Error al eliminar el producto";
    }
    
    mysqli_stmt_close($stmt_delete);
} else {
    mysqli_stmt_close($stmt);
    $_SESSION['error'] = "Producto no encontrado";
}

redirect('productos.php');
?>