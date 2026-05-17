<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

if (!isset($_GET['id'])) {
    redirect('tipos_pago.php');
}

$id = intval($_GET['id']);

// Obtener tipo de pago
$query = "SELECT * FROM tipos_pago WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$tipo_pago = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Tipo de pago no encontrado";
    redirect('tipos_pago.php');
}

mysqli_stmt_close($stmt);

$page_title = 'Editar Tipo de Pago';

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Editar Tipo de Pago</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="tipos_pago.php">Tipos de Pago</a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
</div>

<form method="POST" action="tipos_pago_guardar.php" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?php echo $tipo_pago['id']; ?>">
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Información del Tipo de Pago</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" name="nombre" 
                               value="<?php echo htmlspecialchars($tipo_pago['nombre']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" id="tipo" required onchange="toggleCampos()">
                            <option value="qr" <?php echo ($tipo_pago['tipo'] == 'qr') ? 'selected' : ''; ?>>QR</option>
                            <option value="transferencia" <?php echo ($tipo_pago['tipo'] == 'transferencia') ? 'selected' : ''; ?>>Transferencia Bancaria</option>
                            <option value="otro" <?php echo ($tipo_pago['tipo'] == 'otro') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"><?php echo htmlspecialchars($tipo_pago['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Instrucciones para el Usuario</label>
                        <textarea class="form-control" name="instrucciones" rows="3"><?php echo htmlspecialchars($tipo_pago['instrucciones'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estado *</label>
                        <select class="form-select" name="estado" required>
                            <option value="activo" <?php echo ($tipo_pago['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo ($tipo_pago['estado'] == 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4" id="camposTransferencia" style="display: <?php echo ($tipo_pago['tipo'] == 'transferencia') ? 'block' : 'none'; ?>;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-university me-2"></i>
                        Datos Bancarios
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Banco</label>
                            <input type="text" class="form-control" name="banco" 
                                   value="<?php echo htmlspecialchars($tipo_pago['banco'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número de Cuenta</label>
                            <input type="text" class="form-control" name="numero_cuenta" 
                                   value="<?php echo htmlspecialchars($tipo_pago['numero_cuenta'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Titular de la Cuenta</label>
                            <input type="text" class="form-control" name="titular" 
                                   value="<?php echo htmlspecialchars($tipo_pago['titular'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CI del Titular</label>
                            <input type="text" class="form-control" name="ci_titular" 
                                   value="<?php echo htmlspecialchars($tipo_pago['ci_titular'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>
                        Código QR
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($tipo_pago['imagen_qr']) && file_exists('../../' . $tipo_pago['imagen_qr'])): ?>
                    <div class="mb-3">
                        <img src="<?php echo BASE_URL . '/' . $tipo_pago['imagen_qr']; ?>" 
                             alt="QR Actual" 
                             style="width: 100%; border-radius: 8px; border: 2px solid #667eea; padding: 5px;">
                        <small class="text-muted d-block mt-2">QR Actual</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Cambiar Imagen QR</label>
                        <input type="file" class="form-control" name="imagen_qr" 
                               accept="image/jpeg,image/png,image/jpg" 
                               id="inputQR">
                        <small class="text-muted">Deja vacío para mantener el QR actual</small>
                    </div>
                    
                    <div id="previewQR" style="display: none;">
                        <img id="qrPreview" src="" alt="Preview" 
                             style="width: 100%; border-radius: 8px; border: 2px solid #667eea; padding: 5px;">
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>
                            Actualizar
                        </button>
                        <a href="tipos_pago.php" class="btn btn-secondary">
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
document.getElementById('inputQR').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2097152) {
            mostrarAlerta('warning', 'Archivo muy grande', 'La imagen no debe superar 2MB');
            this.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('qrPreview').src = e.target.result;
            document.getElementById('previewQR').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

function toggleCampos() {
    const tipo = document.getElementById('tipo').value;
    const camposTransferencia = document.getElementById('camposTransferencia');
    
    if (tipo === 'transferencia') {
        camposTransferencia.style.display = 'block';
    } else {
        camposTransferencia.style.display = 'none';
    }
}
</script>
";

include '../../includes/footer.php'; 
?>