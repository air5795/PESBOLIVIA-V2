<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

$page_title = 'Gestión de Categorías';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener todas las categorías
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM productos WHERE id_categoria = c.id) as total_productos
          FROM categorias c 
          ORDER BY nombre ASC";
$categorias = mysqli_query($conexion, $query);

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
            <h1>Gestión de Categorías</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Categorías</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCategoria">
            <i class="fas fa-plus me-2"></i>
            Nueva Categoría
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-th-large me-2"></i>
            Lista de Categorías
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Icono</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($categoria = mysqli_fetch_assoc($categorias)): ?>
                    <tr>
                        <td><?php echo $categoria['id']; ?></td>
                        <td><strong><?php echo $categoria['nombre']; ?></strong></td>
                        <td><?php echo truncar_texto($categoria['descripcion'] ?? '-', 50); ?></td>
                        <td>
                            <?php if (!empty($categoria['icono'])): ?>
                                <i class="fas <?php echo $categoria['icono']; ?> fa-2x" style="color: #667eea;"></i>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info"><?php echo $categoria['total_productos']; ?></span>
                        </td>
                        <td><?php echo badge_estado($categoria['estado']); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editarCategoria(<?php echo $categoria['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($categoria['total_productos'] == 0): ?>
                                <button class="btn btn-outline-danger" onclick="confirmarEliminacion('categorias_eliminar.php?id=<?php echo $categoria['id']; ?>', '¿Eliminar la categoría <?php echo $categoria['nombre']; ?>?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-secondary" disabled title="No se puede eliminar, tiene productos asignados">
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

<!-- Modal Crear/Editar Categoría -->
<div class="modal fade" id="modalCategoria" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCategoriaTitle">
                    <i class="fas fa-plus me-2"></i>
                    Nueva Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategoria" method="POST" action="categorias_guardar.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="categoria_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" id="categoria_nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="categoria_descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Icono *</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="fas fa-image" id="icono-preview"></i></span>
                            <input type="text" class="form-control" name="icono" id="categoria_icono" placeholder="Ej: fa-futbol" required>
                        </div>
                        <div class="text-muted small">
                            <p class="mb-1 fw-bold">Iconos comunes (clic para usar):</p>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-futbol" title="Fútbol"><i class="fas fa-futbol"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-gamepad" title="Juego"><i class="fas fa-gamepad"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-tshirt" title="Kits"><i class="fas fa-tshirt"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-shoe-prints" title="Botines"><i class="fas fa-shoe-prints"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-trophy" title="Torneos"><i class="fas fa-trophy"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-users" title="Equipos"><i class="fas fa-users"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-star" title="Premium"><i class="fas fa-star"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-compact-disc" title="ISOs"><i class="fas fa-compact-disc"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-globe" title="Estadios"><i class="fas fa-globe"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-flag" title="Ligas"><i class="fas fa-flag"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-user" title="Rostros"><i class="fas fa-user"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-image" title="Gráficos"><i class="fas fa-image"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-music" title="Audios"><i class="fas fa-music"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-cogs" title="Herramientas"><i class="fas fa-cogs"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-download" title="Descargas"><i class="fas fa-download"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-store" title="Tienda"><i class="fas fa-store"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-server" title="Servidores"><i class="fas fa-server"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-laptop-code" title="Scripts"><i class="fas fa-laptop-code"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-file-archive" title="Archivos"><i class="fas fa-file-archive"></i></button>
                                <button type="button" class="btn btn-outline-secondary btn-sm icon-btn" data-icon="fa-video" title="Videos"><i class="fas fa-video"></i></button>
                            </div>
                            <p class="mb-0">
                                ¿No encuentras el que buscas? Explora los más de 2000 iconos en <a href="https://fontawesome.com/search?o=r&m=free" target="_blank" class="text-primary text-decoration-underline">FontAwesome</a> y pega su nombre (ej. <code>fa-futbol</code>).
                            </p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado" id="categoria_estado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = "
<script>
// Limpiar formulario al cerrar modal
$('#modalCategoria').on('hidden.bs.modal', function () {
    $('#formCategoria')[0].reset();
    $('#categoria_id').val('');
    $('#icono-preview').attr('class', 'fas fa-image');
    $('#modalCategoriaTitle').html('<i class=\"fas fa-plus me-2\"></i> Nueva Categoría');
});

// Actualizar preview de icono al escribir
$('#categoria_icono').on('input', function() {
    var iconClass = $(this).val();
    if (iconClass) {
        $('#icono-preview').attr('class', 'fas ' + iconClass);
    } else {
        $('#icono-preview').attr('class', 'fas fa-image');
    }
});

// Botones de iconos rápidos
$('.icon-btn').on('click', function() {
    var iconClass = $(this).data('icon');
    $('#categoria_icono').val(iconClass).trigger('input');
});

// Editar categoría
function editarCategoria(id) {
    $.ajax({
        url: 'categorias_obtener.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#categoria_id').val(data.categoria.id);
                $('#categoria_nombre').val(data.categoria.nombre);
                $('#categoria_descripcion').val(data.categoria.descripcion);
                $('#categoria_icono').val(data.categoria.icono).trigger('input');
                $('#categoria_estado').val(data.categoria.estado);
                
                $('#modalCategoriaTitle').html('<i class=\"fas fa-edit me-2\"></i> Editar Categoría');
                $('#modalCategoria').modal('show');
            } else {
                mostrarAlerta('error', 'Error', data.message);
            }
        },
        error: function() {
            mostrarAlerta('error', 'Error', 'No se pudo cargar la categoría');
        }
    });
}
</script>
";

include '../../includes/footer.php'; 
?>