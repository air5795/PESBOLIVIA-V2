<?php
/**
 * CLASE DE MANEJO DE SESIONES Y AUTENTICACIÓN
 */

class Session {
    
    /**
     * Iniciar sesión de usuario
     */
    public static function login($usuario_data) {
        // Regenerar ID de sesión para evitar ataques de Session Fixation
        session_regenerate_id(true);
        
        $_SESSION['usuario_id'] = $usuario_data['id'];
        $_SESSION['usuario_nombre'] = $usuario_data['nombre'];
        $_SESSION['usuario_apellido'] = $usuario_data['apellido'];
        $_SESSION['usuario_email'] = $usuario_data['email'];
        $_SESSION['usuario_rol'] = $usuario_data['rol'];
        $_SESSION['usuario_foto'] = $usuario_data['foto_perfil'];
        $_SESSION['login_time'] = time();
        
        // Limpiar contador de intentos fallidos
        unset($_SESSION['intentos_fallidos']);
        unset($_SESSION['bloqueo_hasta']);
        
        // Actualizar último acceso en BD
        self::actualizar_ultimo_acceso($usuario_data['id']);
        
        // Registrar en logs
        self::registrar_actividad($usuario_data['id'], 'login', null, null, 'Inicio de sesión');
    }
    
    /**
     * Verificar si hay sesión activa
     */
    public static function is_logged_in() {
        return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
    }
    
    /**
     * Obtener ID del usuario logueado
     */
    public static function get_user_id() {
        return $_SESSION['usuario_id'] ?? null;
    }
    
    /**
     * Obtener rol del usuario logueado
     */
    public static function get_user_role() {
        return $_SESSION['usuario_rol'] ?? null;
    }
    
    /**
     * Obtener nombre completo del usuario
     */
    public static function get_user_name() {
        if (self::is_logged_in()) {
            return $_SESSION['usuario_nombre'] . ' ' . $_SESSION['usuario_apellido'];
        }
        return '';
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public static function has_role($rol) {
        return self::get_user_role() === $rol;
    }
    
    /**
     * Verificar si el usuario es superadmin
     */
    public static function is_superadmin() {
        return self::has_role(ROL_SUPERADMIN);
    }
    
    /**
     * Verificar si el usuario es editor
     */
    public static function is_editor() {
        return self::has_role(ROL_EDITOR);
    }
    
    /**
     * Verificar si el usuario es comprador
     */
    public static function is_comprador() {
        return self::has_role(ROL_COMPRADOR);
    }
    
    /**
     * Cerrar sesión
     */
    public static function logout() {
        $usuario_id = self::get_user_id();
        
        // Registrar cierre de sesión
        if ($usuario_id) {
            self::registrar_actividad($usuario_id, 'logout', null, null, 'Cierre de sesión');
        }
        
        // Destruir sesión
        session_unset();
        session_destroy();
        
        // Redirigir al login
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
    
    /**
     * Requiere que el usuario esté logueado
     */
    public static function require_login() {
        if (!self::is_logged_in()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
    }
    
    /**
     * Requiere un rol específico
     */
    public static function require_role($rol) {
        self::require_login();
        
        if (!self::has_role($rol)) {
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        }
    }
    
    /**
     * Actualizar último acceso del usuario
     */
    private static function actualizar_ultimo_acceso($usuario_id) {
        global $conexion;
        
        $query = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "i", $usuario_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Registrar actividad en logs
     */
    public static function registrar_actividad($usuario_id, $accion, $tabla = null, $id_registro = null, $descripcion = null) {
        global $conexion;
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $query = "INSERT INTO logs_actividad (id_usuario, accion, tabla_afectada, id_registro, descripcion, ip, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "issssss", $usuario_id, $accion, $tabla, $id_registro, $descripcion, $ip, $user_agent);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    /**
     * Generar token CSRF
     */
    public static function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>