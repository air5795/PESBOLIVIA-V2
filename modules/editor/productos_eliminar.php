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

// Verificar que el producto pertenece al editor y que es dueño al 100% (no es un producto compartido o del admin)
$query = "SELECT p.nombre, p.imagen, pe.porcentaje FROM productos p
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

if (floatval($producto['porcentaje']) < 100.00) {
    $_SESSION['error'] = "No tienes permiso para eliminar este producto porque es de autoría compartida o del administrador";
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
    // Eliminar imagen principal si existe
    if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])) {
        unlink('../../' . $producto['imagen']);
    }
    
    // Eliminar todas las imágenes de producto_imagenes (archivos físicos)
    $query_imgs = "SELECT imagen FROM producto_imagenes WHERE id_producto = ?";
    $stmt_imgs = mysqli_prepare($conexion, $query_imgs);
    mysqli_stmt_bind_param($stmt_imgs, "i", $id);
    mysqli_stmt_execute($stmt_imgs);
    $result_imgs = mysqli_stmt_get_result($stmt_imgs);
    while ($img_row = mysqli_fetch_assoc($result_imgs)) {
        if (!empty($img_row['imagen']) && $img_row['imagen'] !== $producto['imagen'] && file_exists('../../' . $img_row['imagen'])) {
            unlink('../../' . $img_row['imagen']);
        }
    }
    mysqli_stmt_close($stmt_imgs);
    // Los registros de producto_imagenes se eliminan por CASCADE
    
    Session::registrar_actividad($id_editor, 'eliminar', 'productos', $id, "Producto eliminado: " . $producto['nombre']);
    $_SESSION['success'] = "Producto eliminado correctamente";
} else {
    $_SESSION['error'] = "Error al eliminar el producto";
}

mysqli_stmt_close($stmt);
redirect('productos.php');
?>