<?php
// Views/layouts/sidebar.php

// Verificar que el usuario esté autenticado
if (!isset($current_user)) {
    die("Error: Usuario no autenticado");
}

$current_page = basename($_SERVER['PHP_SELF']);

// Determinar la ruta base CORREGIDA
$relative_path = '../'; // Desde layouts va a Views/

// Intentar obtener tasa actual para mostrar
$tasaActual = ['success' => false];
try {
    $tasaControllerPath = __DIR__ . '/../../Controllers/TasaCambioController.php';
    if (file_exists($tasaControllerPath)) {
        require_once $tasaControllerPath;
        require_once __DIR__ . '/../../Config/Database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        $tasaController = new TasaCambioController($db);
        $tasaActual = $tasaController->obtenerTasaActual();
    }
} catch (Exception $e) {
    // Silenciar error, no es crítico para el sidebar
}
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

            <!-- Mostrar tasa actual en el sidebar -->
            <?php if ($tasaActual['success']): ?>
                <div class="mt-2 p-2 bg-primary bg-opacity-25 rounded">
                    <small class="text-white">
                        <i class="fas fa-exchange-alt me-1"></i>
                        1 USD = <?php echo number_format($tasaActual['data']['tasa_cambio'], 2); ?> Bs
                    </small>
                    <br>
                    <small class="text-white-50">
                        <?php echo date('d/m', strtotime($tasaActual['data']['fecha_actualizacion'])); ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo ($current_page == 'index.php' && strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>dashboard/index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>
            </li>
            
            <?php if (Auth::isAdmin()): ?>
            <!-- Menú de Administración -->
            <li class="nav-item mt-3">
                <small class="text-white-50 sidebar-text px-3">ADMINISTRACIÓN</small>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>usuarios/index.php">
                    <i class="fas fa-users-cog"></i>
                    <span class="sidebar-text">Usuarios</span>
                </a>
            </li>

            <?php endif; ?>
            
            <!-- Menú Principal -->
            <li class="nav-item mt-3">
                <small class="text-white-50 sidebar-text px-3">INVENTARIO</small>
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

            <?php if (Auth::isAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'tasas-cambio') !== false) ? 'active' : ''; ?>" href="<?php echo $relative_path; ?>tasas-cambio/index.php">
                    <i class="fas fa-exchange-alt"></i>
                    <span class="sidebar-text">Tasas de Cambio</span>
                </a>
            </li>
            <?php endif; ?>

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
                <span class="sidebar-text"><?php echo htmlspecialchars($current_user['username'] ?? 'Usuario'); ?></span>
                <br>
                <small class="text-white-50 sidebar-text">
                    <?php echo htmlspecialchars($current_user['rol'] ?? 'usuario'); ?>
                </small>
            </div>
            <a href="<?php echo $relative_path; ?>auth/logout.php" class="btn btn-outline-light btn-sm w-100" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?')">
                <i class="fas fa-sign-out-alt me-1"></i> <span class="sidebar-text">Cerrar Sesión</span>
            </a>
        </div>
    </div>
</nav>