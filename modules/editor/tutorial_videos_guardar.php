<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('productos.php');
}

$id_producto = intval($_POST['id_producto']);
$titulo = limpiar_texto($_POST['titulo']);
$youtube_id = limpiar_texto($_POST['youtube_id']);
$duracion = limpiar_texto($_POST['duracion'] ?? '');
$orden = intval($_POST['orden'] ?? 0);
$id_editor = Session::get_user_id();

// Verificar permisos
$query_check = "SELECT p.id FROM productos p
                INNER JOIN producto_editores pe ON p.id = pe.id_producto
                WHERE p.id = ? AND pe.id_editor = ? AND p.tipo_producto = 'tutorial'";
$stmt = mysqli_prepare($conexion, $query_check);
mysqli_stmt_bind_param($stmt, "ii", $id_producto, $id_editor);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_get_result($stmt)->num_rows === 0) {
    $_SESSION['error'] = "No tienes permiso";
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

// Insertar
$query = "INSERT INTO tutorial_videos (id_producto, titulo, youtube_id, duracion, orden) 
          VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "isssi", $id_producto, $titulo, $youtube_id, $duracion, $orden);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "Video agregado";
}
else {
    $_SESSION['error'] = "Error al agregar";
}

mysqli_stmt_close($stmt);
redirect("tutorial_videos.php?id=$id_producto");
?>