<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('productos.php');
}

$id_producto = intval($_POST['id_producto']);
$id_editor = intval($_POST['id_editor']);
$porcentaje = floatval($_POST['porcentaje']);

$errores = [];

// Validaciones
if ($id_producto <= 0) $errores[] = "Producto no válido";
if ($id_editor <= 0) $errores[] = "Editor no válido";
if ($porcentaje <= 0 || $porcentaje > 100) $errores[] = "El porcentaje debe estar entre 0.01 y 100";

// Verificar que no supere el 100%
$query = "SELECT SUM(porcentaje) as total FROM producto_editores WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_actual = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

if (($total_actual + $porcentaje) > 100) {
    $disponible = 100 - $total_actual;
    $errores[] = "El porcentaje supera el 100%. Disponible: " . number_format($disponible, 2) . "%";
}

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect("productos_editores.php?id=$id_producto");
}

// Insertar asignación
$query = "INSERT INTO producto_editores (id_producto, id_editor, porcentaje) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "iid", $id_producto, $id_editor, $porcentaje);

if (mysqli_stmt_execute($stmt)) {
    Session::registrar_actividad(Session::get_user_id(), 'crear', 'producto_editores', mysqli_insert_id($conexion), 
        "Editor asignado a producto con $porcentaje% de comisión");
    $_SESSION['success'] = "Editor asignado correctamente";
} else {
    $_SESSION['error'] = "Error al asignar el editor";
}

mysqli_stmt_close($stmt);
redirect("productos_editores.php?id=$id_producto");
?>