<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('comisiones.php');
}

$id = intval($_GET['id']);

// Obtener comisión
$query = "SELECT pag.*, e.nombre as editor_nombre, e.email as editor_email, c.codigo_compra
          FROM pagos_editores pag
          INNER JOIN usuarios e ON pag.id_editor = e.id
          INNER JOIN compras c ON pag.id_compra = c.id
          WHERE pag.id = ? AND pag.estado = 'pendiente'";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$comision = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Comisión no encontrada o ya pagada";
    mysqli_stmt_close($stmt);
    redirect('comisiones.php');
}

mysqli_stmt_close($stmt);

// Actualizar a pagado
$query_update = "UPDATE pagos_editores SET estado = 'pagado' WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_update);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    Session::registrar_actividad(Session::get_user_id(), 'pagar', 'pagos_editores', $id, 
        "Comisión pagada: " . formatear_monto($comision['monto']) . " a " . $comision['editor_nombre']);
    
    // Enviar email al editor (deshabilitado temporalmente)
    /*
    try {
        $emailSender = new EmailSender();
        // Email de comisión pagada
    } catch (Exception $e) {
        // Continuar
    }
    */
    
    $_SESSION['success'] = "Comisión marcada como pagada correctamente";
} else {
    $_SESSION['error'] = "Error al marcar la comisión como pagada";
}

mysqli_stmt_close($stmt);
redirect('comisiones.php');
?>