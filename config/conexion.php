<?php
/**
 * ARCHIVO DE CONEXIÓN A BASE DE DATOS
 * Sistema: PES Bolivia - AirPatch
 * Versión: 1.0
 */

// Detección de entorno
if ($_SERVER['SERVER_NAME'] == 'localhost' || 
    $_SERVER['SERVER_NAME'] == '127.0.0.1' || 
    $_SERVER['SERVER_NAME'] == '192.168.100.19' ||
    $_SERVER['SERVER_NAME'] == 'personales.local') {
    $entorno = 'local';
} else {
    $entorno = 'produccion';
}

// Configuración según entorno
if ($entorno == 'local') {
    $db_host = 'localhost:3306';
    $db_user = 'root';
    $db_password = '';
    $db_name = 'pesbolivia';
    
    // Mostrar errores en desarrollo
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    $db_host = 'localhost';
    $db_user = 'airsoftb_aleigles2';
    $db_password = '71811452Ale*';
    $db_name = 'airsoftb_pesbolivia-v2';
    
    // Mostrar errores temporalmente para diagnóstico
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Establecer conexión con manejo de errores mejorado
$conexion = @mysqli_connect($db_host, $db_user, $db_password, $db_name);

if (!$conexion) {
    // En producción, mostrar mensaje genérico
    if ($entorno == 'produccion') {
        die(json_encode([
            'success' => false,
            'message' => 'Error de conexión al sistema. Por favor, contacte al administrador.'
        ]));
    } else {
        // En desarrollo, mostrar detalles
        die("Error de conexión: " . mysqli_connect_error());
    }
}

// Configurar charset para evitar problemas con caracteres especiales
mysqli_set_charset($conexion, "utf8mb4");

// Definir constante de entorno
define('ENTORNO', $entorno);

// Función para cerrar la conexión (opcional, PHP lo hace automáticamente)
function cerrar_conexion() {
    global $conexion;
    if ($conexion) {
        mysqli_close($conexion);
    }
}

// Registrar el cierre automático al finalizar el script
register_shutdown_function('cerrar_conexion');
?>