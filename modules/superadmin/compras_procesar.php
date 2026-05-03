<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$accion = $_REQUEST['accion'] ?? '';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

if (!in_array($accion, ['aprobar', 'rechazar']) || $id <= 0) {
    redirect('compras.php');
}

// Obtener compra
$query = "SELECT c.*, p.nombre as producto_nombre, p.precio, p.drive_link,
          u.nombre as comprador_nombre, u.apellido as comprador_apellido, u.email as comprador_email
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios u ON c.id_comprador = u.id
          WHERE c.id = ? AND c.estado = 'pendiente'";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$compra = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Compra no encontrada o ya procesada";
    mysqli_stmt_close($stmt);
    redirect('compras.php');
}

mysqli_stmt_close($stmt);

$id_aprobador = Session::get_user_id();
$observaciones = limpiar_texto($_POST['observaciones'] ?? '');

if ($accion === 'aprobar') {
    
    // Actualizar compra a aprobado
    $query_update = "UPDATE compras 
                     SET estado = 'aprobado', 
                         id_aprobador = ?,
                         observaciones = ?
                     WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt, "isi", $id_aprobador, $observaciones, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Calcular y registrar comisiones de editores
    $query_editores = "SELECT id_editor, porcentaje 
                       FROM producto_editores 
                       WHERE id_producto = ?";
    $stmt_editores = mysqli_prepare($conexion, $query_editores);
    mysqli_stmt_bind_param($stmt_editores, "i", $compra['id_producto']);
    mysqli_stmt_execute($stmt_editores);
    $result_editores = mysqli_stmt_get_result($stmt_editores);
    
    while ($editor = mysqli_fetch_assoc($result_editores)) {
        $monto_comision = ($compra['monto_total'] * $editor['porcentaje']) / 100;
        
        $query_pago = "INSERT INTO pagos_editores (id_compra, id_editor, monto, porcentaje, estado) 
                       VALUES (?, ?, ?, ?, 'pendiente')";
        $stmt_pago = mysqli_prepare($conexion, $query_pago);
        mysqli_stmt_bind_param($stmt_pago, "iidd", $id, $editor['id_editor'], $monto_comision, $editor['porcentaje']);
        mysqli_stmt_execute($stmt_pago);
        mysqli_stmt_close($stmt_pago);
    }
    
    mysqli_stmt_close($stmt_editores);
    
    // Registrar acceso al Drive (opcional - para futuro)
    /*
    $query_acceso = "INSERT INTO accesos_drive (id_compra, id_usuario, email_acceso) 
                     VALUES (?, ?, ?)";
    $stmt_acceso = mysqli_prepare($conexion, $query_acceso);
    mysqli_stmt_bind_param($stmt_acceso, "iis", $id, $compra['id_comprador'], $compra['comprador_email']);
    mysqli_stmt_execute($stmt_acceso);
    mysqli_stmt_close($stmt_acceso);
    */
    
    // --- AUTOMATIZACIÓN GOOGLE DRIVE (Solo si el aprobador es el Superadmin ID 1) ---
    $is_superadmin_approver = ($id_aprobador === 1);
    $alerta_drive = '';

    if ($is_superadmin_approver && !empty($compra['drive_link'])) {
        $log_msg = "[" . date('Y-m-d H:i:s') . "] Aprobado por Superadmin. Intentando dar acceso a " . $compra['comprador_email'] . " para la carpeta " . $compra['drive_link'] . "\n";
        file_put_contents(BASE_PATH . '/logs/automation.log', $log_msg, FILE_APPEND);
        
        try {
            require_once BASE_PATH . '/utils/GoogleDriveManager.php';
            $driveManager = new GoogleDriveManager();
            $resultado_drive = $driveManager->darAcceso($compra['drive_link'], $compra['comprador_email']);
            
            if (!$resultado_drive) {
                $alerta_drive = " <br><strong>⚠️ ADVERTENCIA:</strong> Falló la automatización de Google Drive. Debes dar acceso a <i>{$compra['comprador_email']}</i> manualmente.";
                // Registrar la observación en la BD
                $obs_adicional = $observaciones . " | DRIVE FAILED: Acceso manual requerido.";
                $q_obs = "UPDATE compras SET observaciones = ? WHERE id = ?";
                $stmt_obs = mysqli_prepare($conexion, $q_obs);
                mysqli_stmt_bind_param($stmt_obs, "si", $obs_adicional, $id);
                mysqli_stmt_execute($stmt_obs);
                mysqli_stmt_close($stmt_obs);
            }

            $log_res = "[" . date('Y-m-d H:i:s') . "] Resultado de darAcceso: " . ($resultado_drive ? "EXITO" : "FALLO") . "\n";
            file_put_contents(BASE_PATH . '/logs/automation.log', $log_res, FILE_APPEND);
        } catch (Exception $e) {
            $log_err = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
            file_put_contents(BASE_PATH . '/logs/automation.log', $log_err, FILE_APPEND);
            $alerta_drive = " <br><strong>⚠️ ERROR CRÍTICO:</strong> La API de Drive falló. Da el acceso manualmente.";
        }
    } else {
        $log_skip = "[" . date('Y-m-d H:i:s') . "] Saltada. Aprobador Admin: " . ($is_superadmin_approver ? 'SI' : 'NO') . " - Link: " . ($compra['drive_link'] ? 'SI' : 'NO') . "\n";
        file_put_contents(BASE_PATH . '/logs/automation.log', $log_skip, FILE_APPEND);
    }

    // Enviar email de aprobación
    try {
        $emailSender = new EmailSender();
        $emailSender->email_compra_aprobada([
            'comprador_nombre' => $compra['comprador_nombre'],
            'comprador_email' => $compra['comprador_email'],
            'codigo_compra' => $compra['codigo_compra'],
            'producto_nombre' => $compra['producto_nombre'],
            'monto_total' => $compra['monto_total'],
            'drive_link' => $compra['drive_link']
        ]);
    } catch (Exception $e) {
        error_log("Error enviando email de aprobación: " . $e->getMessage());
    }
    
    Session::registrar_actividad($id_aprobador, 'aprobar', 'compras', $id, 
        "Compra aprobada: " . $compra['codigo_compra']);
    
    $_SESSION['success'] = "Compra aprobada correctamente. Las comisiones han sido calculadas." . $alerta_drive;
    
} elseif ($accion === 'rechazar') {
    
    // Actualizar compra a rechazado
    $query_update = "UPDATE compras 
                     SET estado = 'rechazado', 
                         id_aprobador = ?,
                         observaciones = ?
                     WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt, "isi", $id_aprobador, $observaciones, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Enviar email de rechazo
    try {
        $emailSender = new EmailSender();
        $emailSender->email_compra_rechazada([
            'comprador_nombre' => $compra['comprador_nombre'],
            'comprador_email' => $compra['comprador_email'],
            'codigo_compra' => $compra['codigo_compra'],
            'observaciones' => $observaciones
        ]);
    } catch (Exception $e) {
        error_log("Error enviando email de rechazo: " . $e->getMessage());
    }
    
    Session::registrar_actividad($id_aprobador, 'rechazar', 'compras', $id, 
        "Compra rechazada: " . $compra['codigo_compra']);
    
    $_SESSION['success'] = "Compra rechazada correctamente.";
}

redirect('compras.php');
?>