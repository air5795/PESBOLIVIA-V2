<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Mi Dashboard';
$id_usuario = Session::get_user_id();

// Verificar que el editor esté aprobado
$query_editor = "SELECT editor_aprobado FROM usuarios WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query_editor);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$editor_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($editor_data['editor_aprobado'] !== 'aprobado') {
?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cuenta Pendiente - <?php echo NOMBRE_SISTEMA; ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="bg-light">
        <div class="container">
            <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body text-center p-5">
                            <?php if ($editor_data['editor_aprobado'] === 'pendiente'): ?>
                                <i class="fas fa-clock fa-4x text-warning mb-4"></i>
                                <h3>Cuenta en Revisión</h3>
                                <p class="text-muted">Tu solicitud de editor está siendo revisada. Te notificaremos cuando sea aprobada (24-48 horas).</p>
                            <?php
    else: ?>
                                <i class="fas fa-times-circle fa-4x text-danger mb-4"></i>
                                <h3>Cuenta No Aprobada</h3>
                                <p class="text-muted">Tu solicitud de editor fue rechazada. Contacta al administrador para más información.</p>
                            <?php
    endif; ?>
                            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-primary mt-3">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Cerrar Sesión
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Estadísticas del editor
// Productos donde es editor asignado
$query_stats = "SELECT 
    COUNT(DISTINCT pe.id_producto) as total_productos,
    COUNT(DISTINCT c.id) as total_ventas,
    SUM(CASE WHEN pag.estado = 'pendiente' AND pag.porcentaje < 100 THEN pag.monto ELSE 0 END) as comisiones_pendientes,
    SUM(CASE WHEN pag.estado = 'pagado' AND pag.porcentaje < 100 THEN pag.monto ELSE 0 END) as comisiones_pagadas
    FROM producto_editores pe
    LEFT JOIN compras c ON pe.id_producto = c.id_producto AND c.estado IN ('aprobado', 'entregado')
    LEFT JOIN pagos_editores pag ON c.id = pag.id_compra AND pag.id_editor = pe.id_editor
    WHERE pe.id_editor = ?";
$stmt = mysqli_prepare($conexion, $query_stats);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Productos del editor
$query_productos = "SELECT p.*, c.nombre as categoria_nombre,
                    (SELECT COUNT(*) FROM compras WHERE id_producto = p.id AND estado IN ('aprobado', 'entregado')) as ventas
                    FROM productos p
                    LEFT JOIN categorias c ON p.id_categoria = c.id
                    INNER JOIN producto_editores pe ON p.id = pe.id_producto
                    WHERE pe.id_editor = ?
                    ORDER BY p.fecha_creacion DESC
                    LIMIT 5";
$stmt = mysqli_prepare($conexion, $query_productos);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$productos_recientes = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Últimas ventas
$query_ventas = "SELECT c.*, p.nombre as producto_nombre, u.nombre as comprador_nombre, u.apellido as comprador_apellido,
                 pag.monto as comision, pag.porcentaje
                 FROM compras c
                 INNER JOIN productos p ON c.id_producto = p.id
                 INNER JOIN usuarios u ON c.id_comprador = u.id
                 INNER JOIN pagos_editores pag ON c.id = pag.id_compra
                 WHERE pag.id_editor = ? AND c.estado IN ('aprobado', 'entregado')
                 ORDER BY c.fecha_compra DESC
                 LIMIT 5";
$stmt = mysqli_prepare($conexion, $query_ventas);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$ultimas_ventas = mysqli_stmt_get_result($stmt);
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
                <i class="fas fa-box"></i>
            </div>
            <h3><?php echo $stats['total_productos']; ?></h3>
            <p>Mis Productos</p>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h3><?php echo $stats['total_ventas']; ?></h3>
            <p>Ventas Totales</p>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="fas fa-clock"></i>
            </div>
            <h3><?php echo formatear_monto($stats['comisiones_pendientes']); ?></h3>
            <p>Comisiones Pendientes</p>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3><?php echo formatear_monto($stats['comisiones_pagadas']); ?></h3>
            <p>Comisiones Pagadas</p>
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
                    <a href="productos.php" class="btn btn-primary">
                        <i class="fas fa-box me-2"></i>
                        Mis Productos
                    </a>
                    <a href="ventas.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Ver Ventas
                    </a>
                    <a href="mis_comisiones.php" class="btn btn-outline-success">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Mis Comisiones
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Productos Recientes -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>
                    Productos Recientes
                </h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($productos_recientes) > 0): ?>
                    <?php while ($prod = mysqli_fetch_assoc($productos_recientes)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong><?php echo truncar_texto($prod['nombre'], 25); ?></strong>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-shopping-cart me-1"></i>
                                <?php echo $prod['ventas']; ?> ventas
                            </small>
                        </div>
                        <span class="badge bg-<?php echo($prod['estado'] == 'activo') ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($prod['estado']); ?>
                        </span>
                    </div>
                    <?php
    endwhile; ?>
                    <a href="productos.php" class="btn btn-sm btn-outline-primary w-100 mt-2">Ver Todos</a>
                <?php
else: ?>
                    <p class="text-muted text-center mb-0">No tienes productos aún.</p>
                <?php
endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Últimas Ventas -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Últimas Ventas
                </h5>
                <a href="ventas.php" class="btn btn-sm btn-primary">Ver todas</a>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($ultimas_ventas) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Comprador</th>
                                    <th>Tu Comisión</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($venta = mysqli_fetch_assoc($ultimas_ventas)): ?>
                                <tr>
                                    <td><strong><?php echo $venta['codigo_compra']; ?></strong></td>
                                    <td><?php echo truncar_texto($venta['producto_nombre'], 25); ?></td>
                                    <td><?php echo $venta['comprador_nombre'] . ' ' . substr($venta['comprador_apellido'], 0, 1) . '.'; ?></td>
                                    <td>
                                        <strong class="text-success"><?php echo formatear_monto($venta['comision']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo $venta['porcentaje']; ?>%</small>
                                    </td>
                                    <td>
                                        <small><?php echo tiempo_transcurrido($venta['fecha_compra']); ?></small>
                                    </td>
                                </tr>
                                <?php
    endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <p>Aún no tienes ventas registradas.</p>
                    </div>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>