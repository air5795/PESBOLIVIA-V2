<?php
/**
 * HEADER COMÚN PARA PANELES
 */

// Verificar que la sesión esté iniciada
if (!Session::is_logged_in()) {
    redirect(BASE_URL . '/login.php');
}

$usuario_nombre = Session::get_user_name();
$usuario_rol = Session::get_user_role();
$usuario_id = Session::get_user_id();

// Obtener datos completos del usuario
$usuario_data = obtener_usuario($usuario_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Panel'; ?> - <?php echo NOMBRE_SISTEMA; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
       <style>
        :root {
            --panel-green: #064e3b;
            --panel-dark: #111111;
            --panel-bg: #f4f7f6;
            --panel-text: #333333;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--panel-bg);
            color: var(--panel-text);
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--panel-dark);
            color: white;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), width 0.3s;
            z-index: 1050;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed { width: var(--sidebar-collapsed-width); }
        
        .sidebar-header {
            padding: 25px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            height: 80px;
        }
        
        .sidebar-header h4 {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            white-space: nowrap;
            letter-spacing: 0.5px;
        }
        
        .sidebar.collapsed .sidebar-header h4 { display: none; }
        
        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border-left-color: var(--panel-green);
        }
        
        .sidebar-menu li a i {
            width: 25px;
            font-size: 1.1rem;
            margin-right: 15px;
            text-align: center;
        }
        
        .sidebar.collapsed .sidebar-menu li a span { display: none; }
        .sidebar.collapsed .sidebar-menu li a i { margin-right: 0; }
        .sidebar.collapsed .sidebar-menu li a { justify-content: center; padding: 15px 0; border-left: none; }

        /* Main Content Wrapper */
        .main-content {
            margin-left: var(--sidebar-width);
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }
        
        .main-content.expanded { margin-left: var(--sidebar-collapsed-width); }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            height: 80px;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 15px rgba(0,0,0,0.04);
        }
        
        .toggle-sidebar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            color: var(--panel-green);
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .toggle-sidebar:hover { background: #e9ecef; }
        
        .user-menu { display: flex; align-items: center; gap: 15px; }
        
        .user-info-text { text-align: right; margin-right: 10px; }
        .user-info-text .name { font-weight: 600; font-size: 0.9rem; color: #333; line-height: 1.2; display: block; }
        .user-info-text .role { font-size: 0.75rem; color: #888; text-transform: capitalize; }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--panel-green);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 8px rgba(6, 78, 59, 0.2);
        }
        
        /* Responsive Mobile Drawer */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px !important;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
            .top-navbar { padding: 0 15px; }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.5);
                display: none;
                z-index: 1040;
                backdrop-filter: blur(2px);
            }
            .sidebar-overlay.active { display: block; }
        }

        /* Content Area Improvements */
        .content-wrapper { padding: 25px; max-width: 1400px; margin: 0 auto; }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid #f1f1f1;
            padding: 20px 25px;
            font-weight: 700;
            color: #2c3e50;
        }
        .btn-primary { background: var(--panel-green); border: none; padding: 10px 22px; border-radius: 10px; font-weight: 600; }
        .btn-primary:hover { background: #054031; transform: translateY(-1px); }

        /* Stats Cards */
        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            height: 100%;
        }
        .stat-icon {
            width: 55px;
            height: 55px;
            min-width: 55px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 0 !important;
        }
        .stat-info h3 { font-size: 1.5rem; font-weight: 800; margin: 0; color: #2c3e50; }
        .stat-info p { margin: 0; color: #888; font-size: 0.85rem; font-weight: 500; }
    </style>le>
    
    <?php if (isset($extra_css))
    echo $extra_css; ?>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4>
                <i class="fas fa-gamepad me-2 text-success"></i>
                PES BOLIVIA
            </h4>
        </div>
        
        <ul class="sidebar-menu">
            <?php
// Menú según rol
$menu_items = [];

if ($usuario_rol === ROL_SUPERADMIN) {
    $menu_items = [
        ['type' => 'header', 'text' => 'Gestión de Plataforma'],
        ['icon' => 'fa-tachometer-alt', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fa-shopping-cart', 'text' => 'Ventas Totales', 'url' => 'compras.php'],
        ['icon' => 'fa-gamepad', 'text' => 'Productos', 'url' => 'productos.php'],
        ['icon' => 'fa-tags', 'text' => 'Categorías', 'url' => 'categorias.php'],
        ['icon' => 'fa-users', 'text' => 'Usuarios', 'url' => 'usuarios.php'],
        ['icon' => 'fa-user-tie', 'text' => 'Editores', 'url' => 'solicitudes_editor.php'],
        ['icon' => 'fa-wallet', 'text' => 'Comisiones', 'url' => 'comisiones.php'],
        ['icon' => 'fa-credit-card', 'text' => 'Pagos', 'url' => 'tipos_pago.php'],
        
        ['type' => 'header', 'text' => 'Acceso Rápido'],
        ['icon' => 'fa-shopping-bag', 'text' => 'Mis Compras', 'url' => 'mis_compras.php'],
        ['icon' => 'fa-store', 'text' => 'Ir a Tienda', 'url' => '../../tienda.php'],
    ];
}
elseif ($usuario_rol === ROL_EDITOR) {
    $menu_items = [
        ['type' => 'header', 'text' => 'Mi Negocio'],
        ['icon' => 'fa-tachometer-alt', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fa-dollar-sign', 'text' => 'Mis Ventas', 'url' => 'ventas.php'],
        ['icon' => 'fa-box-open', 'text' => 'Mis Productos', 'url' => 'productos.php'],
        ['icon' => 'fa-qrcode', 'text' => 'Mis QR de Pago', 'url' => 'tipos_pago.php'],
        
        ['type' => 'header', 'text' => 'Personal'],
        ['icon' => 'fa-shopping-bag', 'text' => 'Mis Compras', 'url' => 'compras.php'],
        ['icon' => 'fa-store', 'text' => 'Ver Tienda', 'url' => '../../tienda.php'],
    ];
}
elseif ($usuario_rol === ROL_COMPRADOR) {
    $menu_items = [
        ['type' => 'header', 'text' => 'Mi Cuenta'],
        ['icon' => 'fa-home', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fa-shopping-bag', 'text' => 'Mis Compras', 'url' => 'mis_compras.php'],
        ['icon' => 'fa-cloud-download-alt', 'text' => 'Descargas', 'url' => 'descargas.php'],
        ['icon' => 'fa-play-circle', 'text' => 'Tutoriales', 'url' => 'ver_tutorial.php'],
        
        ['type' => 'header', 'text' => 'Explorar'],
        ['icon' => 'fa-store', 'text' => 'Tienda Oficial', 'url' => '../../tienda.php'],
    ];
}

$current_page = basename($_SERVER['PHP_SELF']);

foreach ($menu_items as $item) {
    if (isset($item['type']) && $item['type'] === 'header') {
        echo "<li class='menu-header' style='padding: 20px 25px 8px; font-size: 0.65rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px;'>{$item['text']}</li>";
        continue;
    }
    
    $active = ($current_page === $item['url']) ? 'active' : '';
    echo "<li>";
    echo "<a href='{$item['url']}' class='$active'>";
    echo "<i class='fas {$item['icon']}'></i>";
    echo "<span>{$item['text']}</span>";
    echo "</a>";
    echo "</li>";
}
?>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars-staggered"></i>
            </button>
            
            <div class="user-menu">
                <div class="user-info-text d-none d-md-block">
                    <span class="name"><?php echo $usuario_nombre; ?></span>
                    <span class="role"><?php echo ucfirst($usuario_rol); ?></span>
                </div>
                
                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none" 
                       href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($usuario_data['nombre'], 0, 1)); ?>
                        </div>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-3" style="border-radius: 12px; min-width: 200px;">
                        <li class="px-3 py-2 border-bottom d-md-none">
                            <span class="fw-bold d-block"><?php echo $usuario_nombre; ?></span>
                            <small class="text-muted"><?php echo ucfirst($usuario_rol); ?></small>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#">
                                <i class="fas fa-user-circle me-2 opacity-50"></i>
                                Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="#">
                                <i class="fas fa-shield-alt me-2 opacity-50"></i>
                                Seguridad
                            </a>
                        </li>
                        <li><hr class="dropdown-divider opacity-50"></li>
                        <li>
                            <a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-power-off me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-wrapper">