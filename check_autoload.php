<?php
$autoload_path = 'vendor/composer/autoload_psr4.php';
echo "<h1>Verificando Autoload (PSR-4)</h1>";

if (file_exists($autoload_path)) {
    $content = file_get_contents($autoload_path);
    echo "<p>Tamaño del archivo: " . strlen($content) . " bytes</p>";
    
    if (strpos($content, 'Google') !== false) {
        echo "<p style='color:green;'>✅ ¡EXCELENTE! El índice PSR-4 contiene a Google.</p>";
    } else {
        echo "<p style='color:red;'>❌ El índice PSR-4 NO tiene referencias a Google. El servidor está usando un índice antiguo.</p>";
    }
} else {
    echo "<p style='color:red;'>No se encuentra $autoload_path</p>";
}
?>
