<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id']) || !isset($_GET['producto'])) {
    redirect('productos.php');
}

$id = intval($_GET['id']);
$id_producto = intval($_GET['producto']);

// Eliminar asignación
$query = "DELETE FROM producto_editores WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    Session::registrar_actividad(Session::get_user_id(), 'eliminar', 'producto_editores', $id, 
        "Editor removido del producto");
    $_SESSION['success'] = "Editor removido correctamente";
} else {
    $_SESSION['error'] = "Error al remover el editor";
}

mysqli_stmt_close($stmt);
redirect("productos_editores.php?id=$id_producto");
?>