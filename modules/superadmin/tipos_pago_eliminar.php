<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('tipos_pago.php');
}

$id = intval($_GET['id']);

// Obtener tipo de pago
$query = "SELECT nombre, imagen_qr FROM tipos_pago WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($tipo = mysqli_fetch_assoc($result)) {
    mysqli_stmt_close($stmt);
    
    // Eliminar tipo de pago
    $query_delete = "DELETE FROM tipos_pago WHERE id = ?";
    $stmt_delete = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $id);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        // Eliminar imagen QR si existe
        if (!empty($tipo['imagen_qr']) && file_exists('../../' . $tipo['imagen_qr'])) {
            unlink('../../' . $tipo['imagen_qr']);
        }
        
        Session::registrar_actividad(Session::get_user_id(), 'eliminar', 'tipos_pago', $id, "Tipo de pago eliminado: " . $tipo['nombre']);
        $_SESSION['success'] = "Tipo de pago eliminado correctamente";
    } else {
        $_SESSION['error'] = "Error al eliminar el tipo de pago";
    }
    
    mysqli_stmt_close($stmt_delete);
} else {
    mysqli_stmt_close($stmt);
    $_SESSION['error'] = "Tipo de pago no encontrado";
}

redirect('tipos_pago.php');
?>