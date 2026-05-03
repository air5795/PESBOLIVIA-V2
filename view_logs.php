<?php
echo "<h1>Seguimiento de Automatización Drive</h1>";

$logFile = 'logs/automation.log';

if (file_exists($logFile)) {
    echo "<p>Mostrando actividad reciente de: <b>$logFile</b></p>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -100);
    echo "<pre style='background:#111; color:#0f0; padding:15px; border-radius:8px; overflow:auto; max-height:700px; font-family: monospace;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "<p style='color:orange;'>Aún no hay actividad registrada en el log de automatización.</p>";
    echo "<p><b>Paso a seguir:</b> Aprueba una compra para generar actividad.</p>";
}
?>
