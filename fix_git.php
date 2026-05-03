<?php
echo "<h1>Reparando Caché de Git y Desplegando...</h1>";

// 1. Intentar limpiar el caché de cPanel
echo "<p>Limpiando caché de cPanel...</p>";
shell_exec("rm -rf ~/.cpanel/caches/vc 2>&1");

// 2. Forzar sincronización (Hard Reset)
echo "<p>Forzando sincronización con GitHub (Hard Reset)...</p>";
$cmd = "git fetch --all 2>&1 && git reset --hard origin/main 2>&1";
$output = shell_exec($cmd);
echo "<pre>$output</pre>";

// 3. Limpiar archivos basura (opcional)
echo "<p>Limpiando archivos no rastreados...</p>";
$output_clean = shell_exec("git clean -d -f 2>&1");
echo "<pre>$output_clean</pre>";

echo "<p><b>Proceso terminado.</b> Intenta ahora entrar a tu web o usar de nuevo el botón de cPanel.</p>";
?>
