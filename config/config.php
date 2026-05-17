<?php
/**
 * CONFIGURACIÓN GENERAL DEL SISTEMA
 * Sistema: PES Bolivia - AirPatch
 */

// Incluir conexión
require_once __DIR__ . '/conexion.php';

// Incluir Autoloader de Composer (Librerías externas)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// =====================================================
// CONFIGURACIÓN DE SESIONES
// =====================================================
if (session_status() == PHP_SESSION_NONE) {
    // Configuración de seguridad de sesiones ANTES de session_start()
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', ENTORNO == 'produccion' ? 1 : 0);

    // Ahora sí iniciar la sesión
    session_start();
}

// =====================================================
// RUTAS DEL SISTEMA
// =====================================================
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('PRODUCTOS_PATH', UPLOADS_PATH . '/productos');
define('COMPROBANTES_PATH', UPLOADS_PATH . '/comprobantes');
define('IMAGENES_PATH', UPLOADS_PATH . '/imagenes');
define('PERFILES_PATH', UPLOADS_PATH . '/perfiles');
define('QR_PATH', UPLOADS_PATH . '/qr');
define('LOGS_PATH', BASE_PATH . '/logs');

// URL base del sistema
if (ENTORNO == 'local') {
    define('BASE_URL', 'http://localhost/pesbolivia');
}
else {
    define('BASE_URL', 'https://pes-bolivia.airsoftbol.com');
}

// =====================================================
// CARGAR CONFIGURACIÓN DESDE BASE DE DATOS
// =====================================================
function cargar_configuracion()
{
    global $conexion;

    $config = [];
    $query = "SELECT clave, valor FROM configuracion";
    $result = mysqli_query($conexion, $query);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $config[$row['clave']] = $row['valor'];
        }
    }

    return $config;
}

// Cargar configuración en variable global
$CONFIG = cargar_configuracion();

// =====================================================
// CONSTANTES DEL SISTEMA
// =====================================================
define('NOMBRE_SISTEMA', $CONFIG['nombre_sistema'] ?? 'PES BOLIVIA');
define('EMAIL_SISTEMA', $CONFIG['email_sistema'] ?? 'airpatch@pesbolivia.airsoftbol.com');
define('MONEDA', $CONFIG['moneda'] ?? 'Bs.');
define('MAX_UPLOAD_SIZE', $CONFIG['max_upload_size'] ?? 2097152); // 2MB en bytes

// SMTP
define('SMTP_HOST', $CONFIG['smtp_host'] ?? 'smtp.titan.email');
define('SMTP_PORT', $CONFIG['smtp_port'] ?? 587);
define('SMTP_USER', $CONFIG['smtp_user'] ?? 'airpatch@pesbolivia.airsoftbol.com');
define('SMTP_PASSWORD', $CONFIG['smtp_password'] ?? '123456789Ale*');
define('SMTP_FROM_EMAIL', $CONFIG['smtp_from_email'] ?? EMAIL_SISTEMA);
define('SMTP_FROM_NAME', $CONFIG['smtp_from_name'] ?? NOMBRE_SISTEMA);

// =====================================================
// ZONA HORARIA
// =====================================================
date_default_timezone_set('America/La_Paz');

// =====================================================
// FORMATOS
// =====================================================
define('FORMATO_FECHA', 'd/m/Y');
define('FORMATO_FECHA_HORA', 'd/m/Y H:i:s');
define('FORMATO_FECHA_MYSQL', 'Y-m-d');
define('FORMATO_FECHA_HORA_MYSQL', 'Y-m-d H:i:s');

// =====================================================
// EXTENSIONES DE ARCHIVO PERMITIDAS
// =====================================================
define('EXTENSIONES_COMPROBANTE', ['jpg', 'jpeg', 'png', 'pdf']);
define('EXTENSIONES_IMAGEN', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// =====================================================
// ESTADOS
// =====================================================
define('ESTADO_ACTIVO', 'activo');
define('ESTADO_INACTIVO', 'inactivo');
define('COMPRA_PENDIENTE', 'pendiente');
define('COMPRA_APROBADO', 'aprobado');
define('COMPRA_RECHAZADO', 'rechazado');
define('COMPRA_ENTREGADO', 'entregado');

// =====================================================
// ROLES
// =====================================================
define('ROL_SUPERADMIN', 'superadmin');
define('ROL_EDITOR', 'editor');
define('ROL_COMPRADOR', 'comprador');

// =====================================================
// CREAR CARPETAS SI NO EXISTEN
// =====================================================
$carpetas = [
    UPLOADS_PATH,
    PRODUCTOS_PATH,
    COMPROBANTES_PATH,
    IMAGENES_PATH,
    PERFILES_PATH,
    QR_PATH,
    LOGS_PATH
];

foreach ($carpetas as $carpeta) {
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0755, true);
    }
}

// =====================================================
// AUTOLOAD DE CLASES (opcional)
// =====================================================
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/utils/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>