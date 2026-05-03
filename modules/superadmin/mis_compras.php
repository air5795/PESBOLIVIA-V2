<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$page_title = 'Mis Compras Personales';
$id_usuario = Session::get_user_id();

// Obtener compras realizadas por el superadmin actual
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

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Mis Compras Personales</h1>
    <p class="text-muted">Aquí solo verás los productos que has adquirido tú como usuario.</p>
</div>

<div class="card">
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
                            <th>Acceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($compra = mysqli_fetch_assoc($compras)): ?>
                            <tr>
                                <td><?php echo formatear_fecha($compra['fecha_compra']); ?></td>
                                <td>
                                    <strong><?php echo $compra['producto_nombre']; ?></strong>
                                </td>
                                <td><?php echo formatear_monto($compra['monto_total']); ?></td>
                                <td><?php echo badge_estado($compra['estado']); ?></td>
                                <td>
                                    <?php if ($compra['estado'] === 'aprobado' || $compra['estado'] === 'entregado'): ?>
                                        <a href="<?php echo $compra['drive_link']; ?>" target="_blank" class="btn btn-sm btn-success">
                                            <i class="fab fa-google-drive"></i> Ir al Drive
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Esperando Aprobación</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-basket fa-3x mb-3 text-muted"></i>
                <p>No has realizado compras personales todavía.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
