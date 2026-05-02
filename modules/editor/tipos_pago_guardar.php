<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tipos_pago.php');
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$id_editor = Session::get_user_id(); // ID del editor logueado
$nombre = limpiar_texto($_POST['nombre']);
$tipo = limpiar_texto($_POST['tipo']);
$descripcion = limpiar_texto($_POST['descripcion'] ?? '');
$instrucciones = limpiar_texto($_POST['instrucciones'] ?? '');
$banco = limpiar_texto($_POST['banco'] ?? '');
$numero_cuenta = limpiar_texto($_POST['numero_cuenta'] ?? '');
$titular = limpiar_texto($_POST['titular'] ?? '');
$ci_titular = limpiar_texto($_POST['ci_titular'] ?? '');
$estado = limpiar_texto($_POST['estado']);

$errores = [];

// Validaciones
if (empty($nombre))
    $errores[] = "El nombre es requerido";
if (!in_array($tipo, ['qr', 'transferencia', 'otro']))
    $errores[] = "Tipo no válido";
if (!in_array($estado, ['activo', 'inactivo']))
    $errores[] = "Estado no válido";

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect($id > 0 ? "tipos_pago_editar.php?id=$id" : 'tipos_pago_crear.php');
}

// Procesar imagen QR
$qr_path = '';
$qr_anterior = '';

if ($id > 0) {
    // Obtener QR anterior si existe
    $query = "SELECT imagen_qr FROM tipos_pago WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $qr_anterior = $row['imagen_qr'];
    }
    mysqli_stmt_close($stmt);
}

// Si hay nueva imagen QR
if (isset($_FILES['imagen_qr']) && $_FILES['imagen_qr']['error'] === UPLOAD_ERR_OK) {
    $fileValidator = new FileValidator();
    $resultado = $fileValidator->guardar_archivo(
        $_FILES['imagen_qr'],
        QR_PATH,
        'qr_' . time()
    );

    if ($resultado['success']) {
        $qr_path = 'uploads/qr/' . $resultado['filename'];

        // Eliminar QR anterior si existe
        if (!empty($qr_anterior) && file_exists('../../' . $qr_anterior)) {
            unlink('../../' . $qr_anterior);
        }
    }
    else {
        $_SESSION['error'] = implode('<br>', $resultado['errors']);
        redirect($id > 0 ? "tipos_pago_editar.php?id=$id" : 'tipos_pago_crear.php');
    }
}
else {
    // Mantener QR anterior si está editando
    if ($id > 0) {
        $qr_path = $qr_anterior;
    }
}

if ($id > 0) {
    // Editar
    if (!empty($qr_path)) {
        $query = "UPDATE tipos_pago SET nombre = ?, tipo = ?, descripcion = ?, instrucciones = ?, 
                  imagen_qr = ?, numero_cuenta = ?, banco = ?, titular = ?, ci_titular = ?, estado = ? 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "ssssssssssi", $nombre, $tipo, $descripcion, $instrucciones,
            $qr_path, $numero_cuenta, $banco, $titular, $ci_titular, $estado, $id);
    }
    else {
        $query = "UPDATE tipos_pago SET nombre = ?, tipo = ?, descripcion = ?, instrucciones = ?, 
                  numero_cuenta = ?, banco = ?, titular = ?, ci_titular = ?, estado = ? 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "sssssssssi", $nombre, $tipo, $descripcion, $instrucciones,
            $numero_cuenta, $banco, $titular, $ci_titular, $estado, $id);
    }

    if (mysqli_stmt_execute($stmt)) {
        Session::registrar_actividad(Session::get_user_id(), 'actualizar', 'tipos_pago', $id, "Tipo de pago actualizado: $nombre");
        $_SESSION['success'] = "Tipo de pago actualizado correctamente";
    }
    else {
        $_SESSION['error'] = "Error al actualizar el tipo de pago";
    }


}
else {
    // Crear
    $query = "INSERT INTO tipos_pago (id_editor, nombre, tipo, descripcion, instrucciones, imagen_qr, 
              numero_cuenta, banco, titular, ci_titular, estado) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "issssssssss", $id_editor, $nombre, $tipo, $descripcion, $instrucciones,
        $qr_path, $numero_cuenta, $banco, $titular, $ci_titular, $estado);

    if (mysqli_stmt_execute($stmt)) {
        $nuevo_id = mysqli_insert_id($conexion);
        Session::registrar_actividad($id_editor, 'crear', 'tipos_pago', $nuevo_id, "Método de pago creado: $nombre");
        $_SESSION['success'] = "Método de pago creado correctamente";
    }
    else {
        $_SESSION['error'] = "Error al crear el método de pago";
    }
}

mysqli_stmt_close($stmt);
redirect('tipos_pago.php');
?>