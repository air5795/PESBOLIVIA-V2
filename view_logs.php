<?php
echo "<h1>Visor de Logs (Buscando archivo...)</h1>";

$posibles_rutas = [
    'error_log',
    '../error_log',
    'public_html/error_log',
    '/home1/airsoftb/public_html/error_log',
    '/home1/airsoftb/pes-bolivia.airsoftbol.com/error_log'
];

$logFile = null;
foreach ($posibles_rutas as $ruta) {
    if (file_exists($ruta)) {
        $logFile = $ruta;
        break;
    }
}

if ($logFile) {
    echo "<p>Archivo encontrado en: <b>$logFile</b></p>";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -50);
    echo "<pre style='background:#111; color:#eee; padding:15px; border-radius:8px; overflow:auto; max-height:600px;'>" . htmlspecialchars(implode("", $lastLines)) . "</pre>";
} else {
    echo "<p style='color:red;'>No se encontró ningún archivo de log. Intenta aprobar una compra para que el servidor genere uno.</p>";
}
?>
