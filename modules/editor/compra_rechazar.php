<?php
require_once '../../config/config.php';
require_once '../../config/session.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    redirect('compras.php');
}

$id_compra = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Verificar que la compra es de un producto del editor
$query_check = "SELECT c.* 
                FROM compras c
                INNER JOIN productos p ON c.id_producto = p.id
                INNER JOIN producto_editores pe ON p.id = pe.id_producto
                WHERE c.id = ? AND pe.id_editor = ? AND c.estado = 'pendiente'";
$stmt = mysqli_prepare($conexion, $query_check);
mysqli_stmt_bind_param($stmt, "ii", $id_compra, $id_editor);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_get_result($stmt)->num_rows === 0) {
    $_SESSION['error'] = "No tienes permiso para rechazar esta compra";
    redirect('compras.php');
}

mysqli_stmt_close($stmt);

// Rechazar compra
$query_update = "UPDATE compras SET estado = 'rechazado' WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_update);
mysqli_stmt_bind_param($stmt, "i", $id_compra);

if (mysqli_stmt_execute($stmt)) {
    Session::registrar_actividad($id_editor, 'rechazar', 'compras', $id_compra, "Compra rechazada");
    $_SESSION['success'] = "Compra rechazada";
}
else {
    $_SESSION['error'] = "Error al rechazar";
}

mysqli_stmt_close($stmt);
redirect('compras.php');
?>