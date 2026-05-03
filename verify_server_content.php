<?php
echo "<h1>Contenido Real del Servidor</h1>";
$file = 'vendor/composer/autoload_psr4.php';

if (file_exists($file)) {
    echo "<h3>Archivo: $file</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($file)) . "</pre>";
} else {
    echo "❌ No existe el archivo.";
}
?>
