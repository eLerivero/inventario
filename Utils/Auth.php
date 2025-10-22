<?php
// Usar rutas absolutas para evitar problemas de inclusión
$configPath = __DIR__ . '/../Config/Config.php';
if (!file_exists($configPath)) {
    die("Error: No se puede encontrar el archivo de configuración.");
}
require_once $configPath;

class Auth
{
    public static function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Usuario de prueba para desarrollo
        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@inventario.com',
                'rol' => 'admin',
                'nombre' => 'Administrador'
            ];
        }
        
        return true;
    }

    public static function getUser()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function logout()
    {
        session_destroy();
        // Usar ruta absoluta desde la raíz
        header('Location: /inventario/index.php');
        exit();
    }

    public static function hasRole($role)
    {
        $user = self::getUser();
        return $user && $user['rol'] === $role;
    }

    public static function isAuthenticated()
    {
        return isset($_SESSION['user']);
    }
}

// Manejar logout solo si se accede directamente a este archivo
if (basename($_SERVER['PHP_SELF']) === 'Auth.php' && isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
}