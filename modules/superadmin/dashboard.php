<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

$page_title = 'Dashboard';

// Obtener estadísticas
$stats = [];

// Total de usuarios
$query = "SELECT COUNT(*) as total FROM usuarios WHERE estado = 'activo'";
$result = mysqli_query($conexion, $query);
$stats['usuarios'] = mysqli_fetch_assoc($result)['total'];

// Total de productos
$query = "SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'";
$result = mysqli_query($conexion, $query);
$stats['productos'] = mysqli_fetch_assoc($result)['total'];

// Total de compras
$query = "SELECT COUNT(*) as total FROM compras";
$result = mysqli_query($conexion, $query);
$stats['compras_total'] = mysqli_fetch_assoc($result)['total'];

// Compras pendientes
$query = "SELECT COUNT(*) as total FROM compras WHERE estado = 'pendiente'";
$result = mysqli_query($conexion, $query);
$stats['compras_pendientes'] = mysqli_fetch_assoc($result)['total'];

// Ventas totales (Donde el admín recibe la plata u obtiene parte de ella)
$query = "SELECT SUM(c.monto_total) as total 
          FROM compras c
          LEFT JOIN producto_editores pe ON c.id_producto = pe.id_producto
          WHERE c.estado IN ('aprobado', 'entregado')
          AND (pe.id_editor IS NULL OR pe.porcentaje < 100)";
$result = mysqli_query($conexion, $query);
$stats['ventas_totales'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Ventas del mes
$query = "SELECT SUM(c.monto_total) as total 
          FROM compras c
          LEFT JOIN producto_editores pe ON c.id_producto = pe.id_producto
          WHERE c.estado IN ('aprobado', 'entregado') 
          AND MONTH(c.fecha_compra) = MONTH(CURRENT_DATE()) 
          AND YEAR(c.fecha_compra) = YEAR(CURRENT_DATE())
          AND (pe.id_editor IS NULL OR pe.porcentaje < 100)";
$result = mysqli_query($conexion, $query);
$stats['ventas_mes'] = mysqli_fetch_assoc($result)['total'] ?? 0;

// Últimas compras
$query = "SELECT c.*, p.nombre as producto_nombre, u.nombre as comprador_nombre, u.apellido as comprador_apellido
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios u ON c.id_comprador = u.id
          ORDER BY c.fecha_compra DESC
          LIMIT 10";
$ultimas_compras = mysqli_query($conexion, $query);

// Productos más vendidos
$query = "SELECT p.nombre, COUNT(c.id) as ventas, SUM(c.monto_total) as total
          FROM compras c
          INNER JOIN productos p ON c.id_producto = p.id
          WHERE c.estado IN ('aprobado', 'entregado')
          GROUP BY c.id_producto
          ORDER BY ventas DESC
          LIMIT 5";
$productos_top = mysqli_query($conexion, $query);

// Top Editores (Volumen generado y deuda pendiente)
$query_editores = "SELECT 
            u.nombre, u.apellido, u.rol,
            SUM(c.monto_total) as volumen_generado,
            SUM(CASE WHEN pag.estado = 'pendiente' AND pe.porcentaje < 100 THEN pag.monto ELSE 0 END) as deuda_admin
          FROM usuarios u
          INNER JOIN producto_editores pe ON u.id = pe.id_editor
          INNER JOIN compras c ON pe.id_producto = c.id_producto
          LEFT JOIN pagos_editores pag ON c.id = pag.id_compra AND pag.id_editor = u.id
          WHERE c.estado IN ('aprobado', 'entregado')
          GROUP BY u.id
          HAVING u.rol = 'editor'
          ORDER BY volumen_generado DESC
          LIMIT 10";
$top_editores = mysqli_query($conexion, $query_editores);

include '../../includes/header.php';
?>

<style>
/* Estilos Premium para Dashboard Superadmin */
.admin-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 20px;
    padding: 2.5rem;
    color: white;
    margin-bottom: 2rem;
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
    position: relative;
    overflow: hidden;
}
.admin-hero::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: radial-gradient(circle at right, rgba(99, 102, 241, 0.2) 0%, transparent 70%);
}
.hero-title {
    font-weight: 800;
    font-size: 2.2rem;
    letter-spacing: -1px;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.stat-glass-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: left;
    box-shadow: 0 10px 40px rgba(0,0,0,0.03);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    align-items: center;
}
.stat-glass-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
}
.stat-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}
.stat-primary { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.stat-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.stat-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.stat-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.2rem;
    color: #1e293b;
}
.stat-info p {
    margin: 0;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.custom-card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.03);
    background: #fff;
    overflow: hidden;
}
.custom-card-header {
    background: transparent;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    padding: 1.5rem;
    font-weight: 700;
    color: #1e293b;
}

/* Editores List */
.editor-list-item {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    padding: 1rem 1.2rem;
    transition: background 0.2s;
}
.editor-list-item:hover {
    background: #f8fafc;
}
.editor-list-item:last-child {
    border-bottom: none;
}
.editor-avatar {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.volumen-badge {
    background: #f1f5f9;
    color: #475569;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}
.deuda-text {
    font-size: 0.85rem;
    font-weight: 700;
    color: #ef4444;
}
</style>

<div class="page-header d-flex justify-content-between align-items-center mb-0">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active fw-bold text-primary">Panel de Control Principal</li>
        </ol>
    </nav>
</div>

<!-- Hero Section -->
<div class="admin-hero mt-3">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="hero-title">Vista General Admin</h1>
            <p class="text-light opacity-75 mb-0" style="font-size: 1.1rem;">Supervisa el rendimiento global, ventas de afiliados y finanzas.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <div class="d-inline-block text-start bg-white bg-opacity-10 p-3 rounded-3 border border-white border-opacity-25">
                <small class="d-block text-uppercase text-light opacity-75 fw-bold" style="font-size: 0.75rem;">Recaudación Neta Total</small>
                <h3 class="mb-0 text-white fw-bold"><?php echo formatear_monto($stats['ventas_totales']); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card">
            <div class="stat-icon-wrapper stat-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['usuarios']; ?></h3>
                <p>Usuarios Activos</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card">
            <div class="stat-icon-wrapper stat-purple">
                <i class="fas fa-box-open"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['productos']; ?></h3>
                <p>Productos Catálogo</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card">
            <div class="stat-icon-wrapper stat-warning">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['compras_total']; ?></h3>
                <p>Total Compras</p>
                <?php if ($stats['compras_pendientes'] > 0): ?>
                    <small class="text-warning fw-bold mt-1 d-block"><i class="fas fa-clock me-1"></i><?php echo $stats['compras_pendientes']; ?> por revisar</small>
                <?php
endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card">
            <div class="stat-icon-wrapper stat-success">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo formatear_monto($stats['ventas_mes']); ?></h3>
                <p>Ingresos del Mes</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Main Column: Ultimas Compras & Top Productos -->
    <div class="col-xl-8 col-lg-7 mb-4">
        <!-- Últimas Compras -->
        <div class="custom-card mb-4">
            <div class="custom-card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-receipt me-2 text-primary"></i>Actividad Reciente</span>
                <a href="compras.php" class="btn btn-sm btn-light fw-bold text-primary rounded-pill px-3">Ver Todas</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-muted" style="font-size: 0.8rem; text-transform: uppercase;">
                            <tr>
                                <th class="ps-4">Operación</th>
                                <th>Cliente</th>
                                <th>Monto</th>
                                <th>Estado</th>
                                <th class="text-end pe-4">Hace</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($ultimas_compras) > 0): ?>
                                <?php while ($compra = mysqli_fetch_assoc($ultimas_compras)): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?php echo truncar_texto($compra['producto_nombre'], 25); ?></div>
                                        <small class="text-muted font-monospace"><?php echo $compra['codigo_compra']; ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px; font-weight: bold;">
                                                <?php echo strtoupper(substr($compra['comprador_nombre'], 0, 1)); ?>
                                            </div>
                                            <span class="fw-semibold text-dark text-nowrap"><?php echo $compra['comprador_nombre'] . ' ' . $compra['comprador_apellido']; ?></span>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success px-2 py-1 border border-success border-opacity-25"><?php echo formatear_monto($compra['monto_total']); ?></span></td>
                                    <td><?php echo badge_estado($compra['estado']); ?></td>
                                    <td class="text-end pe-4">
                                        <small class="text-muted fw-semibold">
                                            <?php echo tiempo_transcurrido($compra['fecha_compra']); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php
    endwhile; ?>
                            <?php
else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No hay compras registradas
                                    </td>
                                </tr>
                            <?php
endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Productos Más Vendidos -->
        <div class="custom-card">
            <div class="custom-card-header">
                <i class="fas fa-star me-2 text-warning"></i>Top Productos Globales
            </div>
            <div class="card-body p-0">
                <div class="row g-0">
                    <?php if (mysqli_num_rows($productos_top) > 0): ?>
                        <?php while ($producto = mysqli_fetch_assoc($productos_top)): ?>
                        <div class="col-md-6 border-bottom border-end p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark"><?php echo truncar_texto($producto['nombre'], 30); ?></h6>
                                    <small class="text-muted fw-bold">
                                        <i class="fas fa-shopping-bag me-1"></i><?php echo $producto['ventas']; ?> Ventas
                                    </small>
                                </div>
                                <span class="fw-bold text-success" style="font-size: 1.1rem;">
                                    <?php echo formatear_monto($producto['total']); ?>
                                </span>
                            </div>
                        </div>
                        <?php
    endwhile; ?>
                    <?php
else: ?>
                        <div class="p-4 text-center text-muted w-100">
                            No hay productos con ventas
                        </div>
                    <?php
endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column: Top Editores -->
    <div class="col-xl-4 col-lg-5 mb-4">
        <div class="custom-card h-100">
            <div class="custom-card-header bg-gradient bg-light text-dark d-flex justify-content-between align-items-center border-bottom-0 pb-2">
                <div>
                    <h5 class="mb-0 fw-bold"><i class="fas fa-medal me-2" style="color: #f59e0b;"></i>Top Editores</h5>
                    <small class="text-muted">Ranking por Vol. de Ventas</small>
                </div>
            </div>
            <div class="list-group list-group-flush pt-2">
                <?php if (mysqli_num_rows($top_editores) > 0): ?>
                    <?php $pos = 1;
    while ($editor = mysqli_fetch_assoc($top_editores)):
        $is_top = $pos <= 3;
        $medal_color = $pos == 1 ? '#fbbf24' : ($pos == 2 ? '#94a3b8' : ($pos == 3 ? '#b45309' : '#cbd5e1'));
?>
                        <div class="editor-list-item d-flex align-items-center">
                            <div class="me-3 position-relative">
                                <div class="editor-avatar shadow-sm" style="background: <?php echo $is_top ? 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)' : 'linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); color: #475569;'; ?>">
                                    <?php echo strtoupper(substr($editor['nombre'], 0, 1)); ?>
                                </div>
                                <?php if ($is_top): ?>
                                    <div class="position-absolute top-0 start-100 translate-middle" style="color: <?php echo $medal_color; ?>; font-size: 1.2rem; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.2));">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                <?php
        endif; ?>
                            </div>
                            
                            <div class="flex-grow-1 min-width-0">
                                <h6 class="mb-0 fw-bold text-dark text-truncate"><?php echo $editor['nombre'] . ' ' . $editor['apellido']; ?></h6>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <span class="volumen-badge">
                                        +<?php echo formatear_monto($editor['volumen_generado']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="text-end ms-2">
                                <?php if ($editor['deuda_admin'] > 0): ?>
                                    <div class="deuda-text mb-1" title="Deuda pendiente">
                                        <?php echo formatear_monto($editor['deuda_admin']); ?>
                                    </div>
                                    <a href="comisiones.php" class="btn btn-sm btn-danger px-2 py-0" style="font-size: 0.7rem; font-weight: bold; border-radius: 4px;">PAGAR</a>
                                <?php
        else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 pb-0"><i class="fas fa-check-circle"></i> Al Día</span>
                                <?php
        endif; ?>
                            </div>
                        </div>
                    <?php $pos++;
    endwhile; ?>
                <?php
else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-user-slash fa-3x mb-3 opacity-25"></i>
                        <p class="mb-0">Aún no hay editores con ventas registradas.</p>
                    </div>
                <?php
endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>