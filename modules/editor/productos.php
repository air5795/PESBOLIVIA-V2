<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mis Productos';
$id_usuario = Session::get_user_id();

// Obtener productos asignados al editor
$query = "SELECT p.*, c.nombre as categoria_nombre, pe.porcentaje,
          (SELECT COUNT(*) FROM compras WHERE id_producto = p.id AND estado IN ('aprobado', 'entregado')) as total_ventas,
          (SELECT SUM(monto_total) FROM compras WHERE id_producto = p.id AND estado IN ('aprobado', 'entregado')) as total_ingresos,
          (SELECT SUM(monto) FROM pagos_editores WHERE id_compra IN (SELECT id FROM compras WHERE id_producto = p.id) AND id_editor = ?) as mis_comisiones
          FROM productos p
          LEFT JOIN categorias c ON p.id_categoria = c.id
          INNER JOIN producto_editores pe ON p.id = pe.id_producto
          WHERE pe.id_editor = ?
          ORDER BY p.fecha_creacion DESC";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_usuario);
mysqli_stmt_execute($stmt);
$productos = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Mis Productos</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Mis Productos</li>
                </ol>
            </nav>
        </div>
        <a href="productos_crear.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Crear Producto
        </a>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Puedes crear tus propios productos y obtener el <strong>100% de comisión</strong> por cada venta.
</div>

<?php if (mysqli_num_rows($productos) > 0): ?>
    <div class="row">
        <?php while ($producto = mysqli_fetch_assoc($productos)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" 
                                     alt="<?php echo $producto['nombre']; ?>" 
                                     style="width: 100%; border-radius: 8px;">
                            <?php
        else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 100%; height: 150px; border-radius: 8px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php
        endif; ?>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5><?php echo htmlspecialchars($producto['nombre']); ?></h5>
                                <?php echo badge_estado($producto['estado']); ?>
                            </div>
                            
                            <p class="mb-2">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($producto['categoria_nombre']); ?></span>
                            </p>
                            
                            <p class="mb-2">
                                <strong>Precio:</strong> <?php echo formatear_monto($producto['precio']); ?>
                            </p>
                            
                            <p class="mb-2">
                                <strong>Mi Porcentaje:</strong> 
                                <span class="badge bg-info fs-6"><?php echo number_format($producto['porcentaje'], 2); ?>%</span>
                            </p>
                            
                            <hr>
                            
                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <h6 class="text-primary"><?php echo $producto['total_ventas']; ?></h6>
                                    <small class="text-muted">Ventas</small>
                                </div>
                                <div class="col-4">
                                    <h6 class="text-success"><?php echo formatear_monto($producto['total_ingresos']); ?></h6>
                                    <small class="text-muted">Total Generado</small>
                                </div>
                                <div class="col-4">
                                    <h6 class="text-success"><?php echo formatear_monto($producto['mis_comisiones']); ?></h6>
                                    <small class="text-muted">Mis Comisiones</small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="productos_editar.php?id=<?php echo $producto['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>
                                    Editar Producto
                                </a>
                                 <?php if ($producto['total_ventas'] == 0 && floatval($producto['porcentaje']) == 100.00): ?>
                                     <button class="btn btn-outline-danger btn-sm" 
                                             onclick="confirmarEliminacion('productos_eliminar.php?id=<?php echo $producto['id']; ?>', '¿Eliminar <?php echo addslashes($producto['nombre']); ?>?')">
                                         <i class="fas fa-trash me-1"></i>
                                         Eliminar
                                     </button>
                                 <?php elseif ($producto['total_ventas'] > 0): ?>
                                     <small class="text-muted text-center">
                                         <i class="fas fa-info-circle me-1"></i>
                                         No se puede eliminar (tiene ventas)
                                     </small>
                                 <?php else: ?>
                                     <small class="text-muted text-center">
                                         <i class="fas fa-info-circle me-1"></i>
                                         No se puede eliminar (autoría compartida)
                                     </small>
                                 <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    endwhile; ?>
    </div>
<?php
else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-box fa-4x text-muted mb-3"></i>
            <h4>No tienes productos creados</h4>
            <p class="text-muted">Crea tu primer producto y empieza a vender.</p>
            <a href="productos_crear.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Crear Mi Primer Producto
            </a>
        </div>
    </div>
<?php
endif; ?>

<?php include '../../includes/footer.php'; ?>