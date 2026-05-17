<?php
/**
 * AJAX: Eliminar imagen de producto
 * Valida permisos según rol (superadmin = cualquiera, editor = solo sus productos)
 */
require_once '../config/config.php';
require_once '../config/session.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Método no permitido');
}

if (!Session::is_logged_in()) {
    json_response(false, 'No autorizado');
}

$user_role = Session::get_user_role();
$user_id = Session::get_user_id();

if (!in_array($user_role, [ROL_SUPERADMIN, ROL_EDITOR])) {
    json_response(false, 'No tienes permisos');
}

$id_imagen = isset($_POST['id_imagen']) ? intval($_POST['id_imagen']) : 0;

if ($id_imagen <= 0) {
    json_response(false, 'ID de imagen no válido');
}

// Obtener la imagen
$query = "SELECT pi.*, p.id as producto_id FROM producto_imagenes pi 
          INNER JOIN productos p ON pi.id_producto = p.id 
          WHERE pi.id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_imagen);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$imagen = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$imagen) {
    json_response(false, 'Imagen no encontrada');
}

// Validar permisos del editor
if ($user_role === ROL_EDITOR) {
    $query_perm = "SELECT id FROM producto_editores WHERE id_producto = ? AND id_editor = ?";
    $stmt = mysqli_prepare($conexion, $query_perm);
    mysqli_stmt_bind_param($stmt, "ii", $imagen['id_producto'], $user_id);
    mysqli_stmt_execute($stmt);
    $result_perm = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result_perm) === 0) {
        mysqli_stmt_close($stmt);
        json_response(false, 'No tienes permiso para modificar este producto');
    }
    mysqli_stmt_close($stmt);
}

// Verificar que no sea la única imagen
$query_count = "SELECT COUNT(*) as total FROM producto_imagenes WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query_count);
mysqli_stmt_bind_param($stmt, "i", $imagen['id_producto']);
mysqli_stmt_execute($stmt);
$result_count = mysqli_stmt_get_result($stmt);
$total_imagenes = mysqli_fetch_assoc($result_count)['total'];
mysqli_stmt_close($stmt);

// Eliminar el archivo físico
if (!empty($imagen['imagen']) && file_exists(BASE_PATH . '/' . $imagen['imagen'])) {
    unlink(BASE_PATH . '/' . $imagen['imagen']);
}

$era_principal = $imagen['es_principal'] == 1;

// Eliminar registro de BD
$query_delete = "DELETE FROM producto_imagenes WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_delete);
mysqli_stmt_bind_param($stmt, "i", $id_imagen);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Si era la imagen principal, asignar otra como principal
if ($era_principal) {
    $query_next = "SELECT id, imagen FROM producto_imagenes WHERE id_producto = ? ORDER BY orden ASC, id ASC LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query_next);
    mysqli_stmt_bind_param($stmt, "i", $imagen['id_producto']);
    mysqli_stmt_execute($stmt);
    $result_next = mysqli_stmt_get_result($stmt);
    $next = mysqli_fetch_assoc($result_next);
    mysqli_stmt_close($stmt);

    if ($next) {
        // Marcar la siguiente como principal
        $query_update = "UPDATE producto_imagenes SET es_principal = 1 WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query_update);
        mysqli_stmt_bind_param($stmt, "i", $next['id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Actualizar productos.imagen
        $query_sync = "UPDATE productos SET imagen = ? WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query_sync);
        mysqli_stmt_bind_param($stmt, "si", $next['imagen'], $imagen['id_producto']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        // No quedan imágenes, limpiar productos.imagen
        $query_sync = "UPDATE productos SET imagen = '' WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query_sync);
        mysqli_stmt_bind_param($stmt, "i", $imagen['id_producto']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

json_response(true, 'Imagen eliminada correctamente');
?>
