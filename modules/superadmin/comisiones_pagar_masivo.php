<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['comisiones'])) {
    redirect('comisiones.php');
}

$comisiones = $_POST['comisiones'];

if (empty($comisiones) || !is_array($comisiones)) {
    $_SESSION['error'] = "No se seleccionaron comisiones";
    redirect('comisiones.php');
}

$total_pagadas = 0;
$monto_total = 0;

foreach ($comisiones as $id) {
    $id = intval($id);
    
    // Obtener info de la comisión
    $query = "SELECT monto FROM pagos_editores WHERE id = ? AND estado = 'pendiente'";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($com = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        
        // Actualizar a pagado
        $query_update = "UPDATE pagos_editores SET estado = 'pagado' WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query_update);
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $total_pagadas++;
            $monto_total += $com['monto'];
        }
        
        mysqli_stmt_close($stmt);
    } else {
        mysqli_stmt_close($stmt);
    }
}

if ($total_pagadas > 0) {
    Session::registrar_actividad(Session::get_user_id(), 'pagar_masivo', 'pagos_editores', 0, 
        "Pago masivo: $total_pagadas comisiones por " . formatear_monto($monto_total));
    
    $_SESSION['success'] = "Se marcaron $total_pagadas comisiones como pagadas por un total de " . formatear_monto($monto_total);
} else {
    $_SESSION['error'] = "No se pudo pagar ninguna comisión";
}

redirect('comisiones.php');
?>