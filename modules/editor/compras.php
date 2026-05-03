<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mis Compras (Historial)';
$id_usuario = Session::get_user_id();

// Obtener compras realizadas por el usuario actual
$query = "SELECT c.*, p.nombre as producto_nombre, p.imagen, p.drive_link,
          tp.nombre as metodo_pago
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN tipos_pago tp ON c.id_tipo_pago = tp.id
          WHERE c.id_comprador = ?
          ORDER BY c.fecha_compra DESC";

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$compras = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Estadísticas de gasto personal
$query_stats = "SELECT 
    COUNT(*) as total_compras,
    SUM(CASE WHEN estado = 'aprobado' THEN monto_total ELSE 0 END) as total_gastado
    FROM compras 
    WHERE id_comprador = ?";
$stmt = mysqli_prepare($conexion, $query_stats);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$res_stats = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($res_stats);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mis Compras</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Mis Compras</li>
        </ol>
    </nav>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4facfe; color: white;">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h3><?php echo $stats['total_compras']; ?></h3>
            <p>Productos Adquiridos</p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat-card">
            <div class="stat-icon" style="background: #43e97b; color: white;">
                <i class="fas fa-wallet"></i>
            </div>
            <h3><?php echo formatear_monto($stats['total_gastado']); ?></h3>
            <p>Inversión Total</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Historial de Mis Adquisiciones</h5>
    </div>
    <div class="card-body">
        <?php if (mysqli_num_rows($compras) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Acceso / Descarga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($compra = mysqli_fetch_assoc($compras)): ?>
                            <tr>
                                <td><?php echo formatear_fecha($compra['fecha_compra']); ?></td>
                                <td>
                                    <strong><?php echo $compra['producto_nombre']; ?></strong>
                                    <br><small class="text-muted">Vía <?php echo $compra['metodo_pago']; ?></small>
                                </td>
                                <td><?php echo formatear_monto($compra['monto_total']); ?></td>
                                <td><?php echo badge_estado($compra['estado']); ?></td>
                                <td>
                                    <?php if ($compra['estado'] === 'aprobado' || $compra['estado'] === 'entregado'): ?>
                                        <a href="<?php echo $compra['drive_link']; ?>" target="_blank" class="btn btn-sm btn-success">
                                            <i class="fab fa-google-drive me-1"></i> Acceder al Drive
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Disponible tras aprobación</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                <p>Aún no has realizado ninguna compra personal.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>