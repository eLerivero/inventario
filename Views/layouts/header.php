<?php
// Views/layouts/header.php

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Usar rutas absolutas para evitar problemas de inclusión
$configPath = __DIR__ . '/../../Config/Config.php';
$constantsPath = __DIR__ . '/../../Config/Constants.php';
$authPath = __DIR__ . '/../../Utils/Auth.php';

if (!file_exists($configPath)) {
    die("Error: No se puede encontrar el archivo de configuración en: $configPath");
}

require_once $configPath;

// Cargar constantes si existe el archivo
if (file_exists($constantsPath)) {
    require_once $constantsPath;
}

// Cargar Auth si existe el archivo
if (file_exists($authPath)) {
    require_once $authPath;
} else {
    die("Error: No se puede encontrar el archivo de autenticación en: $authPath");
}

// Verificar autenticación
if (!Auth::check()) {
    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    
    // Usar rutas relativas para evitar problemas con BASE_URL
    $login_url = '/inventario/Views/auth/login.php';
    
    // Redirigir al login
    header("Location: $login_url");
    exit();
}

// Obtener usuario actual
$current_user = Auth::user();

// Determinar la ruta base para assets
$current_path = $_SERVER['PHP_SELF'];

// Calcular la ruta relativa correcta para assets
$path_parts = explode('/', $current_path);
$inventario_index = array_search('inventario', $path_parts);

if ($inventario_index !== false) {
    $relative_path = '';
    // Contar cuántos niveles necesitamos retroceder
    $levels_from_inventario = count($path_parts) - $inventario_index - 2;
    for ($i = 0; $i < $levels_from_inventario; $i++) {
        $relative_path .= '../';
    }
} else {
    // Fallback: asumir que estamos en la raíz
    $relative_path = './';
}

// Definir rutas de assets
$css_path = $relative_path . 'Public/css/';
$js_path = $relative_path . 'Public/js/';

// Definir título de página si no está definido
if (!isset($page_title)) {
    $page_title = 'Sistema de Inventario';
}

// Definir SITE_NAME si no está definido
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Sistema de Inventario');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo SITE_NAME; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $css_path; ?>layouts.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar Container -->
        <div class="sidebar-container">
            <?php
            // Incluir sidebar con la ruta correcta
            $sidebar_path = __DIR__ . '/sidebar.php';
            if (file_exists($sidebar_path)) {
                include $sidebar_path;
            } else {
                echo "<!-- Sidebar not found: $sidebar_path -->";
            }
            ?>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            <!-- Header Container -->
            <div class="header-container">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3">
                    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="sidebarToggle">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                        <div class="btn-group me-2">
                            <span class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-user me-1"></i>
                                <?php echo htmlspecialchars($current_user['nombre'] ?? 'Usuario'); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-area">
                <div class="px-md-4">
                    <!-- Page Content Start -->
                    <div class="content-wrapper">