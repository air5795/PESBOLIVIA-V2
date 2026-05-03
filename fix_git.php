<?php
echo "<h1>Reparando Caché de Git y Desplegando...</h1>";

// 1. Intentar limpiar el caché de cPanel
echo "<p>Limpiando caché de cPanel...</p>";
$cmd1 = "rm -rf ~/.cpanel/caches/vc 2>&1";
$output1 = shell_exec($cmd1);
echo "<pre>$output1</pre>";

// 2. Intentar hacer el pull manual
echo "<p>Ejecutando git pull...</p>";
$cmd2 = "git pull origin main 2>&1";
$output2 = shell_exec($cmd2);
echo "<pre>$output2</pre>";

echo "<p><b>Proceso terminado.</b> Intenta ahora entrar a tu web o usar de nuevo el botón de cPanel.</p>";
?>
