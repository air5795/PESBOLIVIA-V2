<?php
echo "<h1>Visor de Logs (Ruta oficial del servidor)</h1>";

$logFile = ini_get('error_log');

if ($logFile && file_exists($logFile)) {
    echo "<p>Archivo configurado encontrado en: <b>$logFile</b></p>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "<pre style='background:#111; color:#eee; padding:15px; border-radius:8px; overflow:auto; max-height:600px;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    // Si ini_get no devuelve nada, intentamos el archivo por defecto otra vez
    $defaultLog = 'error_log';
    if (file_exists($defaultLog)) {
        echo "<p>Archivo por defecto encontrado en la raíz.</p>";
        $lines = file($defaultLog);
        echo "<pre style='background:#111; color:#eee; padding:15px; border-radius:8px; overflow:auto; max-height:600px;'>" . htmlspecialchars(implode("", array_slice($lines, -50))) . "</pre>";
    } else {
        echo "<p style='color:red;'>El servidor no tiene configurado un archivo de error_log o está vacío.</p>";
        echo "<p>Ruta que reporta PHP: <b>" . ($logFile ?: 'Ninguna') . "</b></p>";
    }
}
?>
