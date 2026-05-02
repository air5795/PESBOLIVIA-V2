<?php
require_once 'config/config.php';
require_once 'includes/funciones.php';

// Configuramos la nueva contraseña
$usuario_reset = 'admin';
$nueva_clave = 'admin2026';

// Generamos el hash usando la función oficial del sistema
$hash = hash_password($nueva_clave);

// Actualizamos en la base de datos
$query = "UPDATE usuarios SET password = '$hash' WHERE usuario = '$usuario_reset'";

session_start();
unset($_SESSION['bloqueo_hasta']);
unset($_SESSION['intentos_fallidos']);

if (mysqli_query($conexion, $query)) {
    echo "<h2>¡Contraseña actualizada y Bloqueo eliminado!</h2>";
    echo "<p>Usuario: <b>$usuario_reset</b></p>";
    echo "<p>Nueva contraseña: <b>$nueva_clave</b></p>";
    echo "<hr>";
    echo "<p><b>Ya puedes intentar entrar al login ahora mismo.</b></p>";
    echo "<p style='color:red;'><b>RECORDATORIO:</b> Borra este archivo (reset.php) después de entrar.</p>";
} else {
    echo "Error al actualizar: " . mysqli_error($conexion);
}
?>
