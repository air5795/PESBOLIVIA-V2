<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Verificar que el producto pertenece al editor
$query = "SELECT p.nombre, p.imagen FROM productos p
          INNER JOIN producto_editores pe ON p.id = pe.id_producto
          WHERE p.id = ? AND pe.id_editor = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $id_editor);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Producto no encontrado o no tienes permiso";
    mysqli_stmt_close($stmt);
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

// Verificar que no tenga ventas
$query_ventas = "SELECT COUNT(*) as total FROM compras WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query_ventas);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$ventas = mysqli_fetch_assoc($result)['total'];
mysqli_stmt_close($stmt);

if ($ventas > 0) {
    $_SESSION['error'] = "No se puede eliminar el producto porque tiene ventas registradas";
    redirect('productos.php');
}

// Eliminar asignaciones de editores
$query_delete_asig = "DELETE FROM producto_editores WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query_delete_asig);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Eliminar producto
$query_delete = "DELETE FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_delete);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    // Eliminar imagen si existe
    if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])) {
        unlink('../../' . $producto['imagen']);
    }
    
    Session::registrar_actividad($id_editor, 'eliminar', 'productos', $id, "Producto eliminado: " . $producto['nombre']);
    $_SESSION['success'] = "Producto eliminado correctamente";
} else {
    $_SESSION['error'] = "Error al eliminar el producto";
}

mysqli_stmt_close($stmt);
redirect('productos.php');
?>