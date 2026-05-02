<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = intval($_POST['id']);

$query = "SELECT * FROM categorias WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($categoria = mysqli_fetch_assoc($result)) {
    echo json_encode([
        'success' => true,
        'categoria' => $categoria
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Categoría no encontrada'
    ]);
}

mysqli_stmt_close($stmt);
?>