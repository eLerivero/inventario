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

// Roles de usuario (si los implementas después)
define('ROL_ADMIN', 'admin');
define('ROL_USUARIO', 'usuario');
define('ROL_VENDEDOR', 'vendedor');
