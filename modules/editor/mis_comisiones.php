<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mis Comisiones';
$id_usuario = Session::get_user_id();

// Filtro de estado
$filtro_estado = $_GET['estado'] ?? 'todas';

// Obtener comisiones
$query = "SELECT pag.*, c.codigo_compra, c.fecha_compra, c.monto_total,
          p.nombre as producto_nombre
          FROM pagos_editores pag
          INNER JOIN compras c ON pag.id_compra = c.id
          INNER JOIN productos p ON c.id_producto = p.id
          WHERE pag.id_editor = ? AND pag.porcentaje < 100";

if ($filtro_estado !== 'todas') {
    $query .= " AND pag.estado = '$filtro_estado'";
}

$query .= " ORDER BY c.fecha_compra DESC";

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$comisiones = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Estadísticas
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(monto) as total_monto,
    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) as pagadas
    FROM pagos_editores WHERE id_editor = ? AND porcentaje < 100";
$stmt = mysqli_prepare($conexion, $query_stats);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mis Comisiones</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Comisiones</li>
        </ol>
    </nav>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-list"></i>
            </div>
            <h3><?php echo $stats['total']; ?></h3>
            <p>Total Comisiones</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="fas fa-clock"></i>
            </div>
            <h3><?php echo formatear_monto($stats['pendientes']); ?></h3>
            <p>Pendientes de Pago</p>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3><?php echo formatear_monto($stats['pagadas']); ?></h3>
            <p>Ya Pagadas</p>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <h6 class="mb-2">Filtrar por Estado:</h6>
        <div class="btn-group" role="group">
            <a href="?estado=todas" class="btn btn-sm btn-outline-primary <?php echo($filtro_estado === 'todas') ? 'active' : ''; ?>">
                Todas (<?php echo $stats['total']; ?>)
            </a>
            <a href="?estado=pendiente" class="btn btn-sm btn-outline-warning <?php echo($filtro_estado === 'pendiente') ? 'active' : ''; ?>">
                Pendientes
            </a>
            <a href="?estado=pagado" class="btn btn-sm btn-outline-success <?php echo($filtro_estado === 'pagado') ? 'active' : ''; ?>">
                Pagadas
            </a>
        </div>
    </div>
</div>

<!-- Tabla de Comisiones -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-money-bill-wave me-2"></i>
            Detalle de Comisiones
        </h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($comisiones) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Código Compra</th>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Precio Venta</th>
                            <th>Mi %</th>
                            <th>Mi Comisión</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($com = mysqli_fetch_assoc($comisiones)): ?>
                        <tr>
                            <td><strong><?php echo $com['codigo_compra']; ?></strong></td>
                            <td><?php echo formatear_fecha($com['fecha_compra']); ?></td>
                            <td><?php echo truncar_texto($com['producto_nombre'], 30); ?></td>
                            <td><?php echo formatear_monto($com['monto_total']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($com['porcentaje'], 2); ?>%</span>
                            </td>
                            <td>
                                <strong class="text-success"><?php echo formatear_monto($com['monto']); ?></strong>
                            </td>
                            <td>
                                <?php if ($com['estado'] === 'pendiente'): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>
                                        Pendiente
                                    </span>
                                <?php
        else: ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>
                                        Pagado
                                    </span>
                                <?php
        endif; ?>
                            </td>
                        </tr>
                        <?php
    endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php
else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-money-bill-wave fa-4x mb-3"></i>
                <h4>No tienes comisiones registradas</h4>
                <p>Cuando se vendan tus productos, tus comisiones aparecerán aquí.</p>
            </div>
        <?php
endif; ?>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Nota:</strong> Las comisiones pendientes serán pagadas por el superadministrador. Recibirás una notificación cuando sean marcadas como pagadas.
</div>

<?php include '../../includes/footer.php'; ?>