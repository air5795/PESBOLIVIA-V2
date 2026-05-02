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

    // Enviar email al superadmin (deshabilitado temporalmente)
    /*
     try {
     $emailSender = new EmailSender();
     // Email a superadmin de nueva compra
     } catch (Exception $e) {
     // Continuar
     }
     */

    $_SESSION['success'] = "¡Compra registrada exitosamente! Código: <strong>$codigo_compra</strong><br>Tu compra está pendiente de aprobación. Recibirás un email cuando sea aprobada.";
    redirect('modules/comprador/mis_compras.php');
}
else {
    $_SESSION['error'] = "Error al procesar la compra. Intenta nuevamente.";
    redirect("producto.php?id=$id_producto");
}

mysqli_stmt_close($stmt);
?>