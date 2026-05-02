<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$accion = $_REQUEST['accion'] ?? '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if (!in_array($accion, ['aprobar', 'rechazar']) || $id <= 0) {
    redirect('solicitudes_editor.php');
}

// Obtener solicitud
$query = "SELECT se.*, u.nombre, u.apellido, u.email, u.id as usuario_id
          FROM solicitudes_editor se
          INNER JOIN usuarios u ON se.id_usuario = u.id
          WHERE se.id = ? AND se.estado = 'pendiente'";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$solicitud = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Solicitud no encontrada o ya procesada";
    mysqli_stmt_close($stmt);
    redirect('solicitudes_editor.php');
}

mysqli_stmt_close($stmt);

$id_aprobador = Session::get_user_id();
$observaciones = limpiar_texto($_POST['observaciones'] ?? '');

if ($accion === 'aprobar') {
    
    // Actualizar solicitud a aprobado
    $query_update = "UPDATE solicitudes_editor 
                     SET estado = 'aprobado', 
                         fecha_respuesta = NOW(), 
                         id_aprobador = ?,
                         observaciones = ?
                     WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt, "isi", $id_aprobador, $observaciones, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Activar usuario y marcar como editor aprobado
    $query_usuario = "UPDATE usuarios 
                      SET estado = 'activo', 
                          editor_aprobado = 'aprobado' 
                      WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_usuario);
    mysqli_stmt_bind_param($stmt, "i", $solicitud['usuario_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Enviar email de aprobación (deshabilitado temporalmente)
    /*
    try {
        $emailSender = new EmailSender();
        // Aquí irá el método para email de aprobación de editor
    } catch (Exception $e) {
        // Continuar aunque falle el email
    }
    */
    
    Session::registrar_actividad($id_aprobador, 'aprobar', 'solicitudes_editor', $id, 
        "Solicitud de editor aprobada: " . $solicitud['nombre'] . ' ' . $solicitud['apellido']);
    
    $_SESSION['success'] = "Solicitud aprobada correctamente. El editor ya puede iniciar sesión y crear productos.";
    
} elseif ($accion === 'rechazar') {
    
    // Actualizar solicitud a rechazado
    $query_update = "UPDATE solicitudes_editor 
                     SET estado = 'rechazado', 
                         fecha_respuesta = NOW(), 
                         id_aprobador = ?,
                         observaciones = ?
                     WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt, "isi", $id_aprobador, $observaciones, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Marcar usuario como rechazado
    $query_usuario = "UPDATE usuarios 
                      SET editor_aprobado = 'rechazado' 
                      WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_usuario);
    mysqli_stmt_bind_param($stmt, "i", $solicitud['usuario_id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Enviar email de rechazo (deshabilitado temporalmente)
    /*
    try {
        $emailSender = new EmailSender();
        // Aquí irá el método para email de rechazo
    } catch (Exception $e) {
        // Continuar aunque falle el email
    }
    */
    
    Session::registrar_actividad($id_aprobador, 'rechazar', 'solicitudes_editor', $id, 
        "Solicitud de editor rechazada: " . $solicitud['nombre'] . ' ' . $solicitud['apellido']);
    
    $_SESSION['success'] = "Solicitud rechazada correctamente.";
}

redirect('solicitudes_editor.php');
?>