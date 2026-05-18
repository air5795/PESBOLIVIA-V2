<?php
if (php_sapi_name() === 'cli' && !isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}
require_once 'config/config.php';

echo "Iniciando migración...\n";

// Verificar si la columna ya existe
$result = mysqli_query($conexion, "SHOW COLUMNS FROM productos LIKE 'fijado'");
if (mysqli_num_rows($result) > 0) {
    echo "La columna 'fijado' ya existe en la tabla 'productos'.\n";
} else {
    // Agregar columna 'fijado'
    $query = "ALTER TABLE productos ADD COLUMN fijado ENUM('si', 'no') NOT NULL DEFAULT 'no'";
    if (mysqli_query($conexion, $query)) {
        echo "Columna 'fijado' agregada correctamente a la tabla 'productos'.\n";
    } else {
        echo "Error al agregar la columna 'fijado': " . mysqli_error($conexion) . "\n";
    }
}
?>
