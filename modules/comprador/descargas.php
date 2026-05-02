<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_COMPRADOR);

$page_title = 'Mis Descargas';
$id_usuario = Session::get_user_id();

// Obtener productos comprados y aprobados
$query = "SELECT c.*, p.nombre as producto_nombre, p.imagen, p.drive_link, p.descripcion, p.version,
          (SELECT COUNT(*) FROM descargas WHERE id_compra = c.id) as veces_descargado,
          (SELECT MAX(fecha_descarga) FROM descargas WHERE id_compra = c.id) as ultima_descarga
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          WHERE c.id_comprador = ? AND c.estado IN ('aprobado', 'entregado')
          ORDER BY c.fecha_compra DESC";

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$productos = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$total_productos = mysqli_num_rows($productos);

include '../../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>
            <i class="fas fa-download me-2"></i>
            Mis Descargas
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Descargas</li>
            </ol>
        </nav>
    </div>
</div>

<div class="alert alert-info">
    <i class="fas fa-info-circle me-2"></i>
    Tienes acceso a <strong><?php echo $total_productos; ?></strong> producto(s) comprado(s).
</div>

<?php if ($total_productos > 0): ?>
    <div class="row">
        <?php while ($producto = mysqli_fetch_assoc($productos)): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100">
                <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                    <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" 
                         alt="<?php echo $producto['producto_nombre']; ?>" 
                         class="card-img-top" 
                         style="height: 200px; object-fit: cover;">
                <?php else: ?>
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                         style="height: 200px;">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                <?php endif; ?>
                
                <div class="card-body">
                    <h5 class="card-title"><?php echo $producto['producto_nombre']; ?></h5>
                    
                    <?php if (!empty($producto['version'])): ?>
                        <p class="mb-2">
                            <span class="badge bg-info">Versión <?php echo $producto['version']; ?></span>
                        </p>
                    <?php endif; ?>
                    
                    <p class="card-text text-muted">
                        <?php echo truncar_texto($producto['descripcion'], 100); ?>
                    </p>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Comprado: <?php echo formatear_fecha($producto['fecha_compra']); ?>
                        </small>
                    </div>
                    
                    <?php if ($producto['veces_descargado'] > 0): ?>
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-download me-1"></i>
                            Descargado <?php echo $producto['veces_descargado']; ?> vez(ces)
                        </small>
                        <?php if (!empty($producto['ultima_descarga'])): ?>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Última: <?php echo tiempo_transcurrido($producto['ultima_descarga']); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <a href="registrar_descarga.php?id=<?php echo $producto['id']; ?>" 
                       class="btn btn-success w-100" 
                       target="_blank">
                        <i class="fas fa-download me-2"></i>
                        Descargar Producto
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-download fa-4x text-muted mb-3"></i>
            <h4>No tienes productos para descargar</h4>
            <p class="text-muted">Una vez que tus compras sean aprobadas, podrás descargar los productos aquí.</p>
            <a href="../../tienda.php" class="btn btn-primary">
                <i class="fas fa-store me-2"></i>
                Ir a la Tienda
            </a>
        </div>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>