<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    exit('ID no proporcionado');
}

$id_compra = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Obtener detalle de la compra (solo si es de su producto)
$query = "SELECT c.*, p.nombre as producto_nombre, p.imagen as producto_imagen,
          u.nombre as comprador_nombre, u.apellido as comprador_apellido, 
          u.email as comprador_email,
          tp.nombre as tipo_pago_nombre, tp.instrucciones as tipo_pago_instrucciones
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios u ON c.id_comprador = u.id
          LEFT JOIN tipos_pago tp ON c.id_tipo_pago = tp.id
          WHERE c.id_producto IN (
              SELECT p2.id 
              FROM productos p2
              INNER JOIN producto_editores pe ON p2.id = pe.id_producto
              WHERE pe.id_editor = ?
          ) AND c.id = ?";
$stmt = mysqli_prepare($conexion, $query);

if (!$stmt) {
    exit('Error en la consulta: ' . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, "ii", $id_editor, $id_compra);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$compra = mysqli_fetch_assoc($result)) {
    exit('Compra no encontrada');
}

mysqli_stmt_close($stmt);
?>

<div class="row">
    <div class="col-md-6">
        <h6>Información de la Compra</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Código:</th>
                <td><strong><?php echo $compra['codigo_compra']; ?></strong></td>
            </tr>
            <tr>
                <th>Fecha:</th>
                <td><?php echo date('d/m/Y H:i', strtotime($compra['fecha_compra'])); ?></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><?php echo badge_estado($compra['estado']); ?></td>
            </tr>

        </table>
        
        <h6 class="mt-4">Comprador</h6>
        <table class="table table-sm">
            <tr>
                <th width="40%">Nombre:</th>
                <td><?php echo $compra['comprador_nombre'] . ' ' . $compra['comprador_apellido']; ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo $compra['comprador_email']; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6>Producto</h6>
        <div class="text-center mb-3">
            <?php if (!empty($compra['producto_imagen']) && file_exists('../../' . $compra['producto_imagen'])): ?>
                <img src="../../<?php echo $compra['producto_imagen']; ?>" 
                     alt="<?php echo $compra['producto_nombre']; ?>"
                     style="max-width: 200px; border-radius: 8px;">
            <?php
endif; ?>
            <p class="mt-2"><strong><?php echo $compra['producto_nombre']; ?></strong></p>
        </div>
        
        <h6>Método de Pago</h6>
        <p><strong><?php echo $compra['tipo_pago_nombre'] ?? 'N/A'; ?></strong></p>
        
        <h6>Comprobante</h6>
        <?php if (!empty($compra['comprobante']) && file_exists('../../' . $compra['comprobante'])): ?>
            <a href="../../<?php echo $compra['comprobante']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-file-image me-1"></i>
                Ver Comprobante
            </a>
        <?php
else: ?>
            <p class="text-muted">Sin comprobante</p>
        <?php
endif; ?>
    </div>
</div>