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
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #27CCA0;
            --primary-dark: #1fa87a;
            --primary-glow: rgba(39, 204, 160, 0.3);
            --bg-dark: #0a0a0f;
            --bg-card: #12121a;
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

        /* Navbar Centrada */
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
        .mobile-menu.active { left: 0; }
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
        }
        .mobile-menu-header span { font-weight: 700; color: var(--primary-color); font-size: 1.1rem; }
        .mobile-menu-header button { background: none; border: none; color: var(--text-primary); font-size: 1.3rem; cursor: pointer; padding: 5px; }
        .mobile-menu-links { flex: 1; overflow-y: auto; padding: 15px; }
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
        .mobile-menu-links a:hover { background: var(--surface-alt); color: var(--primary-color); }
        .mobile-menu-links a i { width: 20px; text-align: center; color: var(--primary-color); }
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
        .mobile-menu-user a:hover { background: var(--primary-color); color: #0a0a0f; }
        
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
        .mobile-overlay.active { display: block; }

        .desktop-only { display: flex !important; }
        .modern-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
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
        .modern-actions a:hover { background: var(--surface-alt); color: var(--primary-color); }

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
        .modern-subnav a:hover { background: var(--primary-color); color: #0a0a0f; }

        /* Layout Producto */
        .product-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1.5fr 350px;
            gap: 25px;
            align-items: start;
        }

        .product-image-col {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            position: sticky;
            top: 100px;
        }
        .product-main-img { width: 100%; border-radius: var(--radius-md); object-fit: contain; }
        .product-placeholder-img {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, var(--surface) 0%, var(--surface-alt) 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-placeholder-img i { font-size: 4rem; color: var(--text-muted); }

        .product-details-col {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }
        .product-title { font-size: 1.6rem; font-weight: 700; color: var(--text-primary); margin-bottom: 10px; line-height: 1.3; }
        .product-category { color: var(--primary-color); text-decoration: none; font-size: 0.95rem; font-weight: 500; display: inline-block; margin-bottom: 15px; }
        .product-category:hover { color: var(--primary-dark); }

        .product-rating { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
        .stars-filled { color: var(--primary-color); }
        .stars-empty { color: var(--text-muted); }
        .rating-text { color: var(--primary-color); font-weight: 600; margin-left: 8px; }
        .rating-count { color: var(--text-muted); font-size: 0.9rem; }

        .product-price-section { margin: 20px 0; padding: 20px; background: var(--surface); border-radius: var(--radius-md); border: 1px solid var(--border); }
        .price-label { font-size: 0.9rem; color: var(--text-muted); margin-bottom: 5px; }
        .price-amount { font-size: 2rem; font-weight: 800; color: var(--primary-color); display: flex; align-items: baseline; }
        .price-currency { font-size: 1rem; color: var(--text-secondary); margin-right: 4px; }
        .price-usd { font-size: 0.9rem; color: var(--text-muted); margin-left: 10px; font-weight: 500; }
        .price-free { font-size: 1.8rem; font-weight: 800; color: var(--primary-color); }

        .product-features { margin-top: 20px; }
        .features-title { font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 12px; }
        .features-list { list-style: none; padding: 0; margin: 0; }
        .features-list li { padding: 8px 0; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; color: var(--text-secondary); font-size: 0.9rem; }
        .features-list li:last-child { border-bottom: none; }
        .features-list li i { color: var(--primary-color); font-size: 0.85rem; }

        /* Buy Box */
        .buy-box-col {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 22px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            position: sticky;
            top: 100px;
        }
        .buy-price { font-size: 1.6rem; font-weight: 800; color: var(--primary-color); margin-bottom: 5px; }
        .buy-price-free { font-size: 1.6rem; font-weight: 800; color: var(--primary-color); }
        .stock-status { display: flex; align-items: center; gap: 8px; color: var(--primary-color); font-weight: 600; margin: 12px 0 10px; }
        .stock-status i { font-size: 0.85rem; }
        .sold-by { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 18px; }

        .btn-buy {
            width: 100%;
            padding: 14px 22px;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 0.95rem;
            text-align: center;
            transition: all 0.3s ease;
            display: block;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-primary-modern { background: var(--primary-color); color: #0a0a0f; box-shadow: 0 4px 20px var(--primary-glow); }
        .btn-primary-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px var(--primary-glow); color: #0a0a0f; text-decoration: none; }
        .btn-free-modern { background: var(--primary-color); color: #0a0a0f; box-shadow: 0 4px 20px var(--primary-glow); }
        .btn-free-modern:hover { transform: translateY(-3px); box-shadow: 0 8px 30px var(--primary-glow); color: #0a0a0f; text-decoration: none; }
        .btn-outline-modern { background: transparent; color: var(--text-primary); border: 2px solid var(--border); }
        .btn-outline-modern:hover { border-color: var(--primary-color); color: var(--primary-color); text-decoration: none; }

        .secure-badge { display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 12px; color: var(--text-muted); font-size: 0.8rem; }

        /* Payment */
        .payment-section { margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--border); }
        .payment-title { font-weight: 700; font-size: 0.85rem; color: var(--text-primary); margin-bottom: 10px; }
        .payment-option { border: 2px solid var(--border); border-radius: var(--radius-md); padding: 10px 12px; margin-bottom: 8px; cursor: pointer; background: var(--surface); transition: all 0.2s; display: flex; align-items: center; gap: 10px; }
        .payment-option:hover { border-color: var(--primary-color); background: rgba(39, 204, 160, 0.1); }
        .payment-option.selected { border-color: var(--primary-color); background: rgba(39, 204, 160, 0.15); box-shadow: 0 0 0 3px var(--primary-glow); }
        .payment-option input { margin: 0; }
        .payment-name { font-weight: 600; font-size: 0.9rem; color: var(--text-primary); }
        .payment-details { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }

        .comprobante-section { display: none; margin-top: 12px; padding: 12px; background: var(--surface-alt); border-radius: var(--radius-md); border: 1px solid var(--border); }
        .comprobante-section.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .qr-preview { max-width: 120px; border-radius: var(--radius-sm); display: none; margin: 0 auto 8px; }

        /* Description */
        .description-section { max-width: 1400px; margin: 25px auto; padding: 25px; background: var(--bg-card); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
        .section-title { font-weight: 800; font-size: 1.3rem; color: var(--primary-color); margin-bottom: 18px; padding-bottom: 12px; border-bottom: 2px solid var(--border); }
        .description-text { color: var(--text-secondary); line-height: 1.8; font-size: 0.95rem; }

        /* Reviews */
        .reviews-section { max-width: 1400px; margin: 25px auto; padding: 25px; background: var(--bg-card); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
        .reviews-layout { display: grid; grid-template-columns: 280px 1fr; gap: 30px; }

        .reviews-summary { padding-right: 25px; border-right: 1px solid var(--border); }
        .average-rating { font-size: 2.5rem; font-weight: 800; color: var(--primary-color); line-height: 1; }
        .rating-stars-large i { font-size: 1.1rem; margin: 8px 0; }
        .total-reviews { color: var(--text-muted); font-size: 0.85rem; }

        .write-review-card { background: var(--surface); border-radius: var(--radius-md); padding: 15px; margin-top: 15px; }
        .star-rating-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
        .star-rating-input input { display: none; }
        .star-rating-input label { cursor: pointer; font-size: 1.3rem; color: var(--text-muted); transition: color 0.2s; }
        .star-rating-input label:hover, .star-rating-input label:hover ~ label, .star-rating-input input:checked ~ label { color: var(--primary-color); }

        .review-card { padding: 15px 0; border-bottom: 1px solid var(--border); }
        .review-card:last-child { border-bottom: none; }
        .review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .review-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary-color); display: flex; align-items: center; justify-content: center; color: #0a0a0f; font-weight: 600; font-size: 0.8rem; }
        .review-author { font-weight: 600; color: var(--text-primary); }
        .review-verified { background: var(--primary-color); color: #0a0a0f; font-size: 0.6rem; padding: 2px 6px; border-radius: 8px; font-weight: 600; }
        .review-date { font-size: 0.8rem; color: var(--text-muted); margin: 4px 0; }
        .review-body { color: var(--text-secondary); line-height: 1.5; }

        /* Footer */
        .modern-footer { background: var(--bg-card); color: white; padding: 50px 20px 25px; margin-top: 50px; border-top: 1px solid var(--border); }
        .footer-back-to-top { background: var(--primary-color); color: #0a0a0f; text-align: center; padding: 12px; cursor: pointer; transition: all 0.3s; }
        .footer-back-to-top:hover { background: var(--primary-dark); }
        .footer-content { max-width: 1100px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 30px; padding: 30px 0; }
        .footer-section h4 { font-weight: 700; margin-bottom: 15px; font-size: 1rem; color: var(--primary-color); }
        .footer-section a { color: var(--text-secondary); text-decoration: none; display: block; margin-bottom: 8px; font-size: 0.85rem; transition: color 0.2s; }
        .footer-section a:hover { color: var(--primary-color); }
        .footer-brand { text-align: center; padding-top: 25px; border-top: 1px solid var(--border); }
        .footer-brand i { font-size: 1.8rem; margin-bottom: 8px; color: var(--primary-color); }
        .footer-brand h4 { color: var(--primary-color); }

        /* Responsive */
        @media (max-width: 1200px) { .product-container { grid-template-columns: 1fr 1fr; } .buy-box-col { grid-column: 1 / -1; position: static; } }
        @media (max-width: 991px) { .product-container { grid-template-columns: 1fr; padding: 15px; } .product-image-col, .product-details-col, .buy-box-col { position: static; } .modern-search select { display: none; } .reviews-layout { grid-template-columns: 1fr; } .reviews-summary { border-right: none; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 15px; } }
        @media (max-width: 768px) { .modern-nav { padding: 10px 12px; } .nav-content { flex-wrap: wrap; position: relative; } .nav-brand-menu { order: 1; } .modern-actions { order: 2; right: 12px; left: 12px; } .modern-search { order: 3; width: 100%; flex: 1 1 100%; } .product-title { font-size: 1.3rem; } .price-amount { font-size: 1.6rem; } }
        @media (max-width: 576px) { .product-placeholder-img { height: 220px; } .product-features { display: none; } .modern-subnav { padding: 10px 12px; gap: 6px; } .modern-subnav a { padding: 6px 10px; font-size: 0.75rem; } .modern-brand { font-size: 1rem; } .modern-brand i { font-size: 1.2rem; } }
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
                    <option value="<?php echo $cat['id']; ?>">
                        <?php echo $cat['nombre']; ?>
                    </option>
                <?php
endforeach; ?>
            </select>
            <input type="text" name="buscar" placeholder="Buscar productos...">
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
                <a href="logout.php">
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
        <a href="tienda.php">
            <i class="fas fa-store"></i> Tienda
        </a>
        <a href="tienda.php?tipo=pago">
            <i class="fas fa-fire"></i> Más Vendidos
        </a>
        <a href="tienda.php?tipo=gratuito">
            <i class="fas fa-gift"></i> Gratis
        </a>
        <?php foreach (array_slice($categorias_array, 0, 10) as $cat): ?>
            <a href="tienda.php?categoria=<?php echo $cat['id']; ?>">
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
    <a href="tienda.php"><i class="fas fa-store"></i> Tienda</a>
    <a href="tienda.php?tipo=pago"><i class="fas fa-fire"></i> Más Vendidos</a>
    <a href="tienda.php?tipo=gratuito"><i class="fas fa-gift"></i> Gratuitos</a>
    <?php foreach (array_slice($categorias_array, 0, 5) as $cat): ?>
        <a href="tienda.php?categoria=<?php echo $cat['id']; ?>">
            <?php if (!empty($cat['icono'])): ?><i class="fas <?php echo $cat['icono']; ?>"></i><?php endif; ?>
            <?php echo $cat['nombre']; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="product-container">
    <!-- Columna Izquierda: Imagen -->
    <div class="product-image-col">
        <?php if (!empty($producto['imagen']) && file_exists($producto['imagen'])): ?>
            <img src="<?php echo BASE_URL . '/' . $producto['imagen']; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>" class="product-main-img">
        <?php else: ?>
            <div class="product-placeholder-img">
                <i class="fas fa-image"></i>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Columna Centro: Detalles -->
    <div class="product-details-col">
        <h1 class="product-title"><?php echo htmlspecialchars($producto['nombre']); ?></h1>
        <a href="tienda.php?categoria=<?php echo $producto['id_categoria']; ?>" class="product-category">
            <i class="fas fa-folder me-1"></i> <?php echo $producto['categoria_nombre'] ?? 'PES Bolivia'; ?>
        </a>
        
        <div class="product-rating" style="cursor: pointer;" onclick="document.getElementById('resenas-section').scrollIntoView({behavior: 'smooth'})">
            <?php
$rating = $promedio_valoracion;
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
            <span class="rating-text"><?php echo number_format($rating, 1); ?></span>
            <span class="rating-count">(<?php echo $total_resenas; ?> reseñas)</span>
        </div>

        <div class="product-price-section">
            <?php if ((isset($producto['es_gratuito']) && strtolower($producto['es_gratuito']) === 'si') || empty($producto['precio']) || $producto['precio'] == 0): ?>
                <div class="price-label">Precio</div>
                <div class="price-free">GRATIS</div>
            <?php
else:
    $montoParts = explode('.', number_format($producto['precio'], 2));
    $precio_usd = $producto['precio'] / 6.96;
?>
                <div class="price-label">Precio oficial</div>
                <div class="price-amount">
                    <span class="price-currency">Bs.</span><?php echo $montoParts[0]; ?><span class="price-amount" style="font-size: 1.2rem;">.<?php echo $montoParts[1]; ?></span>
                    <span class="price-usd">(~$<?php echo number_format($precio_usd, 2); ?>)</span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="product-features">
            <h5 class="features-title"><i class="fas fa-info-circle me-2"></i>Características</h5>
            <ul class="features-list">
                <?php if (!empty($producto['version'])): ?>
                    <li><i class="fas fa-check-circle"></i> <strong>Versión:</strong> <?php echo htmlspecialchars($producto['version']); ?></li>
                <?php endif; ?>
                <?php if (!empty($producto['requisitos'])): ?>
                    <li><i class="fas fa-desktop"></i> <strong>Requisitos:</strong> <?php echo htmlspecialchars($producto['requisitos']); ?></li>
                <?php endif; ?>
                <li><i class="fas fa-download"></i> <strong>Entrega:</strong> Descarga digital inmediata</li>
                <li><i class="fas fa-shield-alt"></i> <strong>Seguridad:</strong> 100% libre de virus</li>
            </ul>
        </div>
    </div>
    
    <!-- Columna Derecha: Buy Box -->
    <div class="buy-box-col">
        <?php if ((isset($producto['es_gratuito']) && strtolower($producto['es_gratuito']) === 'si') || empty($producto['precio']) || $producto['precio'] == 0):
    $link_descarga = $producto['link_descarga_directa'] ?? $producto['drive_link'] ?? '#';
?>
            <div class="buy-price-free">GRATIS</div>
            <div class="text-muted mb-3" style="font-size: 0.9rem;">Transacción 100% segura</div>
            
            <div class="stock-status">
                <i class="fas fa-check-circle"></i> Disponible ahora
            </div>
            <div class="sold-by">Vendido por <strong>PES Bolivia Store</strong></div>
            
            <a href="<?php echo $link_descarga; ?>" target="_blank" class="btn-buy btn-free-modern">
                <i class="fas fa-download me-2"></i> Descargar Ahora
            </a>
            
        <?php
else: 
    $precio_usd = $producto['precio'] / 6.96;
?>
            <div class="buy-price">Bs. <?php echo number_format($producto['precio'], 2); ?></div>
            <div class="text-muted mb-3" style="font-size: 0.9rem;">(~$<?php echo number_format($precio_usd, 2); ?> USD)</div>
            
            <div class="stock-status">
                <i class="fas fa-check-circle"></i> Disponible
            </div>
            <div class="sold-by">Vendido por <strong>PES Bolivia Store</strong></div>
            
            <?php if (!$is_logged): ?>
                <a href="login.php" class="btn-buy btn-primary-modern">
                    <i class="fas fa-lock me-2"></i> Iniciar Sesión para Comprar
                </a>
            <?php elseif ($ya_comprado): ?>
                <div class="alert alert-success p-3 mb-3" style="border-radius: 12px; font-size: 0.9rem;">
                    <i class="fas fa-check-circle me-2"></i> Ya compraste este producto
                </div>
                <a href="modules/comprador/mis_compras.php" class="btn-buy btn-outline-modern">
                    <i class="fas fa-box-open me-2"></i> Ver mis Pedidos
                </a>
            <?php else: ?>
                <form method="POST" action="procesar_compra.php" enctype="multipart/form-data" id="formCompra">
                    <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                    <input type="hidden" name="id_tipo_pago" id="id_tipo_pago">
                    
                    <div class="payment-title">Selecciona método de pago:</div>
                    
                    <?php if (mysqli_num_rows($tipos_pago) > 0): ?>
                        <?php while ($tipo = mysqli_fetch_assoc($tipos_pago)): ?>
                            <div class="payment-option" onclick="selectMetodo(<?php echo $tipo['id']; ?>, '<?php echo !empty($tipo['imagen_qr']) ? BASE_URL . '/' . $tipo['imagen_qr'] : ''; ?>')">
                                <input type="radio" name="temp_pago" style="margin: 0;">
                                <div>
                                    <div class="payment-name"><i class="fas fa-money-bill-wave text-success me-2"></i><?php echo $tipo['nombre']; ?></div>
                                    <?php if ($tipo['tipo'] === 'transferencia' && !empty($tipo['banco'])): ?>
                                        <div class="payment-details"><?php echo $tipo['banco']; ?><?php if (!empty($tipo['numero_cuenta'])): ?> - <?php echo $tipo['numero_cuenta']; ?><?php endif; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-danger small">No hay métodos de pago disponibles</div>
                    <?php endif; ?>
                    
                    <div id="comprobanteSection" class="comprobante-section">
                        <img id="qr_img" src="" alt="QR" class="qr-preview">
                        <div class="mb-2" style="font-weight: 600; font-size: 0.9rem; margin-top: 10px;">Subir comprobante:</div>
                        <input type="file" class="form-control mb-2" name="comprobante" accept="image/*,application/pdf" required>
                        <button type="submit" class="btn-buy btn-primary-modern">
                            <i class="fas fa-lock me-2"></i> Finalizar Compra
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <div class="secure-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Transacción segura</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Descripción -->
<div class="description-section">
    <h3 class="section-title"><i class="fas fa-file-alt me-2"></i>Información del Producto</h3>
    <div class="description-text">
        <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
    </div>
</div>

<!-- Reseñas -->
<div class="reviews-section" id="resenas-section">
    <h3 class="section-title"><i class="fas fa-star me-2"></i>Reseñas y Opiniones</h3>
    <div class="reviews-layout">
        <div class="reviews-summary">
            <div class="average-rating"><?php echo number_format($rating, 1); ?></div>
            <div class="rating-stars-large">
                <?php
for ($i = 0; $i < $entero; $i++)
    echo '<i class="fas fa-star stars-filled"></i>';
if ($mitad)
    echo '<i class="fas fa-star-half-alt stars-filled"></i>';
for ($i = 0; $i < $vacio; $i++)
    echo '<i class="far fa-star stars-empty"></i>';
?>
            </div>
            <div class="total-reviews"><?php echo $total_resenas; ?> reseñas</div>

            <div class="write-review-card">
                <h6 class="fw-bold mb-3">Comparte tu opinión</h6>
                
                <?php if (!empty($success_review)): ?>
                    <div class="alert alert-success p-2 small"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_review); ?></div>
                <?php endif; ?>
                <?php if (!empty($error_review)): ?>
                    <div class="alert alert-danger p-2 small"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_review); ?></div>
                <?php endif; ?>

                <?php if (!$is_logged): ?>
                    <a href="login.php" class="btn-buy btn-outline-modern" style="padding: 10px 20px; font-size: 0.9rem;">
                        <i class="fas fa-sign-in-alt me-2"></i> Inicia sesión para opinar
                    </a>
                <?php elseif ($user_ya_reseno): ?>
                    <div class="alert alert-info p-2" style="font-size: 0.85rem;">
                        <i class="fas fa-info-circle me-1"></i> Ya发表了 tu opinión
                    </div>
                <?php else: ?>
                    <button class="btn-buy btn-primary-modern" style="padding: 10px 20px; font-size: 0.9rem;" onclick="document.getElementById('formReviewContainer').style.display='block'; this.style.display='none';">
                        <i class="fas fa-pen me-2"></i> Escribir Reseña
                    </button>
                    
                    <div id="formReviewContainer" style="display:none; margin-top:15px;">
                        <form action="procesar_resena.php" method="POST">
                            <input type="hidden" name="id_producto" value="<?php echo $producto['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 0.9rem;">Tu calificación:</label>
                                <div class="star-rating-input">
                                    <input type="radio" name="calificacion" id="star5" value="5" required>
                                    <label for="star5"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="calificacion" id="star4" value="4">
                                    <label for="star4"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="calificacion" id="star3" value="3">
                                    <label for="star3"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="calificacion" id="star2" value="2">
                                    <label for="star2"><i class="fas fa-star"></i></label>
                                    <input type="radio" name="calificacion" id="star1" value="1">
                                    <label for="star1"><i class="fas fa-star"></i></label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <textarea class="form-control" name="comentario" rows="4" placeholder="Tu opinión sobre el producto..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn-buy btn-primary-modern" style="padding: 10px 20px;">
                                <i class="fas fa-paper-plane me-2"></i> Enviar
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="reviews-list">
            <?php if (mysqli_num_rows($resenas_list) > 0): ?>
                <?php while ($resena = mysqli_fetch_assoc($resenas_list)): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="review-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <span class="review-author"><?php echo htmlspecialchars($resena['usuario_nombre']); ?></span>
                                <?php if ($resena['rol'] === 'superadmin' || $resena['rol'] === 'editor'): ?>
                                    <span class="review-verified">VERIFICADO</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="rating-stars-large" style="margin-bottom: 8px;">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $resena['calificacion'] ? 'stars-filled' : 'stars-empty'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="review-date"><?php echo date('d \d\e M \d\e Y', strtotime($resena['fecha_creacion'])); ?></div>
                        <div class="review-body">
                            <?php echo nl2br(htmlspecialchars($resena['comentario'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php if ($total_paginas_resenas > 1): ?>
                    <div class="mt-4 text-center">
                        <?php if ($pagina_resenas < $total_paginas_resenas): ?>
                            <a href="producto.php?id=<?php echo $producto['id']; ?>&pagina_resenas=<?php echo $pagina_resenas + 1; ?>#resenas-section" class="btn-buy btn-outline-modern d-inline-block px-4" style="font-size: 0.9rem;">Ver más reseñas</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-comments mb-3" style="font-size: 3rem; color: #d1d5db;"></i>
                    <h5 class="text-muted">Aún no hay reseñas</h5>
                    <p class="text-muted small">Sé el primero en compartir tu opinión</p>
                </div>
            <?php endif; ?>
        </div>
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
    
    // Validar formulario pre-submit y agregar loading
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

            // Efecto de Loading
            const btnSubmit = this.querySelector('button[type="submit"]');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Procesando Pago...';
            
            return true;
        });
    }
</script>
</body>
</html>