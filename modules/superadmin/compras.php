<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$page_title = 'Gestión de Compras';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Filtro de estado
$filtro_estado = $_GET['estado'] ?? 'todas';

// Obtener compras con información relacionada
$query = "SELECT c.*, 
          p.nombre as producto_nombre, 
          p.precio as producto_precio,
          u.nombre as comprador_nombre, 
          u.apellido as comprador_apellido,
          u.email as comprador_email,
          tp.nombre as tipo_pago_nombre,
          ap.nombre as aprobador_nombre,
          pe.id_editor as editor_producto,
          pe.porcentaje as porcentaje_editor
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios u ON c.id_comprador = u.id
          INNER JOIN tipos_pago tp ON c.id_tipo_pago = tp.id
          LEFT JOIN usuarios ap ON c.id_aprobador = ap.id
          LEFT JOIN producto_editores pe ON p.id = pe.id_producto";

if ($filtro_estado !== 'todas') {
    $query .= " WHERE c.estado = '$filtro_estado'";
}

$query .= " ORDER BY 
            CASE c.estado 
                WHEN 'pendiente' THEN 1 
                WHEN 'aprobado' THEN 2
                WHEN 'entregado' THEN 3
                WHEN 'rechazado' THEN 4 
            END,
            c.fecha_compra DESC";

$compras = mysqli_query($conexion, $query);

// Estadísticas
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN c.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN c.estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
    SUM(CASE WHEN c.estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados,
    SUM(CASE WHEN c.estado IN ('aprobado', 'entregado') AND (pe.id_editor IS NULL OR pe.porcentaje < 100) THEN c.monto_total ELSE 0 END) as total_ventas
    FROM compras c
    LEFT JOIN producto_editores pe ON c.id_producto = pe.id_producto";
$result_stats = mysqli_query($conexion, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

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
    <div>
        <h1>Gestión de Compras</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Compras</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3><?php echo $stats['total']; ?></h3>
            <p>Total Compras</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="fas fa-clock"></i>
            </div>
            <h3><?php echo $stats['pendientes']; ?></h3>
            <p>Pendientes</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-check"></i>
            </div>
            <h3><?php echo $stats['aprobados']; ?></h3>
            <p>Aprobados</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <h3><?php echo formatear_monto($stats['total_ventas']); ?></h3>
            <p>Total Ventas</p>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="mb-2">Filtrar por Estado:</h6>
                <div class="btn-group" role="group">
                    <a href="?estado=todas" class="btn btn-sm btn-outline-primary <?php echo($filtro_estado === 'todas') ? 'active' : ''; ?>">
                        Todas (<?php echo $stats['total']; ?>)
                    </a>
                    <a href="?estado=pendiente" class="btn btn-sm btn-outline-warning <?php echo($filtro_estado === 'pendiente') ? 'active' : ''; ?>">
                        Pendientes (<?php echo $stats['pendientes']; ?>)
                    </a>
                    <a href="?estado=aprobado" class="btn btn-sm btn-outline-success <?php echo($filtro_estado === 'aprobado') ? 'active' : ''; ?>">
                        Aprobados (<?php echo $stats['aprobados']; ?>)
                    </a>
                    <a href="?estado=rechazado" class="btn btn-sm btn-outline-danger <?php echo($filtro_estado === 'rechazado') ? 'active' : ''; ?>">
                        Rechazados (<?php echo $stats['rechazados']; ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Compras -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            <?php
if ($filtro_estado === 'todas') {
    echo 'Todas las Compras';
}
else {
    echo 'Compras ' . ucfirst($filtro_estado) . 's';
}
?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Comprador</th>
                        <th>Producto</th>
                        <th>Monto</th>
                        <th>Método Pago</th>
                        <th>Comprobante</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($compra = mysqli_fetch_assoc($compras)): ?>
                    <tr>
                        <td>
                            <strong><?php echo $compra['codigo_compra']; ?></strong>
                        </td>
                        <td>
                            <?php echo $compra['comprador_nombre'] . ' ' . $compra['comprador_apellido']; ?>
                            <br><small class="text-muted"><?php echo $compra['comprador_email']; ?></small>
                        </td>
                        <td><?php echo truncar_texto($compra['producto_nombre'], 30); ?></td>
                        <td><strong><?php echo formatear_monto($compra['monto_total']); ?></strong></td>
                        <td><?php echo $compra['tipo_pago_nombre']; ?></td>
                        <td>
                            <?php if (!empty($compra['comprobante'])): ?>
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="verComprobante('<?php echo BASE_URL . '/' . $compra['comprobante']; ?>')">
                                    <i class="fas fa-file-image"></i> Ver
                                </button>
                            <?php
    else: ?>
                                <span class="text-muted">-</span>
                            <?php
    endif; ?>
                        </td>
                        <td>
                            <?php echo formatear_fecha($compra['fecha_compra'], true); ?>
                            <br><small class="text-muted"><?php echo tiempo_transcurrido($compra['fecha_compra']); ?></small>
                        </td>
                        <td><?php echo badge_estado($compra['estado']); ?></td>
                        <td>
                            <?php if ($compra['estado'] === 'pendiente'): ?>
                                <?php if (empty($compra['editor_producto']) || $compra['porcentaje_editor'] < 100): ?>
                                    <!-- Superadmin aprueba productos propios (o donde recibe pago directo) -->
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-success" 
                                                onclick="aprobarCompra(<?php echo $compra['id']; ?>, '<?php echo $compra['codigo_compra']; ?>')"
                                                title="Aprobar">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-danger" 
                                                onclick="rechazarCompra(<?php echo $compra['id']; ?>, '<?php echo $compra['codigo_compra']; ?>')"
                                                title="Rechazar">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                <?php
        else: ?>
                                    <!-- Producto de venta directa del editor - Superadmin NO aprueba -->
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Valida el Editor
                                    </small>
                                <?php
        endif; ?>
                            <?php
    else: ?>
                                <small class="text-muted">
                                    <?php if (!empty($compra['aprobador_nombre'])): ?>
                                        Por: <?php echo $compra['aprobador_nombre']; ?>
                                    <?php
        endif; ?>
                                </small>
                            <?php
    endif; ?>
                        </td>
                    </tr>
                    <?php
endwhile; ?>
                </tbody>
            </table>
        </div>
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

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2"></i>
                    Rechazar Compra
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="compras_procesar.php">
                <input type="hidden" name="accion" value="rechazar">
                <input type="hidden" name="id" id="rechazar_id">
                
                <div class="modal-body">
                    <p>Código: <strong id="rechazar_codigo"></strong></p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        El comprador recibirá una notificación del rechazo.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo del Rechazo (Opcional)</label>
                        <textarea class="form-control" name="observaciones" rows="3" 
                                  placeholder="Ej: Comprobante no válido, monto incorrecto, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>
                        Rechazar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php

$extra_js = "
<script>
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
            window.location.href = 'compras_procesar.php?accion=aprobar&id=' + id;
        }
    });
}

function rechazarCompra(id, codigo) {
    document.getElementById('rechazar_id').value = id;
    document.getElementById('rechazar_codigo').textContent = codigo;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}
</script>
";

include '../../includes/footer.php';

?>