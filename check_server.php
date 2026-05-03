<?php
echo "<h1>Contenido de Vendor en el Servidor</h1>";
$path = 'vendor';

if (is_dir($path)) {
    $files = scandir($path);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            $fullPath = $path . '/' . $file;
            $type = is_dir($fullPath) ? "DIR" : "FILE";
            echo "<li>[$type] $file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red;'>No se encuentra la carpeta vendor.</p>";
}
?>
