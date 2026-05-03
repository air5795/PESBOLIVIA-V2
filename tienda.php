<?php
require_once 'config/config.php';
require_once 'config/session.php';
require_once 'includes/funciones.php';

$page_title = 'Tienda';

// Obtener categorías activas
$query_categorias = "SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC";
$categorias = mysqli_query($conexion, $query_categorias);

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'todos'; // todos, pago, gratuito
$busqueda = isset($_GET['buscar']) ? limpiar_texto($_GET['buscar']) : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'ultimos';

$id_usuario_actual = Session::get_user_id() ?? 0;

$query = "SELECT p.*, c.nombre as categoria_nombre, 
          COALESCE(AVG(r.calificacion), 0) as promedio_resenas, 
          COUNT(r.id) as total_resenas,
          u.usuario as editor_nombre,
          (SELECT COUNT(*) FROM producto_editores pe2 WHERE pe2.id_producto = p.id AND pe2.id_editor = $id_usuario_actual) as es_mi_producto
          FROM productos p
          LEFT JOIN categorias c ON p.id_categoria = c.id
          LEFT JOIN resenas r ON p.id = r.id_producto
          LEFT JOIN (
              SELECT id_producto, MIN(id) as first_pe_id 
              FROM producto_editores 
              GROUP BY id_producto
          ) pe_first ON p.id = pe_first.id_producto
          LEFT JOIN producto_editores pe ON pe_first.first_pe_id = pe.id
          LEFT JOIN usuarios u ON pe.id_editor = u.id
          WHERE p.estado = 'activo'";

// Aplicar filtro de categoría
if ($filtro_categoria > 0) {
    $query .= " AND p.id_categoria = $filtro_categoria";
}

// Aplicar filtro de tipo (pago/gratuito)
if ($filtro_tipo === 'pago') {
    $query .= " AND (p.es_gratuito = 'no' OR p.es_gratuito IS NULL) AND p.precio > 0";
}
elseif ($filtro_tipo === 'gratuito') {
    $query .= " AND (p.es_gratuito = 'si' OR p.precio = 0 OR p.precio IS NULL)";
}

// Aplicar búsqueda
if (!empty($busqueda)) {
    $busqueda_escapada = mysqli_real_escape_string($conexion, $busqueda);
    $query .= " AND (p.nombre LIKE '%$busqueda_escapada%' 
                OR p.descripcion LIKE '%$busqueda_escapada%'
                OR c.nombre LIKE '%$busqueda_escapada%')";
}

// Determinar orden
$order_by = "p.fecha_creacion DESC"; // Default: ultimos agregados
if ($orden === 'destacados') {
    $order_by = "promedio_resenas DESC, p.fecha_creacion DESC";
}
elseif ($orden === 'precio_asc') {
    $order_by = "p.precio ASC, p.fecha_creacion DESC";
}
elseif ($orden === 'precio_desc') {
    $order_by = "p.precio DESC, p.fecha_creacion DESC";
}
elseif ($orden === 'novedades' || $orden === 'ultimos') {
    $order_by = "p.fecha_creacion DESC";
}

$query .= " GROUP BY p.id ORDER BY p.es_gratuito ASC, " . $order_by;

// Obtener total para "Ver más"
$query_count = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
$res_count = mysqli_query($conexion, $query_count);
$row_count = mysqli_fetch_assoc($res_count);
$total_productos = $row_count['total'];

// Aplicar paginación (Load More style)
$limit = isset($_GET['limit']) ? max(10, intval($_GET['limit'])) : 10;
$query .= " LIMIT " . $limit;

$productos = mysqli_query($conexion, $query);

// Verificar si el usuario está logueado
$is_logged = Session::is_logged_in();
$user_role = $is_logged ?Session::get_user_role() : null;

// Convertir categorias a array para fácil acceso y recursión
$categorias_array = [];
if ($categorias) {
    while ($row = mysqli_fetch_assoc($categorias)) {
        $categorias_array[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . NOMBRE_SISTEMA; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Russo+One&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --amazon-dark: #0f1111;
            --amazon-light: #1a1e23;
            --amazon-yellow: #10b981;
            --amazon-yellow-hover: #059669;
            --amazon-orange: #047857;
            --bg-gray: #f2f4f8;
            --amz-border: #e7e7e7;
        }
        
        body { 
            background: var(--bg-gray); 
            font-family: 'Inter', Arial, sans-serif; 
        }

        /* Navbar E-commerce */
        .amazon-nav { 
            background-color: var(--amazon-dark); 
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

        /* Estructura Principal */
        .main-wrapper { 
            max-width: 1600px; 
            margin: 0 auto; 
            padding: 20px; 
            display: flex; 
            gap: 30px; 
        }
        
        /* Menú Lateral */
        .sidebar-filters { 
            width: 260px; 
            flex-shrink: 0; 
            background: white; 
            padding: 25px 20px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
            height: fit-content; 
            border: 1px solid var(--amz-border);
        }
        .sidebar-filters h5 { 
            font-weight: 700; 
            font-size: 1rem; 
            margin-bottom: 12px; 
            color: #0f1111; 
        }
        .filter-list { list-style: none; padding: 0; margin: 0 0 30px 0; }
        .filter-list li { margin-bottom: 8px; }
        .filter-list a { 
            color: #0f1111; 
            font-size: 0.95rem;
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            transition: color 0.1s; 
        }
        .filter-list a:hover { color: var(--amazon-orange); text-decoration: underline; }
        .filter-list a.active { font-weight: 700; color: var(--amazon-dark); }

        /* Área de Productos */
        .products-content { flex-grow: 1; min-width: 0; }
        .products-header { 
            background: white; 
            padding: 15px 25px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
            display: flex; 
            border: 1px solid var(--amz-border);
            justify-content: space-between; 
            align-items: center; 
        }
        
        /* Grid de Productos */
        .products-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
            gap: 20px; 
        }

        /* Tarjeta de Producto Estilo Amazon */
        .amz-card { 
            background: white; 
            border-radius: 12px; 
            padding: 10px; 
            display: flex; 
            flex-direction: column; 
            border: 1px solid var(--amz-border); 
            position: relative; 
            height: 100%; 
            transition: border-color 0.3s ease, box-shadow 0.3s ease, transform 0.3s ease;
        }
        .amz-card:hover { border-color: rgba(0,0,0,0.2); box-shadow: 0 8px 15px rgba(0,0,0,0.08); transform: translateY(-4px); }
        
        /* New Free Card Color */
        .amz-card-free {
            background-color: #f5f8ff; /* fondo azul suave para diferenciar */
            border-color: #d1d5db;
        }

        /* Ribbon para Gratis */
        .corner-ribbon {
            position: absolute;
            top: -6px;
            left: -6px;
            width: 100px;
            height: 100px;
            overflow: hidden;
            z-index: 10;
        }
        .corner-ribbon::before,
        .corner-ribbon::after {
            content: '';
            position: absolute;
            z-index: -1;
            display: block;
            border: 3px solid #6d28d9;
        }
        .corner-ribbon::before { top: 0; right: 0; }
        .corner-ribbon::after { bottom: 0; left: 0; }
        .corner-ribbon span {
            position: absolute;
            display: block;
            width: 150px;
            padding: 6px 0;
            background: linear-gradient(135deg, #a855f7 0%, #8b5cf6 100%);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
            top: 20px;
            right: -25px;
            transform: rotate(-45deg);
            letter-spacing: 1px;
        }
        
        .amz-img-wrapper { 
            height: 220px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-bottom: 8px; 
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
        }
        .amz-img-wrapper img { width: 100%; height: 100%; object-fit: cover; }
        
        .amz-card-title { 
            font-size: 1rem; 
            color: #0f1111; 
            font-weight: 500; 
            line-height: 1.3; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden; 
            text-decoration: none;
            margin-bottom: 5px;
        }
        .amz-card-title:hover { color: var(--amazon-orange); text-decoration: none; }
        
        .amz-rating { color: #ffa41c; font-size: 0.8rem; margin: 0; display: flex; align-items: center; gap: 3px; }
        .amz-rating-count { color: #007185; font-size: 0.75rem; }
        .amz-rating-count:hover { color: var(--amazon-orange); text-decoration: underline; cursor: pointer; }
        
        .amz-badge-prime { 
            color: #00a8e1; 
            font-weight: 900; 
            font-style: italic; 
            font-size: 1rem; 
            margin-bottom: 5px; 
            display: flex; 
            align-items: center;
            line-height: 1;
        }
        .amz-badge-prime span { color: #f99000; margin-left: 3px; font-weight: bold;}
        
        .amz-price-block { margin-top: auto; margin-bottom: 8px; }
        .amz-price-symbol { font-size: 0.7rem; position: relative; top: -0.7em; }
        .amz-price-whole { font-size: 1.6rem; font-weight: 600; }
        .amz-price-fraction { font-size: 0.8rem; position: relative; top: -0.7em; }
        
        .amz-btn { 
            background: var(--amazon-yellow); 
            border: 1px solid var(--amazon-orange); 
            border-radius: 100px; 
            padding: 8px 12px; 
            width: 100%; 
            font-size: 0.9rem; 
            text-align: center; 
            color: white; 
            text-decoration: none; 
            transition: background 0.2s, box-shadow 0.2s; 
            box-shadow: 0 2px 4px rgba(213,217,217,.5); 
            display: block; 
            font-weight: 500;
        }
        .amz-btn:hover { background: var(--amazon-yellow-hover); color: white; text-decoration: none; }
        .amz-btn-free { background: #F6F6F6; border-color: #D5D9D9; color: #0f1111; }
        .amz-btn-free:hover { background: #E3E6E6; color: #0f1111; }

        /* Responsive */
        @media (max-width: 991px) {
            .main-wrapper { flex-direction: column; }
            .sidebar-filters { width: 100%; display: flex; gap: 30px; flex-wrap: wrap; }
            .sidebar-filters > div { flex: 1; min-width: 200px; }
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
    </style>
</head>
<body>

<!-- Navegación Superior Estilo Amazon -->
<nav class="amazon-nav">
    <div class="d-flex align-items-center w-100 justify-content-between flex-wrap" style="gap: 15px;">
        <a href="index.php" class="amazon-brand mb-0 text-center w-sm-100">
            <i class="fas fa-gamepad me-2 text-white"></i> PES <span class="ms-1 text-danger">BOLIVIA</span> &nbsp; <span class="text-warning">EDITION</span> &nbsp; <span class="text-success">STORE</span>
        </a>
    
    <form method="GET" action="tienda.php" class="amazon-search">
        <select name="categoria">
            <option value="0">Buscar por</option>
            <?php foreach ($categorias_array as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo($filtro_categoria == $cat['id']) ? 'selected' : ''; ?>>
                    <?php echo $cat['nombre']; ?>
                </option>
            <?php
endforeach; ?>
        </select>
        <input type="text" name="buscar" placeholder="Busca productos, actualizaciones exclusivas..." value="<?php echo htmlspecialchars($busqueda); ?>">
        <?php if ($filtro_tipo !== 'todos'): ?>
            <input type="hidden" name="tipo" value="<?php echo $filtro_tipo; ?>">
        <?php
endif; ?>
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

<!-- Subnavegación de Promociones -->
<div class="amazon-subnav">
    <a href="tienda.php" style="<?php echo ($filtro_categoria == 0 && $filtro_tipo == 'todos' && empty($busqueda)) ? 'border-color: white; font-weight: bold;' : ''; ?>">
        <i class="fas fa-bars me-1"></i> Todo
    </a>
    <a href="tienda.php?tipo=pago" style="<?php echo ($filtro_tipo == 'pago') ? 'border-color: white; font-weight: bold;' : ''; ?>">
        <i class="fas fa-fire me-1 text-warning"></i> Más Vendidos
    </a>
    <a href="tienda.php?tipo=gratuito" style="<?php echo ($filtro_tipo == 'gratuito') ? 'border-color: white; font-weight: bold;' : ''; ?>">
        <i class="fas fa-gift me-1"></i> Promociones y Gratis
    </a>
    <?php foreach ($categorias_array as $cat): ?>
        <a href="tienda.php?categoria=<?php echo $cat['id']; ?>" style="<?php echo ($filtro_categoria == $cat['id']) ? 'border-color: white; font-weight: bold;' : ''; ?>">
            <?php if (!empty($cat['icono'])): ?>
                <i class="fas <?php echo $cat['icono']; ?> me-1 opacity-75"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($cat['nombre']); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="main-wrapper" style="max-width: 1400px; margin: 0 auto;">
    
    <!-- Menú Lateral -->
    <div class="sidebar-filters d-none d-lg-block">
        <h5>Departamentos</h5>
        <ul class="filter-list">
            <li><a href="tienda.php" class="<?php echo ($filtro_categoria == 0 && $filtro_tipo == 'todos' && empty($busqueda)) ? 'active' : ''; ?>">Todas las categorías</a></li>
            <li><a href="tienda.php?tipo=pago" class="<?php echo ($filtro_tipo == 'pago') ? 'active' : ''; ?>">Más Vendidos</a></li>
            <li><a href="tienda.php?tipo=gratuito" class="<?php echo ($filtro_tipo == 'gratuito') ? 'active' : ''; ?>">Promociones y Gratis</a></li>
            
            <h5 class="mt-4 text-dark">Categorías</h5>
            <?php foreach ($categorias_array as $cat): ?>
                <li>
                    <a href="tienda.php?categoria=<?php echo $cat['id']; ?>" class="<?php echo ($filtro_categoria == $cat['id']) ? 'active' : ''; ?>">
                        <i class="fas <?php echo !empty($cat['icono']) ? $cat['icono'] : 'fa-folder'; ?> text-secondary me-2" style="width:16px; text-align:center;"></i>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Contenido Central (Grid) -->
    <div class="products-content">
        <div class="products-header">
            <div>
                <strong>Mostrando <?php echo mysqli_num_rows($productos); ?> de <?php echo $total_productos; ?> resultados</strong> para "<?php echo(!empty($busqueda)) ? htmlspecialchars($busqueda) : 'Productos'; ?>"
            </div>
            <div>
                <?php
$url_params = $_GET;
unset($url_params['orden']);
$base_url = 'tienda.php?' . http_build_query($url_params);
if (!empty($url_params))
    $base_url .= '&';
?>
                <select class="form-select form-select-sm d-inline-block w-auto" style="border-radius: 8px; border-color: #D5D9D9; background-color: #F0F2F2; box-shadow: 0 2px 5px rgba(15,17,17,.15);" onchange="window.location.href='<?php echo $base_url; ?>orden='+this.value;">
                    <option value="ultimos" <?php echo($orden == 'ultimos') ? 'selected' : ''; ?>>Últimos agregados</option>
                    <option value="destacados" <?php echo($orden == 'destacados') ? 'selected' : ''; ?>>Ordenar por: Destacados</option>
                    <option value="precio_asc" <?php echo($orden == 'precio_asc') ? 'selected' : ''; ?>>Precio: De menor a mayor</option>
                    <option value="precio_desc" <?php echo($orden == 'precio_desc') ? 'selected' : ''; ?>>Precio: De mayor a menor</option>
                </select>
            </div>
        </div>

        <div class="products-grid">
            <?php if (mysqli_num_rows($productos) > 0): ?>
                <?php while ($producto = mysqli_fetch_assoc($productos)): ?>
                <?php
        $is_free = ((isset($producto['es_gratuito']) && strtolower($producto['es_gratuito']) === 'si') || empty($producto['precio']) || $producto['precio'] == 0);
?>
                <div class="amz-card <?php echo $is_free ? 'amz-card-free' : ''; ?>">
                    <?php if ($is_free): ?>
                        <div class="corner-ribbon"><span>GRATIS</span></div>
                    <?php
        endif; ?>
                    
                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="amz-img-wrapper text-decoration-none <?php echo $is_free ? '' : 'bg-white'; ?>">
                        <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php
        else: ?>
                            <i class="fas fa-image fa-5x text-secondary opacity-25"></i>
                        <?php
        endif; ?>
                    </a>
                    
                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="amz-card-title">
                        <?php echo htmlspecialchars($producto['nombre']); ?>
                    </a>
                    
                    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap" style="row-gap: 4px;">
                        <div style="font-size: 0.8rem; color: #565959;">
                            por <span class="fw-bold" style="color: #007185;"><?php echo htmlspecialchars($producto['editor_nombre'] ?? 'Administrador'); ?></span>
                        </div>
                        
                        <!-- Rating Simulado -->
                        <div class="amz-rating">
                            <?php
        $rating = $producto['promedio_resenas'];
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
                            <span class="amz-rating-count ms-1"><?php echo $producto['total_resenas']; ?></span>
                        </div>
                    </div>

                    <div class="amz-price-block">
                        <?php
        $tasa_usd = 6.96;
        if ($is_free):
?>
                            <span class="text-dark">
                                <span class="amz-price-symbol">Bs.</span>
                                <span class="amz-price-whole">0</span>
                                <span class="amz-price-fraction">00</span>
                                <span class="text-secondary ms-1 fw-normal" style="font-size: 0.8rem;">($0.00)</span>
                            </span>
                        <?php
        else:
            $precio_s = $producto['precio'];
            $precio_usd = $precio_s / $tasa_usd;
            $montoParts = explode('.', number_format($precio_s, 2));
?>
                            <span class="text-dark">
                                <span class="amz-price-symbol">Bs.</span>
                                <span class="amz-price-whole"><?php echo $montoParts[0]; ?></span>
                                <span class="amz-price-fraction"><?php echo $montoParts[1]; ?></span>
                                <span class="text-secondary ms-1 fw-normal" style="font-size: 0.8rem;">($<?php echo number_format($precio_usd, 2); ?>)</span>
                            </span>
                        <?php
        endif; ?>
                    </div>
                    
                    <?php if ($producto['es_mi_producto'] > 0): ?>
                        <button class="amz-btn text-white opacity-75" style="background: #ccc; border: none; cursor: not-allowed;" disabled>Tu Producto</button>
                    <?php elseif ($is_free): ?>
                        <a href="producto.php?id=<?php echo $producto['id']; ?>" class="amz-btn text-white" style="background: linear-gradient(135deg, #a855f7 0%, #8b5cf6 100%); border: none;">Ver Info y Descargar</a>
                    <?php else: ?>
                        <?php if ($is_logged): ?>
                            <a href="producto.php?id=<?php echo $producto['id']; ?>" class="amz-btn text-white">Ver Detalles</a>
                        <?php else: ?>
                            <a href="login.php" class="amz-btn amz-btn-free">Inicia Sesión</a>
                        <?php endif; ?>
                    <?php
        endif; ?>
                </div>
                <?php
    endwhile; ?>
            <?php
else: ?>
                <div style="grid-column: 1 / -1;">
                    <div class="p-5 text-center bg-white border rounded" style="border-color: #D5D9D9 !important;">
                        <i class="fas fa-search-minus fa-5x mb-4 text-muted" style="opacity: 0.5;"></i>
                        <h4 class="fw-bold mb-2 text-dark">Lamentablemente no pudimos encontrar productos.</h4>
                        <p class="text-muted" style="font-size: 1.1rem;">Verifica la ortografía o intenta buscar usando términos más generales.</p>
                        <a href="tienda.php" class="amz-btn d-inline-block mt-3" style="width: auto; padding: 10px 30px;">Limpiar búsqueda</a>
                    </div>
                </div>
            <?php
endif; ?>
        </div>
        
        <?php if ($total_productos > $limit): ?>
            <div class="text-center mt-5 mb-3">
                <a href="tienda.php?categoria=<?php echo $filtro_categoria; ?>&tipo=<?php echo $filtro_tipo; ?>&buscar=<?php echo urlencode($busqueda); ?>&orden=<?php echo $orden; ?>&limit=<?php echo $limit + 10; ?>" 
                   id="load-more-btn"
                   class="amz-btn d-inline-block px-5 shadow-sm" style="width: auto; background: #fff; color: #0f1111; border: 1px solid #D5D9D9; border-radius: 8px;">
                   <span>Ver más resultados</span>
                </a>
            </div>
        <?php
endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('#load-more-btn');
        if (btn) {
            e.preventDefault();
            const url = btn.href;
            
            // Estado de carga
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando más productos...';
            btn.style.pointerEvents = 'none';
            btn.style.opacity = '0.7';
            
            fetch(url)
                .then(res => res.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Actualizamos solo las partes necesarias fluidamente
                    const newGrid = doc.querySelector('.products-grid');
                    if (newGrid) {
                        document.querySelector('.products-grid').innerHTML = newGrid.innerHTML;
                    }
                    
                    const newHeaderInfo = doc.querySelector('.products-header');
                    if(newHeaderInfo) {
                        document.querySelector('.products-header').innerHTML = newHeaderInfo.innerHTML;
                    }
                    
                    const newLoadMore = doc.querySelector('#load-more-btn');
                    if (newLoadMore) {
                        btn.href = newLoadMore.href;
                        btn.innerHTML = newLoadMore.innerHTML;
                        btn.style.pointerEvents = 'auto';
                        btn.style.opacity = '1';
                    } else {
                        btn.parentElement.remove();
                    }
                })
                .catch(err => {
                    // Restaurar botón si hay error
                    btn.innerHTML = '<span>Ver más resultados</span>';
                    btn.style.pointerEvents = 'auto';
                    btn.style.opacity = '1';
                });
        }
    });
});
</script>

</body>
</html>