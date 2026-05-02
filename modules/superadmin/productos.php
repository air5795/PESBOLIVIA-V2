<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$page_title = 'Gestión de Productos';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener productos con información de categoría
$query = "SELECT p.*, c.nombre as categoria_nombre,
          (SELECT COUNT(*) FROM compras WHERE id_producto = p.id) as total_ventas
          FROM productos p
          LEFT JOIN categorias c ON p.id_categoria = c.id
          ORDER BY p.fecha_creacion DESC";
$productos = mysqli_query($conexion, $query);

include '../../includes/header.php';
?>

<?php if (!empty($success)): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php echo $success; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-circle me-2"></i>
    <?php echo $error; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>Gestión de Productos</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Productos</li>
                </ol>
            </nav>
        </div>
        <a href="productos_crear.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Nuevo Producto
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-box me-2"></i>
            Lista de Productos
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Versión</th>
                        <th>Ventas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($producto = mysqli_fetch_assoc($productos)): ?>
                    <tr>
                        <td><?php echo $producto['id']; ?></td>
                        <td>
                            <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                                <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" 
                                     alt="<?php echo $producto['nombre']; ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $producto['nombre']; ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                <?php echo $producto['categoria_nombre'] ?? 'Sin categoría'; ?>
                            </span>
                        </td>
                        <td><strong><?php echo formatear_monto($producto['precio']); ?></strong></td>
                        <td><?php echo $producto['version'] ?? '-'; ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo $producto['total_ventas']; ?></span>
                        </td>
                        <td><?php echo badge_estado($producto['estado']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="productos_editar.php?id=<?php echo $producto['id']; ?>" 
                                   class="btn btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="productos_editores.php?id=<?php echo $producto['id']; ?>" 
                                   class="btn btn-outline-success" title="Asignar Editores">
                                    <i class="fas fa-users"></i>
                                </a>
                                <?php if ($producto['total_ventas'] == 0): ?>
                                <button class="btn btn-outline-danger" 
                                        onclick="confirmarEliminacion('productos_eliminar.php?id=<?php echo $producto['id']; ?>', '¿Eliminar el producto <?php echo $producto['nombre']; ?>?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled 
                                        title="No se puede eliminar, tiene ventas">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>