<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Permitir cualquier usuario logueado
if (!Session::is_logged_in()) {
    redirect('../../login.php');
}

if (!isset($_GET['id'])) {
    redirect('descargas.php');
}

$id_producto = intval($_GET['id']);
$id_usuario = Session::get_user_id();

// Verificar que el usuario compró este tutorial
$query_check = "SELECT c.*, p.nombre, p.descripcion, p.imagen
                FROM compras c
                INNER JOIN productos p ON c.id_producto = p.id
                WHERE c.id_producto = ? AND c.id_comprador = ? 
                AND c.estado IN ('aprobado', 'entregado') 
                AND p.tipo_producto = 'tutorial'";
$stmt = mysqli_prepare($conexion, $query_check);
mysqli_stmt_bind_param($stmt, "ii", $id_producto, $id_usuario);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "No tienes acceso a este tutorial";
    redirect('descargas.php');
}

mysqli_stmt_close($stmt);

// Obtener videos del tutorial
$query_videos = "SELECT * FROM tutorial_videos 
                 WHERE id_producto = ? 
                 ORDER BY orden ASC, id ASC";
$stmt = mysqli_prepare($conexion, $query_videos);

if (!$stmt) {
    $_SESSION['error'] = "Error al cargar videos. Asegúrate de haber ejecutado el script SQL de tutoriales.";
    redirect('descargas.php');
}

mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$videos = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

$total_videos = mysqli_num_rows($videos);

// Video seleccionado (primer video por defecto)
$video_actual_id = isset($_GET['video']) ? intval($_GET['video']) : 0;

$page_title = $producto['nombre'];
include '../../includes/header.php';
?>

<style>
.playlist-item {
    cursor: pointer;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}
.playlist-item:hover {
    background: #f8f9fa;
    border-left-color: #667eea;
}
.playlist-item.active {
    background: #e7f1ff;
    border-left-color: #667eea;
}
.video-container {
    position: relative;
    background: #000;
    border-radius: 8px;
    overflow: hidden;
}

/* Ocultar elementos de YouTube */
.video-wrapper {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 */
    height: 0;
    overflow: hidden;
}

.video-wrapper iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

/* Capa para ocultar título y botones de YouTube */
.video-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, transparent 100%);
    z-index: 10;
    pointer-events: none;
}

.video-overlay-bottom {
    position: absolute;
    bottom: 50px;
    right: 0;
    width: 200px;
    height: 50px;
    background: rgba(0,0,0,0.9);
    z-index: 10;
    pointer-events: none;
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1>
                <i class="fas fa-graduation-cap me-2"></i>
                <?php echo $producto['nombre']; ?>
            </h1>
            <p class="text-muted mb-0"><?php echo $total_videos; ?> videos disponibles</p>
        </div>
        <a href="descargas.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver a Descargas
        </a>
    </div>
</div>

<?php if ($total_videos > 0): ?>
<div class="row">
    <!-- Video Player -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-body p-0">
                <div class="video-container">
                    <div class="video-wrapper">
                        <?php
    // Obtener el video a reproducir
    mysqli_data_seek($videos, 0);
    $video_mostrar = null;
    $index = 0;

    while ($v = mysqli_fetch_assoc($videos)) {
        if ($video_actual_id == 0 && $index == 0) {
            $video_mostrar = $v;
            break;
        }
        elseif ($v['id'] == $video_actual_id) {
            $video_mostrar = $v;
            break;
        }
        $index++;
    }

    if ($video_mostrar):
?>
                            <iframe src="https://www.youtube.com/embed/<?php echo $video_mostrar['youtube_id']; ?>?autoplay=0&rel=0&modestbranding=1&showinfo=0&controls=1&fs=1&iv_load_policy=3&cc_load_policy=0&disablekb=0&playsinline=1" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" 
                                    allowfullscreen
                                    loading="lazy"></iframe>
                            
                            <!-- Capa para ocultar título del video -->
                            <div class="video-overlay"></div>
                            
                            <!-- Capa para ocultar botones "Ver más tarde" y "Compartir" -->
                            <div class="video-overlay-bottom"></div>
                        <?php
    endif; ?>
                    </div>
                </div>
            </div>
            <?php if ($video_mostrar): ?>
            <div class="card-footer bg-white">
                <h5 class="mb-1"><?php echo $video_mostrar['titulo']; ?></h5>
                <?php if (!empty($video_mostrar['descripcion'])): ?>
                    <p class="text-muted mb-0"><?php echo $video_mostrar['descripcion']; ?></p>
                <?php
        endif; ?>
            </div>
            <?php
    endif; ?>
        </div>
    </div>
    
    <!-- Playlist -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Reproducción
                </h5>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                <div class="list-group list-group-flush">
                    <?php
    mysqli_data_seek($videos, 0);
    $numero = 1;
    while ($video = mysqli_fetch_assoc($videos)):
        $is_active = ($video_mostrar && $video['id'] == $video_mostrar['id']) ? 'active' : '';
?>
                    <a href="?id=<?php echo $id_producto; ?>&video=<?php echo $video['id']; ?>" 
                       class="list-group-item list-group-item-action playlist-item <?php echo $is_active; ?>">
                        <div class="d-flex align-items-start">
                            <div class="me-3">
                                <span class="badge bg-primary"><?php echo $numero; ?></span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo $video['titulo']; ?></h6>
                                <?php if (!empty($video['duracion'])): ?>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $video['duracion']; ?>
                                    </small>
                                <?php
        endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php
        $numero++;
    endwhile;
?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
else: ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-video-slash fa-4x text-muted mb-3"></i>
        <h4>Este tutorial no tiene videos aún</h4>
        <p class="text-muted">El creador del tutorial aún no ha agregado videos.</p>
    </div>
</div>
<?php
endif; ?>

<?php include '../../includes/footer.php'; ?>