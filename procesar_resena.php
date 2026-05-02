<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tienda.php');
}

if (!Session::is_logged_in()) {
    redirect('login.php');
}

$id_producto = isset($_POST['id_producto']) ? intval($_POST['id_producto']) : 0;
$calificacion = isset($_POST['calificacion']) ? intval($_POST['calificacion']) : 0;
$comentario = limpiar_texto($_POST['comentario'] ?? '');
$id_usuario = Session::get_user_id();

if ($id_producto <= 0 || $calificacion < 1 || $calificacion > 5 || empty($comentario)) {
    $_SESSION['error_review'] = "Todos los campos son obligatorios y la calificación debe ser válida.";
    redirect("producto.php?id=" . $id_producto . "#resenas-section");
}

// Check if user already reviewed
$query_check = "SELECT id FROM resenas WHERE id_producto = ? AND id_usuario = ?";
$stmt = mysqli_prepare($conexion, $query_check);
mysqli_stmt_bind_param($stmt, "ii", $id_producto, $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    $_SESSION['error_review'] = "Ya has publicado una opinión para este producto.";
    redirect("producto.php?id=" . $id_producto . "#resenas-section");
}
mysqli_stmt_close($stmt);

// Insert review
$query_insert = "INSERT INTO resenas (id_producto, id_usuario, calificacion, comentario, fecha_creacion) VALUES (?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conexion, $query_insert);
mysqli_stmt_bind_param($stmt, "iiis", $id_producto, $id_usuario, $calificacion, $comentario);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['success_review'] = "¡Gracias! Tu opinión se ha publicado correctamente.";
    Session::registrar_actividad($id_usuario, 'crear', 'resenas', mysqli_insert_id($conexion), "Nueva reseña para el producto ID $id_producto");
}
else {
    $_SESSION['error_review'] = "Ocurrió un error al guardar tu opinión. Intenta de nuevo.";
}
mysqli_stmt_close($stmt);

redirect("producto.php?id=" . $id_producto . "#resenas-section");
