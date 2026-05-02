<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('productos.php');
}

$id = intval($_POST['id']);
$id_producto = intval($_POST['id_producto']);
$porcentaje = floatval($_POST['porcentaje']);

$errores = [];

// Validaciones
if ($id <= 0) $errores[] = "Asignación no válida";
if ($porcentaje <= 0 || $porcentaje > 100) $errores[] = "El porcentaje debe estar entre 0.01 y 100";

// Obtener porcentaje actual
$query = "SELECT porcentaje FROM producto_editores WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$porcentaje_actual = mysqli_fetch_assoc($result)['porcentaje'] ?? 0;
mysqli_stmt_close($stmt);

// Verificar que no supere el 100%
$query = "SELECT SUM(porcentaje) as total FROM producto_editores WHERE id_producto = ? AND id != ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_producto, $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_otros = mysqli_fetch_assoc($result)['total'] ?? 0;
mysqli_stmt_close($stmt);

if (($total_otros + $porcentaje) > 100) {
    $disponible = 100 - $total_otros;
    $errores[] = "El porcentaje supera el 100%. Máximo permitido: " . number_format($disponible, 2) . "%";
}

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect("productos_editores.php?id=$id_producto");
}

// Actualizar porcentaje
$query = "UPDATE producto_editores SET porcentaje = ? WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "di", $porcentaje, $id);

if (mysqli_stmt_execute($stmt)) {
    Session::registrar_actividad(Session::get_user_id(), 'actualizar', 'producto_editores', $id, 
        "Porcentaje actualizado de $porcentaje_actual% a $porcentaje%");
    $_SESSION['success'] = "Porcentaje actualizado correctamente";
} else {
    $_SESSION['error'] = "Error al actualizar el porcentaje";
}

mysqli_stmt_close($stmt);
redirect("productos_editores.php?id=$id_producto");
?>