<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

$query = "SELECT id, nombre, apellido, usuario, email, rol, estado FROM usuarios WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($usuario = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'usuario' => $usuario
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no encontrado'
    ]);
}

mysqli_stmt_close($stmt);
?>