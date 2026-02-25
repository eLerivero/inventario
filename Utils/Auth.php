<?php
// Utils/Auth.php

class Auth
{
    // Asegurar que la sesión esté iniciada
    private static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Configurar cookie de sesión segura
            session_set_cookie_params([
                'lifetime' => 3600,
                'path' => '/',
                'domain' => $_SERVER['HTTP_HOST'] ?? '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }

    // Iniciar sesión
    public static function login($userData)
    {
        self::startSession();
        
        // Regenerar ID de sesión para prevenir fijación
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_username'] = $userData['username'];
        $_SESSION['user_nombre'] = $userData['nombre'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_rol'] = $userData['rol'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Log para debugging
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("Auth::login() - Usuario: {$userData['username']}, Rol: {$userData['rol']}");
        }
        
        return true;
    }

    // Cerrar sesión
    public static function logout()
    {
        self::startSession();
        
        // Limpiar todas las variables de sesión
        $_SESSION = [];
        
        // Destruir la cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }

    // Verificar si está autenticado
    public static function check()
    {
        self::startSession();
        
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Verificar tiempo de sesión (1 hora)
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 3600)) {
            self::logout();
            return false;
        }
        
        // Actualizar tiempo de sesión (para mantenerla viva)
        $_SESSION['login_time'] = time();
        
        return true;
    }

    // Obtener datos del usuario
    public static function user()
    {
        if (self::check()) {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['user_username'] ?? '',
                'nombre' => $_SESSION['user_nombre'] ?? '',
                'email' => $_SESSION['user_email'] ?? '',
                'rol' => $_SESSION['user_rol'] ?? ''
            ];
        }
        return null;
    }

    // Verificar si es admin
    public static function isAdmin()
    {
        if (!self::check()) {
            return false;
        }
        
        $user_rol = $_SESSION['user_rol'] ?? '';
        
        // Debugging
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("Auth::isAdmin() - Rol en sesión: '$user_rol'");
        }
        
        // Verificar si el rol es 'admin' (case-insensitive)
        $is_admin = (strtolower(trim($user_rol)) === 'admin');
        
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("Auth::isAdmin() - Es admin: " . ($is_admin ? 'SI' : 'NO'));
        }
        
        return $is_admin;
    }

    // Verificar rol específico
    public static function hasRole($role)
    {
        if (!self::check()) {
            return false;
        }
        
        $user_rol = $_SESSION['user_rol'] ?? '';
        return strtolower(trim($user_rol)) === strtolower(trim($role));
    }

    // Requerir autenticación
    public static function requireAuth()
    {
        if (!self::check()) {
            // Guardar URL actual para redirigir después del login
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            
            // Debugging
            if (defined('APP_ENV') && APP_ENV === 'development') {
                error_log("Auth::requireAuth() - Redirigiendo a login desde: " . $_SERVER['REQUEST_URI']);
            }
            
            header("Location: " . self::getLoginUrl());
            exit();
        }
    }

    // Requerir administrador
    public static function requireAdmin()
    {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            // Debugging
            if (defined('APP_ENV') && APP_ENV === 'development') {
                $user = self::user();
                error_log("Auth::requireAdmin() - Acceso denegado para usuario: " . ($user['username'] ?? 'desconocido'));
                error_log("Auth::requireAdmin() - Rol del usuario: " . ($user['rol'] ?? 'no definido'));
            }
            
            $_SESSION['error'] = 'Acceso restringido. Se requieren permisos de administrador.';
            header("Location: " . self::getDashboardUrl());
            exit();
        }
        
        // Debugging
        if (defined('APP_ENV') && APP_ENV === 'development') {
            error_log("Auth::requireAdmin() - Acceso permitido a admin");
        }
    }

    // Obtener URL del login
    private static function getLoginUrl()
    {
        // URL base desde la configuración
        $base_url = defined('BASE_URL') ? BASE_URL : '';
        
        // Si estamos en una carpeta específica
        $current_script = $_SERVER['SCRIPT_NAME'] ?? '';
        $depth = substr_count($current_script, '/') - 1;
        
        if ($depth > 0) {
            return str_repeat('../', $depth) . 'auth/login.php';
        }
        
        return 'auth/login.php';
    }

    // Obtener URL del dashboard
    private static function getDashboardUrl()
    {
        // URL base desde la configuración
        $base_url = defined('BASE_URL') ? BASE_URL : '';
        
        // Si estamos en una carpeta específica
        $current_script = $_SERVER['SCRIPT_NAME'] ?? '';
        $depth = substr_count($current_script, '/') - 1;
        
        if ($depth > 0) {
            return str_repeat('../', $depth) . 'inventario/Views/dashboard/index.php';
        }
        
        return 'dashboard/index.php';
    }

    // Función de debug para desarrollo
    public static function debug()
    {
        self::startSession();
        
        if (defined('APP_ENV') && APP_ENV === 'development') {
            echo "<div style='background:#f0f0f0; padding:10px; border:1px solid #ccc; margin:10px;'>";
            echo "<h3>Debug de Autenticación</h3>";
            echo "<pre>";
            echo "Session ID: " . session_id() . "\n";
            echo "Session Status: " . session_status() . "\n";
            echo "Is Logged In: " . (isset($_SESSION['logged_in']) ? 'SI' : 'NO') . "\n";
            echo "User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "\n";
            echo "Username: " . ($_SESSION['user_username'] ?? 'NO DEFINIDO') . "\n";
            echo "User Rol: " . ($_SESSION['user_rol'] ?? 'NO DEFINIDO') . "\n";
            echo "Is Admin (check): " . (self::isAdmin() ? 'SI' : 'NO') . "\n";
            echo "Session Data:\n";
            print_r($_SESSION);
            echo "</pre>";
            echo "</div>";
        }
    }

    // En Utils/Auth.php, añade estas funciones:

// Verificar si el usuario tiene acceso a ventas
public static function canAccessVentas()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // 'admin' y 'usuario' pueden acceder a ventas
    return in_array($rol, ['admin', 'usuario']);
}

// Verificar si el usuario tiene acceso a clientes
public static function canAccessClientes()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // 'admin' y 'usuario' pueden acceder a clientes
    return in_array($rol, ['admin', 'usuario']);
}

// Verificar si el usuario tiene acceso a productos
public static function canAccessProductos()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // Solo 'admin' puede acceder a productos
    return $rol === 'admin';
}

// Verificar si el usuario tiene acceso a categorías
public static function canAccessCategorias()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // Solo 'admin' puede acceder a categorías
    return $rol === 'admin';
}

// Verificar si el usuario tiene acceso a tasas de cambio
public static function canAccessTasasCambio()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // Solo 'admin' puede acceder a tasas de cambio
    return $rol === 'admin';
}

// Verificar si el usuario tiene acceso a tipos de pago
public static function canAccessTiposPago()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // Solo 'admin' puede acceder a tipos de pago
    return $rol === 'admin';
}

// Verificar si el usuario tiene acceso a historial de stock
public static function canAccessHistorialStock()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // Solo 'admin' puede acceder a historial de stock
    return $rol === 'admin';
}

// Verificar si el usuario tiene acceso a usuarios
    public static function canAccessUsuarios()
    {
        if (!self::check()) {
            return false;
        }
        
        $user_rol = $_SESSION['user_rol'] ?? '';
        $rol = strtolower(trim($user_rol));
        
        // Solo 'admin' puede acceder a usuarios
        return $rol === 'admin';
    }

    public static function canAccessCierreCaja()
{
    if (!self::check()) {
        return false;
    }
    
    $user_rol = $_SESSION['user_rol'] ?? '';
    $rol = strtolower(trim($user_rol));
    
    // Solo 'admin' puede acceder a tasas de cambio
    return $rol === 'admin';
}

// Requerir acceso a usuarios
public static function requireAccessToUsuarios()
{
    if (!self::canAccessUsuarios()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de usuarios';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

// Requerir acceso a ventas
public static function requireAccessToVentas()
{
    if (!self::canAccessVentas()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de ventas';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

// Requerir acceso a clientes
public static function requireAccessToClientes()
{
    if (!self::canAccessClientes()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de clientes';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

// Requerir acceso a productos
public static function requireAccessToProductos()
{
    if (!self::canAccessProductos()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de productos';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

public static function requireAccessToCategorias()
{
    if (!self::canAccessCategorias()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de categorias';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

public static function requireAccessToTiposPagos()
{
    if (!self::canAccessTiposPago()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de tipo de pagos';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

public static function requireAccessToHistorialStock()
{
    if (!self::canAccessHistorialStock()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de tipo de historial de stock';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}

public static function requireAccessToCierreCaja()
{
    if (!self::canAccessHistorialStock()) {
        $_SESSION['error'] = 'No tienes permisos para acceder al módulo de tipo de historial de stock';
        header("Location: " . self::getDashboardUrl());
        exit();
    }
}


}
?>