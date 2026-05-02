<?php
require_once '../../config/config.php';
require_once '../../config/session.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id_video = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Obtener producto del video y verificar permisos
$query = "SELECT tv.id_producto FROM tutorial_videos tv
          INNER JOIN productos p ON tv.id_producto = p.id
          INNER JOIN producto_editores pe ON p.id = pe.id_producto
          WHERE tv.id = ? AND pe.id_editor = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_video, $id_editor);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$video = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Video no encontrado";
    redirect('productos.php');
}

$id_producto = $video['id_producto'];
mysqli_stmt_close($stmt);

// Eliminar
$query_del = "DELETE FROM tutorial_videos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_del);
mysqli_stmt_bind_param($stmt, "i", $id_video);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success'] = "Video eliminado";
}
else {
    $_SESSION['error'] = "Error al eliminar";
}

mysqli_stmt_close($stmt);
redirect("tutorial_videos.php?id=$id_producto");
?>