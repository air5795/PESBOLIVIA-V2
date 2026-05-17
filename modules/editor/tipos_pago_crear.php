<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_EDITOR);

$page_title = 'Nuevo Método de Pago';

include '../../includes/header.php';
?>

<div class="page-header">
    <h1>Nuevo Método de Pago</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="tipos_pago.php">Métodos de Pago</a></li>
            <li class="breadcrumb-item active">Nuevo</li>
        </ol>
    </nav>
</div>

<form method="POST" action="tipos_pago_guardar.php" enctype="multipart/form-data">
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
                               placeholder="Ej: QR Banco Union, Transferencia BNB" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo *</label>
                        <select class="form-select" name="tipo" id="tipo" required onchange="toggleCampos()">
                            <option value="">Seleccione...</option>
                            <option value="qr">QR (Simple Pay, Tigo Money, etc.)</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2" 
                                  placeholder="Breve descripción del método de pago"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Instrucciones para el Usuario</label>
                        <textarea class="form-control" name="instrucciones" rows="3" 
                                  placeholder="Ej: Escanea el código QR con tu app de banco"></textarea>
                        <small class="text-muted">Estas instrucciones se mostrarán al usuario al momento de pagar</small>
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
            
            <!-- Campos de Transferencia -->
            <div class="card mb-4" id="camposTransferencia" style="display: none;">
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
                                   placeholder="Ej: Banco Union">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número de Cuenta</label>
                            <input type="text" class="form-control" name="numero_cuenta" 
                                   placeholder="1234567890">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Titular de la Cuenta</label>
                            <input type="text" class="form-control" name="titular" 
                                   placeholder="Nombre del titular">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CI del Titular</label>
                            <input type="text" class="form-control" name="ci_titular" 
                                   placeholder="12345678 LP">
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
                        Código QR (Opcional)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Sube la imagen del código QR si el método de pago lo utiliza.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subir Imagen QR</label>
                        <input type="file" class="form-control" name="imagen_qr" 
                               accept="image/jpeg,image/png,image/jpg" 
                               id="inputQR">
                        <small class="text-muted">JPG o PNG. Máximo 2MB</small>
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
                            Crear Tipo de Pago
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
// Preview de QR
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

// Toggle campos según tipo
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