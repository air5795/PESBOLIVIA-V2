<?php
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

// 3. Probar conexión
try {
    $drive = new GoogleDriveManager();
    // Intentaremos listar algo simple para ver si conecta
    echo "<p>Intentando conectar con Google Drive API...</p>";
    
    // Si llegas aquí, intenta dar acceso a un correo de prueba tuyo
    // Cambia 'TU_EMAIL@gmail.com' por uno tuyo para probar
    // Y 'ID_DE_TU_CARPETA' por el ID de tu carpeta de Drive
    /*
    $resultado = $drive->darAcceso('ID_DE_TU_CARPETA', 'TU_EMAIL@gmail.com');
    if ($resultado) echo "✅ Prueba de acceso exitosa.";
    else echo "❌ Falló la prueba de acceso.";
    */
} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR de Conexión: " . $e->getMessage() . "</p>";
}
?>
