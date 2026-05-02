<?php
require_once '../../config/config.php';
require_once '../../config/session.php';
require_once '../../includes/funciones.php';

Session::require_role(ROL_SUPERADMIN);

$page_title = 'Gestión de Comisiones';

// Mensajes
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Filtro de estado
$filtro_estado = $_GET['estado'] ?? 'todas';
$filtro_editor = isset($_GET['editor']) ? intval($_GET['editor']) : 0;

// Obtener comisiones
$query = "SELECT pag.*, 
          c.codigo_compra, c.fecha_compra, c.monto_total,
          p.nombre as producto_nombre,
          e.nombre as editor_nombre, e.apellido as editor_apellido, e.email as editor_email
          FROM pagos_editores pag
          INNER JOIN compras c ON pag.id_compra = c.id
          INNER JOIN productos p ON c.id_producto = p.id
          INNER JOIN usuarios e ON pag.id_editor = e.id";

$conditions = ["pag.porcentaje < 100"];
if ($filtro_estado !== 'todas') {
    $conditions[] = "pag.estado = '$filtro_estado'";
}
if ($filtro_editor > 0) {
    $conditions[] = "pag.id_editor = $filtro_editor";
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY 
            CASE pag.estado 
                WHEN 'pendiente' THEN 1 
                WHEN 'pagado' THEN 2 
            END,
            c.fecha_compra DESC";

$comisiones = mysqli_query($conexion, $query);

// Estadísticas generales
$query_stats = "SELECT 
    COUNT(*) as total,
    SUM(monto) as total_monto,
    SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END) as pendientes,
    SUM(CASE WHEN estado = 'pagado' THEN monto ELSE 0 END) as pagadas,
    COUNT(DISTINCT id_editor) as total_editores
    FROM pagos_editores
    WHERE porcentaje < 100";
$result_stats = mysqli_query($conexion, $query_stats);
$stats = mysqli_fetch_assoc($result_stats);

// Obtener editores con comisiones (para el select de filtros)
$query_editores = "SELECT DISTINCT e.id, e.nombre, e.apellido,
                   SUM(CASE WHEN pag.estado = 'pendiente' THEN pag.monto ELSE 0 END) as pendiente
                   FROM usuarios e
                   INNER JOIN pagos_editores pag ON e.id = pag.id_editor
                   WHERE pag.porcentaje < 100
                   GROUP BY e.id
                   ORDER BY e.nombre ASC";
$editores = mysqli_query($conexion, $query_editores);

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
    <h1>Gestión de Comisiones</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Comisiones</li>
        </ol>
    </nav>
</div>

<style>
/* Estilos Premium para Cards de Comisiones */
.stat-glass-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: left;
    box-shadow: 0 10px 40px rgba(0,0,0,0.03);
    border: 1px solid rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    align-items: center;
}
.stat-glass-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.08);
}
.stat-icon-wrapper {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.5rem;
    margin-right: 1rem;
    flex-shrink: 0;
}
.stat-primary { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.stat-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.stat-warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.stat-purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 800;
    margin-bottom: 0.2rem;
    color: #1e293b;
}
.stat-info p {
    margin: 0;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<!-- Estadísticas -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card">
            <div class="stat-icon-wrapper stat-primary">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Nro. Comisiones</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card border-warning border-opacity-50">
            <div class="stat-icon-wrapper stat-warning">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-warning"><?php echo formatear_monto($stats['pendientes']); ?></h3>
                <p>Deuda por Pagar</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card border-success border-opacity-50">
            <div class="stat-icon-wrapper stat-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <h3 class="text-success"><?php echo formatear_monto($stats['pagadas']); ?></h3>
                <p>Dinero Pagado</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-glass-card">
            <div class="stat-icon-wrapper stat-purple">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo $stats['total_editores']; ?></h3>
                <p>Editores Afiliados</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filtrar por Estado:</label>
                <div class="btn-group w-100" role="group">
                    <a href="?estado=todas<?php echo($filtro_editor > 0) ? '&editor=' . $filtro_editor : ''; ?>" 
                       class="btn btn-sm btn-outline-primary <?php echo($filtro_estado === 'todas') ? 'active' : ''; ?>">
                        Todas
                    </a>
                    <a href="?estado=pendiente<?php echo($filtro_editor > 0) ? '&editor=' . $filtro_editor : ''; ?>" 
                       class="btn btn-sm btn-outline-warning <?php echo($filtro_estado === 'pendiente') ? 'active' : ''; ?>">
                        Pendientes
                    </a>
                    <a href="?estado=pagado<?php echo($filtro_editor > 0) ? '&editor=' . $filtro_editor : ''; ?>" 
                       class="btn btn-sm btn-outline-success <?php echo($filtro_estado === 'pagado') ? 'active' : ''; ?>">
                        Pagadas
                    </a>
                </div>
            </div>
            
            <div class="col-md-6">
                <label class="form-label">Filtrar por Editor:</label>
                <select class="form-select" name="editor" onchange="this.form.submit()">
                    <option value="0">Todos los Editores</option>
                    <?php while ($editor = mysqli_fetch_assoc($editores)): ?>
                        <option value="<?php echo $editor['id']; ?>" <?php echo($filtro_editor == $editor['id']) ? 'selected' : ''; ?>>
                            <?php echo $editor['nombre'] . ' ' . $editor['apellido']; ?>
                            (Pendiente: <?php echo formatear_monto($editor['pendiente']); ?>)
                        </option>
                    <?php
endwhile; ?>
                </select>
                <input type="hidden" name="estado" value="<?php echo $filtro_estado; ?>">
            </div>
            
            <div class="col-md-2">
                <?php if ($filtro_editor > 0): ?>
                    <a href="?estado=<?php echo $filtro_estado; ?>" class="btn btn-secondary w-100">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </a>
                <?php
endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Tabs de Vista -->
<ul class="nav nav-tabs mb-4" id="vistaTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="porEditor-tab" data-bs-toggle="tab" data-bs-target="#porEditor" type="button">
            <i class="fas fa-users me-2"></i>
            Vista por Editor
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="porComision-tab" data-bs-toggle="tab" data-bs-target="#porComision" type="button">
            <i class="fas fa-list me-2"></i>
            Vista Detallada
        </button>
    </li>
</ul>

<div class="tab-content" id="vistaTabsContent">
    
    <!-- VISTA POR EDITOR -->
    <div class="tab-pane fade show active" id="porEditor" role="tabpanel">
        <?php
// Agrupar comisiones por editor
mysqli_data_seek($comisiones, 0);
$editores_agrupados = [];

while ($com = mysqli_fetch_assoc($comisiones)) {
    $id_editor = $com['id_editor'];

    if (!isset($editores_agrupados[$id_editor])) {
        $editores_agrupados[$id_editor] = [
            'nombre' => $com['editor_nombre'] . ' ' . $com['editor_apellido'],
            'email' => $com['editor_email'],
            'total_comisiones' => 0,
            'total_pendiente' => 0,
            'total_pagado' => 0,
            'comisiones' => [],
            'ids_pendientes' => []
        ];
    }

    $editores_agrupados[$id_editor]['comisiones'][] = $com;
    $editores_agrupados[$id_editor]['total_comisiones'] += $com['monto'];

    if ($com['estado'] === 'pendiente') {
        $editores_agrupados[$id_editor]['total_pendiente'] += $com['monto'];
        $editores_agrupados[$id_editor]['ids_pendientes'][] = $com['id'];
    }
    else {
        $editores_agrupados[$id_editor]['total_pagado'] += $com['monto'];
    }
}
?>
        
        <div class="row">
            <?php foreach ($editores_agrupados as $id_editor => $editor): ?>
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <?php echo $editor['nombre']; ?>
                                </h5>
                                <small class="text-muted"><?php echo $editor['email']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <h6 class="text-muted mb-0">Total</h6>
                                <h4 class="text-primary mb-0"><?php echo formatear_monto($editor['total_comisiones']); ?></h4>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted mb-0">Pendiente</h6>
                                <h4 class="text-warning mb-0"><?php echo formatear_monto($editor['total_pendiente']); ?></h4>
                            </div>
                            <div class="col-4">
                                <h6 class="text-muted mb-0">Pagado</h6>
                                <h4 class="text-success mb-0"><?php echo formatear_monto($editor['total_pagado']); ?></h4>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <p class="mb-2">
                            <i class="fas fa-list-ul me-2"></i>
                            <strong>Comisiones:</strong> <?php echo count($editor['comisiones']); ?> registros
                        </p>
                        
                        <?php if ($editor['total_pendiente'] > 0): ?>
                            <button class="btn btn-success w-100" onclick="pagarEditor(<?php echo $id_editor; ?>, '<?php echo addslashes($editor['nombre']); ?>', '<?php echo formatear_monto($editor['total_pendiente']); ?>', [<?php echo implode(',', $editor['ids_pendientes']); ?>])">
                                <i class="fas fa-check-circle me-2"></i>
                                Pagar Todo el Pendiente (<?php echo formatear_monto($editor['total_pendiente']); ?>)
                            </button>
                        <?php
    else: ?>
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                No tiene comisiones pendientes
                            </div>
                        <?php
    endif; ?>
                        
                        <button class="btn btn-outline-primary btn-sm w-100 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#detalle<?php echo $id_editor; ?>">
                            <i class="fas fa-eye me-2"></i>
                            Ver Detalle
                        </button>
                        
                        <div class="collapse mt-3" id="detalle<?php echo $id_editor; ?>">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Monto</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($editor['comisiones'] as $com): ?>
                                        <tr>
                                            <td><small><?php echo $com['codigo_compra']; ?></small></td>
                                            <td><small><?php echo truncar_texto($com['producto_nombre'], 20); ?></small></td>
                                            <td><small><strong><?php echo formatear_monto($com['monto']); ?></strong></small></td>
                                            <td>
                                                <?php if ($com['estado'] === 'pendiente'): ?>
                                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                                <?php
        else: ?>
                                                    <span class="badge bg-success">Pagado</span>
                                                <?php
        endif; ?>
                                            </td>
                                        </tr>
                                        <?php
    endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
endforeach; ?>
        </div>
    </div>
    
    <!-- VISTA DETALLADA (TABLA ORIGINAL) -->
    <div class="tab-pane fade" id="porComision" role="tabpanel">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Lista de Comisiones
                </h5>
                <?php if ($filtro_estado === 'pendiente'): ?>
                    <button class="btn btn-success" onclick="pagarSeleccionadas()">
                        <i class="fas fa-check me-2"></i>
                        Pagar Seleccionadas
                    </button>
                <?php
endif; ?>
            </div>
    <div class="card-body">
        <form id="formPagarMasivo">
            <div class="table-responsive">
                <table class="table table-hover datatable">
                    <thead>
                        <tr>
                            <?php if ($filtro_estado === 'pendiente'): ?>
                                <th width="30">
                                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                                </th>
                            <?php
endif; ?>
                            <th>Código Compra</th>
                            <th>Fecha</th>
                            <th>Editor</th>
                            <th>Producto</th>
                            <th>Precio Venta</th>
                            <th>%</th>
                            <th>Comisión</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($com = mysqli_fetch_assoc($comisiones)): ?>
                        <tr>
                            <?php if ($filtro_estado === 'pendiente'): ?>
                                <td>
                                    <?php if ($com['estado'] === 'pendiente'): ?>
                                        <input type="checkbox" name="comisiones[]" value="<?php echo $com['id']; ?>" class="comision-check">
                                    <?php
        endif; ?>
                                </td>
                            <?php
    endif; ?>
                            <td><strong><?php echo $com['codigo_compra']; ?></strong></td>
                            <td><?php echo formatear_fecha($com['fecha_compra']); ?></td>
                            <td>
                                <?php echo $com['editor_nombre'] . ' ' . $com['editor_apellido']; ?>
                                <br><small class="text-muted"><?php echo $com['editor_email']; ?></small>
                            </td>
                            <td><?php echo truncar_texto($com['producto_nombre'], 30); ?></td>
                            <td><?php echo formatear_monto($com['monto_total']); ?></td>
                            <td>
                                <span class="badge bg-info"><?php echo number_format($com['porcentaje'], 2); ?>%</span>
                            </td>
                            <td>
                                <strong class="text-success"><?php echo formatear_monto($com['monto']); ?></strong>
                            </td>
                            <td>
                                <?php if ($com['estado'] === 'pendiente'): ?>
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php
    else: ?>
                                    <span class="badge bg-success">Pagado</span>
                                <?php
    endif; ?>
                            </td>
                            <td>
                                <?php if ($com['estado'] === 'pendiente'): ?>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="pagarComision(<?php echo $com['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php
    else: ?>
                                    <small class="text-muted">Pagado</small>
                                <?php
    endif; ?>
                            </td>
                        </tr>
                        <?php
endwhile; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
    </div><!-- Fin tab-pane porComision -->
    
</div><!-- Fin tab-content -->

<?php

$extra_js = "
<script>
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.comision-check');
    checkboxes.forEach(checkbox => checkbox.checked = source.checked);
}

function pagarComision(id) {
    Swal.fire({
        title: '¿Marcar como Pagada?',
        text: 'Esta acción confirmará que has pagado esta comisión.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Marcar como Pagada',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'comisiones_pagar.php?id=' + id;
        }
    });
}

function pagarEditor(idEditor, nombreEditor, montoPendiente, idsComisiones) {
    Swal.fire({
        title: '¿Pagar a ' + nombreEditor + '?',
        html: '<p>Total a pagar: <strong class=\"text-success\">' + montoPendiente + '</strong></p>' +
              '<p>Se marcarán <strong>' + idsComisiones.length + ' comisiones</strong> como pagadas.</p>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Pagar Todo',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario dinámico
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'comisiones_pagar_masivo.php';
            
            // Agregar IDs de comisiones
            idsComisiones.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'comisiones[]';
                input.value = id;
                form.appendChild(input);
            });
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function pagarSeleccionadas() {
    const checkboxes = document.querySelectorAll('.comision-check:checked');
    
    if (checkboxes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Selecciona comisiones',
            text: 'Debes seleccionar al menos una comisión',
            confirmButtonColor: '#667eea'
        });
        return;
    }
    
    Swal.fire({
        title: '¿Pagar ' + checkboxes.length + ' comisiones?',
        text: 'Esto marcará todas las comisiones seleccionadas como pagadas.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, Pagar Todas',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formPagarMasivo').action = 'comisiones_pagar_masivo.php';
            document.getElementById('formPagarMasivo').method = 'POST';
            document.getElementById('formPagarMasivo').submit();
        }
    });
}
</script>
";

include '../../includes/footer.php';

?>