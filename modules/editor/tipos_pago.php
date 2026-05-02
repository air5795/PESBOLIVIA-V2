<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mis Métodos de Pago';
$id_editor = Session::get_user_id();

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener tipos de pago del editor
$query = "SELECT * FROM tipos_pago WHERE id_editor = ? ORDER BY id ASC";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_editor);
mysqli_stmt_execute($stmt);
$tipos_pago = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php
endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php
endif; ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Mis Métodos de Pago</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Métodos de Pago</li>
                </ol>
            </nav>
        </div>
        <a href="tipos_pago_crear.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nuevo Método de Pago
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Crea tus propios métodos de pago (QR, Transferencias). Los compradores de tus productos usarán estos métodos.
</div>

<div class="row">
    <?php if (mysqli_num_rows($tipos_pago) > 0): ?>
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
                        <?php
        else: ?>
                        <div class="col-12">
                        <?php
        endif; ?>
                            
                            <p class="mb-2">
                                <strong>Tipo:</strong> 
                                <span class="badge bg-secondary"><?php echo ucfirst($tipo['tipo']); ?></span>
                            </p>
                            
                            <?php if (!empty($tipo['descripcion'])): ?>
                            <p class="mb-2">
                                <strong>Descripción:</strong><br>
                                <?php echo nl2br(htmlspecialchars($tipo['descripcion'])); ?>
                            </p>
                            <?php
        endif; ?>
                            
                            <?php if ($tipo['tipo'] === 'transferencia'): ?>
                            <div class="mt-3">
                                <strong>Datos Bancarios:</strong>
                                <ul class="list-unstyled mt-2">
                                    <?php if (!empty($tipo['banco'])): ?>
                                        <li><small><i class="fas fa-university me-1"></i> <?php echo $tipo['banco']; ?></small></li>
                                    <?php
            endif; ?>
                                    <?php if (!empty($tipo['numero_cuenta'])): ?>
                                        <li><small><i class="fas fa-hashtag me-1"></i> <?php echo $tipo['numero_cuenta']; ?></small></li>
                                    <?php
            endif; ?>
                                    <?php if (!empty($tipo['titular'])): ?>
                                        <li><small><i class="fas fa-user me-1"></i> <?php echo $tipo['titular']; ?></small></li>
                                    <?php
            endif; ?>
                                </ul>
                            </div>
                            <?php
        endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="tipos_pago_editar.php?id=<?php echo $tipo['id']; ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="confirmarEliminacion('tipos_pago_eliminar.php?id=<?php echo $tipo['id']; ?>', '¿Eliminar <?php echo addslashes($tipo['nombre']); ?>?')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    endwhile; ?>
    <?php
else: ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                    <h4>No tienes métodos de pago configurados</h4>
                    <p class="text-muted">Crea al menos un método de pago para que los clientes puedan comprar tus productos.</p>
                    <a href="tipos_pago_crear.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Crear Mi Primer Método de Pago
                    </a>
                </div>
            </div>
        </div>
    <?php
endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>