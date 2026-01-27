<?php
// Configuración de la aplicación

// Solo definir si no están ya definidas
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Sistema de Inventario');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '1.0.0');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}

// Configuración de base de datos PostgreSQL
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_PORT')) {
    define('DB_PORT', '5432');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'sistema_inventario');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'postgres');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', 'password');
}

// Configuración de URLs y rutas
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Rutas de directorios
if (!defined('UPLOAD_DIR')) {
    define('UPLOAD_DIR', BASE_PATH . '/uploads/');
}

if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5242880); // 5MB
}

// Configuración de moneda
if (!defined('CURRENCY')) {
    define('CURRENCY', '$');
}

if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', '$');
}

if (!defined('DECIMAL_SEPARATOR')) {
    define('DECIMAL_SEPARATOR', '.');
}

if (!defined('THOUSANDS_SEPARATOR')) {
    define('THOUSANDS_SEPARATOR', ',');
}

// Configuración de fecha y hora - ACTUALIZADO A CARACAS/VENEZUELA
if (!defined('DATE_FORMAT')) {
    define('DATE_FORMAT', 'd/m/Y');
}

if (!defined('TIME_FORMAT')) {
    define('TIME_FORMAT', 'H:i:s');
}

if (!defined('DATETIME_FORMAT')) {
    define('DATETIME_FORMAT', 'd/m/Y H:i:s');
}

// Configuración de seguridad
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
}

if (!defined('CSRF_TOKEN_EXPIRY')) {
    define('CSRF_TOKEN_EXPIRY', 3600); // 1 hora
}

// Configuración de inventario
if (!defined('DEFAULT_STOCK_MINIMUM')) {
    define('DEFAULT_STOCK_MINIMUM', 5);
}

if (!defined('LOW_STOCK_ALERT')) {
    define('LOW_STOCK_ALERT', true);
}

if (!defined('AUTO_GENERATE_SKU')) {
    define('AUTO_GENERATE_SKU', true);
}

// Configuración de ventas
if (!defined('DEFAULT_TAX_RATE')) {
    define('DEFAULT_TAX_RATE', 0.00);
}

if (!defined('ALLOW_BACKORDERS')) {
    define('ALLOW_BACKORDERS', false);
}

if (!defined('AUTO_UPDATE_STOCK')) {
    define('AUTO_UPDATE_STOCK', true);
}

// Niveles de log
if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR
}

// Mostrar errores según el entorno
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Configuración de zona horaria
date_default_timezone_set('America/Caracas');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Incluir Logger si existe
if (file_exists(BASE_PATH . '/Utils/Logger.php')) {
    require_once BASE_PATH . '/Utils/Logger.php';
}

// Función para cargar clases automáticamente
spl_autoload_register(function ($className) {
    $directories = [
        BASE_PATH . '/Models/',
        BASE_PATH . '/Controllers/',
        BASE_PATH . '/Utils/'
    ];

    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Log si no se encuentra la clase
    if (APP_ENV === 'development' && function_exists('appLog')) {
        appLog('WARNING', "Clase no encontrada: $className");
    }
});

// Función para generar token CSRF
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

// Función para validar token CSRF
function validateCSRFToken($token)
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    if ($_SESSION['csrf_token'] !== $token) {
        return false;
    }

    // Verificar expiración
    if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }

    return true;
}

// Función para manejo básico de errores
function handleError($errno, $errstr, $errfile, $errline)
{
    // No mostrar errores de constantes ya definidas
    if (strpos($errstr, 'Constant') !== false && strpos($errstr, 'already defined') !== false) {
        return true; // Silenciar estos errores
    }
    
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorType = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_DEPRECATED => 'DEPRECATED'
    ];

    $type = isset($errorType[$errno]) ? $errorType[$errno] : 'UNKNOWN';

    // Usar appLog si está disponible, si no, error_log normal
    if (function_exists('appLog')) {
        appLog('ERROR', "$type: $errstr en $errfile línea $errline");
    } else {
        error_log("$type: $errstr en $errfile línea $errline");
    }

    return true;
}

set_error_handler('handleError');

// Función para manejo de excepciones no capturadas
function handleException($exception)
{
    // Usar appLog si está disponible
    if (function_exists('appLog')) {
        appLog('ERROR', "Excepción no capturada: " . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    } else {
        error_log("Excepción no capturada: " . $exception->getMessage() .
            " en " . $exception->getFile() . " línea " . $exception->getLine());
    }

    if (APP_ENV === 'development') {
        echo "<pre>";
        echo "Excepción: " . $exception->getMessage() . "\n";
        echo "Archivo: " . $exception->getFile() . "\n";
        echo "Línea: " . $exception->getLine() . "\n";
        echo "Trace: " . $exception->getTraceAsString() . "\n";
        echo "</pre>";
    } else {
        header('HTTP/1.1 500 Internal Server Error');
        echo "Ha ocurrido un error interno. Por favor, contacte al administrador.";
    }
}

set_exception_handler('handleException');
?>