<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

// Obtener categorías activas para Nav
$query_categorias = "SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
$categorias = mysqli_query($conexion, $query_categorias);
$categorias_array = [];
if ($categorias) {
    while ($row = mysqli_fetch_assoc($categorias)) {
        $categorias_array[] = $row;
    }
}

if (!isset($_GET['id'])) {
    redirect('tienda.php');
}

$id_producto = intval($_GET['id']);
$is_logged = Session::is_logged_in();
$user_role = $is_logged ?Session::get_user_role() : null;
$id_usuario = $is_logged ?Session::get_user_id() : 0;

// Obtener producto
$query = "SELECT p.*, c.nombre as categoria_nombre 
          FROM productos p
          LEFT JOIN categorias c ON p.id_categoria = c.id
          WHERE p.id = ? AND p.estado = 'activo'";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$producto = mysqli_fetch_assoc($result)) {
    $_SESSION['error'] = "Producto no encontrado";
    redirect('tienda.php');
}
mysqli_stmt_close($stmt);

// Verificar si ya compró este producto (solo si logueado)
$ya_comprado = false;
if ($is_logged) {
    $query_comprado = "SELECT COUNT(*) as comprado FROM compras 
                       WHERE id_comprador = ? AND id_producto = ? 
                       AND estado IN ('aprobado', 'entregado')";
    $stmt = mysqli_prepare($conexion, $query_comprado);
    mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_producto);
    mysqli_stmt_execute($stmt);
    $result_comp = mysqli_stmt_get_result($stmt);
    $ya_comprado = mysqli_fetch_assoc($result_comp)['comprado'] > 0;
    mysqli_stmt_close($stmt);
}

// Obtener tipos de pago
$tipos_pago = null;
if (!empty($producto['id_tipo_pago'])) {
    $query_tipos = "SELECT * FROM tipos_pago WHERE FIND_IN_SET(id, ?) > 0 AND estado = 'activo'";
    $stmt = mysqli_prepare($conexion, $query_tipos);
    mysqli_stmt_bind_param($stmt, "s", $producto['id_tipo_pago']);
    mysqli_stmt_execute($stmt);
    $tipos_pago = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
}
else {
    $query_editor = "SELECT id_editor FROM producto_editores WHERE id_producto = ? ORDER BY porcentaje DESC LIMIT 1";
    $stmt = mysqli_prepare($conexion, $query_editor);
    mysqli_stmt_bind_param($stmt, "i", $id_producto);
    mysqli_stmt_execute($stmt);
    $result_editor = mysqli_stmt_get_result($stmt);
    $editor_producto = mysqli_fetch_assoc($result_editor);
    mysqli_stmt_close($stmt);

    if (!empty($editor_producto['id_editor'])) {
        $query_tipos = "SELECT * FROM tipos_pago WHERE estado = 'activo' AND id_editor = ? ORDER BY id ASC";
        $stmt = mysqli_prepare($conexion, $query_tipos);
        mysqli_stmt_bind_param($stmt, "i", $editor_producto['id_editor']);
        mysqli_stmt_execute($stmt);
        $tipos_pago = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
    }
    else {
        $query_tipos = "SELECT * FROM tipos_pago WHERE estado = 'activo' AND id_editor IS NULL ORDER BY id ASC";
        $tipos_pago = mysqli_query($conexion, $query_tipos);
    }
}

// Cargar reseñas / valoraciones
$query_valoraciones = "SELECT COALESCE(AVG(calificacion), 0) as promedio, COUNT(*) as total_resenas FROM resenas WHERE id_producto = ?";
$stmt = mysqli_prepare($conexion, $query_valoraciones);
mysqli_stmt_bind_param($stmt, "i", $id_producto);
mysqli_stmt_execute($stmt);
$result_val = mysqli_stmt_get_result($stmt);
$valoraciones = mysqli_fetch_assoc($result_val);
$promedio_valoracion = round($valoraciones['promedio'], 1);
$total_resenas = $valoraciones['total_resenas'];
mysqli_stmt_close($stmt);

// Paginación de reseñas
$resenas_por_pagina = 5;
$pagina_resenas = isset($_GET['pagina_resenas']) ? max(1, intval($_GET['pagina_resenas'])) : 1;
$offset_resenas = ($pagina_resenas - 1) * $resenas_por_pagina;

// Lista de reseñas
$query_resenas_list = "SELECT r.*, u.nombre as usuario_nombre, u.rol FROM resenas r JOIN usuarios u ON r.id_usuario = u.id WHERE r.id_producto = ? ORDER BY r.calificacion DESC, r.fecha_creacion DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conexion, $query_resenas_list);
mysqli_stmt_bind_param($stmt, "iii", $id_producto, $resenas_por_pagina, $offset_resenas);
mysqli_stmt_execute($stmt);
$resenas_list = mysqli_stmt_get_result($stmt);

// Total reseñas para paginación
$total_paginas_resenas = ceil($total_resenas / $resenas_por_pagina);

$user_ya_reseno = false;
if ($is_logged) {
    // Comprobar si el usuario actual ya reseñó
    $q_ya = "SELECT id FROM resenas WHERE id_producto = ? AND id_usuario = ?";
    $stmt_ya = mysqli_prepare($conexion, $q_ya);
    mysqli_stmt_bind_param($stmt_ya, "ii", $id_producto, $id_usuario);
    mysqli_stmt_execute($stmt_ya);
    if (mysqli_num_rows(mysqli_stmt_get_result($stmt_ya)) > 0) {
        $user_ya_reseno = true;
    }
    mysqli_stmt_close($stmt_ya);
}

// Mensajes de review
$success_review = $_SESSION['success_review'] ?? '';
$error_review = $_SESSION['error_review'] ?? '';
unset($_SESSION['success_review'], $_SESSION['error_review']);

$page_title = $producto['nombre'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title) . ' - ' . NOMBRE_SISTEMA; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --amazon-dark: #0f1111;
            --amazon-light: #1a1e23;
            --amazon-yellow: #10b981;
            --amazon-yellow-hover: #059669;
            --amazon-orange: #047857;
            --bg-gray: #fff;
            --amz-border: #e7e7e7;
        }
        
        body { 
            background: var(--bg-gray); 
            font-family: 'Inter', Arial, sans-serif; 
        }

        /* Navbar E-commerce */
        .amazon-nav { 
            background-color: var(--amazon-); 
            color: white; 
            padding: 10px 20px; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 25px; 
        }
        .amazon-brand { 
            font-family: 'Russo One', sans-serif;
            color: white; 
            font-size: 1.6rem; 
            font-weight: 400; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            letter-spacing: 1px;
        }
        .amazon-brand:hover { color: white; }
        .amazon-brand span { color: var(--amazon-yellow); }
        
        /* Barra de Búsqueda */
        .amazon-search { 
            flex-grow: 1; 
            display: flex; 
            max-width: 900px; 
            height: 44px; 
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 0 0 2px transparent;
            transition: box-shadow 0.2s;
        }
        .amazon-search:focus-within {
            box-shadow: 0 0 0 2px var(--amazon-orange);
        }
        .amazon-search select { 
            background: #f3f3f3; 
            border: none; 
            padding: 0 15px; 
            border-right: 1px solid #ccc; 
            width: auto; 
            max-width: 150px; 
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
            outline: none;
        }
        .amazon-search input { 
            flex-grow: 1; 
            border: none; 
            padding: 0 15px; 
            outline: none; 
            font-size: 1rem;
        }
        .amazon-search button { 
            background: var(--amazon-yellow); 
            border: none; 
            padding: 0 20px; 
            color: white; 
            font-size: 1.2rem; 
            cursor: pointer; 
            transition: background 0.2s; 
        }
        .amazon-search button:hover { background: var(--amazon-yellow-hover); }

        /* Acciones Derecha */
        .amazon-actions { display: flex; align-items: center; gap: 20px; }
        .amazon-actions a { 
            color: white; 
            text-decoration: none; 
            display: flex; 
            flex-direction: column; 
            font-size: 0.8rem; 
            line-height: 1.2; 
            padding: 5px 8px;
            border: 1px solid transparent;
            border-radius: 2px;
            transition: border-color 0.2s;
        }
        .amazon-actions a:hover { border-color: rgba(255,255,255,0.7); }
        .amazon-actions a span.fw-bold { font-size: 0.95rem; }

        /* Submenú / Categorías rápidas */
        .amazon-subnav { 
            background-color: #198754; 
            color: white; 
            padding: 8px 20px; 
            display: flex; 
            gap: 15px; 
            font-size: 0.95rem; 
            overflow-x: auto;
            white-space: nowrap;
        }
        .amazon-subnav a { 
            color: white; 
            text-decoration: none; 
            padding: 4px 8px; 
            border: 1px solid transparent; 
            border-radius: 2px; 
        }
        .amazon-subnav a:hover { border-color: white; }

        /* Contenedor del Producto */
        .amz-product-container {
            max-width: 1500px;
            margin: 20px auto;
            padding: 0 20px 40px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }
        .amz-left-col {
            flex: 0 0 35%;
            position: sticky;
            top: 20px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .amz-img-main {
            width: 100%;
            max-width: 500px;
            object-fit: contain;
            border: 1px solid var(--amz-border);
            border-radius: 8px;
            padding: 10px;
        }
        .amz-mid-col {
            flex: 1;
            padding: 0 10px;
        }
        .amz-right-col {
            flex: 0 0 320px;
            border: 1px solid var(--amz-border);
            border-radius: 8px;
            padding: 18px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
        }
        
        .p-title {
            font-size: 1.6rem;
            font-weight: 500;
            line-height: 1.3;
            color: #0f1111;
            margin-bottom: 5px;
        }
        .p-brand { color: #007185; text-decoration: none; font-size: 0.95rem; display: block; }
        .p-brand:hover { color: var(--amazon-orange); text-decoration: underline; }
        
        .amz-rating { color: #ffa41c; font-size: 1.1rem; margin: 10px 0; display: flex; align-items: center; gap: 5px; }
        .amz-rating-count { color: #007185; font-size: 0.95rem; }

        .p-price-block { margin: 15px 0; border-top: 1px solid #e7e7e7; padding-top: 15px; }
        .p-price { font-size: 1.8rem; font-weight: 500; color: #0f1111; display:flex;}
        
        .amz-badge-prime { 
            color: #00a8e1; 
            font-weight: 900; 
            font-style: italic; 
            font-size: 1.1rem; 
            display: flex; 
            align-items: center;
        }
        .amz-badge-prime span { color: #f99000; margin-left: 3px; font-weight: bold;}
        
        /* Lista de Detalles */
        .p-details-list {
            margin-top: 20px;
            font-size: 0.95rem;
            color: #0f1111;
            padding-left: 20px;
        }
        .p-details-list li { margin-bottom: 8px; }

        /* Formulario Comprar (Right Col) */
        .buy-box-price { font-size: 1.8rem; font-weight: 500; color: #0f1111; }
        .buy-box-price-free { font-size: 1.8rem; font-weight: 700; color: #007600; }
        .amz-instock { font-size: 1.1rem; color: #007600; font-weight: 500; margin: 15px 0 10px; }
        
        .metodo-pago-label {
            border: 1px solid #D5D9D9;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            background: #fff;
            transition: all 0.2s;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .metodo-pago-label:hover { background-color: #F7FAFA; }
        .metodo-pago-label.selected { 
            background-color: #FDF8E3; 
            border-color: var(--amazon-orange); 
            box-shadow: 0 0 0 1px var(--amazon-orange); 
        }

        .amz-btn {
            background: var(--amazon-yellow);
            border: 1px solid var(--amazon-orange);
            border-radius: 100px;
            padding: 12px 15px;
            width: 100%;
            font-size: 0.95rem;
            text-align: center;
            color: white;
            text-decoration: none;
            box-shadow: 0 2px 5px rgba(213,217,217,.5);
            display: block;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 15px;
        }
        .amz-btn:hover { background: var(--amazon-yellow-hover); color: white; text-decoration: none; }
        .amz-btn-alt { background: #047857; border-color: #064e3b; }
        .amz-btn-alt:hover { background: #064e3b; }

        /* Estilos para Descripción y Reseñas */
        .description-section {
            background: #fff;
            border-top: 1px solid var(--amz-border);
            padding: 40px 20px 20px;
            margin-top: 30px;
        }
        .reviews-section {
            background: #fff;
            border-top: 1px solid var(--amz-border);
            padding: 40px 20px;
        }
        .reviews-container {
            max-width: 1500px;
            margin: 0 auto;
            display: flex;
            gap: 40px;
        }
        .reviews-left {
            flex: 0 0 300px;
        }
        .reviews-right {
            flex: 1;
        }
        .review-card {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--amz-border);
        }
        .review-card:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .review-author {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 3px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .review-avatar {
            width: 28px;
            height: 28px;
            background: #e7e7e7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
            font-size: 0.8rem;
        }
        .review-stars i {
            color: #f99000;
            font-size: 0.8rem;
        }
        .review-date {
            font-size: 0.8rem;
            color: #565959;
            margin-bottom: 6px;
            display: inline-block;
        }
        .review-body {
            font-size: 0.85rem;
            line-height: 1.4;
            color: #0f1111;
        }
        .star-input {
            display: none;
        }
        .star-label {
            cursor: pointer;
            color: #ddd;
            font-size: 1.5rem;
            transition: color 0.1s;
            float: right;
            padding: 0 2px;
        }
        .star-input:checked ~ .star-label,
        .star-label:hover,
        .star-label:hover ~ .star-label {
            color: #f99000;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .amz-product-container { flex-direction: column; }
            .amz-left-col, .amz-mid-col, .amz-right-col { flex: auto; width: 100%; position: static; }
            .amazon-search select { display: none; }
            .amazon-nav { padding: 10px; gap: 15px; }
            .amazon-brand { font-size: 1.2rem; }
        }
        @media (max-width: 768px) {
            .amazon-nav { flex-wrap: wrap; justify-content: space-between; }
            .amazon-search { order: 3; width: 100%; max-width: 100%; margin-top: 10px; }
            .amazon-actions { gap: 10px; }
            .amazon-actions a { font-size: 0.75rem; padding: 2px; }
            .amazon-actions a span.fw-bold { font-size: 0.85rem; }
            .amazon-brand { font-size: 1rem; width: 100%; text-align: center; justify-content: center; margin-bottom: 5px; }
            /* Mostrar actions arriba o dejarlos en flex-end */
            .amazon-nav { flex-direction: column; align-items: stretch; }
            .amazon-nav > div { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        }

        .qr-preview {
            max-width: 120px;
            border-radius: 8px;
            margin-top: 5px;
            display: none; /* Se muestra por JS */
        }
        
    </style>
</head>
<body>

<!-- Navegación Superior -->
<nav class="amazon-nav" style="background-color: var(--amazon-dark);">
    <div class="d-flex align-items-center w-100 justify-content-between flex-wrap" style="gap: 15px;">
        <a href="index.php" class="amazon-brand mb-0 text-center w-sm-100">
            <i class="fas fa-gamepad me-2 text-white"></i> PES <span class="ms-1 text-danger">BOLIVIA</span> &nbsp; <span class="text-warning">EDITION</span> &nbsp; <span class="text-success">STORE</span>
        </a>
    
    <form method="GET" action="tienda.php" class="amazon-search">
        <select name="categoria">
            <option value="0">Todos los deptos.</option>
            <?php foreach ($categorias_array as $cat): ?>
                <option value="<?php echo $cat['id']; ?>">
                    <?php echo $cat['nombre']; ?>
                </option>
            <?php
endforeach; ?>
        </select>
        <input type="text" name="buscar" placeholder="Busca productos, actualizaciones exclusivas...">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>
    
    <div class="amazon-actions">
        <?php if ($is_logged): ?>
            <a href="modules/<?php echo $user_role; ?>/dashboard.php">
                <span>Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
                <span class="fw-bold">Cuenta y Panel</span>
            </a>
            <?php if ($user_role === ROL_COMPRADOR): ?>
                <a href="modules/comprador/mis_compras.php">
                    <span>Devoluciones</span>
                    <span class="fw-bold">y Pedidos</span>
                </a>
            <?php
    endif; ?>
            <a href="logout.php">
                <span>Sesión</span>
                <span class="fw-bold text-danger">Cerrar</span>
            </a>
        <?php
else: ?>
            <a href="login.php">
                <span>Hola, Invitado</span>
                <span class="fw-bold">Ingresar</span>
            </a>
            <a href="registro.php" class="d-none d-sm-flex">
                <span>¿Eres nuevo?</span>
                <span class="fw-bold">Empieza aquí.</span>
            </a>
        <?php
endif; ?>
    </div>
    </div>
</nav>

<!-- Subnavegación -->
<div class="amazon-subnav">
    <a href="tienda.php"><i class="fas fa-bars me-1"></i> Todo</a>
    <a href="tienda.php?tipo=pago">Más Vendidos</a>
    <a href="tienda.php?tipo=gratuito">Promociones y Gratis</a>
    <?php foreach (array_slice($categorias_array, 0, 5) as $cat): ?>
        <a href="tienda.php?categoria=<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></a>
    <?php
endforeach; ?>
</div>

<div class="amz-product-container mt-4">
    <!-- Izquierda: Imagen -->
    <div class="amz-left-col">
        <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
            <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="amz-img-main">
        <?php
else: ?>
            <div class="amz-img-main bg-light d-flex align-items-center justify-content-center" style="height: 300px;">
                <i class="fas fa-image fa-5x text-secondary opacity-25"></i>
            </div>
        <?php
endif; ?>
    </div>
    
    <!-- Centro: Detalles -->
    <div class="amz-mid-col">
        <h1 class="p-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
        <a href="tienda.php?categoria=<?php echo $producto['id_categoria']; ?>" class="p-brand">Ir a la tienda de <?php echo $producto['categoria_nombre'] ?? 'PES Bolivia'; ?></a>
        
        <div class="amz-rating" style="cursor: pointer;" onclick="document.getElementById('resenas-section').scrollIntoView({behavior: 'smooth'})">
            <?php
$rating = $promedio_valoracion;
$entero = floor($rating);
$mitad = ($rating - $entero) >= 0.5 ? 1 : 0;
$vacio = 5 - $entero - $mitad;
for ($i = 0; $i < $entero; $i++)
    echo '<i class="fas fa-star"></i>';
if ($mitad)
    echo '<i class="fas fa-star-half-alt"></i>';
for ($i = 0; $i < $vacio; $i++)
    echo '<i class="far fa-star"></i>';
?>
            <span class="ms-2 fw-bold text-dark"><?php echo number_format($rating, 1); ?> de 5</span>
            <span class="amz-rating-count ms-2"><?php echo $total_resenas; ?> calificaciones globales</span>
        </div>
        
        <div class="amz-badge-prime mb-3 d-none">
            <i class="fas fa-check" style="color: #f99000;"></i> PES <span>PRIME</span>
        </div>

        <div class="p-price-block">
            <?php if ((isset($producto['es_gratuito']) && strtolower($producto['es_gratuito']) === 'si') || empty($producto['precio']) || $producto['precio'] == 0): ?>
                <span class="text-success fw-bold" style="font-size: 1.8rem;">GRATIS</span>
            <?php
else:
    $montoParts = explode('.', number_format($producto['precio'], 2));
?>
                <span class="text-secondary" style="font-size: 0.9rem;">Precio:</span>
                <div class="p-price text-danger d-flex align-items-baseline">
                    <div>
                        <span style="font-size: 0.8rem; position: relative; top: -0.7em;">Bs.</span>
                        <span style="font-size: 2rem; font-weight: 500; line-height:1;"><?php echo $montoParts[0]; ?></span>
                        <span style="font-size: 0.85rem; position: relative; top: -0.7em;"><?php echo $montoParts[1]; ?></span>
                    </div>
                    <?php $precio_usd = $producto['precio'] / 6.96; ?>
                    <span class="text-secondary ms-2" style="font-size: 1.1rem; font-weight: 400;">(aprox. $<?php echo number_format($precio_usd, 2); ?>)</span>
                </div>
                <div class="text-secondary mt-1" style="font-size: 0.85rem;">Devoluciones GRATIS en componentes elegibles.</div>
            <?php
endif; ?>
        </div>
        
        <div class="mt-4">
            <h5 class="fw-bold" style="font-size: 1.1rem; color: #0f1111;">Acerca de este artículo</h5>
            <ul class="p-details-list">
                <?php if (!empty($producto['version'])): ?>
                    <li><strong>Versión Soportada:</strong> <?php echo htmlspecialchars($producto['version']); ?></li>
                <?php
endif; ?>
                <?php if (!empty($producto['requisitos'])): ?>
                    <li><strong>Requisitos de Sistema:</strong> <?php echo htmlspecialchars($producto['requisitos']); ?></li>
                <?php
endif; ?>
                <li><strong>Entrega Digital Inmediata:</strong> Enlace de descarga seguro y garantizado libre de virus.</li>
                <li><strong>Actualizaciones:</strong> Acceso continuo a parches menores por parte del creador.</li>
            </ul>
        </div>
        
    </div>
    
    <!-- Derecha: Buy Box -->
    <div class="amz-right-col">
        <?php if ((isset($producto['es_gratuito']) && strtolower($producto['es_gratuito']) === 'si') || empty($producto['precio']) || $producto['precio'] == 0):
    $link_descarga = $producto['link_descarga_directa'] ?? $producto['drive_link'] ?? '#';
?>
            <div class="buy-box-price text-success mb-1">Bs. 0.00 <span style="font-size:1.2rem; font-weight:700;">GRATUITO</span></div>
            <div class="text-secondary mb-2" style="font-size: 0.9rem;">Transacciones 100% Seguras.</div>
            
            <div class="amz-instock">Disponible.</div>
            <div class="text-dark mb-4" style="font-size: 0.9rem;">Enviado de forma digital por <br> <strong>PES Bolivia Store</strong></div>
            
            <a href="<?php echo $link_descarga; ?>" target="_blank" class="amz-btn text-white d-flex justify-content-center align-items-center">
                <i class="fas fa-download me-2" style="font-size:1.1rem;"></i> Descargar Gratis Aquí
            </a>
            
        <?php
else: ?>
            <?php $precio_usd = $producto['precio'] / 6.96; ?>
            <div class="buy-box-price text-danger mb-1 d-flex flex-column">
                <span>Bs. <?php echo number_format($producto['precio'], 2); ?></span>
                <span class="text-secondary" style="font-size: 1rem; font-weight: 500;">(USD $<?php echo number_format($precio_usd, 2); ?>)</span>
            </div>
            <div class="text-secondary mb-2" style="font-size: 0.9rem;">Transacciones 100% Seguras.</div>
            
            <div class="amz-instock">Disponible.</div>
            <div class="text-dark mb-4" style="font-size: 0.9rem;">Enviado de forma digital por <br> <strong>PES Bolivia Store</strong></div>
            
            <?php if (!$is_logged): ?>
                <a href="login.php" class="amz-btn">Inicia Sesión para Comprar</a>
            <?php
    elseif ($ya_comprado): ?>
                <div class="alert alert-success p-2 mb-3" style="font-size: 0.85rem;">
                    <i class="fas fa-check-circle me-1"></i> Ya compraste este ítem.
                </div>
                <a href="modules/comprador/mis_compras.php" class="amz-btn amz-btn-alt">Ir a mis Pedidos</a>
            <?php
    else: ?>
                <h6 class="fw-bold" style="font-size:0.95rem;">Finalizar Compra</h6>
                
                <form method="POST" action="procesar_compra.php" enctype="multipart/form-data" id="formCompra">
                    <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                    <input type="hidden" name="id_tipo_pago" id="id_tipo_pago">
                    
                    <div style="font-size: 0.85rem; font-weight:700; color: #0f1111; margin-bottom: 5px;">Módulos de Pago:</div>
                    
                    <?php if (mysqli_num_rows($tipos_pago) > 0): ?>
                        <?php while ($tipo = mysqli_fetch_assoc($tipos_pago)): ?>
                            <div class="metodo-pago-label" onclick="selectMetodo(<?php echo $tipo['id']; ?>, '<?php echo !empty($tipo['imagen_qr']) ? BASE_URL . '/' . $tipo['imagen_qr'] : ''; ?>')">
                                <input type="radio" name="temp_pago" class="form-check-input mt-0" style="cursor:pointer;" onchange="selectMetodo(<?php echo $tipo['id']; ?>, '<?php echo !empty($tipo['imagen_qr']) ? BASE_URL . '/' . $tipo['imagen_qr'] : ''; ?>')">
                                <div class="w-100">
                                    <div class="fw-bold"><i class="fas fa-money-bill-wave text-success me-1"></i> <?php echo $tipo['nombre']; ?></div>
                                    <?php if ($tipo['tipo'] === 'transferencia'): ?>
                                        <div class="text-muted" style="font-size: 0.75rem; margin-top:2px;">
                                            <?php if (!empty($tipo['banco']))
                        echo $tipo['banco'] . ' '; ?>
                                            <?php if (!empty($tipo['numero_cuenta']))
                        echo '<br>#' . $tipo['numero_cuenta']; ?>
                                        </div>
                                    <?php
                endif; ?>
                                </div>
                            </div>
                        <?php
            endwhile; ?>
                    <?php
        else: ?>
                        <div class="text-danger small mb-3">No hay métodos de pago habilitados.</div>
                    <?php
        endif; ?>
                    
                    <div id="comprobanteSection" style="display: none; background: #f7fafa; padding: 10px; border-radius: 6px; border: 1px solid #D5D9D9; margin-top: 15px;">
                        <!-- QR Preview -->
                        <div class="text-center w-100 mb-2" style="position: relative;">
                            <img id="qr_img" src="" alt="QR" class="qr-preview" style="max-height: 150px; border-radius: 6px; transition: transform 0.3s ease; cursor: zoom-in;" onmouseover="this.style.transform='scale(1.8)'; this.style.position='relative'; this.style.zIndex='1050';" onmouseout="this.style.transform='scale(1)'; this.style.zIndex='1';">
                            <div class="mt-2 text-center w-100">
                                <a id="btn_descargar_qr" href="#" download="QR_Pago.jpg" class="amz-btn amz-btn-alt d-none" style="padding: 4px 10px; font-size: 0.85rem; width: 100%;">
                                    <i class="fas fa-download me-1"></i> Descargar Imagen QR
                                </a>
                            </div>
                        </div>
                        
                        <div style="font-size: 0.85rem; font-weight:700; color: #0f1111; margin-bottom: 5px;">Tu comprobante:</div>
                        <input type="file" class="form-control form-control-sm mb-2" name="comprobante" accept="image/jpeg,image/png,image/jpg,application/pdf" required style="font-size: 0.75rem;">
                        <button type="submit" class="amz-btn amz-btn-alt w-100 mb-1">
                            <i class="fas fa-lock me-1"></i> Pago Seguro y Finalizar
                        </button>
                    </div>
                </form>
            <?php
    endif; ?>
            
            <div class="text-center mt-3">
                <i class="fas fa-shield-alt text-secondary"></i>
                <a href="#" class="text-secondary ms-1" style="font-size: 0.8rem; text-decoration: none;">Transacción segura</a>
            </div>
        <?php
endif; ?>
    </div>
</div>

<!-- Descripción Full Width -->
<div class="description-section">
    <div style="max-width: 1500px; margin: 0 auto;">
        <h4 class="fw-bold mb-4" style="color: #0f1111; font-size: 1.5rem;">Información del Producto</h4>
        <div class="text-dark" style="font-size: 1rem; line-height: 1.8;">
            <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
        </div>
    </div>
</div>

<!-- Sección de Reseñas -->
<div class="reviews-section" id="resenas-section">
    <div class="reviews-container">
        <!-- Columna Izquierda: Escribir reseña y barra global -->
        <div class="reviews-left">
            <h4 class="fw-bold mb-3">Opiniones de clientes</h4>
            
            <div class="d-flex align-items-center mb-1 amz-rating" style="font-size:1.2rem;">
                <?php
for ($i = 0; $i < $entero; $i++)
    echo '<i class="fas fa-star"></i>';
if ($mitad)
    echo '<i class="fas fa-star-half-alt"></i>';
for ($i = 0; $i < $vacio; $i++)
    echo '<i class="far fa-star"></i>';
?>
                <span class="ms-2 fw-bold text-dark"><?php echo number_format($rating, 1); ?> de 5</span>
            </div>
            <div class="text-secondary mb-4" style="font-size:0.9rem;"><?php echo $total_resenas; ?> calificaciones globales</div>
            
            <hr class="mb-4 text-muted">

            <h5 class="fw-bold mb-3">Revisar este producto</h5>
            <p class="text-secondary mb-3" style="font-size:0.9rem;">Comparte tus pensamientos con otros clientes</p>
            
            <?php if (!empty($success_review)): ?>
                <div class="alert alert-success p-2 small"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_review); ?></div>
            <?php
endif; ?>
            <?php if (!empty($error_review)): ?>
                <div class="alert alert-danger p-2 small"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_review); ?></div>
            <?php
endif; ?>

            <?php if (!$is_logged): ?>
                <a href="login.php" class="amz-btn text-center" style="background:#fff; border:1px solid #d5d9d9; color:#0f1111; box-shadow:0 2px 5px rgba(213,217,217,.5);">
                    Inicia Sesión para opinar
                </a>
            <?php
elseif ($user_ya_reseno): ?>
                <div class="alert alert-info p-2" style="font-size: 0.85rem;"><i class="fas fa-info-circle me-1"></i> Ya has publicado una opinión para este producto.</div>
            <?php
else: ?>
                <button class="amz-btn" style="background:#fff; border:1px solid #d5d9d9; color:#0f1111; box-shadow:0 2px 5px rgba(213,217,217,.5);" onclick="document.getElementById('formReviewContainer').style.display='block'; this.style.display='none';">
                    Escribir una opinión
                </button>
                
                <div id="formReviewContainer" style="display:none; margin-top:15px; background: #f7fafa; padding: 15px; border-radius: 8px; border: 1px solid #e7e7e7;">
                    <form action="procesar_resena.php" method="POST">
                        <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                        
                        <div class="mb-2 fw-bold" style="font-size: 0.9rem;">Calificación general</div>
                        <div class="mb-3" id="star-rating" style="display: inline-block;">
                            <input type="radio" class="star-input" name="calificacion" id="star5" value="5" required>
                            <label for="star5" class="star-label"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" class="star-input" name="calificacion" id="star4" value="4">
                            <label for="star4" class="star-label"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" class="star-input" name="calificacion" id="star3" value="3">
                            <label for="star3" class="star-label"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" class="star-input" name="calificacion" id="star2" value="2">
                            <label for="star2" class="star-label"><i class="fas fa-star"></i></label>
                            
                            <input type="radio" class="star-input" name="calificacion" id="star1" value="1">
                            <label for="star1" class="star-label"><i class="fas fa-star"></i></label>
                        </div>
                        <div style="clear: both; margin-bottom: 20px;"></div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold" style="font-size:0.9rem;">Añadir una opinión escrita</label>
                            <textarea class="form-control" name="comentario" rows="4" placeholder="¿Qué te ha parecido este producto? ¿Cómo le fue en el juego?" required style="font-size:0.85rem; resize:none;"></textarea>
                        </div>
                        
                        <button type="submit" class="amz-btn" style="padding:8px 15px;">Enviar opinión</button>
                    </form>
                </div>
            <?php
endif; ?>
        </div>
        
        <!-- Columna Derecha: Lista de reseñas -->
        <div class="reviews-right">
            <?php if (mysqli_num_rows($resenas_list) > 0): ?>
                <?php while ($resena = mysqli_fetch_assoc($resenas_list)): ?>
                    <div class="review-card">
                        <div class="review-author">
                            <div class="review-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="text-dark"><?php echo htmlspecialchars($resena['usuario_nombre']); ?></span>
                            <?php if ($resena['rol'] === 'superadmin' || $resena['rol'] === 'editor'): ?>
                                <span class="badge bg-danger ms-1" style="font-size:0.6rem;">VERIFICADO</span>
                            <?php
        endif; ?>
                        </div>
                        
                        <div class="review-stars mb-1">
                            <?php
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $resena['calificacion']) {
                echo '<i class="fas fa-star"></i>';
            }
            else {
                echo '<i class="far fa-star"></i>';
            }
        }
?>
                        </div>
                        
                        <div class="review-date">Revisado en Bolivia el <?php echo date('d \d\e m Y', strtotime($resena['fecha_creacion'])); ?></div>
                        
                        <div class="review-body">
                            <?php echo nl2br(htmlspecialchars($resena['comentario'])); ?>
                        </div>
                    </div>
                <?php
    endwhile; ?>
                
                <?php if ($total_paginas_resenas > 1): ?>
                    <div class="mt-4 text-center">
                        <?php if ($pagina_resenas < $total_paginas_resenas): ?>
                            <a href="producto.php?id=<?php echo $producto['id']; ?>&pagina_resenas=<?php echo $pagina_resenas + 1; ?>#resenas-section" class="amz-btn d-inline-block w-auto px-4" style="background:#fff; border:1px solid #d5d9d9; color:#0f1111; padding: 6px 15px;">Ver más opiniones</a>
                        <?php
        endif; ?>
                        
                        <?php if ($pagina_resenas > 1): ?>
                            <a href="producto.php?id=<?php echo $producto['id']; ?>&pagina_resenas=<?php echo $pagina_resenas - 1; ?>#resenas-section" class="btn btn-link text-secondary mt-2 ms-2" style="font-size:0.85rem; text-decoration:none;">Ver anteriores</a>
                        <?php
        endif; ?>
                    </div>
                <?php
    endif; ?>
                
            <?php
else: ?>
                <div class="text-center py-5">
                    <img src="https://m.media-amazon.com/images/G/01/error/500_dog_6._V392223706_.png" alt="No reseñas" style="max-height: 120px; opacity: 0.5; margin-bottom:15px;">
                    <h5 class="text-secondary"> Aún no hay reseñas activas.</h5>
                    <p class="text-muted small">Sé el primero en compartir tu opinión sobre este excelente parche u opción.</p>
                </div>
            <?php
endif; ?>
        </div>
    </div>
</div>

<footer class="mt-5 bg-dark">
    <div style="background-color: #37475a; padding: 15px 0; text-align: center; cursor: pointer;" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
        <a class="text-white text-decoration-none" style="font-size:0.95rem; font-weight: 500;">Inicio de página</a>
    </div>
    <div style="background-color: #232f3e; padding: 50px 20px; color: white;">
        <div class="container text-center">
            <h4 class="mb-4 d-flex justify-content-center align-items-center">
                <i class="fas fa-gamepad text-white me-2"></i> PES <span style="color: #ffd814" class="ms-1">Bolivia</span>
            </h4>
            <div class="d-flex justify-content-center gap-4 mb-4" style="font-size: 0.9rem;">
                <a href="#" class="text-white text-decoration-none">Condiciones de Uso</a>
                <a href="#" class="text-white text-decoration-none">Aviso de Privacidad</a>
                <a href="#" class="text-white text-decoration-none">Tus opciones de Privacidad</a>
            </div>
            <p class="text-white opacity-75 mb-0" style="font-size: 0.85rem;">&copy; <?php echo date('Y'); ?>. Replicación visual E-Commerce global. Todos los derechos reservados.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function selectMetodo(id, qr_url) {
        // Remover selección visual anterior
        document.querySelectorAll('.metodo-pago-label').forEach(el => {
            el.classList.remove('selected');
            el.querySelector('input[type="radio"]').checked = false;
        });
        
        // Aplicar selección al clickeado (via event currentTarget o si fue desde input)
        let container = event.currentTarget.closest('.metodo-pago-label');
        if(container) {
            container.classList.add('selected');
            container.querySelector('input[type="radio"]').checked = true;
        }
        
        document.getElementById('id_tipo_pago').value = id;
        
        // Manejar QR
        let qrImg = document.getElementById('qr_img');
        let btnDescargarQr = document.getElementById('btn_descargar_qr');
        if(qr_url) {
            qrImg.src = qr_url;
            qrImg.style.display = 'inline-block';
            
            btnDescargarQr.href = qr_url;
            btnDescargarQr.classList.remove('d-none');
            // Extract filename from URL to set proper download name
            const fileName = qr_url.substring(qr_url.lastIndexOf('/') + 1) || 'QR_Pago.jpg';
            btnDescargarQr.setAttribute('download', fileName);
        } else {
            qrImg.style.display = 'none';
            btnDescargarQr.classList.add('d-none');
        }
        
        // Mostrar form
        document.getElementById('comprobanteSection').style.display = 'block';
    }
    
    // Validar formulario pre-submit
    let amzForm = document.getElementById('formCompra');
    if(amzForm) {
        amzForm.addEventListener('submit', function(e) {
            const id_tipo_pago = document.getElementById('id_tipo_pago').value;
            if (!id_tipo_pago) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona un método de pago',
                    confirmButtonColor: '#f7ca00'
                });
                return false;
            }
        });
    }
</script>
</body>
</html>