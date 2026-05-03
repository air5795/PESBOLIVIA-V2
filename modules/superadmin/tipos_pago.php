<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$page_title = 'Tipos de Pago';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener tipos de pago con información del editor
$query = "SELECT tp.*, u.nombre as editor_nombre, u.apellido as editor_apellido, u.usuario as editor_usuario 
          FROM tipos_pago tp 
          LEFT JOIN usuarios u ON tp.id_editor = u.id 
          ORDER BY tp.id ASC";
$tipos_pago = mysqli_query($conexion, $query);

include '../../includes/header.php';
?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Tipos de Pago</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Tipos de Pago</li>
                </ol>
            </nav>
        </div>
        <a href="tipos_pago_crear.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nuevo Tipo de Pago
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Los tipos de pago se mostrarán a los usuarios cuando compren productos o se registren como editores.
</div>

<div class="row">
    <?php while ($tipo = mysqli_fetch_assoc($tipos_pago)): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-credit-card me-2"></i>
                    <?php echo $tipo['nombre']; ?>
                </h5>
                <?php echo badge_estado($tipo['estado']); ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($tipo['imagen_qr']) && file_exists('../../' . $tipo['imagen_qr'])): ?>
                    <div class="col-md-5 text-center mb-3 mb-md-0">
                        <img src="<?php echo BASE_URL . '/' . $tipo['imagen_qr']; ?>" 
                             alt="QR <?php echo $tipo['nombre']; ?>" 
                             style="max-width: 100%; border: 2px solid #667eea; border-radius: 8px; padding: 5px;">
                        <p class="text-muted mt-2 mb-0"><small>Código QR</small></p>
                    </div>
                    <div class="col-md-7">
                    <?php else: ?>
                    <div class="col-12">
                    <?php endif; ?>
                        
                        <p class="mb-2">
                            <strong>Tipo:</strong> 
                            <span class="badge bg-secondary"><?php echo ucfirst($tipo['tipo']); ?></span>
                        </p>
                        
                        <?php if (!empty($tipo['descripcion'])): ?>
                        <p class="mb-2">
                            <strong>Descripción:</strong><br>
                            <?php echo nl2br(htmlspecialchars($tipo['descripcion'])); ?>
                        </p>
                        <?php endif; ?>
                        
                        <?php if ($tipo['tipo'] === 'transferencia'): ?>
                        <div class="mt-3">
                            <strong>Datos Bancarios:</strong>
                            <ul class="list-unstyled mt-2">
                                <?php if (!empty($tipo['banco'])): ?>
                                    <li><small><i class="fas fa-university me-1"></i> <?php echo $tipo['banco']; ?></small></li>
                                <?php endif; ?>
                                <?php if (!empty($tipo['numero_cuenta'])): ?>
                                    <li><small><i class="fas fa-hashtag me-1"></i> <?php echo $tipo['numero_cuenta']; ?></small></li>
                                <?php endif; ?>
                                <?php if (!empty($tipo['titular'])): ?>
                                    <li><small><i class="fas fa-user me-1"></i> <?php echo $tipo['titular']; ?></small></li>
                                <?php endif; ?>
                                <?php if (!empty($tipo['ci_titular'])): ?>
                                    <li><small><i class="fas fa-id-card me-1"></i> CI: <?php echo $tipo['ci_titular']; ?></small></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="mt-3 pt-2 border-top">
                            <small class="text-muted">
                                <i class="fas fa-user-tag me-1"></i> Dueño: 
                                <span class="fw-bold text-primary">
                                    <?php echo !empty($tipo['editor_nombre']) ? ($tipo['editor_nombre'] . ' ' . $tipo['editor_apellido']) : 'ADMINISTRADOR'; ?>
                                </span>
                            </small>
                        </div>
                        
                        <?php if (!empty($tipo['instrucciones'])): ?>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php echo nl2br(htmlspecialchars(truncar_texto($tipo['instrucciones'], 100))); ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="d-flex justify-content-between">
                    <small class="text-muted">
                        Creado: <?php echo formatear_fecha($tipo['fecha_creacion']); ?>
                    </small>
                    <div>
                        <a href="tipos_pago_editar.php?id=<?php echo $tipo['id']; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="confirmarEliminacion('tipos_pago_eliminar.php?id=<?php echo $tipo['id']; ?>', '¿Eliminar <?php echo $tipo['nombre']; ?>?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<?php if (mysqli_num_rows($tipos_pago) === 0): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
        <h4>No hay tipos de pago configurados</h4>
        <p class="text-muted">Crea al menos un tipo de pago para que los usuarios puedan realizar pagos.</p>
        <a href="tipos_pago_crear.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Crear Primer Tipo de Pago
        </a>
    </div>
</div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>