<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    redirect('compras.php');
}

$id_compra = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Verificar que la compra es de un producto del editor y obtener datos para el email
$query_check = "SELECT c.*, pe.id_editor, p.nombre as producto_nombre, p.precio, p.drive_link,
                u.nombre as comprador_nombre, u.email as comprador_email
                FROM compras c
                INNER JOIN productos p ON c.id_producto = p.id
                INNER JOIN producto_editores pe ON p.id = pe.id_producto
                INNER JOIN usuarios u ON c.id_comprador = u.id
                WHERE c.id = ? AND pe.id_editor = ? AND c.estado = 'pendiente' AND pe.porcentaje >= 100";
$stmt = mysqli_prepare($conexion, $query_check);
mysqli_stmt_bind_param($stmt, "ii", $id_compra, $id_editor);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$compra = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "No tienes permiso para aprobar esta compra";
    mysqli_stmt_close($stmt);
    redirect('compras.php');
}

mysqli_stmt_close($stmt);

// Aprobar compra
$query_update = "UPDATE compras SET estado = 'aprobado', fecha_aprobacion = NOW() WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_update);
mysqli_stmt_bind_param($stmt, "i", $id_compra);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);

    // Calcular comisiones directamente
    // Obtener editores asignados al producto con sus porcentajes
    $query_editores = "SELECT pe.id_editor, pe.porcentaje, p.precio
                       FROM producto_editores pe
                       INNER JOIN productos p ON pe.id_producto = p.id
                       INNER JOIN compras c ON c.id_producto = p.id
                       WHERE c.id = ?";
    $stmt_editores = mysqli_prepare($conexion, $query_editores);
    mysqli_stmt_bind_param($stmt_editores, "i", $id_compra);
    mysqli_stmt_execute($stmt_editores);
    $editores = mysqli_stmt_get_result($stmt_editores);

    // Calcular comisión para cada editor
    while ($editor = mysqli_fetch_assoc($editores)) {
        $monto_comision = ($editor['precio'] * $editor['porcentaje']) / 100;

        // Insertar comisión
        $query_comision = "INSERT INTO pagos_editores (id_compra, id_editor, monto, porcentaje, estado) 
                          VALUES (?, ?, ?, ?, 'pendiente')";
        $stmt_comision = mysqli_prepare($conexion, $query_comision);
        mysqli_stmt_bind_param($stmt_comision, "iidd", $id_compra, $editor['id_editor'], $monto_comision, $editor['porcentaje']);
        mysqli_stmt_execute($stmt_comision);
        mysqli_stmt_close($stmt_comision);
    }

    mysqli_stmt_close($stmt_editores);

    // --- AUTOMATIZACIÓN GOOGLE DRIVE ---
    if (!empty($compra['drive_link'])) {
        try {
            require_once '../../utils/GoogleDriveManager.php';
            $driveManager = new GoogleDriveManager();
            $driveManager->darAcceso($compra['drive_link'], $compra['comprador_email']);
        } catch (Exception $e) {
            error_log("Error en automatización de Drive (Editor): " . $e->getMessage());
        }
    }

    // Enviar email de aprobación al comprador
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
        error_log("Error enviando email de aprobación (Editor): " . $e->getMessage());
    }

    Session::registrar_actividad($id_editor, 'aprobar', 'compras', $id_compra, "Compra aprobada: {$compra['codigo_compra']}");
    $_SESSION['success'] = "Compra aprobada correctamente. Comisiones calculadas y cliente notificado.";
}
else {
    $_SESSION['error'] = "Error al aprobar la compra";
}

redirect('compras.php');
?>