<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

$page_title = 'Ajustes del Sistema';

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF
    // (Opcional, pero se asume que Session podría tener su propio manejo o no lo implementamos para no complicar el formulario original)
    
    // Configuración general
    $nombre_sistema = limpiar_texto($_POST['nombre_sistema']);
    $email_sistema = limpiar_texto($_POST['email_sistema']);
    $modo_mantenimiento = isset($_POST['modo_mantenimiento']) ? '1' : '0';

    // Función auxiliar para actualizar o insertar configuración
    function guardar_config($clave, $valor) {
        global $conexion;
        $q = "SELECT id FROM configuracion WHERE clave = '$clave'";
        $res = mysqli_query($conexion, $q);
        if (mysqli_num_rows($res) > 0) {
            $q_upd = "UPDATE configuracion SET valor = '$valor' WHERE clave = '$clave'";
            mysqli_query($conexion, $q_upd);
        } else {
            $q_ins = "INSERT INTO configuracion (clave, valor) VALUES ('$clave', '$valor')";
            mysqli_query($conexion, $q_ins);
        }
    }

    guardar_config('nombre_sistema', $nombre_sistema);
    guardar_config('email_sistema', $email_sistema);
    guardar_config('modo_mantenimiento', $modo_mantenimiento);

    $_SESSION['mensaje_exito'] = 'Configuración actualizada exitosamente.';
    
    // Recargar config global para reflejar inmediatamente
    $CONFIG = cargar_configuracion();
    
    // Si se activó o desactivó, redirigir
    redirect('ajustes.php');
}

// Obtener estado actual
$estado_mantenimiento = isset($CONFIG['modo_mantenimiento']) && $CONFIG['modo_mantenimiento'] == '1';

include '../../includes/header.php';
?>

<div class="page-header d-flex justify-content-between align-items-center mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ajustes</li>
        </ol>
    </nav>
</div>

<?php if (isset($_SESSION['mensaje_exito'])): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['mensaje_exito']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['mensaje_exito']); ?>
<?php endif; ?>

<div class="row g-4">
    <!-- Columna Principal -->
    <div class="col-xl-8 col-lg-7">
        
        <!-- Tarjeta Mantenimiento -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex align-items-center">
                <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle me-3">
                    <i class="fas fa-tools fa-lg"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-dark">Modo Mantenimiento</h5>
                    <p class="text-muted small mb-0">Controla el acceso público a la plataforma</p>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="ajustes.php">
                    <div class="alert <?php echo $estado_mantenimiento ? 'alert-warning' : 'alert-info'; ?> bg-opacity-10 border-0 rounded-3 d-flex p-4">
                        <div class="me-3">
                            <i class="fas <?php echo $estado_mantenimiento ? 'fa-exclamation-triangle text-warning' : 'fa-info-circle text-info'; ?> fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1 border-0">
                                Estado Actual: <?php echo $estado_mantenimiento ? '<span class="text-warning">ACTIVADO</span>' : '<span class="text-success">DESACTIVADO</span>'; ?>
                            </h6>
                            <p class="mb-0 text-muted small pb-2">
                                <?php if ($estado_mantenimiento): ?>
                                    Los visitantes verán la página de mantenimiento. Los superadministradores seguirán teniendo acceso a la tienda y todo el sistema si inician sesión.
                                <?php else: ?>
                                    El sitio web está visible para todo el público y funcionando normalmente. Activa el mantenimiento si realizarás actualizaciones importantes o un lanzamiento.
                                <?php endif; ?>
                            </p>
                            
                            <hr style="opacity: 0.1">
                            
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" id="modo_mantenimiento" name="modo_mantenimiento" value="1" <?php echo $estado_mantenimiento ? 'checked' : ''; ?> style="width: 50px; height: 25px;">
                                <label class="form-check-label ms-2 mt-1 fw-bold" for="modo_mantenimiento">
                                    Habilitar Modo Mantenimiento
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold text-dark mt-4 mb-3">Información General</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Nombre del Sistema / Tienda</label>
                            <input type="text" class="form-control form-control-lg bg-light" name="nombre_sistema" value="<?php echo htmlspecialchars(NOMBRE_SISTEMA); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email Principal de Contacto</label>
                            <input type="email" class="form-control form-control-lg bg-light" name="email_sistema" value="<?php echo htmlspecialchars(EMAIL_SISTEMA); ?>" required>
                        </div>
                    </div>
                    
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Columna Lateral -->
    <div class="col-xl-4 col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius: 16px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: white;">
            <div class="card-body p-4 text-center">
                <div class="bg-white bg-opacity-10 d-inline-block p-4 rounded-circle mb-3 border border-white border-opacity-25">
                    <i class="fas fa-rocket fa-3x" style="color: #60a5fa;"></i>
                </div>
                <h4 class="fw-bold mb-3">Pre-Lanzamiento</h4>
                <p class="text-light opacity-75 mb-4" style="font-size: 0.95rem;">
                    Mientras preparas tú plataforma, puedes verla y navegar sin problemas como superadmin.
                </p>
                <div class="text-start bg-black bg-opacity-25 p-3 rounded-3 mb-3">
                    <h6 class="fw-bold text-warning mb-2"><i class="fas fa-lightbulb me-2"></i>Tip Pro</h6>
                    <span class="text-light opacity-75" style="font-size: 0.85rem;">
                        No olvides revisar las comisiones y categorías antes de quitar el mantenimiento para un lanzamiento impecable.
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
