<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Crear Producto';

// Obtener categorías activas
$query_categorias = "SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
$categorias = mysqli_query($conexion, $query_categorias);

// Obtener métodos de pago del editor
$id_editor = Session::get_user_id();
$query_metodos = "SELECT * FROM tipos_pago WHERE id_editor = ? AND estado = 'activo' ORDER BY nombre ASC";
$stmt = mysqli_prepare($conexion, $query_metodos);
mysqli_stmt_bind_param($stmt, "i", $id_editor);
mysqli_stmt_execute($stmt);
$metodos_pago = mysqli_stmt_get_result($stmt);
$num_metodos = mysqli_num_rows($metodos_pago);
mysqli_stmt_close($stmt);

include '../../includes/header.php';
?>

<?php if ($num_metodos === 0): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-lock me-2"></i>
    <strong>Función Bloqueada:</strong> No puedes crear productos de pago porque no tienes métodos de pago configurados. 
    Solo puedes crear productos gratuitos hasta que <a href="tipos_pago_crear.php" class="alert-link">agregues un método de pago aquí</a>.
</div>
<?php
endif; ?>

<div class="page-header">
    <h1>Crear Nuevo Producto</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="productos.php">Mis Productos</a></li>
            <li class="breadcrumb-item active">Crear</li>
        </ol>
    </nav>
</div>

<form method="POST" action="productos_guardar.php" enctype="multipart/form-data">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Información del Producto</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Producto *</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categoría *</label>
                        <select class="form-select" name="id_categoria" required>
                            <option value="">Seleccione una categoría...</option>
                            <?php
mysqli_data_seek($categorias, 0);
while ($cat = mysqli_fetch_assoc($categorias)):
?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo $cat['nombre']; ?>
                                </option>
                            <?php
endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción *</label>
                        <textarea class="form-control" name="descripcion" rows="4" required></textarea>
                    </div>
                    
                    <!-- TIPO DE PRODUCTO -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Producto *</label>
                            <select class="form-select" name="tipo_producto" id="tipoProducto" required onchange="toggleTipoProducto()">
                                <option value="normal">Producto Descargable</option>
                                <option value="tutorial">Video Tutorial (YouTube)</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">¿Es Gratuito? *</label>
                            <select class="form-select" name="es_gratuito" id="esGratuito" required onchange="toggleTipoProducto()">
                                <option value="no">Producto de Pago</option>
                                <option value="si">Producto Gratuito</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Alerta Tutorial -->
                    <div class="alert alert-info" id="alertaTutorial" style="display: none;">
                        <h6><i class="fas fa-lightbulb me-2"></i>Cómo crear un Video Tutorial:</h6>
                        <ol class="mb-2">
                            <li>Sube tus videos a YouTube con privacidad <strong>"Oculto"</strong></li>
                            <li>Una vez creado el producto, podrás <strong>agregar los videos</strong></li>
                            <li>Los compradores verán los videos <strong>solo en tu plataforma</strong></li>
                        </ol>
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            No necesitas el ID ahora, lo agregarás después.
                        </small>
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
                                        <input class="form-check-input" type="checkbox" name="id_tipo_pago[]" value="<?php echo $metodo['id']; ?>" id="metodo_<?php echo $metodo['id']; ?>">
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
                            <input type="number" class="form-control" name="precio" id="inputPrecio" step="0.01" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Versión</label>
                            <input type="text" class="form-control" name="version">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Requisitos</label>
                        <input type="text" class="form-control" name="requisitos">
                    </div>
                    
                    <!-- Google Drive (solo productos normales de pago) -->
                    <div class="mb-3" id="campoDrive">
                        <label class="form-label">Link de Google Drive *</label>
                        <input type="url" class="form-control" name="drive_link" id="inputDrive" required>
                    </div>
                    
                    <!-- Descarga Directa (solo gratuitos normales) -->
                    <div class="mb-3" id="campoDescargaDirecta" style="display: none;">
                        <label class="form-label">Link de Descarga Directa *</label>
                        <input type="url" class="form-control" name="link_descarga_directa" id="inputDescargaDirecta">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado" required>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
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
                    <input type="file" class="form-control" name="imagenes[]" accept="image/jpeg,image/png,image/jpg,image/webp" id="inputImagenes" multiple>
                    <small class="text-muted d-block mt-1"><i class="fas fa-info-circle me-1"></i>Sube múltiples imágenes. Click en una para marcarla como principal.</small>
                    <input type="hidden" name="imagen_principal_index" id="imagenPrincipalIndex" value="0">
                    <div id="previewImagenes" class="mt-3 d-flex flex-wrap gap-2"></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>
                        Crear Producto
                    </button>
                    <a href="productos.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php

$extra_js = "
<script>
// Multi-image preview
document.getElementById('inputImagenes').addEventListener('change', function(e) {
    const files = e.target.files;
    const preview = document.getElementById('previewImagenes');
    preview.innerHTML = '';
    
    if (files.length > 20) {
        alert('Máximo 20 imágenes a la vez');
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
            wrapper.style.cssText = 'position:relative;width:100px;height:100px;border-radius:8px;overflow:hidden;cursor:pointer;border:3px solid ' + (index === 0 ? '#27CCA0' : '#dee2e6');
            wrapper.setAttribute('data-index', index);
            wrapper.onclick = function() {
                document.getElementById('imagenPrincipalIndex').value = index;
                document.querySelectorAll('#previewImagenes > div').forEach(d => d.style.borderColor = '#dee2e6');
                document.querySelectorAll('#previewImagenes .badge-principal').forEach(b => b.remove());
                this.style.borderColor = '#27CCA0';
                const badge = document.createElement('span');
                badge.className = 'badge-principal';
                badge.style.cssText = 'position:absolute;bottom:0;left:0;right:0;background:rgba(39,204,160,0.9);color:#000;text-align:center;font-size:0.65rem;padding:2px;font-weight:700;';
                badge.textContent = 'PRINCIPAL';
                this.appendChild(badge);
            };
            const img = document.createElement('img');
            img.src = ev.target.result;
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            wrapper.appendChild(img);
            if (index === 0) {
                const badge = document.createElement('span');
                badge.className = 'badge-principal';
                badge.style.cssText = 'position:absolute;bottom:0;left:0;right:0;background:rgba(39,204,160,0.9);color:#000;text-align:center;font-size:0.65rem;padding:2px;font-weight:700;';
                badge.textContent = 'PRINCIPAL';
                wrapper.appendChild(badge);
            }
            preview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });
});

function toggleTipoProducto() {
    const tipoProducto = document.getElementById('tipoProducto').value;
    const esGratuito = document.getElementById('esGratuito').value;
    const campoPrecio = document.getElementById('campoPrecio');
    const campoDrive = document.getElementById('campoDrive');
    const campoDescargaDirecta = document.getElementById('campoDescargaDirecta');
    const campoMetodoPago = document.getElementById('campoMetodoPago');
    const alertaTutorial = document.getElementById('alertaTutorial');
    const inputPrecio = document.getElementById('inputPrecio');
    const inputDrive = document.getElementById('inputDrive');
    const inputDescargaDirecta = document.getElementById('inputDescargaDirecta');
    
    if (tipoProducto === 'tutorial') {
        alertaTutorial.style.display = 'block';
        campoDrive.style.display = 'none';
        inputDrive.required = false;
        campoDescargaDirecta.style.display = 'none';
        inputDescargaDirecta.required = false;
    } else {
        alertaTutorial.style.display = 'none';
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
    
    const tieneMetodos = <?php echo ($num_metodos > 0) ? 'true' : 'false'; ?>;
    const selectGratuito = document.getElementById('esGratuito');

    if (!tieneMetodos) {
        selectGratuito.value = 'si';
        for (let i = 0; i < selectGratuito.options.length; i++) {
            if (selectGratuito.options[i].value === 'no') {
                selectGratuito.options[i].disabled = true;
            }
        }
    }

    if (esGratuito === 'si') {
        campoPrecio.style.display = 'none';
        inputPrecio.required = false;
        inputPrecio.value = '0';
        if (campoMetodoPago) campoMetodoPago.style.display = 'none';
    } else {
        campoPrecio.style.display = 'block';
        inputPrecio.required = true;
        if (campoMetodoPago) campoMetodoPago.style.display = 'block';
    }
}

// Ejecutar al cargar
toggleTipoProducto();
</script>
";

include '../../includes/footer.php';

?>