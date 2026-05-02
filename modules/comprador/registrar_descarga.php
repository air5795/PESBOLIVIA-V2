<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Permitir cualquier usuario logueado
if (!Session::is_logged_in()) {
    redirect('../../login.php');
}

if (!isset($_GET['id'])) {
    redirect('descargas.php');
}

$id_compra = intval($_GET['id']);
$id_usuario = Session::get_user_id();

// Verificar que la compra pertenece al usuario y está aprobada
$query = "SELECT c.*, p.drive_link, p.tipo_producto, p.id as id_producto
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          WHERE c.id = ? AND c.id_comprador = ? AND c.estado IN ('aprobado', 'entregado')";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_compra, $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$compra = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "No tienes acceso a esta descarga";
    redirect('descargas.php');
}

mysqli_stmt_close($stmt);

// Registrar la descarga
$ip_cliente = obtener_ip_cliente();

$query_descarga = "INSERT INTO descargas (id_compra, id_usuario, ip_descarga) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conexion, $query_descarga);
mysqli_stmt_bind_param($stmt, "iis", $id_compra, $id_usuario, $ip_cliente);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Actualizar estado de compra a 'entregado' si está en 'aprobado'
if ($compra['estado'] === 'aprobado') {
    $query_update = "UPDATE compras SET estado = 'entregado' WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query_update);
    mysqli_stmt_bind_param($stmt, "i", $id_compra);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Registrar actividad
Session::registrar_actividad($id_usuario, 'descarga', 'productos', $compra['id_producto'],
    "Descarga de producto - Compra: {$compra['codigo_compra']}");

// Redirigir según el tipo de producto
if ($compra['tipo_producto'] === 'tutorial') {
    // Para tutoriales, redirigir al visor de videos
    redirect("ver_tutorial.php?id={$compra['id_producto']}");
}
else {
    // Para productos normales, redirigir al link de Google Drive
    if (!empty($compra['drive_link'])) {
        header("Location: " . $compra['drive_link']);
        exit;
    }
    else {
        $_SESSION['error'] = "Link de descarga no disponible";
        redirect('descargas.php');
    }
}
?>