<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id = intval($_GET['id']);
$id_editor = Session::get_user_id();

// Obtener producto (solo si pertenece al editor)
$query = "SELECT p.* FROM productos p
          INNER JOIN producto_editores pe ON p.id = pe.id_producto
          WHERE p.id = ? AND pe.id_editor = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "ii", $id, $id_editor);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Producto no encontrado o no tienes permiso para editarlo";
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

$page_title = 'Editar Producto';

// Obtener categorías activas
$query_categorias = "SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
$categorias = mysqli_query($conexion, $query_categorias);

// Obtener métodos de pago del editor
$query_metodos = "SELECT * FROM tipos_pago WHERE id_editor = ? AND estado = 'activo' ORDER BY nombre ASC";
$stmt_metodos = mysqli_prepare($conexion, $query_metodos);
mysqli_stmt_bind_param($stmt_metodos, "i", $id_editor);
mysqli_stmt_execute($stmt_metodos);
$metodos_pago = mysqli_stmt_get_result($stmt_metodos);
mysqli_stmt_close($stmt_metodos);

// Métodos seleccionados
$metodos_seleccionados = explode(',', $producto['id_tipo_pago'] ?? '');

// Obtener imágenes del producto
$query_imagenes = "SELECT * FROM producto_imagenes WHERE id_producto = ? ORDER BY es_principal DESC, orden ASC";
$stmt_img = mysqli_prepare($conexion, $query_imagenes);
mysqli_stmt_bind_param($stmt_img, "i", $id);
mysqli_stmt_execute($stmt_img);
$imagenes_producto = mysqli_stmt_get_result($stmt_img);
$imagenes_array = [];
while ($img = mysqli_fetch_assoc($imagenes_producto)) {
    $imagenes_array[] = $img;
}
mysqli_stmt_close($stmt_img);

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Editar Producto</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="productos.php">Mis Productos</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
</div>

<form method="POST" action="productos_guardar.php" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Información del Producto</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto *</label>
                        <input type="text" class="form-control" name="nombre" required
                               value="<?php echo htmlspecialchars($producto['nombre']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categoría *</label>
                        <select class="form-select" name="id_categoria" required>
                            <option value="">Seleccione una categoría...</option>
                            <?php while ($cat = mysqli_fetch_assoc($categorias)): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo($producto['id_categoria'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $cat['nombre']; ?>
                                </option>
                            <?php
endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <textarea class="form-control" name="descripcion" rows="4" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                    
                    <!-- TIPO DE PRODUCTO -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Producto *</label>
                            <select class="form-select" name="tipo_producto" id="tipoProducto" required onchange="toggleTipoProducto()">
                                <option value="normal" <?php echo($producto['tipo_producto'] == 'normal') ? 'selected' : ''; ?>>Producto Descargable</option>
                                <option value="tutorial" <?php echo($producto['tipo_producto'] == 'tutorial') ? 'selected' : ''; ?>>Video Tutorial (YouTube)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">¿Es Gratuito? *</label>
                            <select class="form-select" name="es_gratuito" id="esGratuito" required onchange="toggleTipoProducto()">
                                <option value="no" <?php echo($producto['es_gratuito'] == 'no' || empty($producto['es_gratuito'])) ? 'selected' : ''; ?>>Producto de Pago</option>
                                <option value="si" <?php echo($producto['es_gratuito'] == 'si') ? 'selected' : ''; ?>>Producto Gratuito</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Método de Pago -->
                    <div class="mb-3 border rounded p-3 bg-light" id="campoMetodoPago">
                        <label class="form-label fw-bold">Métodos de Pago Habilitados</label>
                        <div class="row">
                            <?php
mysqli_data_seek($metodos_pago, 0);
while ($metodo = mysqli_fetch_assoc($metodos_pago)):
?>
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="id_tipo_pago[]" value="<?php echo $metodo['id']; ?>" id="metodo_<?php echo $metodo['id']; ?>" <?php echo in_array($metodo['id'], $metodos_seleccionados) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="metodo_<?php echo $metodo['id']; ?>">
                                            <?php echo htmlspecialchars($metodo['nombre']); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php
endwhile; ?>
                        </div>
                        <small class="text-muted mt-2 d-block"><i class="fas fa-info-circle me-1"></i>Si no seleccionas ninguno, se habilitarán todos tus métodos activos por defecto.</small>
                    </div>
                    
                    <!-- Precio -->
                    <div class="row">
                        <div class="col-md-6 mb-3" id="campoPrecio">
                            <label class="form-label">Precio (Bs.) *</label>
                            <input type="number" class="form-control" name="precio" id="inputPrecio" step="0.01" min="0" required
                                   value="<?php echo $producto['precio']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Versión</label>
                            <input type="text" class="form-control" name="version"
                                   value="<?php echo htmlspecialchars($producto['version'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Requisitos del Sistema</label>
                        <input type="text" class="form-control" name="requisitos"
                               value="<?php echo htmlspecialchars($producto['requisitos'] ?? ''); ?>">
                    </div>
                    
                    <!-- Google Drive -->
                    <div class="mb-3" id="campoDrive">
                        <label class="form-label">Link de Google Drive *</label>
                        <input type="url" class="form-control" name="drive_link" id="inputDrive" required
                               value="<?php echo htmlspecialchars($producto['drive_link']); ?>">
                    </div>
                    
                    <!-- Descarga Directa -->
                    <div class="mb-3" id="campoDescargaDirecta" style="display: none;">
                        <label class="form-label">Link de Descarga Directa *</label>
                        <input type="url" class="form-control" name="link_descarga_directa" id="inputDescargaDirecta" value="<?php echo htmlspecialchars($producto['link_descarga_directa'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado" required>
                            <option value="activo" <?php echo($producto['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo($producto['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-images me-2"></i>Imágenes del Producto</h5>
                </div>
                <div class="card-body">
                    <!-- Imágenes existentes -->
                    <?php if (!empty($imagenes_array)): ?>
                    <label class="form-label fw-bold mb-2">Imágenes actuales (<?php echo count($imagenes_array); ?>)</label>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="imagenesExistentes">
                        <?php foreach ($imagenes_array as $img): ?>
                        <div class="position-relative" id="img-container-<?php echo $img['id']; ?>" style="width:100px;height:100px;border-radius:8px;overflow:hidden;border:3px solid <?php echo $img['es_principal'] ? '#27CCA0' : '#dee2e6'; ?>;">
                            <img src="<?php echo BASE_URL . '/' . $img['imagen']; ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php if ($img['es_principal']): ?>
                            <span style="position:absolute;bottom:0;left:0;right:0;background:rgba(39,204,160,0.9);color:#000;text-align:center;font-size:0.65rem;padding:2px;font-weight:700;">PRINCIPAL</span>
                            <?php endif; ?>
                            <button type="button" onclick="eliminarImagen(<?php echo $img['id']; ?>)" class="btn btn-sm" style="position:absolute;top:2px;right:2px;background:rgba(220,53,69,0.9);color:#fff;border:none;border-radius:50%;width:22px;height:22px;padding:0;font-size:0.7rem;line-height:22px;">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php if (!$img['es_principal']): ?>
                            <button type="button" onclick="hacerPrincipal(<?php echo $img['id']; ?>)" class="btn btn-sm" style="position:absolute;top:2px;left:2px;background:rgba(39,204,160,0.9);color:#000;border:none;border-radius:50%;width:22px;height:22px;padding:0;font-size:0.7rem;line-height:22px;" title="Hacer principal">
                                <i class="fas fa-star"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Agregar nuevas imágenes -->
                    <div class="mb-3">
                        <label class="form-label">Agregar más imágenes</label>
                        <input type="file" class="form-control" name="imagenes[]" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               id="inputImagenes" multiple>
                        <small class="text-muted">Selecciona imágenes adicionales</small>
                        <input type="hidden" name="imagen_principal_index" id="imagenPrincipalIndex" value="-1">
                    </div>
                    
                    <div id="previewImagenes" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Actualizar Producto
                        </button>
                        <a href="productos.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php

$extra_js = "
<script>
// Multi-image preview for new images
document.getElementById('inputImagenes').addEventListener('change', function(e) {
    const files = e.target.files;
    const preview = document.getElementById('previewImagenes');
    preview.innerHTML = '';
    const maxNew = 20;
    
    if (files.length > maxNew) {
        alert('Solo puedes agregar ' + maxNew + ' imágenes más');
        this.value = '';
        return;
    }
    
    Array.from(files).forEach((file, index) => {
        if (file.size > 2097152) {
            alert('La imagen ' + file.name + ' supera 2MB');
            return;
        }
        const reader = new FileReader();
        reader.onload = function(ev) {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'position:relative;width:100px;height:100px;border-radius:8px;overflow:hidden;cursor:pointer;border:3px solid #dee2e6;';
            const img = document.createElement('img');
            img.src = ev.target.result;
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            wrapper.appendChild(img);
            wrapper.onclick = function() {
                document.getElementById('imagenPrincipalIndex').value = index;
                document.querySelectorAll('#previewImagenes > div').forEach(d => d.style.borderColor = '#dee2e6');
                document.querySelectorAll('#previewImagenes .badge-p').forEach(b => b.remove());
                this.style.borderColor = '#27CCA0';
                const badge = document.createElement('span');
                badge.className = 'badge-p';
                badge.style.cssText = 'position:absolute;bottom:0;left:0;right:0;background:rgba(39,204,160,0.9);color:#000;text-align:center;font-size:0.65rem;padding:2px;font-weight:700;';
                badge.textContent = 'PRINCIPAL';
                this.appendChild(badge);
            };
            preview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });
});

function eliminarImagen(idImagen) {
    if (!confirm('¿Eliminar esta imagen?')) return;
    
    fetch('" . BASE_URL . "/ajax/eliminar_imagen_producto.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_imagen=' + idImagen
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('img-container-' + idImagen).remove();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(err => alert('Error de conexión'));
}

function hacerPrincipal(idImagen) {
    fetch('" . BASE_URL . "/ajax/cambiar_imagen_principal.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_imagen=' + idImagen
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(err => alert('Error de conexión'));
}
</script>
<script>
function toggleTipoProducto() {
    const tipoProducto = document.getElementById('tipoProducto').value;
    const esGratuito = document.getElementById('esGratuito').value;
    const campoPrecio = document.getElementById('campoPrecio');
    const campoDrive = document.getElementById('campoDrive');
    const campoDescargaDirecta = document.getElementById('campoDescargaDirecta');
    const campoMetodoPago = document.getElementById('campoMetodoPago');
    const inputPrecio = document.getElementById('inputPrecio');
    const inputDrive = document.getElementById('inputDrive');
    const inputDescargaDirecta = document.getElementById('inputDescargaDirecta');
    
    if (tipoProducto === 'tutorial') {
        campoDrive.style.display = 'none';
        inputDrive.required = false;
        campoDescargaDirecta.style.display = 'none';
        inputDescargaDirecta.required = false;
    } else {
        if (esGratuito === 'si') {
            campoDrive.style.display = 'none';
            inputDrive.required = false;
            campoDescargaDirecta.style.display = 'block';
            inputDescargaDirecta.required = true;
        } else {
            campoDrive.style.display = 'block';
            inputDrive.required = true;
            campoDescargaDirecta.style.display = 'none';
            inputDescargaDirecta.required = false;
        }
    }
    
    if (esGratuito === 'si') {
        campoPrecio.style.display = 'none';
        inputPrecio.required = false;
        if(inputPrecio.value === '') inputPrecio.value = '0';
        if (campoMetodoPago) campoMetodoPago.style.display = 'none';
    } else {
        campoPrecio.style.display = 'block';
        inputPrecio.required = true;
        if (campoMetodoPago) campoMetodoPago.style.display = 'block';
    }
}
// Init fields on load
window.addEventListener('load', toggleTipoProducto);
</script>
";

include '../../includes/footer.php';

?>