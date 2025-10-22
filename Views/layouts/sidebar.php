<?php
// Incluir configuración y autenticación
require_once '../../Config/Config.php';
require_once '../../Utils/Auth.php';

// Verificar autenticación
Auth::checkAuth();

$current_page = basename($_SERVER['PHP_SELF']);
$user = Auth::getUser();

// Determinar la ruta base CORREGIDA - desde Views/layouts
$base_path = __DIR__ . '/../'; // Retrocede a Views/
$relative_path = '../'; // Desde cualquier página en Views/ va a la raíz
?>
<!-- Sidebar -->
<nav class="sidebar">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h4 class="text-white">
                <i class="fas fa-boxes"></i>
                <span class="sidebar-text"><?php echo SITE_NAME; ?></span>
            </h4>
            <small class="text-white-50 sidebar-text">v<?php echo SITE_VERSION; ?></small>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>dashboard/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'productos') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>productos/index.php">
                    <i class="fas fa-box"></i>
                    <span class="sidebar-text">Productos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'categorias') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>categorias/index.php">
                    <i class="fas fa-tags"></i>
                    <span class="sidebar-text">Categorías</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'clientes') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>clientes/index.php">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-text">Clientes</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'ventas') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>ventas/index.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="sidebar-text">Ventas</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'tipos-pago') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>tipos-pago/index.php">
                    <i class="fas fa-credit-card"></i>
                    <span class="sidebar-text">Tipos de Pago</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'historial-stock') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>historial-stock/index.php">
                    <i class="fas fa-history"></i>
                    <span class="sidebar-text">Historial Stock</span>
                </a>
            </li>
        </ul>

        <div class="mt-5 p-3 border-top border-secondary">
            <div class="text-white small mb-2">
                <i class="fas fa-user me-2"></i>
                <span class="sidebar-text"><?php echo htmlspecialchars($user['username'] ?? 'Usuario'); ?></span>
                <br>
                <small class="text-white-50 sidebar-text">
                    <?php echo htmlspecialchars($user['rol'] ?? 'usuario'); ?>
                </small>
            </div>
            <a href="<?php echo $relative_path; ?>../Utils/Auth.php?action=logout" class="btn btn-outline-light btn-sm w-100" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                <i class="fas fa-sign-out-alt me-1"></i> <span class="sidebar-text">Cerrar Sesión</span>
            </a>
        </div>
    </div>
</nav>