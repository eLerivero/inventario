<?php
// Usar rutas absolutas para evitar problemas de inclusión
$configPath = __DIR__ . '/../../Config/Config.php';
$constantsPath = __DIR__ . '/../../Config/Constants.php';
$authPath = __DIR__ . '/../../Utils/Auth.php';

if (!file_exists($configPath) || !file_exists($constantsPath) || !file_exists($authPath)) {
    die("Error: No se pueden encontrar los archivos requeridos.");
}

require_once $configPath;
require_once $constantsPath;
require_once $authPath;

// Iniciar autenticación
Auth::checkAuth();

// Determinar la ruta base para assets y enlaces
$base_url = SITE_URL;
$current_path = $_SERVER['PHP_SELF'];
$path_parts = explode('/', $current_path);
$views_index = array_search('Views', $path_parts);
$relative_path = '';

if ($views_index !== false) {
    for ($i = 0; $i <= $views_index; $i++) {
        $relative_path .= '../';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom CSS -->
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .sidebar {
            min-height: 100vh;
            background: var(--primary-color);
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            transition: all 0.3s;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar.collapsed .sidebar-text {
            display: none;
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            text-decoration: none;
            white-space: nowrap;
        }

        .sidebar .nav-link:hover {
            background: var(--secondary-color);
            border-left: 4px solid var(--accent-color);
            color: #fff;
        }

        .sidebar .nav-link.active {
            background: var(--secondary-color);
            border-left: 4px solid var(--accent-color);
            color: #fff;
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            text-align: center;
            flex-shrink: 0;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            transition: all 0.3s;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        .navbar {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: var(--header-height);
            z-index: 999;
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            transition: all 0.3s;
        }

        .main-content.expanded .navbar {
            left: 70px;
        }

        .content-wrapper {
            padding: 20px;
            margin-top: var(--header-height);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .stat-card {
            border-left: 4px solid var(--accent-color);
        }

        .stat-card.warning {
            border-left-color: #f39c12;
        }

        .stat-card.danger {
            border-left-color: #e74c3c;
        }

        .stat-card.success {
            border-left-color: #27ae60;
        }

        .btn-primary {
            background: var(--accent-color);
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #2c3e50;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }
            
            .sidebar .sidebar-text {
                display: none;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .navbar {
                left: 70px;
            }
        }

        .user-info {
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
            padding: 10px;
            margin: 10px;
        }

        .logo {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo h4 {
            color: white;
            margin: 0;
            font-weight: 600;
        }

        .logo small {
            color: rgba(255,255,255,0.7);
            font-size: 0.75rem;
        }
        
        /* Asegurar que el contenido no se oculte detrás del navbar fijo */
        body {
            padding-top: 0 !important;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo">
                <h4><i class="fas fa-boxes"></i> <span class="sidebar-text"><?php echo SITE_NAME; ?></span></h4>
                <small class="sidebar-text">v<?php echo SITE_VERSION; ?></small>
            </div>

            <ul class="nav flex-column mt-3">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>dashboard/index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'productos') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>productos/index.php">
                        <i class="fas fa-box"></i>
                        <span class="sidebar-text">Productos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'categorias') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>categorias/index.php">
                        <i class="fas fa-tags"></i>
                        <span class="sidebar-text">Categorías</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'clientes') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>clientes/index.php">
                        <i class="fas fa-users"></i>
                        <span class="sidebar-text">Clientes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'ventas') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>ventas/index.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="sidebar-text">Ventas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'tipos-pago') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>tipos-pago/index.php">
                        <i class="fas fa-credit-card"></i>
                        <span class="sidebar-text">Tipos de Pago</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'historial-stock') !== false ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>historial-stock/index.php">
                        <i class="fas fa-history"></i>
                        <span class="sidebar-text">Historial Stock</span>
                    </a>
                </li>
            </ul>

            <div class="user-info mt-auto">
                <div class="text-white small">
                    <i class="fas fa-user me-2"></i>
                    <span class="sidebar-text"><?php echo htmlspecialchars(Auth::getUser()['username'] ?? 'Usuario'); ?></span>
                    <br>
                    <small class="text-white-50 sidebar-text">
                        <?php echo htmlspecialchars(Auth::getUser()['rol'] ?? 'usuario'); ?>
                    </small>
                </div>
                <a href="<?php echo $relative_path; ?>Utils/Auth.php?action=logout" class="btn btn-outline-light btn-sm w-100 mt-2" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                    <i class="fas fa-sign-out-alt me-1"></i> <span class="sidebar-text">Cerrar Sesión</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white">
                <div class="container-fluid">
                    <button class="btn btn-link text-dark" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <span class="navbar-text me-3 d-none d-md-block">
                            <i class="fas fa-calendar-alt me-1"></i>
                            <?php echo date('d/m/Y'); ?>
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-link text-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars(Auth::getUser()['nombre'] ?? 'Usuario'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo $relative_path; ?>Utils/Auth.php?action=logout" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content-wrapper">