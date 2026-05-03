<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
}
require_once 'config/config.php';
require_once 'includes/funciones.php';
require_once 'utils/GoogleDriveManager.php';

echo "<h1>Diagnóstico de Google Drive</h1>";

// 1. Verificar archivo de credenciales
$path = BASE_PATH . '/config/google-credentials.json';
if (file_exists($path)) {
    echo "<p style='color:green;'>✅ Archivo de credenciales encontrado.</p>";
    $json = json_decode(file_get_contents($path), true);
    echo "<p>Cuenta de servicio: <b>" . ($json['client_email'] ?? 'No encontrada') . "</b></p>";
} else {
    echo "<p style='color:red;'>❌ ERROR: No se encuentra el archivo en: $path</p>";
}

// 2. Verificar Librería Vendor
if (class_exists('Google\Client')) {
    echo "<p style='color:green;'>✅ Librería Google API Client cargada correctamente.</p>";
} else {
    echo "<p style='color:red;'>❌ ERROR: La librería de Google no está en la carpeta vendor. ¿Ejecutaste composer require?</p>";
}

// 3. Probar conexión y realizar prueba real
try {
    $drive = new GoogleDriveManager();
    echo "<p style='color:blue;'>ℹ️ Intentando realizar una prueba real de acceso...</p>";
    
    // --- CONFIGURA ESTO PARA TU PRUEBA ---
    $id_carpeta_prueba = '110TdGC8wa8HitMYZWKfkcvGd8MX3DIUs'; // Tu ID de carpeta
    $email_prueba = 'air5795@gmail.com'; // Tu email para probar
    // ------------------------------------
    
    echo "Probando carpeta ID: <b>$id_carpeta_prueba</b> con email: <b>$email_prueba</b>...<br>";
    
    $resultado = $drive->darAcceso($id_carpeta_prueba, $email_prueba);
    
    if ($resultado) {
        echo "<h2 style='color:green;'>✅ ¡ÉXITO! Se otorgó el acceso correctamente.</h2>";
        echo "<p>Revisa tu correo ($email_prueba) o intenta entrar a la carpeta ahora.</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ FALLÓ la prueba de acceso.</h2>";
    echo "<p><b>Error de Google:</b> <i style='color:red;'>" . htmlspecialchars($e->getMessage()) . "</i></p>";
    echo "<p><b>Posible solución:</b> Verifica que hayas compartido la carpeta de Drive con el email de la Cuenta de Servicio como 'Editor'.</p>";
}
?>
