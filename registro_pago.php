<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

// Verificar que venga del registro
if (!isset($_SESSION['editor_registrado_id'])) {
    redirect('registro.php');
}

$id_usuario = $_SESSION['editor_registrado_id'];
$nombre = $_SESSION['editor_nombre'];

// Obtener montos de configuración
$monto_usd = $CONFIG['editor_monto_registro'] ?? '10.00';
$monto_bs = $CONFIG['editor_monto_registro_bs'] ?? '70.00';

$error = '';
$success = '';

// Procesar envío de comprobante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comprobante'])) {
    
    if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = "Debes subir el comprobante de pago";
    } else {
        $fileValidator = new FileValidator();
        $resultado = $fileValidator->guardar_archivo(
            $_FILES['comprobante'],
            COMPROBANTES_PATH,
            'editor_' . $id_usuario . '_' . time()
        );
        
        if ($resultado['success']) {
            $comprobante_path = 'uploads/comprobantes/' . $resultado['filename'];
            
            // Guardar solicitud
            $query = "INSERT INTO solicitudes_editor (id_usuario, monto_pago, comprobante) 
                      VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conexion, $query);
            mysqli_stmt_bind_param($stmt, "ids", $id_usuario, $monto_usd, $comprobante_path);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                
                // Limpiar sesión
                unset($_SESSION['editor_registrado_id'], $_SESSION['editor_nombre']);
                
                // Mensaje de éxito
                $_SESSION['solicitud_enviada'] = true;
                redirect('login.php');
            } else {
                $error = "Error al enviar la solicitud";
                mysqli_stmt_close($stmt);
            }
        } else {
            $error = implode('<br>', $resultado['errors']);
        }
    }
}

// Obtener tipos de pago activos y globales (Del superadmin)
$query_tipos = "SELECT * FROM tipos_pago WHERE id_editor IS NULL AND estado = 'activo' ORDER BY id ASC";
$tipos_pago = mysqli_query($conexion, $query_tipos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Registro - <?php echo NOMBRE_SISTEMA; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #111111;
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .pago-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .pago-header {
            background: #004d00;
            color: white;
            padding: 30px;
            border-radius: 20px 20px 0 0;
            text-align: center;
        }
        
        .pago-body {
            padding: 40px;
        }
        
        .metodo-pago {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .metodo-pago h5 {
            color: #004d00;
            margin-bottom: 15px;
        }
        
        .qr-image {
            max-width: 250px;
            border: 3px solid #004d00;
            border-radius: 12px;
            padding: 10px;
        }
        
        .info-cuenta {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .btn-primary {
            background-color: #004d00;
            border-color: #004d00;
        }
        
        .btn-primary:hover {
            background-color: #003300;
            border-color: #003300;
        }

        .step-number {
            width: 35px;
            height: 35px;
            background: #004d00;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-right: 15px;
            flex-shrink: 0; /* Evita que el circulo se aplaste en móviles */
        }
        
        /* Optimizaciones para celular */
        @media (max-width: 768px) {
            body {
                padding: 15px;
                background: linear-gradient(135deg, #111111 0%, #002200 100%);
            }
            .pago-container {
                border-radius: 15px;
            }
            .pago-header {
                padding: 25px 20px;
                border-radius: 15px 15px 0 0;
            }
            .pago-header h2 {
                font-size: 1.6rem;
            }
            .pago-body {
                padding: 20px 15px;
            }
            .metodo-pago {
                padding: 15px;
            }
            .qr-image {
                max-width: 100%;
                height: auto;
            }
            .btn-primary.btn-lg {
                font-size: 1.1rem;
                padding: 14px;
            }
        }
    </style>
</head>
<body>
    
    <div class="pago-container">
        <div class="pago-header">
            <h2>
                <i class="fas fa-credit-card me-2"></i>
                Pago de Registro - Editor
            </h2>
            <p class="mb-0">Bienvenido, <?php echo $nombre; ?></p>
        </div>
        
        <div class="pago-body">
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle me-2"></i>Monto a Pagar</h5>
                <h3 class="mb-0">$<?php echo $monto_usd; ?> USD <small class="text-muted">(≈ Bs. <?php echo $monto_bs; ?>)</small></h3>
            </div>
            
            <h4 class="mb-4">Métodos de Pago Disponibles:</h4>
            
            <div class="accordion mb-4" id="accordionMetodosPago">
                <?php 
                $contador = 0;
                while ($tipo = mysqli_fetch_assoc($tipos_pago)): 
                    $contador++;
                    $isFirst = ($contador === 1);
                    $collapseId = "collapseMetodo" . $contador;
                    $headingId = "headingMetodo" . $contador;
                ?>
                <div class="accordion-item" style="border: 2px solid #e0e0e0; border-radius: 12px; margin-bottom: 10px; overflow: hidden;">
                    <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                        <button class="accordion-button <?php echo $isFirst ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="<?php echo $isFirst ? 'true' : 'false'; ?>" aria-controls="<?php echo $collapseId; ?>" style="background-color: #f8f9fa; color: #004d00; font-weight: 600; font-size: 1.1rem; border-bottom: none; box-shadow: none;">
                            <i class="fas fa-credit-card me-3"></i>
                            <?php echo $tipo['nombre']; ?>
                        </button>
                    </h2>
                    
                    <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $isFirst ? 'show' : ''; ?>" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#accordionMetodosPago">
                        <div class="accordion-body bg-white pt-2">
                            
                            <?php if (!empty($tipo['instrucciones'])): ?>
                                <p><?php echo nl2br($tipo['instrucciones']); ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($tipo['imagen_qr']) && file_exists($tipo['imagen_qr'])): ?>
                            <div class="text-center my-3">
                                <img src="<?php echo BASE_URL . '/' . $tipo['imagen_qr']; ?>" 
                                     alt="QR <?php echo $tipo['nombre']; ?>" 
                                     class="qr-image">
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($tipo['tipo'] === 'transferencia'): ?>
                            <div class="info-cuenta mt-3">
                                <?php if (!empty($tipo['banco'])): ?>
                                    <p class="mb-1"><strong>Banco:</strong> <?php echo $tipo['banco']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($tipo['numero_cuenta'])): ?>
                                    <p class="mb-1"><strong>Nº Cuenta:</strong> <?php echo $tipo['numero_cuenta']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($tipo['titular'])): ?>
                                    <p class="mb-1"><strong>Titular:</strong> <?php echo $tipo['titular']; ?></p>
                                <?php endif; ?>
                                <?php if (!empty($tipo['ci_titular'])): ?>
                                    <p class="mb-0"><strong>CI:</strong> <?php echo $tipo['ci_titular']; ?></p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <hr class="my-4">
            
            <h4 class="mb-3">Pasos a Seguir:</h4>
            
            <div class="step">
                <div class="step-number">1</div>
                <div>Realiza el pago usando uno de los métodos arriba</div>
            </div>
            
            <div class="step">
                <div class="step-number">2</div>
                <div>Toma una foto o captura del comprobante de pago</div>
            </div>
            
            <div class="step">
                <div class="step-number">3</div>
                <div>Sube el comprobante en el formulario de abajo</div>
            </div>
            
            <div class="step">
                <div class="step-number">4</div>
                <div>Espera la aprobación del administrador (24-48 horas)</div>
            </div>
            
            <div class="step">
                <div class="step-number">5</div>
                <div>Recibirás un email cuando tu cuenta sea activada</div>
            </div>
            
            <hr class="my-4">
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="card">
                    <div class="card-header text-white" style="background-color: #004d00;">
                        <h5 class="mb-0">
                            <i class="fas fa-upload me-2"></i>
                            Subir Comprobante de Pago
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Comprobante de Pago *</label>
                            <input type="file" class="form-control" name="comprobante" 
                                   accept="image/jpeg,image/png,image/jpg,application/pdf" required>
                            <small class="text-muted">JPG, PNG o PDF. Máximo 1MB</small>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Asegúrate de que el comprobante sea legible y muestre claramente el monto pagado.
                        </div>
                        
                        <button type="submit" name="enviar_comprobante" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-paper-plane me-2"></i>
                            Enviar Comprobante y Finalizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>