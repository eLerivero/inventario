<?php
// Controllers/AuthController.php

require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Config/Database.php';

class AuthController
{
    private $db;
    private $usuario;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->usuario = new Usuario($this->db);
        
        // Log
        $this->log("AuthController inicializado");
    }

    private function log($message) {
        $log_file = __DIR__ . '/../logs/app.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] AuthController: $message\n", FILE_APPEND);
    }

    // Procesar login
    public function login($username, $password)
    {
        $this->log("Iniciando login para: $username");
        
        // Debug: mostrar lo que llega
        $this->log("Username recibido: $username");
        $this->log("Password recibido (primeros 3 chars): " . substr($password, 0, 3) . "...");
        $this->log("Password completo: " . $password);
        
        // Buscar usuario por username o email
        $this->log("Buscando usuario por username: $username");
        $usuario = $this->usuario->buscarPorUsername($username);
        
        if (!$usuario) {
            $this->log("Usuario no encontrado por username, buscando por email");
            $usuario = $this->usuario->buscarPorEmail($username);
        }
        
        if (!$usuario) {
            $this->log("Usuario NO encontrado: $username");
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }
        
        $this->log("Usuario encontrado: " . json_encode([
            'id' => $usuario['id'],
            'username' => $usuario['username'],
            'activo' => $usuario['activo'],
            'hash_length' => strlen($usuario['password_hash'])
        ]));
        
        if (!$usuario['activo']) {
            $this->log("Usuario desactivado: " . $usuario['username']);
            return ['success' => false, 'message' => 'Usuario desactivado'];
        }
        
        // Debug del hash
        $this->log("Hash en BD: " . $usuario['password_hash']);
        $this->log("Password a verificar: $password");
        
        // Verificar la contraseña
        $this->log("Verificando contraseña...");
        
        // PRUEBA: Verificar con diferentes métodos
        $this->log("=== PRUEBA DE VERIFICACIÓN ===");
        
        // Método 1: password_verify normal
        $verificacion_normal = password_verify($password, $usuario['password_hash']);
        $this->log("password_verify resultado: " . ($verificacion_normal ? 'TRUE' : 'FALSE'));
        
        // Método 2: Para debugging, aceptar 'admin' si el hash es el placeholder
        if ($usuario['password_hash'] === '$2y$10$YourHashedPasswordHere') {
            $this->log("Hash es placeholder, probando con 'admin'");
            if ($password === 'admin') {
                $this->log("Contraseña 'admin' aceptada para placeholder");
                // Actualizar el hash a uno válido
                $this->actualizarHashValido($usuario['id'], $password);
                $verificacion_normal = true;
            }
        }
        
        // Método 3: Si el hash parece bcrypt pero no verifica, probar con hash manual
        if (!$verificacion_normal && preg_match('/^\$2[ayb]\$[0-9]{2}\$/', $usuario['password_hash'])) {
            $this->log("Hash parece bcrypt pero no verifica, probando verificación manual");
            
            // Extraer información del hash
            $parts = explode('$', $usuario['password_hash']);
            if (count($parts) === 4) {
                $this->log("Hash parts: " . json_encode($parts));
                
                // Crear un hash temporal para comparar
                $test_hash = password_hash($password, PASSWORD_BCRYPT);
                $this->log("Test hash generado: $test_hash");
                
                // Comparar los primeros caracteres para ver si son similares
                if (substr($usuario['password_hash'], 0, 7) === substr($test_hash, 0, 7)) {
                    $this->log("Los hashes tienen el mismo formato");
                }
            }
        }
        
        if ($verificacion_normal) {
            $this->log("✓ Contraseña verificada correctamente");
            
            // Actualizar último login
            $this->log("Actualizando último login para usuario ID: " . $usuario['id']);
            $this->usuario->actualizarUltimoLogin($usuario['id']);
            
            $this->log("Login exitoso para: " . $usuario['username']);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $usuario['id'],
                    'username' => $usuario['username'],
                    'nombre' => $usuario['nombre'],
                    'email' => $usuario['email'],
                    'rol' => $usuario['rol']
                ]
            ];
        }
        
        $this->log("✗ Contraseña incorrecta");
        $this->log("Hash almacenado: " . $usuario['password_hash']);
        $this->log("Password ingresada: $password");
        
        return ['success' => false, 'message' => 'Contraseña incorrecta'];
    }
    
    private function actualizarHashValido($userId, $password)
    {
        $this->log("Actualizando hash para usuario ID: $userId");
        
        $nuevo_hash = password_hash($password, PASSWORD_BCRYPT);
        $this->log("Nuevo hash generado: $nuevo_hash");
        
        try {
            $query = "UPDATE usuarios SET password_hash = :hash WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":hash", $nuevo_hash);
            $stmt->bindParam(":id", $userId);
            
            if ($stmt->execute()) {
                $this->log("✓ Hash actualizado en BD");
            } else {
                $this->log("✗ Error al actualizar hash");
            }
        } catch (Exception $e) {
            $this->log("Error actualizando hash: " . $e->getMessage());
        }
    }

    // Cerrar sesión
    public function logout()
    {
        session_destroy();
        return true;
    }

    // Verificar si el usuario está autenticado
    public static function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }

    // Verificar rol
    public static function hasRole($rol)
    {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        return $_SESSION['user_rol'] === $rol;
    }

    // Obtener usuario actual
    public static function getCurrentUser()
    {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['user_username'],
            'nombre' => $_SESSION['user_nombre'],
            'email' => $_SESSION['user_email'],
            'rol' => $_SESSION['user_rol']
        ];
    }

    // Redirigir si no está autenticado
    public static function requireAuth()
    {
        if (!self::isAuthenticated()) {
            header("Location: /Views/auth/login.php");
            exit();
        }
    }

    // Redirigir si no tiene el rol requerido
    public static function requireRole($rol)
    {
        self::requireAuth();
        
        if (!self::hasRole($rol)) {
            header("Location: /Views/dashboard/index.php");
            exit();
        }
    }
}
?>