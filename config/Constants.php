<?php
// Constantes de la aplicación
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Sistema de Inventario');
}

if (!defined('SITE_VERSION')) {
    define('SITE_VERSION', '1.0.0');
}

if (!defined('DEFAULT_CURRENCY')) {
    define('DEFAULT_CURRENCY', '$');
}

// Estados de venta
if (!defined('VENTA_PENDIENTE')) {
    define('VENTA_PENDIENTE', 'pendiente');
}

if (!defined('VENTA_COMPLETADA')) {
    define('VENTA_COMPLETADA', 'completada');
}

if (!defined('VENTA_CANCELADA')) {
    define('VENTA_CANCELADA', 'cancelada');
}

// Tipos de movimiento de stock
if (!defined('MOVIMIENTO_ENTRADA')) {
    define('MOVIMIENTO_ENTRADA', 'entrada');
}

if (!defined('MOVIMIENTO_SALIDA')) {
    define('MOVIMIENTO_SALIDA', 'salida');
}

if (!defined('MOVIMIENTO_AJUSTE')) {
    define('MOVIMIENTO_AJUSTE', 'ajuste');
}

if (!defined('MOVIMIENTO_VENTA')) {
    define('MOVIMIENTO_VENTA', 'venta');
}

if (!defined('MOVIMIENTO_COMPRA')) {
    define('MOVIMIENTO_COMPRA', 'compra');
}

// Roles de usuario - CORREGIDOS
if (!defined('ROL_ADMIN')) {
    define('ROL_ADMIN', 'admin');
}

if (!defined('ROL_USUARIO')) {
    define('ROL_USUARIO', 'usuario'); // ¡¡CORREGIDO!! estaba como 'admin'
}

if (!defined('ROL_VENDEDOR')) {
    define('ROL_VENDEDOR', 'vendedor'); // ¡¡CORREGIDO!! estaba como 'admin'
}

// Configuración de monedas
if (!defined('MONEDA_BASE')) {
    define('MONEDA_BASE', 'USD');
}

if (!defined('MONEDA_LOCAL')) {
    define('MONEDA_LOCAL', 'VES');
}

if (!defined('SIMBOLO_USD')) {
    define('SIMBOLO_USD', '$');
}

if (!defined('SIMBOLO_BS')) {
    define('SIMBOLO_BS', 'Bs');
}

if (!defined('TASA_DEFAULT')) {
    define('TASA_DEFAULT', 247.30); // Tasa por defecto si no hay configurada
}

// Permisos para tasa de cambio
if (!defined('PERMISO_ACTUALIZAR_TASA')) {
    define('PERMISO_ACTUALIZAR_TASA', 'actualizar_tasa');
}

if (!defined('PERMISO_VER_HISTORIAL_TASA')) {
    define('PERMISO_VER_HISTORIAL_TASA', 'ver_historial_tasa');
}

// Constantes adicionales para el sistema
if (!defined('APP_ENV')) {
    define('APP_ENV', 'development');
}

if (!defined('BASE_URL')) {
    // Detectar automáticamente la URL base
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    // Remover index.php si está presente
    $base_path = str_replace('/index.php', '', $script_name);
    
    define('BASE_URL', $protocol . $host . $base_path);
}

if (!defined('SITE_URL')) {
    define('SITE_URL', BASE_URL);
}
?>