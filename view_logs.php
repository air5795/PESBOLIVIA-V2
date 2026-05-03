<?php
echo "<h1>Visor de Logs (Últimas 50 líneas)</h1>";
$logFile = 'error_log'; // Nombre estándar en cPanel/HostGator

if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "<p>No se encontró el archivo error_log en la raíz.</p>";
}
?>
