<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$page_title = 'Solicitudes de Editores';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener solicitudes con datos del usuario
$query = "SELECT se.*, u.nombre, u.apellido, u.email, u.usuario, u.estado as usuario_estado
          FROM solicitudes_editor se
          INNER JOIN usuarios u ON se.id_usuario = u.id
          ORDER BY 
            CASE se.estado 
                WHEN 'pendiente' THEN 1 
                WHEN 'aprobado' THEN 2 
                WHEN 'rechazado' THEN 3 
            END,
            se.fecha_solicitud DESC";
$solicitudes = mysqli_query($conexion, $query);

// Estadísticas
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobados,
    SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados
    FROM solicitudes_editor";
$result_stats = mysqli_query($conexion, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

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
    <div>
        <h1>Solicitudes de Editores</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Solicitudes de Editores</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <h3><?php echo $stats['total']; ?></h3>
            <p>Total Solicitudes</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">
                <i class="fas fa-clock"></i>
            </div>
            <h3><?php echo $stats['pendientes']; ?></h3>
            <p>Pendientes</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white;">
                <i class="fas fa-check"></i>
            </div>
            <h3><?php echo $stats['aprobados']; ?></h3>
            <p>Aprobados</p>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <i class="fas fa-times"></i>
            </div>
            <h3><?php echo $stats['rechazados']; ?></h3>
            <p>Rechazados</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-list me-2"></i>
            Todas las Solicitudes
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Editor</th>
                        <th>Email</th>
                        <th>Usuario</th>
                        <th>Monto</th>
                        <th>Comprobante</th>
                        <th>Fecha Solicitud</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($solicitud = mysqli_fetch_assoc($solicitudes)): ?>
                    <tr>
                        <td><?php echo $solicitud['id']; ?></td>
                        <td>
                            <strong><?php echo $solicitud['nombre'] . ' ' . $solicitud['apellido']; ?></strong>
                        </td>
                        <td><?php echo $solicitud['email']; ?></td>
                        <td><?php echo $solicitud['usuario']; ?></td>
                        <td><strong>$<?php echo number_format($solicitud['monto_pago'], 2); ?></strong></td>
                        <td>
                            <?php if (!empty($solicitud['comprobante'])): ?>
                                <button class="btn btn-sm btn-outline-info" 
                                        onclick="verComprobante('<?php echo BASE_URL . '/' . $solicitud['comprobante']; ?>')">
                                    <i class="fas fa-file-image"></i> Ver
                                </button>
                            <?php else: ?>
                                <span class="text-muted">Sin archivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatear_fecha($solicitud['fecha_solicitud'], true); ?></td>
                        <td>
                            <?php
                            if ($solicitud['estado'] === 'pendiente') {
                                echo '<span class="badge bg-warning text-dark">Pendiente</span>';
                            } elseif ($solicitud['estado'] === 'aprobado') {
                                echo '<span class="badge bg-success">Aprobado</span>';
                            } else {
                                echo '<span class="badge bg-danger">Rechazado</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($solicitud['estado'] === 'pendiente'): ?>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-success" 
                                        onclick="aprobarSolicitud(<?php echo $solicitud['id']; ?>, '<?php echo $solicitud['nombre'] . ' ' . $solicitud['apellido']; ?>')">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-danger" 
                                        onclick="rechazarSolicitud(<?php echo $solicitud['id']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php else: ?>
                                <span class="text-muted">
                                    <?php echo ucfirst($solicitud['estado']); ?>
                                    <?php if (!empty($solicitud['fecha_respuesta'])): ?>
                                        <br><small><?php echo formatear_fecha($solicitud['fecha_respuesta'], true); ?></small>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Ver Comprobante -->
<div class="modal fade" id="modalComprobante" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-image me-2"></i>
                    Comprobante de Pago
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="imagenComprobante" src="" alt="Comprobante" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        </div>
    </div>
</div>

<!-- Modal Rechazar -->
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2"></i>
                    Rechazar Solicitud
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="solicitudes_editor_procesar.php">
                <input type="hidden" name="accion" value="rechazar">
                <input type="hidden" name="id" id="rechazar_id">
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Esta acción rechazará la solicitud y el usuario deberá volver a registrarse.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo del Rechazo (Opcional)</label>
                        <textarea class="form-control" name="observaciones" rows="3" 
                                  placeholder="Ej: Comprobante no válido, monto incorrecto, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>
                        Rechazar Solicitud
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$extra_js = "
<script>
function verComprobante(url) {
    document.getElementById('imagenComprobante').src = url;
    new bootstrap.Modal(document.getElementById('modalComprobante')).show();
}

function aprobarSolicitud(id, nombre) {
    Swal.fire({
        title: '¿Aprobar Solicitud?',
        html: 'Se activará la cuenta de <strong>' + nombre + '</strong> como Editor.<br>Recibirá un email de confirmación.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'solicitudes_editor_procesar.php?accion=aprobar&id=' + id;
        }
    });
}

function rechazarSolicitud(id) {
    document.getElementById('rechazar_id').value = id;
    new bootstrap.Modal(document.getElementById('modalRechazar')).show();
}
</script>
";

include '../../includes/footer.php'; 
?>