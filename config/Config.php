<?php
// Configuración de la aplicación
define('APP_NAME', 'Sistema de Inventario');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development');

// Configuración de base de datos PostgreSQL
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'sistema_inventario');
define('DB_USER', 'postgres');
define('DB_PASS', 'password');

// Configuración de la aplicación
define('SITE_URL', 'http://localhost/inventario');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_DIR', BASE_PATH . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Configuración de moneda
define('CURRENCY', '$');
define('CURRENCY_SYMBOL', '$');
define('DECIMAL_SEPARATOR', '.');
define('THOUSANDS_SEPARATOR', ',');

// Configuración de fecha y hora - ACTUALIZADO A CARACAS/VENEZUELA
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i:s');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

// Configuración de seguridad
define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hora

// Configuración de inventario
define('DEFAULT_STOCK_MINIMUM', 5);
define('LOW_STOCK_ALERT', true);
define('AUTO_GENERATE_SKU', true);

// Configuración de ventas
define('DEFAULT_TAX_RATE', 0.00);
define('ALLOW_BACKORDERS', false);
define('AUTO_UPDATE_STOCK', true);

// Niveles de log
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR

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
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $errorType = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        //       E_STRICT => 'STRICT',
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
