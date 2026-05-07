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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #27CCA0;
            --primary-dark: #1fa87a;
            --primary-glow: rgba(39, 204, 160, 0.3);
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
            --bg-card-hover: #1a1a24;
            --surface: #16161f;
            --surface-alt: #1c1c26;
            --border: #2a2a3a;
            --text-primary: #e8e8ec;
            --text-secondary: #9999a8;
            --text-muted: #666677;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.3);
            --shadow-md: 0 8px 25px rgba(0,0,0,0.4);
            --shadow-lg: 0 15px 40px rgba(0,0,0,0.5);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-full: 50px;
        }
        
        * { box-sizing: border-box; }
        
        body { 
            background: var(--bg-dark); 
            font-family: 'Outfit', sans-serif; 
            min-height: 100vh;
            color: var(--text-primary);
        }

        /* Navbar Oscura Centrada */
        .modern-nav {
            background: rgba(18, 18, 26, 0.98);
            backdrop-filter: 20px;
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 12px 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
        }
        .nav-content {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            gap: 20px;
        }
        .nav-brand-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .mobile-menu-btn {
            background: var(--surface);
            border: 1px solid var(--border);
            color: var(--text-primary);
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .mobile-menu-btn:hover {
            background: var(--primary-color);
            color: #0a0a0f;
            border-color: var(--primary-color);
        }
        
        @media (min-width: 992px) {
            .mobile-menu-btn { display: none !important; }
        }

.modern-brand {
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--text-primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 1px;
            letter-spacing: -0.5px;
            flex-shrink: 0;
        }
        .modern-brand:hover { color: var(--text-primary); }
        .modern-brand i { color: var(--primary-color); font-size: 1.2rem; margin-right: 3px; }
        .modern-brand .pes-red { color: #fff; font-size: 0.95rem; }
        .modern-brand .bo-red { color: #e74c3c; font-weight: 900; font-size: 0.95rem; }
        .modern-brand .li-yellow { color: #f1c40f; font-weight: 900; font-size: 0.95rem; }
        .modern-brand .via-green { color: var(--primary-color); font-weight: 900; font-size: 0.95rem; }
        .modern-brand .badge-store {
            background: var(--primary-color);
            color: #0a0a0f;
            font-size: 0.5rem;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: 700;
            margin-left: 3px;
        }
        
        @media (min-width: 768px) {
            .modern-brand { font-size: 1.3rem; gap: 2px; }
            .modern-brand i { font-size: 1.4rem; margin-right: 5px; }
            .modern-brand .pes-red, .modern-brand .bo-red, .modern-brand .li-yellow, .modern-brand .via-green { font-size: 1rem; }
            .modern-brand .badge-store { font-size: 0.6rem; padding: 3px 6px; margin-left: 5px; }
        }
        
        @media (min-width: 992px) {
            .modern-brand { font-size: 1.4rem; }
            .modern-brand i { font-size: 1.5rem; }
        }
        .modern-brand:hover { color: var(--text-primary); }
        .modern-brand i {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-right: 5px;
        }
        .modern-brand .pes-red { color: #fff; }
        .modern-brand .bo-red { color: #e74c3c; font-weight: 900; }
        .modern-brand .li-yellow { color: #f1c40f; font-weight: 900; }
        .modern-brand .via-green { color: var(--primary-color); font-weight: 900; }
        .modern-brand .badge-store {
            background: var(--primary-color);
            color: #0a0a0f;
            font-size: 0.6rem;
            padding: 3px 6px;
            border-radius: 4px;
            font-weight: 700;
            margin-left: 5px;
        }

        /* Barra de Búsqueda */
        .modern-search {
            flex-grow: 1;
            display: flex;
            max-width: 600px;
            height: 46px;
            background: var(--surface);
            border-radius: var(--radius-full);
            overflow: hidden;
            border: 2px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }
        .modern-search:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }
        .modern-search select {
            background: var(--surface-alt);
            border: none;
            padding: 0 15px;
            font-size: 0.85rem;
            color: var(--text-secondary);
            cursor: pointer;
            outline: none;
            max-width: 120px;
            font-weight: 500;
        }
        .modern-search input {
            flex-grow: 1;
            border: none;
            padding: 0 15px;
            outline: none;
            font-size: 0.95rem;
            color: var(--text-primary);
            background: transparent;
        }
        .modern-search input::placeholder { color: var(--text-muted); }
        .modern-search button {
            background: var(--primary-color);
            border: none;
            padding: 0 20px;
            color: #0a0a0f;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .modern-search button:hover { transform: scale(1.05); }

        /* Acciones del Navbar */
        .modern-actions { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            flex-shrink: 0;
        }
        .modern-actions a {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: var(--radius-full);
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .modern-actions a:hover {
            background: var(--surface-alt);
            color: var(--primary-color);
        }
        .modern-actions a i { font-size: 1rem; }

        /* Menu Mobile Full Screen */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 80%;
            max-width: 320px;
            height: 100vh;
            background: var(--bg-card);
            z-index: 2000;
            transition: left 0.3s ease;
            display: flex;
            flex-direction: column;
            box-shadow: 5px 0 30px rgba(0,0,0,0.5);
        }
        .mobile-menu.active {
            left: 0;
        }
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }
        .mobile-menu-header span {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        .mobile-menu-header button {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.3rem;
            cursor: pointer;
            padding: 5px;
        }
        .mobile-menu-links {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        .mobile-menu-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 5px;
        }
        .mobile-menu-links a:hover {
            background: var(--surface-alt);
            color: var(--primary-color);
        }
        .mobile-menu-links a.active {
            background: rgba(39, 204, 160, 0.15);
            color: var(--primary-color);
        }
        .mobile-menu-links a i {
            width: 20px;
            text-align: center;
            color: var(--primary-color);
        }
        .mobile-menu-search {
            padding: 15px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }
        .mobile-menu-search form {
            display: flex;
            background: var(--surface-alt);
            border-radius: var(--radius-full);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .mobile-menu-search input {
            flex: 1;
            border: none;
            padding: 12px 15px;
            background: transparent;
            color: var(--text-primary);
            outline: none;
            font-size: 0.9rem;
        }
        .mobile-menu-search input::placeholder { color: var(--text-muted); }
        .mobile-menu-search button {
            background: var(--primary-color);
            border: none;
            padding: 0 18px;
            color: #0a0a0f;
            cursor: pointer;
            font-size: 1rem;
        }
        .mobile-menu-user {
            padding: 15px;
            border-top: 1px solid var(--border);
            background: var(--surface);
        }
        .mobile-menu-user a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 500;
            margin-bottom: 8px;
            background: var(--surface-alt);
        }
        .mobile-menu-user a:hover {
            background: var(--primary-color);
            color: #0a0a0f;
        }
        
        /* Overlay oscuro */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1500;
            display: none;
        }
        .mobile-overlay.active {
            display: block;
        }

        /* Desktop only actions */
        .desktop-only {
            display: flex !important;
        }
        .modern-actions {
            display: flex; 
            align-items: center; 
            gap: 8px; 
            flex-shrink: 0;
        }
        .modern-actions a {
            color: var(--text-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: var(--radius-full);
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.85rem;
        }
        .modern-actions a:hover {
            background: var(--surface-alt);
            color: var(--primary-color);
        }

        /* Subnav Oscura */
        .modern-subnav {
            background: var(--surface);
            padding: 12px 20px;
            display: flex;
            gap: 10px;
            overflow-x: auto;
            scrollbar-width: none;
            border-bottom: 1px solid var(--border);
            justify-content: center;
        }
        .modern-subnav::-webkit-scrollbar { display: none; }
        .modern-subnav a {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 8px 16px;
            border-radius: var(--radius-full);
            background: var(--surface-alt);
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .modern-subnav a:hover {
            background: var(--primary-color);
            color: #0a0a0f;
        }
        .modern-subnav a.active {
            background: var(--primary-color);
            color: #0a0a0f;
            box-shadow: 0 4px 15px var(--primary-glow);
        }

        /* Layout Principal */
        .main-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
            display: flex;
            gap: 30px;
        }

        /* Sidebar Oscura */
        .modern-sidebar {
            width: 260px;
            flex-shrink: 0;
            background: var(--bg-card);
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            height: fit-content;
            border: 1px solid var(--border);
        }
        .modern-sidebar h5 {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 12px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modern-sidebar h5 i { color: var(--primary-color); }
        .filter-list { list-style: none; padding: 0; margin: 0 0 20px 0; }
        .filter-list li { margin-bottom: 4px; }
        .filter-list a {
            color: var(--text-secondary);
            font-size: 0.9rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
            font-weight: 500;
        }
        .filter-list a:hover {
            background: var(--surface-alt);
            color: var(--primary-color);
        }
        .filter-list a.active {
            background: rgba(39, 204, 160, 0.15);
            color: var(--primary-color);
            font-weight: 600;
        }
        .filter-list a i {
            width: 18px;
            text-align: center;
            margin-right: 6px;
            font-size: 0.8rem;
            color: var(--primary-color);
        }

        /* Área de Productos */
        .products-content { flex-grow: 1; min-width: 0; }
        
        /* Header de Productos */
        .products-header {
            background: var(--bg-card);
            padding: 18px 22px;
            border-radius: var(--radius-lg);
            margin-bottom: 25px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid var(--border);
            justify-content: space-between;
            align-items: center;
        }
        .products-header strong { color: var(--primary-color); }
        .products-header .text-primary { color: var(--primary-color) !important; }
        
        /* Grid de Productos */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        /* Tarjeta de Producto Oscura */
        .product-card {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 0;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border);
            position: relative;
            height: 100%;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        /* Badge Gratis */
        .free-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: var(--primary-color);
            color: #0a0a0f;
            padding: 5px 12px;
            border-radius: var(--radius-full);
            font-size: 0.7rem;
            font-weight: 700;
            z-index: 10;
            box-shadow: 0 4px 15px var(--primary-glow);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Imagen del Producto */
        .product-image {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-alt) 100%);
            overflow: hidden;
            position: relative;
        }
        .product-image::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(39, 204, 160, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        .product-card:hover .product-image::before { transform: translateX(100%); }
        .product-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s; }
        .product-card:hover .product-image img { transform: scale(1.1); }
        .product-image i { color: var(--text-muted); font-size: 3.5rem; }

        /* Contenido de la Tarjeta */
        .product-content { padding: 0 15px 15px; flex-grow: 1; display: flex; flex-direction: column; }
        
        .product-title {
            font-size: 0.95rem;
            color: var(--text-primary);
            font-weight: 600;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-decoration: none;
            margin-bottom: 6px;
        }
        .product-title:hover { color: var(--primary-color); text-decoration: none; }

        .product-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.8rem;
        }
        .product-author { color: var(--text-muted); }
        .product-author strong { color: var(--primary-color); font-weight: 600; }

        /* Rating Estrellas */
        .product-rating { display: flex; align-items: center; gap: 4px; margin-bottom: 10px; }
        .product-rating i { font-size: 0.75rem; }
        .stars-filled { color: var(--primary-color); }
        .stars-empty { color: var(--text-muted); }
        .rating-count { color: var(--text-muted); font-size: 0.75rem; margin-left: 4px; }

        /* Precio */
        .product-price {
            margin-top: auto;
            margin-bottom: 12px;
            display: flex;
            align-items: baseline;
            gap: 3px;
        }
        .price-currency { font-size: 0.8rem; color: var(--text-secondary); font-weight: 500; }
        .price-amount { font-size: 1.4rem; font-weight: 800; color: var(--primary-color); }
        .price-usd { font-size: 0.75rem; color: var(--text-muted); margin-left: 4px; }

        /* Botones */
        .btn-product {
            padding: 10px 16px;
            border-radius: var(--radius-full);
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            border: none;
            cursor: pointer;
        }
        .btn-primary-gradient {
            background: var(--primary-color);
            color: #0a0a0f;
            box-shadow: 0 4px 15px var(--primary-glow);
        }
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
            color: #0a0a0f;
            text-decoration: none;
        }
        .btn-free-gradient {
            background: var(--primary-color);
            color: #0a0a0f;
            box-shadow: 0 4px 15px var(--primary-glow);
        }
        .btn-free-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px var(--primary-glow);
            color: #0a0a0f;
            text-decoration: none;
        }
        .btn-outline {
            background: transparent;
            color: var(--text-primary);
            border: 2px solid var(--border);
        }
        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
        }

        /* Estado: Tu Producto */
        .btn-owned { background: var(--surface-alt); color: var(--text-muted); cursor: not-allowed; }

        /* Select Ordenar */
        .modern-select {
            padding: 8px 14px;
            border-radius: var(--radius-full);
            border: 2px solid var(--border);
            background: var(--surface);
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
            cursor: pointer;
            outline: none;
            transition: all 0.2s;
        }
        .modern-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        /* Botón Ver Más */
        .load-more-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 30px;
            background: transparent;
            color: var(--text-primary);
            border-radius: var(--radius-full);
            font-weight: 600;
            text-decoration: none;
            border: 2px solid var(--border);
            transition: all 0.3s;
            margin-top: 40px;
        }
        .load-more-btn:hover {
            background: var(--primary-color);
            color: #0a0a0f;
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px var(--primary-glow);
            text-decoration: none;
        }

        /* Footer Oscuro */
        .modern-footer {
            background: var(--bg-card);
            color: white;
            padding: 50px 20px 25px;
            margin-top: 50px;
            border-top: 1px solid var(--border);
        }
        .footer-back-to-top {
            background: var(--primary-color);
            color: #0a0a0f;
            text-align: center;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .footer-back-to-top:hover { background: var(--primary-dark); }
        .footer-content {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 30px;
            padding: 30px 0;
        }
        .footer-section h4 { font-weight: 700; margin-bottom: 15px; font-size: 1rem; color: var(--primary-color); }
        .footer-section a { color: var(--text-secondary); text-decoration: none; display: block; margin-bottom: 8px; font-size: 0.85rem; transition: color 0.2s; }
        .footer-section a:hover { color: var(--primary-color); }
        .footer-brand { text-align: center; padding-top: 25px; border-top: 1px solid var(--border); }
        .footer-brand i { font-size: 1.8rem; margin-bottom: 8px; color: var(--primary-color); }
        .footer-brand h4 { color: var(--primary-color); }
        .footer-bottom { text-align: center; color: var(--text-muted); font-size: 0.8rem; padding-top: 15px; }

        /* Responsive */
        @media (max-width: 1200px) {
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); }
        }
        @media (max-width: 991px) {
            .main-wrapper { flex-direction: column; padding: 20px 15px; }
            .modern-sidebar { width: 100%; display: flex; flex-wrap: wrap; gap: 15px; }
            .modern-sidebar > * { flex: 1; min-width: 180px; }
            .modern-search select { display: none; }
            .modern-brand { font-size: 1.2rem; }
            .nav-content { flex-wrap: wrap; position: relative; }
        }
        @media (max-width: 768px) {
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
            .modern-nav { padding: 10px 12px; }
            .nav-content { gap: 12px; }
            .nav-brand-menu { order: 1; }
            .modern-actions { order: 2; right: 12px; left: 12px; }
            .modern-search { order: 3; width: 100%; max-width: 100%; flex: 1 1 100%; }
            .product-image { height: 130px; }
            .price-amount { font-size: 1.2rem; }
            .modern-sidebar > * { min-width: 100%; }
            .footer-content { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 576px) {
            .products-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .products-header { flex-direction: column; align-items: stretch; }
            .modern-select { width: 100%; }
            .product-content { padding: 0 10px 10px; }
            .product-title { font-size: 0.85rem; }
            .product-image { height: 110px; }
            .btn-product { padding: 8px 12px; font-size: 0.75rem; }
            .modern-subnav { padding: 10px 12px; gap: 6px; }
            .modern-subnav a { padding: 6px 10px; font-size: 0.75rem; }
            .modern-brand { font-size: 1rem; }
            .modern-brand i { font-size: 1.2rem; }
            .footer-content { grid-template-columns: 1fr; text-align: center; }
        }
    </style>
</head>
<body>

<nav class="modern-nav">
    <div class="nav-content">
        <div class="nav-brand-menu">
            <button class="mobile-menu-btn" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
            <a href="index.php" class="modern-brand">
                <i class="fas fa-gamepad"></i>
                <span class="pes-red">PES</span>
                <span class="bo-red">BO</span>
                <span class="li-yellow">LI</span>
                <span class="via-green">VIA</span>
                <span class="badge-store">STORE</span>
            </a>
        </div>
    
        <form method="GET" action="tienda.php" class="modern-search d-none d-lg-flex">
            <select name="categoria">
                <option value="0">Categorías</option>
                <?php foreach ($categorias_array as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo($filtro_categoria == $cat['id']) ? 'selected' : ''; ?>>
                        <?php echo $cat['nombre']; ?>
                    </option>
                <?php
endforeach; ?>
            </select>
            <input type="text" name="buscar" placeholder="Buscar productos, actualizaciones..." value="<?php echo htmlspecialchars($busqueda); ?>">
            <?php if ($filtro_tipo !== 'todos'): ?>
                <input type="hidden" name="tipo" value="<?php echo $filtro_tipo; ?>">
            <?php
endif; ?>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    
        <div class="modern-actions desktop-only d-none d-lg-flex">
            <?php if ($is_logged): ?>
                <a href="modules/<?php echo $user_role; ?>/dashboard.php">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?></span>
                </a>
                <?php if ($user_role === ROL_COMPRADOR): ?>
                    <a href="modules/comprador/mis_compras.php">
                        <i class="fas fa-box-open"></i>
                        <span>Mis Compras</span>
                    </a>
                <?php endif; ?>
                <a href="logout.php" title="Cerrar sesión">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            <?php else: ?>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Ingresar</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Menú móvil con categorías -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <span>Categorías</span>
        <button onclick="toggleMobileMenu()"><i class="fas fa-times"></i></button>
    </div>
    <div class="mobile-menu-search">
        <form method="GET" action="tienda.php">
            <input type="text" name="buscar" placeholder="Buscar productos...">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="mobile-menu-links">
        <a href="tienda.php" class="<?php echo ($filtro_categoria == 0 && $filtro_tipo == 'todos' && empty($busqueda)) ? 'active' : ''; ?>">
            <i class="fas fa-th"></i> Todo
        </a>
        <a href="tienda.php?tipo=pago" class="<?php echo ($filtro_tipo == 'pago') ? 'active' : ''; ?>">
            <i class="fas fa-fire"></i> Más Vendidos
        </a>
        <a href="tienda.php?tipo=gratuito" class="<?php echo ($filtro_tipo == 'gratuito') ? 'active' : ''; ?>">
            <i class="fas fa-gift"></i> Gratis
        </a>
        <?php foreach ($categorias_array as $cat): ?>
            <a href="tienda.php?categoria=<?php echo $cat['id']; ?>" class="<?php echo ($filtro_categoria == $cat['id']) ? 'active' : ''; ?>">
                <i class="fas <?php echo !empty($cat['icono']) ? $cat['icono'] : 'fa-folder'; ?>"></i> <?php echo htmlspecialchars($cat['nombre']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="mobile-menu-user">
        <?php if ($is_logged): ?>
            <a href="modules/<?php echo $user_role; ?>/dashboard.php">
                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario'); ?>
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        <?php else: ?>
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i> Ingresar
            </a>
            <a href="registro.php">
                <i class="fas fa-user-plus"></i> Registrarse
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileMenu()"></div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileOverlay');
    menu.classList.toggle('active');
    overlay.classList.toggle('active');
}
</script>

<div class="modern-subnav d-none d-md-flex">
    <a href="tienda.php" class="<?php echo ($filtro_categoria == 0 && $filtro_tipo == 'todos' && empty($busqueda)) ? 'active' : ''; ?>">
        <i class="fas fa-th"></i> Todo
    </a>
    <a href="tienda.php?tipo=pago" class="<?php echo ($filtro_tipo == 'pago') ? 'active' : ''; ?>">
        <i class="fas fa-fire"></i> Más Vendidos
    </a>
    <a href="tienda.php?tipo=gratuito" class="<?php echo ($filtro_tipo == 'gratuito') ? 'active' : ''; ?>">
        <i class="fas fa-gift"></i> Gratis
    </a>
    <?php foreach ($categorias_array as $cat): ?>
        <a href="tienda.php?categoria=<?php echo $cat['id']; ?>" class="<?php echo ($filtro_categoria == $cat['id']) ? 'active' : ''; ?>">
            <?php if (!empty($cat['icono'])): ?>
                <i class="fas <?php echo $cat['icono']; ?>"></i>
            <?php endif; ?>
            <?php echo htmlspecialchars($cat['nombre']); ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="main-wrapper">
    
    <!-- Menú Lateral -->
    <div class="modern-sidebar d-none d-lg-block">
        <h5><i class="fas fa-stream"></i> Explorar</h5>
        <ul class="filter-list">
            <li><a href="tienda.php" class="<?php echo ($filtro_categoria == 0 && $filtro_tipo == 'todos' && empty($busqueda)) ? 'active' : ''; ?>">
                <i class="fas fa-store"></i> Todos los productos
            </a></li>
            <li><a href="tienda.php?tipo=pago" class="<?php echo ($filtro_tipo == 'pago') ? 'active' : ''; ?>">
                <i class="fas fa-fire-alt"></i> Más Vendidos
            </a></li>
            <li><a href="tienda.php?tipo=gratuito" class="<?php echo ($filtro_tipo == 'gratuito') ? 'active' : ''; ?>">
                <i class="fas fa-gift"></i> Gratuitos
            </a></li>
            
            <h5 class="mt-4"><i class="fas fa-tags"></i> Categorías</h5>
            <?php foreach ($categorias_array as $cat): ?>
                <li>
                    <a href="tienda.php?categoria=<?php echo $cat['id']; ?>" class="<?php echo ($filtro_categoria == $cat['id']) ? 'active' : ''; ?>">
                        <i class="fas <?php echo !empty($cat['icono']) ? $cat['icono'] : 'fa-folder'; ?>"></i>
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
                <strong><?php echo mysqli_num_rows($productos); ?></strong> de <strong><?php echo $total_productos; ?></strong> resultados
                <?php if(!empty($busqueda)): ?>
                    para "<span class="text-primary"><?php echo htmlspecialchars($busqueda); ?></span>"
                <?php endif; ?>
            </div>
            <div>
                <?php
$url_params = $_GET;
unset($url_params['orden']);
$base_url = 'tienda.php?' . http_build_query($url_params);
if (!empty($url_params))
    $base_url .= '&';
?>
                <select class="modern-select" onchange="window.location.href='<?php echo $base_url; ?>orden='+this.value;">
                    <option value="ultimos" <?php echo($orden == 'ultimos') ? 'selected' : ''; ?>>Más Recientes</option>
                    <option value="destacados" <?php echo($orden == 'destacados') ? 'selected' : ''; ?>>Mejor Valorados</option>
                    <option value="precio_asc" <?php echo($orden == 'precio_asc') ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                    <option value="precio_desc" <?php echo($orden == 'precio_desc') ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                </select>
            </div>
        </div>

        <div class="products-grid">
            <?php if (mysqli_num_rows($productos) > 0): ?>
                <?php while ($producto = mysqli_fetch_assoc($productos)): ?>
                <?php
        $is_free = ((isset($producto['es_gratuito']) && strtolower($producto['es_gratuito']) === 'si') || empty($producto['precio']) || $producto['precio'] == 0);
?>
                <div class="product-card">
                    <?php if ($is_free): ?>
                        <div class="free-badge"><i class="fas fa-gift me-1"></i>GRATIS</div>
                    <?php
        endif; ?>
                    
                    <a href="producto.php?id=<?php echo $producto['id']; ?>" class="product-image">
                        <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
                            <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                        <?php
        else: ?>
                            <i class="fas fa-image"></i>
                        <?php
        endif; ?>
                    </a>
                    
                    <div class="product-content">
                        <a href="producto.php?id=<?php echo $producto['id']; ?>" class="product-title">
                            <?php echo htmlspecialchars($producto['nombre']); ?>
                        </a>
                        
                        <div class="product-meta">
                            <span class="product-author">por <strong><?php echo htmlspecialchars($producto['editor_nombre'] ?? 'PES Bolivia'); ?></strong></span>
                        </div>

                        <div class="product-rating">
                            <?php
        $rating = $producto['promedio_resenas'];
        $entero = floor($rating);
        $mitad = ($rating - $entero) >= 0.5 ? 1 : 0;
        $vacio = 5 - $entero - $mitad;
        for ($i = 0; $i < $entero; $i++)
            echo '<i class="fas fa-star stars-filled"></i>';
        if ($mitad)
            echo '<i class="fas fa-star-half-alt stars-filled"></i>';
        for ($i = 0; $i < $vacio; $i++)
            echo '<i class="far fa-star stars-empty"></i>';
?>
                            <span class="rating-count">(<?php echo $producto['total_resenas']; ?>)</span>
                        </div>

                        <div class="product-price">
                            <?php
        $tasa_usd = 6.96;
        if ($is_free):
?>
                                <span class="price-currency">Bs.</span>
                                <span class="price-amount" style="color: #f5576c;">0</span>
                                <span class="price-usd">($0.00)</span>
                            <?php
        else:
            $precio_s = $producto['precio'];
            $precio_usd = $precio_s / $tasa_usd;
            $montoParts = explode('.', number_format($precio_s, 2));
?>
                                <span class="price-currency">Bs.</span>
                                <span class="price-amount"><?php echo $montoParts[0]; ?></span>
                                <span class="price-amount" style="font-size: 1rem;">.<?php echo $montoParts[1]; ?></span>
                                <span class="price-usd">($<?php echo number_format($precio_usd, 2); ?>)</span>
                            <?php
        endif; ?>
                        </div>
                        
                        <?php if ($producto['es_mi_producto'] > 0): ?>
                            <button class="btn-product btn-owned" disabled><i class="fas fa-check me-1"></i> Tu Producto</button>
                        <?php elseif ($is_free): ?>
                            <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-product btn-free-gradient">
                                <i class="fas fa-download me-2"></i> Descargar Gratis
                            </a>
                        <?php else: ?>
                            <?php if ($is_logged): ?>
                                <a href="producto.php?id=<?php echo $producto['id']; ?>" class="btn-product btn-primary-gradient">
                                    <i class="fas fa-eye me-2"></i> Ver Detalles
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn-product btn-outline">
                                    <i class="fas fa-lock me-2"></i> Iniciar Sesión
                                </a>
                            <?php endif; ?>
                        <?php
        endif; ?>
                    </div>
                </div>
                <?php
    endwhile; ?>
            <?php
else: ?>
                <div style="grid-column: 1 / -1;">
                    <div class="p-5 text-center bg-white border rounded" style="border-color: var(--border-light) !important;">
                        <i class="fas fa-search mb-4" style="font-size: 4rem; color: #d1d5db;"></i>
                        <h4 class="fw-bold mb-2" style="color: var(--text-primary);">No se encontraron productos</h4>
                        <p class="text-muted" style="font-size: 1rem;">Intenta con otros términos de búsqueda</p>
                        <a href="tienda.php" class="btn-product btn-primary-gradient d-inline-block mt-3" style="width: auto; padding: 12px 30px;">Ver Todos los Productos</a>
                    </div>
                </div>
            <?php
endif; ?>
        </div>
        
        <?php if ($total_productos > $limit): ?>
            <div class="text-center">
                <a href="tienda.php?categoria=<?php echo $filtro_categoria; ?>&tipo=<?php echo $filtro_tipo; ?>&buscar=<?php echo urlencode($busqueda); ?>&orden=<?php echo $orden; ?>&limit=<?php echo $limit + 10; ?>" 
                   id="load-more-btn"
                   class="load-more-btn">
                   <span>Ver más productos</span>
                   <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php
endif; ?>
    </div>
</div>

<footer class="modern-footer">
    <div class="footer-back-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'});">
        <i class="fas fa-arrow-up me-2"></i> Volver al inicio
    </div>
    <div class="footer-brand">
        <i class="fas fa-gamepad"></i>
        <h4 class="text-white mb-2">PES <span style="color: var(--primary-color);">BOLIVIA</span> STORE</h4>
        <p class="text-white-50 mb-0">&copy; <?php echo date('Y'); ?>. Todos los derechos reservados.</p>
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