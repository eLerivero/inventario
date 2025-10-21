<?php
// Incluir configuración y autenticación
require_once '../../Config/Config.php';
require_once '../../Utils/Auth.php';

// Verificar autenticación
Auth::checkAuth();

$current_page = basename($_SERVER['PHP_SELF']);
$user = Auth::getUser();
?>
<!-- Sidebar -->
<nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <div class="text-center mb-4">
            <h4 class="text-white">
                <i class="fas fa-boxes"></i>
                <?php echo defined('SITE_NAME') ? SITE_NAME : 'Inventario'; ?>
            </h4>
            <small class="text-white-50">v<?php echo defined('SITE_VERSION') ? SITE_VERSION : '1.0.0'; ?></small>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="../dashboard/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['index.php', 'crear.php', 'editar.php']) && strpos($_SERVER['REQUEST_URI'], 'productos') !== false ? 'active' : ''; ?>" href="../productos/index.php">
                    <i class="fas fa-box"></i>
                    Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['index.php', 'crear.php', 'editar.php']) && strpos($_SERVER['REQUEST_URI'], 'categorias') !== false ? 'active' : ''; ?>" href="../categorias/index.php">
                    <i class="fas fa-tags"></i>
                    Categorías
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['index.php', 'crear.php', 'editar.php']) && strpos($_SERVER['REQUEST_URI'], 'clientes') !== false ? 'active' : ''; ?>" href="../clientes/index.php">
                    <i class="fas fa-users"></i>
                    Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($current_page, ['index.php', 'crear.php', 'detalle.php']) && strpos($_SERVER['REQUEST_URI'], 'ventas') !== false ? 'active' : ''; ?>" href="../ventas/index.php">
                    <i class="fas fa-shopping-cart"></i>
                    Ventas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'tipos-pago') !== false ? 'active' : ''; ?>" href="../tipos-pago/index.php">
                    <i class="fas fa-credit-card"></i>
                    Tipos de Pago
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'historial-stock') !== false ? 'active' : ''; ?>" href="../historial-stock/index.php">
                    <i class="fas fa-history"></i>
                    Historial Stock
                </a>
            </li>
        </ul>

        <div class="mt-5 p-3 border-top border-secondary">
            <div class="text-white small mb-2">
                <i class="fas fa-user me-2"></i>
                <?php echo htmlspecialchars($user['username'] ?? 'Usuario'); ?>
                <br>
                <small class="text-white-50">
                    <?php echo htmlspecialchars($user['rol'] ?? 'usuario'); ?>
                </small>
            </div>
            <a href="../../Utils/Auth.php?action=logout" class="btn btn-outline-light btn-sm w-100">
                <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
            </a>
        </div>
    </div>
</nav>