<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mis Ventas';
$id_usuario = Session::get_user_id();

// Obtener ventas donde es editor del producto
$query = "SELECT c.*, p.nombre as producto_nombre, p.imagen,
          u.nombre as comprador_nombre, u.apellido as comprador_apellido,
          pe.porcentaje
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios u ON c.id_comprador = u.id
          INNER JOIN producto_editores pe ON p.id = pe.id_producto
          WHERE pe.id_editor = ?
          ORDER BY 
            CASE WHEN c.estado = 'pendiente' THEN 1 ELSE 2 END,
            c.fecha_compra DESC";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$ventas = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Estadísticas
$query_stats = "SELECT 
    COUNT(*) as total_ventas,
    SUM(c.monto_total * pe.porcentaje / 100) as total_comisiones,
    SUM(CASE WHEN c.estado = 'pendiente' THEN 1 ELSE 0 END) as ventas_pendientes,
    SUM(CASE WHEN c.estado = 'aprobado' THEN 1 ELSE 0 END) as ventas_aprobadas
    FROM compras c
    INNER JOIN productos p ON c.id_producto = p.id
    INNER JOIN producto_editores pe ON p.id = pe.id_producto
    WHERE pe.id_editor = ?";
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
            <h3><?php echo $stats['ventas_pendientes']; ?></h3>
            <p>Pedidos Pendientes</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="fas fa-check-double"></i>
            </div>
            <h3><?php echo $stats['ventas_aprobadas']; ?></h3>
            <p>Ventas Aprobadas</p>
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
                            <th>Mi Ganancia</th>
                            <th>Estado</th>
                            <th>Acciones</th>
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
                            <td>
                                <strong class="text-success"><?php echo formatear_monto(($venta['monto_total'] * $venta['porcentaje']) / 100); ?></strong>
                            </td>
                            <td>
                                <?php echo badge_estado($venta['estado']); ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary btn-detalle" 
                                        data-id="<?php echo $venta['id']; ?>" title="Ver Detalle / Gestionar">
                                    <i class="fas fa-eye"></i> Gestionar
                                </button>
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
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ver Comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-image me-2"></i>
                    Comprobante de Pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagenComprobante" src="" alt="Comprobante" 
                     style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle / Gestión -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" id="detalleContenido">
            <!-- Cargado vía AJAX -->
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Cargando detalles...</p>
            </div>
        </div>
    </div>
</div>

<?php 
$extra_js = "
<script>
$(document).ready(function() {
    $('.btn-detalle').on('click', function() {
        var id = $(this).data('id');
        $('#detalleContenido').load('compra_detalle_ajax.php?id=' + id, function() {
            new bootstrap.Modal(document.getElementById('modalDetalle')).show();
        });
    });
});

function verComprobante(url) {
    document.getElementById('imagenComprobante').src = url;
    new bootstrap.Modal(document.getElementById('modalComprobante')).show();
}

function aprobarCompra(id, codigo) {
    Swal.fire({
        title: '¿Aprobar Compra?',
        html: 'Código: <strong>' + codigo + '</strong><br>Se enviará el acceso al producto al comprador.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'compra_aprobar.php?id=' + id;
        }
    });
}

function rechazarCompra(id, codigo) {
    Swal.fire({
        title: '¿Rechazar Compra?',
        text: 'Código: ' + codigo + '. El comprador será notificado.',
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Motivo del rechazo (opcional)...',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Rechazar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            var motivo = result.value;
            window.location.href = 'compra_rechazar.php?id=' + id + '&observaciones=' + encodeURIComponent(motivo);
        }
    });
}
</script>
";

include '../../includes/footer.php'; 
?>