<?php
// Config/Constants.php

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

// Roles de usuario
if (!defined('ROL_ADMIN')) {
    define('ROL_ADMIN', 'admin');
}

if (!defined('ROL_USUARIO')) {
    define('ROL_USUARIO', 'usuario');
}

if (!defined('ROL_VENDEDOR')) {
    define('ROL_VENDEDOR', 'vendedor');
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

// ================================================
// NUEVAS CONSTANTES PARA PERMISOS DE MÓDULOS
// ================================================

// Permisos de acceso a módulos
if (!defined('PERMISO_DASHBOARD')) {
    define('PERMISO_DASHBOARD', 'acceso_dashboard');
}

if (!defined('PERMISO_USUARIOS')) {
    define('PERMISO_USUARIOS', 'acceso_usuarios');
}

if (!defined('PERMISO_PRODUCTOS')) {
    define('PERMISO_PRODUCTOS', 'acceso_productos');
}

if (!defined('PERMISO_CATEGORIAS')) {
    define('PERMISO_CATEGORIAS', 'acceso_categorias');
}

if (!defined('PERMISO_CLIENTES')) {
    define('PERMISO_CLIENTES', 'acceso_clientes');
}

if (!defined('PERMISO_VENTAS')) {
    define('PERMISO_VENTAS', 'acceso_ventas');
}

if (!defined('PERMISO_TASAS_CAMBIO')) {
    define('PERMISO_TASAS_CAMBIO', 'acceso_tasas_cambio');
}

if (!defined('PERMISO_TIPOS_PAGO')) {
    define('PERMISO_TIPOS_PAGO', 'acceso_tipos_pago');
}

if (!defined('PERMISO_HISTORIAL_STOCK')) {
    define('PERMISO_HISTORIAL_STOCK', 'acceso_historial_stock');
}

// Permisos CRUD dentro de módulos
if (!defined('PERMISO_CREAR')) {
    define('PERMISO_CREAR', 'crear');
}

if (!defined('PERMISO_LEER')) {
    define('PERMISO_LEER', 'leer');
}

if (!defined('PERMISO_ACTUALIZAR')) {
    define('PERMISO_ACTUALIZAR', 'actualizar');
}

if (!defined('PERMISO_ELIMINAR')) {
    define('PERMISO_ELIMINAR', 'eliminar');
}

// Permisos específicos para ventas
if (!defined('PERMISO_VENTA_CREAR')) {
    define('PERMISO_VENTA_CREAR', 'venta_crear');
}

if (!defined('PERMISO_VENTA_EDITAR')) {
    define('PERMISO_VENTA_EDITAR', 'venta_editar');
}

if (!defined('PERMISO_VENTA_ELIMINAR')) {
    define('PERMISO_VENTA_ELIMINAR', 'venta_eliminar');
}

if (!defined('PERMISO_VENTA_COMPLETAR')) {
    define('PERMISO_VENTA_COMPLETAR', 'venta_completar');
}

if (!defined('PERMISO_VENTA_CANCELAR')) {
    define('PERMISO_VENTA_CANCELAR', 'venta_cancelar');
}

// Permisos específicos para clientes
if (!defined('PERMISO_CLIENTE_CREAR')) {
    define('PERMISO_CLIENTE_CREAR', 'cliente_crear');
}

if (!defined('PERMISO_CLIENTE_EDITAR')) {
    define('PERMISO_CLIENTE_EDITAR', 'cliente_editar');
}

if (!defined('PERMISO_CLIENTE_ELIMINAR')) {
    define('PERMISO_CLIENTE_ELIMINAR', 'cliente_eliminar');
}

if (!defined('PERMISO_CLIENTE_VER')) {
    define('PERMISO_CLIENTE_VER', 'cliente_ver');
}

// Permisos específicos para productos
if (!defined('PERMISO_PRODUCTO_CREAR')) {
    define('PERMISO_PRODUCTO_CREAR', 'producto_crear');
}

if (!defined('PERMISO_PRODUCTO_EDITAR')) {
    define('PERMISO_PRODUCTO_EDITAR', 'producto_editar');
}

if (!defined('PERMISO_PRODUCTO_ELIMINAR')) {
    define('PERMISO_PRODUCTO_ELIMINAR', 'producto_eliminar');
}

if (!defined('PERMISO_PRODUCTO_VER')) {
    define('PERMISO_PRODUCTO_VER', 'producto_ver');
}

// Permisos específicos para usuarios
if (!defined('PERMISO_USUARIO_CREAR')) {
    define('PERMISO_USUARIO_CREAR', 'usuario_crear');
}

if (!defined('PERMISO_USUARIO_EDITAR')) {
    define('PERMISO_USUARIO_EDITAR', 'usuario_editar');
}

if (!defined('PERMISO_USUARIO_ELIMINAR')) {
    define('PERMISO_USUARIO_ELIMINAR', 'usuario_eliminar');
}

if (!defined('PERMISO_USUARIO_VER')) {
    define('PERMISO_USUARIO_VER', 'usuario_ver');
}

// ================================================
// CONSTANTES DE CONFIGURACIÓN DEL SISTEMA
// ================================================

if (!defined('APP_ENV')) {
    define('APP_ENV', 'development'); // development, testing, production
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

// Configuración de sesión
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 3600); // 1 hora en segundos
}

if (!defined('SESSION_REGENERATE')) {
    define('SESSION_REGENERATE', 300); // Regenerar ID cada 5 minutos
}

// Configuración de seguridad
if (!defined('PASSWORD_MIN_LENGTH')) {
    define('PASSWORD_MIN_LENGTH', 6);
}

if (!defined('MAX_LOGIN_ATTEMPTS')) {
    define('MAX_LOGIN_ATTEMPTS', 5);
}

if (!defined('LOGIN_LOCKOUT_TIME')) {
    define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos en segundos
}

// Configuración de paginación
if (!defined('ITEMS_PER_PAGE')) {
    define('ITEMS_PER_PAGE', 10);
}

if (!defined('MAX_ITEMS_PER_PAGE')) {
    define('MAX_ITEMS_PER_PAGE', 100);
}

// Configuración de archivos
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 2097152); // 2MB en bytes
}

if (!defined('ALLOWED_FILE_TYPES')) {
    define('ALLOWED_FILE_TYPES', 'jpg,jpeg,png,gif,pdf,doc,docx');
}

// Configuración de notificaciones
if (!defined('ENABLE_EMAIL_NOTIFICATIONS')) {
    define('ENABLE_EMAIL_NOTIFICATIONS', false);
}

if (!defined('ENABLE_STOCK_ALERTS')) {
    define('ENABLE_STOCK_ALERTS', true);
}

if (!defined('STOCK_MIN_THRESHOLD')) {
    define('STOCK_MIN_THRESHOLD', 10);
}

// Configuración de reportes
if (!defined('REPORT_DATE_FORMAT')) {
    define('REPORT_DATE_FORMAT', 'd/m/Y');
}

if (!defined('REPORT_TIME_FORMAT')) {
    define('REPORT_TIME_FORMAT', 'H:i:s');
}

// ================================================
// CONSTANTES DE MENSAJES DEL SISTEMA
// ================================================

// Mensajes de error generales
if (!defined('MSG_NO_PERMISSION')) {
    define('MSG_NO_PERMISSION', 'No tienes permisos para realizar esta acción.');
}

if (!defined('MSG_LOGIN_REQUIRED')) {
    define('MSG_LOGIN_REQUIRED', 'Debes iniciar sesión para acceder a esta página.');
}

if (!defined('MSG_INVALID_REQUEST')) {
    define('MSG_INVALID_REQUEST', 'Solicitud inválida.');
}

if (!defined('MSG_NOT_FOUND')) {
    define('MSG_NOT_FOUND', 'Recurso no encontrado.');
}

if (!defined('MSG_SERVER_ERROR')) {
    define('MSG_SERVER_ERROR', 'Error del servidor. Por favor, intente más tarde.');
}

// Mensajes de éxito
if (!defined('MSG_SUCCESS_CREATE')) {
    define('MSG_SUCCESS_CREATE', 'Registro creado exitosamente.');
}

if (!defined('MSG_SUCCESS_UPDATE')) {
    define('MSG_SUCCESS_UPDATE', 'Registro actualizado exitosamente.');
}

if (!defined('MSG_SUCCESS_DELETE')) {
    define('MSG_SUCCESS_DELETE', 'Registro eliminado exitosamente.');
}

if (!defined('MSG_SUCCESS_ACTION')) {
    define('MSG_SUCCESS_ACTION', 'Acción completada exitosamente.');
}

// ================================================
// CONSTANTES DE VALIDACIÓN
// ================================================

// Validación de usuarios
if (!defined('USERNAME_MIN_LENGTH')) {
    define('USERNAME_MIN_LENGTH', 3);
}

if (!defined('USERNAME_MAX_LENGTH')) {
    define('USERNAME_MAX_LENGTH', 50);
}

if (!defined('NAME_MIN_LENGTH')) {
    define('NAME_MIN_LENGTH', 2);
}

if (!defined('NAME_MAX_LENGTH')) {
    define('NAME_MAX_LENGTH', 100);
}

if (!defined('EMAIL_MAX_LENGTH')) {
    define('EMAIL_MAX_LENGTH', 255);
}

// Validación de productos
if (!defined('PRODUCT_CODE_MIN_LENGTH')) {
    define('PRODUCT_CODE_MIN_LENGTH', 1);
}

if (!defined('PRODUCT_CODE_MAX_LENGTH')) {
    define('PRODUCT_CODE_MAX_LENGTH', 50);
}

if (!defined('PRODUCT_NAME_MIN_LENGTH')) {
    define('PRODUCT_NAME_MIN_LENGTH', 2);
}

if (!defined('PRODUCT_NAME_MAX_LENGTH')) {
    define('PRODUCT_NAME_MAX_LENGTH', 255);
}

if (!defined('PRODUCT_DESCRIPTION_MAX_LENGTH')) {
    define('PRODUCT_DESCRIPTION_MAX_LENGTH', 1000);
}

// Validación de clientes
if (!defined('CLIENT_NAME_MIN_LENGTH')) {
    define('CLIENT_NAME_MIN_LENGTH', 2);
}

if (!defined('CLIENT_NAME_MAX_LENGTH')) {
    define('CLIENT_NAME_MAX_LENGTH', 200);
}

if (!defined('CLIENT_PHONE_MAX_LENGTH')) {
    define('CLIENT_PHONE_MAX_LENGTH', 20);
}

if (!defined('CLIENT_ADDRESS_MAX_LENGTH')) {
    define('CLIENT_ADDRESS_MAX_LENGTH', 500);
}

// ================================================
// CONSTANTES DE BASE DE DATOS
// ================================================

// Estatus de registros
if (!defined('STATUS_ACTIVE')) {
    define('STATUS_ACTIVE', 1);
}

if (!defined('STATUS_INACTIVE')) {
    define('STATUS_INACTIVE', 0);
}

if (!defined('STATUS_DELETED')) {
    define('STATUS_DELETED', -1);
}

// Campos de auditoría
if (!defined('FIELD_CREATED_AT')) {
    define('FIELD_CREATED_AT', 'created_at');
}

if (!defined('FIELD_UPDATED_AT')) {
    define('FIELD_UPDATED_AT', 'updated_at');
}

if (!defined('FIELD_DELETED_AT')) {
    define('FIELD_DELETED_AT', 'deleted_at');
}

if (!defined('FIELD_CREATED_BY')) {
    define('FIELD_CREATED_BY', 'created_by');
}

if (!defined('FIELD_UPDATED_BY')) {
    define('FIELD_UPDATED_BY', 'updated_by');
}

if (!defined('FIELD_DELETED_BY')) {
    define('FIELD_DELETED_BY', 'deleted_by');
}

// ================================================
// CONSTANTES DE TASA DE CAMBIO
// ================================================

if (!defined('TASA_UPDATE_HOUR')) {
    define('TASA_UPDATE_HOUR', 8); // Hora para actualizar tasa (8 AM)
}

if (!defined('TASA_EXPIRATION_HOURS')) {
    define('TASA_EXPIRATION_HOURS', 24); // Tasa válida por 24 horas
}

if (!defined('TASA_SOURCE_API')) {
    define('TASA_SOURCE_API', 'BCV'); // Fuente de la tasa
}

// ================================================
// CONSTANTES DE TIPOS DE PAGO
// ================================================

if (!defined('PAYMENT_CASH')) {
    define('PAYMENT_CASH', 'efectivo');
}

if (!defined('PAYMENT_CARD')) {
    define('PAYMENT_CARD', 'tarjeta');
}

if (!defined('PAYMENT_TRANSFER')) {
    define('PAYMENT_TRANSFER', 'transferencia');
}

if (!defined('PAYMENT_OTHER')) {
    define('PAYMENT_OTHER', 'otro');
}

// ================================================
// CONSTANTES PARA LOGS DEL SISTEMA
// ================================================

// Niveles de log
if (!defined('LOG_LEVEL_ERROR')) {
    define('LOG_LEVEL_ERROR', 'ERROR');
}

if (!defined('LOG_LEVEL_WARNING')) {
    define('LOG_LEVEL_WARNING', 'WARNING');
}

if (!defined('LOG_LEVEL_INFO')) {
    define('LOG_LEVEL_INFO', 'INFO');
}

if (!defined('LOG_LEVEL_DEBUG')) {
    define('LOG_LEVEL_DEBUG', 'DEBUG');
}

// Tipos de acción
if (!defined('LOG_ACTION_CREATE')) {
    define('LOG_ACTION_CREATE', 'CREATE');
}

if (!defined('LOG_ACTION_UPDATE')) {
    define('LOG_ACTION_UPDATE', 'UPDATE');
}

if (!defined('LOG_ACTION_DELETE')) {
    define('LOG_ACTION_DELETE', 'DELETE');
}

if (!defined('LOG_ACTION_LOGIN')) {
    define('LOG_ACTION_LOGIN', 'LOGIN');
}

if (!defined('LOG_ACTION_LOGOUT')) {
    define('LOG_ACTION_LOGOUT', 'LOGOUT');
}

if (!defined('LOG_ACTION_ACCESS')) {
    define('LOG_ACTION_ACCESS', 'ACCESS');
}
?>