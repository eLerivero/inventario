<?php
// Constantes de la aplicación
define('SITE_NAME', 'Sistema de Inventario');
define('SITE_VERSION', '1.0.0');
define('DEFAULT_CURRENCY', '$');

// Estados de venta
define('VENTA_PENDIENTE', 'pendiente');
define('VENTA_COMPLETADA', 'completada');
define('VENTA_CANCELADA', 'cancelada');

// Tipos de movimiento de stock
define('MOVIMIENTO_ENTRADA', 'entrada');
define('MOVIMIENTO_SALIDA', 'salida');
define('MOVIMIENTO_AJUSTE', 'ajuste');
define('MOVIMIENTO_VENTA', 'venta');
define('MOVIMIENTO_COMPRA', 'compra');

// Roles de usuario 
define('ROL_ADMIN', 'admin');
define('ROL_USUARIO', 'usuario');
define('ROL_VENDEDOR', 'vendedor');



// Configuración de monedas
define('MONEDA_BASE', 'USD');
define('MONEDA_LOCAL', 'VES');
define('SIMBOLO_USD', '$');
define('SIMBOLO_BS', 'Bs');
define('TASA_DEFAULT', 247.30); // Tasa por defecto si no hay configurada

// Permisos para tasa de cambio
define('PERMISO_ACTUALIZAR_TASA', 'actualizar_tasa');
define('PERMISO_VER_HISTORIAL_TASA', 'ver_historial_tasa');

