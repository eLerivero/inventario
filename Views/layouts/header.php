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

// Determinar la ruta base para assets - CORREGIDO
$base_url = SITE_URL;
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

// Definir rutas de assets - CORREGIDO
$css_path = $relative_path . 'Public/css/';
$js_path = $relative_path . 'Public/js/';
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
            <div class="content-area">
                <div class="px-md-4">
                    <!-- Page Content Start -->
                    <div class="content-wrapper">