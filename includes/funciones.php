<?php
/**
 * FUNCIONES AUXILIARES DEL SISTEMA
 */

/**
 * Sanitizar entrada de texto
 */
function limpiar_texto($texto) {
    global $conexion;
    return mysqli_real_escape_string($conexion, trim($texto));
}

/**
 * Generar código único para compras
 */
function generar_codigo_compra() {
    return 'PES-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Formatear fecha de MySQL a formato legible
 */
function formatear_fecha($fecha, $incluir_hora = false) {
    if (empty($fecha) || $fecha == '0000-00-00' || $fecha == '0000-00-00 00:00:00') {
        return '-';
    }
    
    $timestamp = strtotime($fecha);
    
    if ($incluir_hora) {
        return date(FORMATO_FECHA_HORA, $timestamp);
    } else {
        return date(FORMATO_FECHA, $timestamp);
    }
}

/**
 * Formatear monto con moneda
 */
function formatear_monto($monto) {
    if ($monto === null || $monto === '') {
        $monto = 0;  // ✅ Convierte null a 0
    }
    return MONEDA . ' ' . number_format((float)$monto, 2, '.', ',');
}
/**
 * Generar password hash
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verificar password
 */
function verificar_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validar email
 */
function validar_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar token aleatorio
 */
function generar_token($longitud = 64) {
    return bin2hex(random_bytes($longitud / 2));
}

/**
 * Obtener IP del cliente
 */
function obtener_ip_cliente() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Redireccionar a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Mostrar alerta con SweetAlert2
 */
function mostrar_alerta($tipo, $titulo, $mensaje, $redirect = null) {
    $tipos_permitidos = ['success', 'error', 'warning', 'info'];
    
    if (!in_array($tipo, $tipos_permitidos)) {
        $tipo = 'info';
    }
    
    $script = "
    <script>
        Swal.fire({
            icon: '$tipo',
            title: '$titulo',
            text: '$mensaje',
            confirmButtonText: 'OK'
        })" . ($redirect ? ".then(() => { window.location.href = '$redirect'; })" : "") . ";
    </script>
    ";
    
    return $script;
}

/**
 * Respuesta JSON
 */
function json_response($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Validar usuario único
 */
function usuario_existe($usuario, $excluir_id = null) {
    global $conexion;
    
    $query = "SELECT id FROM usuarios WHERE usuario = ?";
    
    if ($excluir_id) {
        $query .= " AND id != ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "si", $usuario, $excluir_id);
    } else {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "s", $usuario);
    }
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $existe = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    
    return $existe;
}

/**
 * Validar email único
 */
function email_existe($email, $excluir_id = null) {
    global $conexion;
    
    $query = "SELECT id FROM usuarios WHERE email = ?";
    
    if ($excluir_id) {
        $query .= " AND id != ?";
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "si", $email, $excluir_id);
    } else {
        $stmt = mysqli_prepare($conexion, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
    }
    
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $existe = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
    
    return $existe;
}

/**
 * Obtener datos de usuario por ID
 */
function obtener_usuario($id) {
    global $conexion;
    
    $query = "SELECT * FROM usuarios WHERE id = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $usuario = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $usuario;
}

/**
 * Calcular comisiones de editores para un producto
 */
function calcular_comisiones($id_producto, $monto_venta) {
    global $conexion;
    
    $comisiones = [];
    
    $query = "SELECT pe.id_editor, pe.porcentaje, u.nombre, u.apellido 
              FROM producto_editores pe
              INNER JOIN usuarios u ON pe.id_editor = u.id
              WHERE pe.id_producto = ?";
    
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_producto);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $monto_comision = ($monto_venta * $row['porcentaje']) / 100;
        
        $comisiones[] = [
            'id_editor' => $row['id_editor'],
            'nombre' => $row['nombre'] . ' ' . $row['apellido'],
            'porcentaje' => $row['porcentaje'],
            'monto' => $monto_comision
        ];
    }
    
    mysqli_stmt_close($stmt);
    
    return $comisiones;
}

/**
 * Obtener badge HTML según estado
 */
function badge_estado($estado) {
    $badges = [
        'activo' => '<span class="badge bg-success">Activo</span>',
        'inactivo' => '<span class="badge bg-secondary">Inactivo</span>',
        'pendiente' => '<span class="badge bg-warning text-dark">Pendiente</span>',
        'aprobado' => '<span class="badge bg-success">Aprobado</span>',
        'rechazado' => '<span class="badge bg-danger">Rechazado</span>',
        'entregado' => '<span class="badge bg-info">Entregado</span>',
        'pagado' => '<span class="badge bg-success">Pagado</span>'
    ];
    
    return $badges[$estado] ?? '<span class="badge bg-secondary">' . ucfirst($estado) . '</span>';
}

/**
 * Obtener icono según rol
 */
function icono_rol($rol) {
    $iconos = [
        'superadmin' => '<i class="fas fa-user-shield"></i>',
        'editor' => '<i class="fas fa-user-edit"></i>',
        'comprador' => '<i class="fas fa-user"></i>'
    ];
    
    return $iconos[$rol] ?? '<i class="fas fa-user"></i>';
}

/**
 * Tiempo transcurrido desde una fecha
 */
function tiempo_transcurrido($fecha) {
    $timestamp = strtotime($fecha);
    $diferencia = time() - $timestamp;
    
    if ($diferencia < 60) {
        return 'Hace ' . $diferencia . ' segundos';
    } elseif ($diferencia < 3600) {
        return 'Hace ' . floor($diferencia / 60) . ' minutos';
    } elseif ($diferencia < 86400) {
        return 'Hace ' . floor($diferencia / 3600) . ' horas';
    } elseif ($diferencia < 604800) {
        return 'Hace ' . floor($diferencia / 86400) . ' días';
    } else {
        return formatear_fecha($fecha);
    }
}

/**
 * Truncar texto
 */
function truncar_texto($texto, $longitud = 100, $sufijo = '...') {
    if (strlen($texto) > $longitud) {
        return substr($texto, 0, $longitud) . $sufijo;
    }
    return $texto;
}

/**
 * Generar URL de imagen o placeholder
 */
function url_imagen($ruta, $placeholder = 'placeholder.jpg') {
    if (!empty($ruta) && file_exists($ruta)) {
        return BASE_URL . '/' . $ruta;
    }
    return BASE_URL . '/assets/img/' . $placeholder;
}

/**
 * Verificar modo mantenimiento
 */
function verificar_mantenimiento() {
    global $CONFIG;
    
    if (!isset($CONFIG['modo_mantenimiento']) || $CONFIG['modo_mantenimiento'] != '1') {
        return;
    }
    
    $current_script = basename($_SERVER['PHP_SELF']);
    $allowed_scripts = ['mantenimiento.php', 'login.php', 'logout.php', 'test_login.php', 'db_check.php', 'db_alter.php'];
    
    if (in_array($current_script, $allowed_scripts)) {
        return;
    }
    
    if (class_exists('Session') && Session::is_logged_in() && Session::is_superadmin()) {
        return;
    }
    
    redirect(BASE_URL . '/mantenimiento.php');
}

// Ejecutar verificación global
verificar_mantenimiento();
?>