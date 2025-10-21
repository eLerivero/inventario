<?php
// Incluir configuración
require_once '../Config/Config.php';

class Auth
{
    public static function checkAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: ../Views/auth/login.php");
            exit();
        }

        // Verificar timeout de sesión
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            self::logout();
        }

        $_SESSION['last_activity'] = time();
    }

    public static function login($user_id, $username, $rol = 'usuario')
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['rol'] = $rol;
        $_SESSION['last_activity'] = time();

        appLog('INFO', 'Usuario logueado', [
            'user_id' => $user_id,
            'username' => $username,
            'rol' => $rol
        ]);
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $user_id = $_SESSION['user_id'] ?? null;

        session_destroy();

        appLog('INFO', 'Usuario cerró sesión', ['user_id' => $user_id]);
        header("Location: ../Views/auth/login.php");
        exit();
    }

    public static function getUser()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'rol' => $_SESSION['rol'] ?? 'usuario'
            ];
        }

        return null;
    }

    public static function hasRole($role)
    {
        $user = self::getUser();
        return $user && $user['rol'] === $role;
    }
}

// Manejar acción de logout desde URL
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    Auth::logout();
}
