<?php
require_once '../../config/config.php';
require_once '../../config/session.php';

$id_actual = Session::get_user_id();
echo "ID Sesión Actual: $id_actual\n\n";

$query = "SELECT id, nombre, id_editor FROM tipos_pago";
$res = mysqli_query($conexion, $query);

echo "Contenido de tipos_pago:\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Nombre: {$row['nombre']} | Dueño (id_editor): " . ($row['id_editor'] ?? 'NULL') . "\n";
}
?>
