<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mis Ventas';
$id_usuario = Session::get_user_id();

// Obtener ventas donde tiene comisión
$query = "SELECT c.*, p.nombre as producto_nombre, p.imagen,
          u.nombre as comprador_nombre, u.apellido as comprador_apellido,
          pag.monto as comision, pag.porcentaje, pag.estado as estado_pago
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios u ON c.id_comprador = u.id
          INNER JOIN pagos_editores pag ON c.id = pag.id_compra
          WHERE pag.id_editor = ? AND c.estado IN ('aprobado', 'entregado')
          ORDER BY c.fecha_compra DESC";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$ventas = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Estadísticas
$query_stats = "SELECT 
    COUNT(*) as total_ventas,
    SUM(pag.monto) as total_comisiones,
    SUM(CASE WHEN pag.estado = 'pendiente' AND pag.porcentaje < 100 THEN pag.monto ELSE 0 END) as pendientes,
    SUM(CASE WHEN pag.estado = 'pagado' AND pag.porcentaje < 100 THEN pag.monto ELSE 0 END) as pagadas,
    SUM(CASE WHEN c.estado = 'aprobado' THEN 1 ELSE 0 END) as ventas_aprobadas,
    SUM(CASE WHEN c.estado = 'entregado' THEN 1 ELSE 0 END) as ventas_entregadas
    FROM compras c
    INNER JOIN pagos_editores pag ON c.id = pag.id_compra
    WHERE pag.id_editor = ? AND c.estado IN ('aprobado', 'entregado')";
$stmt = mysqli_prepare($conexion, $query_stats);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mis Ventas</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Ventas</li>
        </ol>
    </nav>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3><?php echo $stats['total_ventas']; ?></h3>
            <p>Total Ventas</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h3><?php echo formatear_monto($stats['total_comisiones']); ?></h3>
            <p>Total Generado</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="fas fa-clock"></i>
            </div>
            <h3><?php echo formatear_monto($stats['pendientes']); ?></h3>
            <p>Por Cobrar al Admin</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="fas fa-check-double"></i>
            </div>
            <h3><?php echo formatear_monto($stats['pagadas']); ?></h3>
            <p>Cobrado Exitosamente</p>
        </div>
    </div>
</div>

<!-- Tabla de Ventas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            Historial de Ventas
        </h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($ventas) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Fecha</th>
                            <th>Producto Vendido</th>
                            <th>Comprador</th>
                            <th>Mi %</th>
                            <th>Mi Ganancia</th>
                            <th>Estado de Envío</th>
                            <th>Pago (Admin)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
    mysqli_data_seek($ventas, 0);
    while ($venta = mysqli_fetch_assoc($ventas)):
?>
                        <tr>
                            <td><strong><?php echo $venta['codigo_compra']; ?></strong></td>
                            <td><?php echo formatear_fecha($venta['fecha_compra'], true); ?></td>
                            <td><?php echo truncar_texto($venta['producto_nombre'], 30); ?></td>
                            <td><?php echo $venta['comprador_nombre'] . ' ' . $venta['comprador_apellido']; ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($venta['porcentaje'], 2); ?>%</span>
                            </td>
                            <td>
                                <strong class="text-success"><?php echo formatear_monto($venta['comision']); ?></strong>
                            </td>
                            <td>
                                <?php if ($venta['estado'] === 'aprobado'): ?>
                                    <span class="badge bg-primary">Aprobado</span>
                                <?php
        elseif ($venta['estado'] === 'entregado'): ?>
                                    <span class="badge bg-success">Entregado</span>
                                <?php
        endif; ?>
                            </td>
                            <td>
                                <?php if ($venta['porcentaje'] >= 100 && $venta['estado_pago'] === 'pendiente'): ?>
                                    <span class="badge bg-success" title="El pago fue directo a tu método">Cobrado (Directo)</span>
                                <?php
        elseif ($venta['estado_pago'] === 'pendiente'): ?>
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php
        else: ?>
                                    <span class="badge bg-success">Pagado por Admin</span>
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
                <i class="fas fa-chart-line fa-4x mb-3"></i>
                <h4>No tienes ventas registradas</h4>
                <p>Cuando se vendan tus productos, las verás aquí.</p>
            </div>
        <?php
endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>