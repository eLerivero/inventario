<?php
// Views/includes/init.php

// Inicializar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definir ruta base
define('BASE_VIEW_PATH', dirname(__DIR__));

// Incluir configuración
require_once BASE_VIEW_PATH . '/../Config/Config.php';
require_once BASE_VIEW_PATH . '/../Config/Constants.php';

// Incluir utilidades
require_once BASE_VIEW_PATH . '/../Utils/Auth.php';

// Manejar errores
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>