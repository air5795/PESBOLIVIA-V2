<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

// Verificar que esté logueado
if (!Session::is_logged_in()) {
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tienda.php');
}

$id_producto = intval($_POST['id_producto']);
$id_tipo_pago = intval($_POST['id_tipo_pago']);
$id_comprador = Session::get_user_id();

$errores = [];

// Validaciones
if ($id_producto <= 0)
    $errores[] = "Producto no válido";
if ($id_tipo_pago <= 0)
    $errores[] = "Método de pago no válido";

// Verificar que no haya comprado ya
$query = "SELECT COUNT(*) as comprado FROM compras 
          WHERE id_comprador = ? AND id_producto = ? 
          AND estado IN ('pendiente', 'aprobado', 'entregado')";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_comprador, $id_producto);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (mysqli_fetch_assoc($result)['comprado'] > 0) {
    $errores[] = "Ya has comprado este producto o tienes una compra pendiente";
}
mysqli_stmt_close($stmt);

// Obtener precio del producto
$query = "SELECT precio FROM productos WHERE id = ? AND estado = 'activo'";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
if (!$producto = mysqli_fetch_assoc($result)) {
    $errores[] = "Producto no encontrado";
}
mysqli_stmt_close($stmt);

// Validar comprobante
if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] === UPLOAD_ERR_NO_FILE) {
    $errores[] = "Debes subir el comprobante de pago";
}

if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    redirect("producto.php?id=$id_producto");
}

// Guardar comprobante
$fileValidator = new FileValidator();
$resultado = $fileValidator->guardar_archivo(
    $_FILES['comprobante'],
    COMPROBANTES_PATH,
    'compra_' . $id_comprador . '_' . time()
);

if (!$resultado['success']) {
    $_SESSION['error'] = implode('<br>', $resultado['errors']);
    redirect("producto.php?id=$id_producto");
}

$comprobante_path = 'uploads/comprobantes/' . $resultado['filename'];
$monto_total = $producto['precio'];
$codigo_compra = generar_codigo_compra();

// Crear compra
$query = "INSERT INTO compras (codigo_compra, id_comprador, id_producto, id_tipo_pago, monto_total, comprobante, estado) 
          VALUES (?, ?, ?, ?, ?, ?, 'pendiente')";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "siiids", $codigo_compra, $id_comprador, $id_producto, $id_tipo_pago, $monto_total, $comprobante_path);

if (mysqli_stmt_execute($stmt)) {
    $id_compra = mysqli_insert_id($conexion);

    Session::registrar_actividad($id_comprador, 'crear', 'compras', $id_compra, "Compra creada: $codigo_compra");

    // --- LÓGICA DE NOTIFICACIONES POR EMAIL ---
    try {
        $emailSender = new EmailSender();
        
        // 1. Obtener datos extra para el email (Comprador, Producto, Editor, Tipo Pago)
        $query_info = "SELECT p.nombre as producto_nombre, u.nombre as comprador_nombre, u.email as comprador_email,
                       tp.nombre as tipo_pago_nombre, e.email as editor_email
                       FROM productos p
                       JOIN usuarios u ON u.id = ?
                       JOIN tipos_pago tp ON tp.id = ?
                       LEFT JOIN producto_editores pe ON pe.id_producto = p.id
                       LEFT JOIN usuarios e ON e.id = pe.id_editor
                       WHERE p.id = ?";
        $stmt_info = mysqli_prepare($conexion, $query_info);
        mysqli_stmt_bind_param($stmt_info, "iii", $id_comprador, $id_tipo_pago, $id_producto);
        mysqli_stmt_execute($stmt_info);
        $res_info = mysqli_stmt_get_result($stmt_info);
        $info = mysqli_fetch_assoc($res_info);
        mysqli_stmt_close($stmt_info);

        $compra_data = [
            'id_compra' => $id_compra,
            'codigo_compra' => $codigo_compra,
            'comprador_nombre' => $info['comprador_nombre'],
            'comprador_email' => $info['comprador_email'],
            'producto_nombre' => $info['producto_nombre'],
            'monto_total' => $monto_total,
            'fecha_compra' => date('d/m/Y H:i'),
            'tipo_pago' => $info['tipo_pago_nombre'],
            'rol_destino' => 'superadmin' 
        ];

        // 2. Enviar email al Comprador
        $emailSender->email_confirmacion_compra($compra_data);

        // 3. Enviar email al Admin (ale.empresa2@gmail.com)
        $emailSender->email_nueva_compra_admin($compra_data, 'ale.empresa2@gmail.com');

        // 4. Enviar email al Editor (si existe y no es el mismo admin)
        if (!empty($info['editor_email']) && $info['editor_email'] !== 'ale.empresa2@gmail.com') {
            $compra_data['rol_destino'] = 'editor';
            $emailSender->email_nueva_compra_admin($compra_data, $info['editor_email']);
        }

    } catch (Exception $e) {
        error_log("Error en notificaciones de compra: " . $e->getMessage());
    }

    $_SESSION['success'] = "¡Compra registrada exitosamente! Código: <strong>$codigo_compra</strong><br>Tu compra está pendiente de aprobación. Recibirás un email cuando sea aprobada.";
    redirect('modules/comprador/mis_compras.php');
}
else {
    $_SESSION['error'] = "Error al procesar la compra. Intenta nuevamente.";
    redirect("producto.php?id=$id_producto");
}

mysqli_stmt_close($stmt);
?>