<?php
$autoload_path = 'vendor/autoload.php';
echo "<h1>Verificando Autoload</h1>";

if (file_exists($autoload_path)) {
    $content = file_get_contents($autoload_path);
    echo "<p>Tamaño del archivo: " . strlen($content) . " bytes</p>";
    echo "<p>Última modificación: " . date("Y-m-d H:i:s", filemtime($autoload_path)) . "</p>";
    
    if (strpos($content, 'Google') !== false) {
        echo "<p style='color:green;'>✅ El autoloader parece contener referencias a Google.</p>";
    } else {
        echo "<p style='color:red;'>❌ El autoloader NO tiene referencias a Google. Es una versión antigua.</p>";
    }
} else {
    echo "<p style='color:red;'>No se encuentra vendor/autoload.php</p>";
}
?>
