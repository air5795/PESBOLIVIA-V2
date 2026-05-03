<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea comprador
Session::require_role(ROL_COMPRADOR);

$page_title = 'Mi Dashboard';
$id_usuario = Session::get_user_id();

// Estadísticas del comprador
$query_stats = "SELECT 
    COUNT(*) as total_compras,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobadas,
    SUM(CASE WHEN estado IN ('aprobado', 'entregado') THEN monto_total ELSE 0 END) as total_gastado
    FROM compras WHERE id_comprador = ?";
$stmt = mysqli_prepare($conexion, $query_stats);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Últimas compras
$query_compras = "SELECT c.*, p.nombre as producto_nombre, p.imagen
                  FROM compras c
                  INNER JOIN productos p ON c.id_producto = p.id
                  WHERE c.id_comprador = ?
                  ORDER BY c.fecha_compra DESC
                  LIMIT 5";
$stmt = mysqli_prepare($conexion, $query_compras);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$ultimas_compras = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Bienvenido, <?php echo Session::get_user_name(); ?>!</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_compras']; ?></h3>
                <p>Total Compras</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
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
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-check"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['aprobadas']; ?></h3>
                <p>Aprobadas</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo formatear_monto($stats['total_gastado']); ?></h3>
                <p>Total Invertido</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Acceso Rápido -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-rocket me-2"></i>
                    Acceso Rápido
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../../tienda.php" class="btn btn-primary">
                        <i class="fas fa-store me-2"></i>
                        Ver Tienda
                    </a>
                    <a href="mis_compras.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Mis Compras
                    </a>
                    <a href="descargas.php" class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>
                        Mis Descargas
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Últimas Compras -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Últimas Compras
                </h5>
                <a href="mis_compras.php" class="btn btn-sm btn-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($ultimas_compras) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($compra = mysqli_fetch_assoc($ultimas_compras)): ?>
                                <tr>
                                    <td><strong><?php echo $compra['codigo_compra']; ?></strong></td>
                                    <td><?php echo truncar_texto($compra['producto_nombre'], 30); ?></td>
                                    <td><?php echo formatear_monto($compra['monto_total']); ?></td>
                                    <td><?php echo badge_estado($compra['estado']); ?></td>
                                    <td>
                                        <small><?php echo tiempo_transcurrido($compra['fecha_compra']); ?></small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p>Aún no has realizado compras.</p>
                        <a href="../../tienda.php" class="btn btn-primary">
                            <i class="fas fa-store me-2"></i>
                            Ir a la Tienda
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>