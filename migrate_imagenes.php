<?php
/**
 * MIGRACIÓN: Crear tabla producto_imagenes y migrar datos existentes
 * Ejecutar UNA sola vez: http://localhost/pesbolivia/migrate_imagenes.php
 */
require_once 'config/config.php';

echo "<h2>Migración: Multi-Imágenes de Productos</h2><pre>";

// 1. Crear tabla producto_imagenes
$sql_create = "CREATE TABLE IF NOT EXISTS producto_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    imagen VARCHAR(500) NOT NULL,
    es_principal TINYINT(1) DEFAULT 0,
    orden INT DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto (id_producto),
    INDEX idx_principal (id_producto, es_principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($conexion, $sql_create)) {
    echo "✅ Tabla 'producto_imagenes' creada (o ya existía).\n";
} else {
    echo "❌ Error al crear tabla: " . mysqli_error($conexion) . "\n";
}

// 1.5 Modificar la columna id_tipo_pago para permitir múltiples métodos (VARCHAR)
// Primero intentamos eliminar la clave foránea si existe
$sql_drop_fk = "ALTER TABLE productos DROP FOREIGN KEY fk_productos_tipo_pago";
mysqli_query($conexion, $sql_drop_fk);

$sql_alter = "ALTER TABLE productos MODIFY id_tipo_pago VARCHAR(255) NULL";
if (mysqli_query($conexion, $sql_alter)) {
    echo "✅ Columna 'id_tipo_pago' modificada a VARCHAR(255) para permitir múltiples métodos.\n";
} else {
    echo "❌ Error al modificar columna: " . mysqli_error($conexion) . "\n";
}

// 2. Migrar imágenes existentes de productos.imagen a producto_imagenes
$query = "SELECT id, imagen FROM productos WHERE imagen IS NOT NULL AND imagen != ''";
$result = mysqli_query($conexion, $query);
$migradas = 0;
$omitidas = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Verificar si ya fue migrada
        $check = "SELECT id FROM producto_imagenes WHERE id_producto = ? AND imagen = ?";
        $stmt = mysqli_prepare($conexion, $check);
        mysqli_stmt_bind_param($stmt, "is", $row['id'], $row['imagen']);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) === 0) {
            // Insertar como imagen principal
            $insert = "INSERT INTO producto_imagenes (id_producto, imagen, es_principal, orden) VALUES (?, ?, 1, 0)";
            $stmt_insert = mysqli_prepare($conexion, $insert);
            mysqli_stmt_bind_param($stmt_insert, "is", $row['id'], $row['imagen']);
            if (mysqli_stmt_execute($stmt_insert)) {
                $migradas++;
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            $omitidas++;
        }
        mysqli_stmt_close($stmt);
    }
}

echo "✅ Migración completada: {$migradas} imágenes migradas, {$omitidas} ya existían.\n";

// 3. Agregar tasa de cambio USD si no existe
$check_tasa = "SELECT id FROM configuracion WHERE clave = 'tasa_cambio_usd'";
$res_tasa = mysqli_query($conexion, $check_tasa);
if ($res_tasa && mysqli_num_rows($res_tasa) === 0) {
    mysqli_query($conexion, "INSERT INTO configuracion (clave, valor) VALUES ('tasa_cambio_usd', '6.96')");
    echo "✅ Tasa de cambio USD agregada (6.96 Bs por defecto).\n";
} else {
    echo "✅ Tasa de cambio USD ya configurada.\n";
}

// 4. Actualizar o insertar max_upload_size para que sea de 2MB (2097152 bytes)
$check_upload_size = "SELECT id FROM configuracion WHERE clave = 'max_upload_size'";
$res_upload_size = mysqli_query($conexion, $check_upload_size);
if ($res_upload_size && mysqli_num_rows($res_upload_size) > 0) {
    mysqli_query($conexion, "UPDATE configuracion SET valor = '2097152' WHERE clave = 'max_upload_size'");
    echo "✅ Límite de subida de archivos (max_upload_size) actualizado a 2MB en la base de datos.\n";
} else {
    mysqli_query($conexion, "INSERT INTO configuracion (clave, valor) VALUES ('max_upload_size', '2097152')");
    echo "✅ Límite de subida de archivos (max_upload_size) creado como 2MB en la base de datos.\n";
}

echo "\n🎉 ¡Migración exitosa! Ahora puedes eliminar este archivo.\n";
echo "</pre>";
?>
