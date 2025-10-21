<?php
// Inicializar la aplicación
session_start();

// Definir ruta base
define('BASE_VIEW_PATH', dirname(__DIR__));

// Incluir configuración
require_once BASE_VIEW_PATH . '/../Config/Config.php';
require_once BASE_VIEW_PATH . '/../Config/Constants.php';

// Incluir utilidades
require_once BASE_VIEW_PATH . '/../Utils/Auth.php';
require_once BASE_VIEW_PATH . '/../Utils/Ayuda.php';

// Manejar errores
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Verificar autenticación en todas las páginas excepto login
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && !isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
