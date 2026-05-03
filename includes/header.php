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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            background: #111111;
            color: white;
            transition: all 0.3s;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            transition: all 0.3s;
        }
        
        .sidebar.collapsed .sidebar-header h4 {
            display: none;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar-menu li a i {
            width: 30px;
            font-size: 1.2rem;
        }
        
        .sidebar-menu li a span {
            transition: all 0.3s;
        }
        
        .sidebar.collapsed .sidebar-menu li a span {
            display: none;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            transition: all 0.3s;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: 70px;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .toggle-sidebar {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #064e3b;
            cursor: pointer;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #064e3b;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .dropdown-toggle::after {
            margin-left: 8px;
        }
        
        /* Content Area */
        .content-wrapper {
            padding: 30px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .page-header .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px;
            font-weight: 600;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            color: #666;
            margin: 0;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #064e3b;
            border: none;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
        }
        
        /* DataTables */
        .dataTables_wrapper {
            padding: 0;
        }
        
        table.dataTable thead th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        /* Bootstrap Global Overrides */
        .text-primary { color: #004d00 !important; }
        .bg-primary { background-color: #004d00 !important; }
        
        .btn-outline-primary {
            color: #004d00;
            border-color: #004d00;
        }
        
        .btn-outline-primary:hover {
            background-color: #004d00;
            border-color: #004d00;
            color: white;
        }

        a { color: #004d00; text-decoration: none; }
        a:hover { color: #003300; }

        .pagination .page-link {
            color: #004d00;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #004d00;
            border-color: #004d00;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .sidebar-header h4,
            .sidebar .sidebar-menu li a span {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
        }
    </style>
    
    <?php if (isset($extra_css))
    echo $extra_css; ?>
</head>
<body>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4>
                <i class="fas fa-futbol me-2"></i>
                PES Bolivia
            </h4>
        </div>
        
        <ul class="sidebar-menu">
            <?php
// Menú según rol
$menu_items = [];

if ($usuario_rol === ROL_SUPERADMIN) {
    $menu_items = [
        ['icon' => 'fa-home', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fa-store', 'text' => 'Tienda', 'url' => '../../tienda.php'],
        ['icon' => 'fa-users', 'text' => 'Usuarios', 'url' => 'usuarios.php'],
        ['icon' => 'fa-user-check', 'text' => 'Solicitudes Editor', 'url' => 'solicitudes_editor.php'],
        ['icon' => 'fa-th-large', 'text' => 'Categorías', 'url' => 'categorias.php'],
        ['icon' => 'fa-box', 'text' => 'Productos', 'url' => 'productos.php'],
        ['icon' => 'fa-credit-card', 'text' => 'Tipos de Pago', 'url' => 'tipos_pago.php'],
        ['icon' => 'fa-receipt', 'text' => 'Ventas del Sitio', 'url' => 'compras.php'],
        ['icon' => 'fa-shopping-bag', 'text' => 'Mis Compras', 'url' => 'mis_compras.php'],
        ['icon' => 'fa-chart-line', 'text' => 'Reportes', 'url' => 'reportes.php'],
        ['icon' => 'fa-money-bill-wave', 'text' => 'Comisiones', 'url' => 'comisiones.php'],
        ['icon' => 'fa-cog', 'text' => 'Ajustes', 'url' => 'ajustes.php'],
    ];
}
elseif ($usuario_rol === ROL_EDITOR) {
    $menu_items = [
        ['icon' => 'fa-home', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fa-store', 'text' => 'Tienda', 'url' => '../../tienda.php'],
        ['icon' => 'fa-box', 'text' => 'Mis Productos', 'url' => 'productos.php'],
        ['icon' => 'fa-credit-card', 'text' => 'Métodos de Pago', 'url' => 'tipos_pago.php'],
        ['icon' => 'fa-shopping-bag', 'text' => 'Mis Compras', 'url' => 'compras.php'],
        ['icon' => 'fa-shopping-cart', 'text' => 'Mis Ventas', 'url' => 'ventas.php'],
        ['icon' => 'fa-money-bill-wave', 'text' => 'Mis Comisiones', 'url' => 'mis_comisiones.php'],
    ];
}
else {
    $menu_items = [
        ['icon' => 'fa-home', 'text' => 'Dashboard', 'url' => 'dashboard.php'],
        ['icon' => 'fa-store', 'text' => 'Tienda', 'url' => '../../tienda.php'],
        ['icon' => 'fa-shopping-bag', 'text' => 'Mis Compras', 'url' => 'mis_compras.php'],
        ['icon' => 'fa-download', 'text' => 'Descargas', 'url' => 'descargas.php'],
    ];
}

$current_page = basename($_SERVER['PHP_SELF']);

foreach ($menu_items as $item) {
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
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="user-menu">
                <div class="dropdown">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($usuario_data['nombre'], 0, 1)); ?>
                        </div>
                        <div class="ms-2">
                            <div style="font-weight: 600; color: #333;">
                                <?php echo $usuario_nombre; ?>
                            </div>
                            <small style="color: #999;">
                                <?php echo ucfirst($usuario_rol); ?>
                            </small>
                        </div>
                    </a>
                    
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user me-2"></i>
                                Mi Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-2"></i>
                                Configuración
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-wrapper">