<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('productos.php');
}

$id = intval($_GET['id']);

// Obtener producto
$query = "SELECT * FROM productos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Producto no encontrado";
    redirect('productos.php');
}

mysqli_stmt_close($stmt);

$page_title = 'Editar Producto';

// Obtener categorías activas
$query_categorias = "SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
$categorias = mysqli_query($conexion, $query_categorias);

// Obtener métodos de pago del superadmin (donde id_editor ES NULL)
$query_metodos = "SELECT * FROM tipos_pago WHERE id_editor IS NULL AND estado = 'activo' ORDER BY nombre ASC";
$metodos_pago = mysqli_query($conexion, $query_metodos);

// Métodos seleccionados
$metodos_seleccionados = explode(',', $producto['id_tipo_pago'] ?? '');

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Editar Producto</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="productos.php">Productos</a></li>
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
                    <h5 class="mb-0">Imagen del Producto</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($producto['imagen']) && file_exists('../../' . $producto['imagen'])): ?>
                        <div class="mb-3">
                            <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" 
                                 alt="Imagen actual" 
                                 style="width: 100%; border-radius: 8px;">
                            <small class="text-muted d-block mt-2">Imagen actual</small>
                        </div>
                    <?php
endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Cambiar Imagen</label>
                        <input type="file" class="form-control" name="imagen" 
                               accept="image/jpeg,image/png,image/jpg,image/webp" 
                               id="inputImagen">
                        <small class="text-muted">Deja vacío para mantener la imagen actual</small>
                    </div>
                    
                    <div id="previewImagen" style="display: none;">
                        <img id="imagenPreview" src="" alt="Preview" 
                             style="width: 100%; border-radius: 8px;">
                    </div>
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
document.getElementById('inputImagen').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 1048576) {
            mostrarAlerta('warning', 'Archivo muy grande', 'La imagen no debe superar 1MB');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagenPreview').src = e.target.result;
            document.getElementById('previewImagen').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

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
    
    // Tutorial overrides
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
    
    // Precio
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