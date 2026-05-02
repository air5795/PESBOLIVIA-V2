<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id_producto = intval($_GET['id']);

// Obtener producto
$query = "SELECT * FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Producto no encontrado";
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

$page_title = 'Asignar Editores';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener editores asignados a este producto
$query_asignados = "SELECT pe.*, u.nombre, u.apellido, u.email
                    FROM producto_editores pe
                    INNER JOIN usuarios u ON pe.id_editor = u.id
                    WHERE pe.id_producto = ?";
$stmt = mysqli_prepare($conexion, $query_asignados);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$editores_asignados = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Calcular porcentaje total asignado
$query_total = "SELECT SUM(porcentaje) as total FROM producto_editores WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query_total);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$result_total = mysqli_stmt_get_result($stmt);
$total_porcentaje = mysqli_fetch_assoc($result_total)['total'] ?? 0;
mysqli_stmt_close($stmt);

// Obtener editores activos que NO están asignados
$query_disponibles = "SELECT u.id, u.nombre, u.apellido, u.email 
                      FROM usuarios u
                      WHERE u.rol = 'editor' 
                      AND u.estado = 'activo'
                      AND u.editor_aprobado = 'aprobado'
                      AND u.id NOT IN (
                          SELECT id_editor FROM producto_editores WHERE id_producto = ?
                      )
                      ORDER BY u.nombre ASC";
$stmt = mysqli_prepare($conexion, $query_disponibles);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$editores_disponibles = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

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
    <h1>Asignar Editores al Producto</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="productos.php">Productos</a></li>
            <li class="breadcrumb-item active">Asignar Editores</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Información del Producto -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-box me-2"></i>
                    <?php echo $producto['nombre']; ?>
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Precio:</strong> <?php echo formatear_monto($producto['precio']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Estado:</strong> <?php echo badge_estado($producto['estado']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Editores Asignados -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Editores Asignados
                </h5>
                <span class="badge bg-<?php echo ($total_porcentaje <= 100) ? 'success' : 'danger'; ?> fs-6">
                    Total: <?php echo number_format($total_porcentaje, 2); ?>%
                </span>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($editores_asignados) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Editor</th>
                                    <th>Email</th>
                                    <th>Porcentaje</th>
                                    <th>Ganancia por Venta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($editores_asignados, 0);
                                while ($editor = mysqli_fetch_assoc($editores_asignados)): 
                                    $ganancia = ($producto['precio'] * $editor['porcentaje']) / 100;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $editor['nombre'] . ' ' . $editor['apellido']; ?></strong>
                                    </td>
                                    <td><?php echo $editor['email']; ?></td>
                                    <td>
                                        <span class="badge bg-info fs-6"><?php echo number_format($editor['porcentaje'], 2); ?>%</span>
                                    </td>
                                    <td>
                                        <strong><?php echo formatear_monto($ganancia); ?></strong>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editarPorcentaje(<?php echo $editor['id']; ?>, <?php echo $editor['porcentaje']; ?>, '<?php echo $editor['nombre'] . ' ' . $editor['apellido']; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmarEliminacion('productos_editores_eliminar.php?id=<?php echo $editor['id']; ?>&producto=<?php echo $id_producto; ?>', '¿Quitar a <?php echo $editor['nombre']; ?>?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_porcentaje > 100): ?>
                    <div class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>¡Atención!</strong> El porcentaje total supera el 100%. Ajusta los porcentajes.
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>No hay editores asignados a este producto.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Agregar Editor -->
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Agregar Editor
                </h5>
            </div>
            <div class="card-body">
                <?php if (mysqli_num_rows($editores_disponibles) > 0): ?>
                    <form method="POST" action="productos_editores_agregar.php">
                        <input type="hidden" name="id_producto" value="<?php echo $id_producto; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Seleccionar Editor *</label>
                            <select class="form-select" name="id_editor" required>
                                <option value="">Seleccione...</option>
                                <?php while ($editor = mysqli_fetch_assoc($editores_disponibles)): ?>
                                    <option value="<?php echo $editor['id']; ?>">
                                        <?php echo $editor['nombre'] . ' ' . $editor['apellido']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Porcentaje de Comisión *</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="porcentaje" 
                                       step="0.01" min="0.01" max="100" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">
                                Disponible: <?php echo number_format(100 - $total_porcentaje, 2); ?>%
                            </small>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-calculator me-2"></i>
                            <small>
                                <strong>Ejemplo:</strong><br>
                                Precio: <?php echo formatear_monto($producto['precio']); ?><br>
                                Con 25% → Ganancia: <?php echo formatear_monto($producto['precio'] * 0.25); ?>
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-2"></i>
                            Asignar Editor
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay más editores disponibles para asignar.
                    </div>
                <?php endif; ?>
                
                <a href="productos.php" class="btn btn-secondary w-100 mt-3">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a Productos
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Porcentaje -->
<div class="modal fade" id="modalEditarPorcentaje" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Editar Porcentaje
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="productos_editores_actualizar.php">
                <input type="hidden" name="id" id="editar_id">
                <input type="hidden" name="id_producto" value="<?php echo $id_producto; ?>">
                
                <div class="modal-body">
                    <p id="editar_nombre"></p>
                    
                    <div class="mb-3">
                        <label class="form-label">Nuevo Porcentaje *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="porcentaje" 
                                   id="editar_porcentaje" step="0.01" min="0.01" max="100" required>
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = "
<script>
function editarPorcentaje(id, porcentaje, nombre) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_porcentaje').value = porcentaje;
    document.getElementById('editar_nombre').innerHTML = '<strong>Editor:</strong> ' + nombre;
    
    new bootstrap.Modal(document.getElementById('modalEditarPorcentaje')).show();
}
</script>
";

include '../../includes/footer.php'; 
?>