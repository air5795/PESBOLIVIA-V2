<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_COMPRADOR);

$page_title = 'Mis Compras';
$id_usuario = Session::get_user_id();

// Filtro de estado
$filtro_estado = $_GET['estado'] ?? 'todas';

// Obtener compras del usuario
$query = "SELECT c.*, p.nombre as producto_nombre, p.imagen, p.drive_link,
          tp.nombre as tipo_pago_nombre
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN tipos_pago tp ON c.id_tipo_pago = tp.id
          WHERE c.id_comprador = ?";

if ($filtro_estado !== 'todas') {
    $query .= " AND c.estado = '$filtro_estado'";
}

$query .= " ORDER BY c.fecha_compra DESC";

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$compras = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Estadísticas
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados
    FROM compras WHERE id_comprador = ?";
$stmt = mysqli_prepare($conexion, $query_stats);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Mis Compras</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Mis Compras</li>
                </ol>
            </nav>
        </div>
        <a href="../../tienda.php" class="btn btn-primary">
            <i class="fas fa-store me-2"></i>
            Ir a la Tienda
        </a>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Compras</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['pendientes']; ?></h3>
                <p>Pendientes</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['aprobados']; ?></h3>
                <p>Aprobados</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <i class="fas fa-times"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['rechazados']; ?></h3>
                <p>Rechazados</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-2">Filtrar por Estado:</h6>
        <div class="btn-group" role="group">
            <a href="?estado=todas" class="btn btn-sm btn-outline-primary <?php echo ($filtro_estado === 'todas') ? 'active' : ''; ?>">
                Todas (<?php echo $stats['total']; ?>)
            </a>
            <a href="?estado=pendiente" class="btn btn-sm btn-outline-warning <?php echo ($filtro_estado === 'pendiente') ? 'active' : ''; ?>">
                Pendientes (<?php echo $stats['pendientes']; ?>)
            </a>
            <a href="?estado=aprobado" class="btn btn-sm btn-outline-success <?php echo ($filtro_estado === 'aprobado') ? 'active' : ''; ?>">
                Aprobados (<?php echo $stats['aprobados']; ?>)
            </a>
            <a href="?estado=rechazado" class="btn btn-sm btn-outline-danger <?php echo ($filtro_estado === 'rechazado') ? 'active' : ''; ?>">
                Rechazados (<?php echo $stats['rechazados']; ?>)
            </a>
        </div>
    </div>
</div>

<!-- Lista de Compras -->
<?php if (mysqli_num_rows($compras) > 0): ?>
    <div class="row">
        <?php while ($compra = mysqli_fetch_assoc($compras)): ?>
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if (!empty($compra['imagen']) && file_exists('../../' . $compra['imagen'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $compra['imagen']; ?>" 
                                     alt="<?php echo $compra['producto_nombre']; ?>" 
                                     style="width: 100%; border-radius: 8px;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                     style="width: 100%; height: 150px; border-radius: 8px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h5><?php echo $compra['producto_nombre']; ?></h5>
                            
                            <p class="mb-2">
                                <strong>Código:</strong> 
                                <span class="badge bg-secondary"><?php echo $compra['codigo_compra']; ?></span>
                            </p>
                            
                            <p class="mb-2">
                                <strong>Monto:</strong> 
                                <span class="text-primary"><?php echo formatear_monto($compra['monto_total']); ?></span>
                            </p>
                            
                            <p class="mb-2">
                                <strong>Método de Pago:</strong> <?php echo $compra['tipo_pago_nombre']; ?>
                            </p>
                            
                            <p class="mb-2">
                                <strong>Fecha:</strong> <?php echo formatear_fecha($compra['fecha_compra'], true); ?>
                            </p>
                            
                            <p class="mb-3">
                                <strong>Estado:</strong> <?php echo badge_estado($compra['estado']); ?>
                            </p>
                            
                            <?php if ($compra['estado'] === 'pendiente'): ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <small>Tu compra está siendo revisada.</small>
                                </div>
                            <?php elseif ($compra['estado'] === 'aprobado'): ?>
                                <a href="<?php echo $compra['drive_link']; ?>" 
                                   target="_blank" 
                                   class="btn btn-success w-100">
                                    <i class="fas fa-download me-2"></i>
                                    Descargar Producto
                                </a>
                            <?php elseif ($compra['estado'] === 'rechazado'): ?>
                                <div class="alert alert-danger mb-2">
                                    <small>
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <?php echo $compra['observaciones'] ?? 'Tu compra fue rechazada.'; ?>
                                    </small>
                                </div>
                                <a href="../../producto.php?id=<?php echo $compra['id_producto']; ?>" 
                                   class="btn btn-warning w-100">
                                    <i class="fas fa-redo me-2"></i>
                                    Intentar de Nuevo
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
            <h4>No tienes compras registradas</h4>
            <p class="text-muted">Explora nuestra tienda y encuentra los mejores productos.</p>
            <a href="../../tienda.php" class="btn btn-primary">
                <i class="fas fa-store me-2"></i>
                Ir a la Tienda
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>