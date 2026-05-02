<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id_producto = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Verificar que el producto pertenece al editor y es tipo tutorial
$query = "SELECT p.* FROM productos p
          INNER JOIN producto_editores pe ON p.id = pe.id_producto
          WHERE p.id = ? AND pe.id_editor = ? AND p.tipo_producto = 'tutorial'";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id_producto, $id_editor);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Tutorial no encontrado o no tienes permiso";
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

$page_title = 'Videos del Tutorial';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener videos del tutorial
$query_videos = "SELECT * FROM tutorial_videos WHERE id_producto = ? ORDER BY orden ASC, id ASC";
$stmt = mysqli_prepare($conexion, $query_videos);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$videos = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$total_videos = mysqli_num_rows($videos);

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
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-video me-2"></i>
                Videos del Tutorial
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="productos.php">Mis Productos</a></li>
                    <li class="breadcrumb-item active">Videos</li>
                </ol>
            </nav>
        </div>
        <a href="productos.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver
        </a>
    </div>
</div>

<!-- Info del Tutorial -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                    <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" 
                         alt="<?php echo $producto['nombre']; ?>" 
                         style="max-width: 100%; border-radius: 8px;">
                <?php
else: ?>
                    <i class="fas fa-graduation-cap fa-4x text-muted"></i>
                <?php
endif; ?>
            </div>
            <div class="col-md-7">
                <h4><?php echo $producto['nombre']; ?></h4>
                <p class="text-muted mb-0"><?php echo truncar_texto($producto['descripcion'], 150); ?></p>
            </div>
            <div class="col-md-3 text-center">
                <h2 class="text-primary mb-0"><?php echo $total_videos; ?></h2>
                <small class="text-muted">Videos agregados</small>
            </div>
        </div>
    </div>
</div>

<!-- Instrucciones -->
<div class="alert alert-info">
    <h6><i class="fas fa-lightbulb me-2"></i>Cómo obtener el ID de YouTube:</h6>
    <p class="mb-2">En la URL <code>youtube.com/watch?v=<strong>dQw4w9WgXcQ</strong></code>, el ID es <strong>dQw4w9WgXcQ</strong></p>
    <small>
        <i class="fas fa-shield-alt me-1"></i>
        Configura tus videos como <strong>"Oculto"</strong> en YouTube.
    </small>
</div>

<!-- Formulario Agregar Video -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-plus-circle me-2"></i>
            Agregar Video
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="tutorial_videos_guardar.php">
            <input type="hidden" name="id_producto" value="<?php echo $id_producto; ?>">
            
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">Título *</label>
                    <input type="text" class="form-control" name="titulo" required>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">ID YouTube *</label>
                    <input type="text" class="form-control" name="youtube_id" required id="inputYoutubeId">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Duración</label>
                    <input type="text" class="form-control" name="duracion" placeholder="10:35">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Orden</label>
                    <input type="number" class="form-control" name="orden" value="<?php echo $total_videos + 1; ?>">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>
                Agregar
            </button>
        </form>
    </div>
</div>

<!-- Lista de Videos -->
<?php if ($total_videos > 0): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Videos (<?php echo $total_videos; ?>)</h5>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php while ($video = mysqli_fetch_assoc($videos)): ?>
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col-md-1 text-center">
                        <h4 class="mb-0">#<?php echo $video['orden']; ?></h4>
                    </div>
                    <div class="col-md-2">
                        <img src="https://img.youtube.com/vi/<?php echo $video['youtube_id']; ?>/mqdefault.jpg" 
                             style="width: 100%; border-radius: 4px;">
                    </div>
                    <div class="col-md-6">
                        <h6><?php echo $video['titulo']; ?></h6>
                        <small><code><?php echo $video['youtube_id']; ?></code></small>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="confirmarEliminacion('tutorial_videos_eliminar.php?id=<?php echo $video['id']; ?>', 'Eliminar')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php
    endwhile; ?>
        </div>
    </div>
</div>
<?php
else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-video fa-4x text-muted mb-3"></i>
        <h4>Sin videos</h4>
    </div>
</div>
<?php
endif; ?>

<?php include '../../includes/footer.php'; ?>