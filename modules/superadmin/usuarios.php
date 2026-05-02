<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

// Verificar que sea superadmin
Session::require_role(ROL_SUPERADMIN);

$page_title = 'Gestión de Usuarios';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Obtener todos los usuarios
$query = "SELECT * FROM usuarios ORDER BY fecha_registro DESC";
$usuarios = mysqli_query($conexion, $query);

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
            <h1>Gestión de Usuarios</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Usuarios</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUsuario">
            <i class="fas fa-user-plus me-2"></i>
            Nuevo Usuario
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-users me-2"></i>
            Lista de Usuarios
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($usuario = mysqli_fetch_assoc($usuarios)): ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar" style="width: 35px; height: 35px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 10px; font-weight: 600;">
                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></strong>
                                </div>
                            </div>
                        </td>
                        <td><?php echo $usuario['usuario']; ?></td>
                        <td><?php echo $usuario['email']; ?></td>
                        <td>
                            <?php echo icono_rol($usuario['rol']); ?>
                            <span class="ms-1"><?php echo ucfirst($usuario['rol']); ?></span>
                        </td>
                        <td><?php echo badge_estado($usuario['estado']); ?></td>
                        <td><?php echo formatear_fecha($usuario['fecha_registro'], true); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="editarUsuario(<?php echo $usuario['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($usuario['id'] != Session::get_user_id()): ?>
                                <button class="btn btn-outline-danger" onclick="confirmarEliminacion('usuarios_eliminar.php?id=<?php echo $usuario['id']; ?>', '¿Eliminar a <?php echo $usuario['nombre']; ?>?')">
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

<!-- Modal Crear/Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUsuarioTitle">
                    <i class="fas fa-user-plus me-2"></i>
                    Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUsuario" method="POST" action="usuarios_guardar.php">
                <div class="modal-body">
                    <input type="hidden" name="id" id="usuario_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" id="usuario_nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-control" name="apellido" id="usuario_apellido" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Usuario *</label>
                            <input type="text" class="form-control" name="usuario" id="usuario_username" required>
                            <small class="text-muted">Solo letras, números y guiones bajos</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="usuario_email" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rol *</label>
                            <select class="form-select" name="rol" id="usuario_rol" required>
                                <option value="">Seleccione...</option>
                                <option value="editor">Editor</option>
                                <option value="comprador">Comprador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado *</label>
                            <select class="form-select" name="estado" id="usuario_estado" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row" id="passwordFields">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" name="password" id="usuario_password">
                            <small class="text-muted">Mínimo 6 caracteres</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmar Contraseña *</label>
                            <input type="password" class="form-control" name="password_confirm" id="usuario_password_confirm">
                        </div>
                    </div>
                    
                    <div class="alert alert-info" id="editPasswordNote" style="display: none;">
                        <i class="fas fa-info-circle me-2"></i>
                        Deja la contraseña en blanco si no deseas cambiarla.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Usuario
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
$('#modalUsuario').on('hidden.bs.modal', function () {
    $('#formUsuario')[0].reset();
    $('#usuario_id').val('');
    $('#modalUsuarioTitle').html('<i class=\"fas fa-user-plus me-2\"></i> Nuevo Usuario');
    $('#passwordFields input').prop('required', true);
    $('#editPasswordNote').hide();
});

// Editar usuario
function editarUsuario(id) {
    $.ajax({
        url: 'usuarios_obtener.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                $('#usuario_id').val(data.usuario.id);
                $('#usuario_nombre').val(data.usuario.nombre);
                $('#usuario_apellido').val(data.usuario.apellido);
                $('#usuario_username').val(data.usuario.usuario);
                $('#usuario_email').val(data.usuario.email);
                $('#usuario_rol').val(data.usuario.rol);
                $('#usuario_estado').val(data.usuario.estado);
                
                $('#modalUsuarioTitle').html('<i class=\"fas fa-user-edit me-2\"></i> Editar Usuario');
                $('#passwordFields input').prop('required', false);
                $('#editPasswordNote').show();
                
                $('#modalUsuario').modal('show');
            } else {
                mostrarAlerta('error', 'Error', data.message);
            }
        },
        error: function() {
            mostrarAlerta('error', 'Error', 'No se pudo cargar el usuario');
        }
    });
}

// Validar formulario
$('#formUsuario').on('submit', function(e) {
    const password = $('#usuario_password').val();
    const password_confirm = $('#usuario_password_confirm').val();
    const isEdit = $('#usuario_id').val() !== '';
    
    // Si es nuevo usuario o si está editando y escribió contraseña
    if (!isEdit || password !== '') {
        if (password.length < 6) {
            e.preventDefault();
            mostrarAlerta('warning', 'Contraseña muy corta', 'La contraseña debe tener al menos 6 caracteres');
            return false;
        }
        
        if (password !== password_confirm) {
            e.preventDefault();
            mostrarAlerta('warning', 'Contraseñas no coinciden', 'Las contraseñas deben ser iguales');
            return false;
        }
    }
});
</script>
";

include '../../includes/footer.php'; 
?>