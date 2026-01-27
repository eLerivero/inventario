<?php
// Controllers/UsuarioController.php

require_once __DIR__ . '/../Models/Usuario.php';

class UsuarioController
{
    private $db;
    private $usuario;

    public function __construct($db)
    {
        $this->db = $db;
        $this->usuario = new Usuario($db);
    }

    // Listar todos los usuarios
    public function index()
    {
        try {
            $stmt = $this->usuario->leer();
            $usuarios = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $usuarios[] = $row;
            }
            
            return $usuarios;
        } catch (Exception $e) {
            error_log("Error en UsuarioController::index(): " . $e->getMessage());
            return [];
        }
    }

    // Obtener usuario por ID
    public function show($id)
    {
        try {
            $this->usuario->id = $id;
            $usuario = $this->usuario->leerUno();
            
            if (!$usuario) {
                throw new Exception("Usuario no encontrado");
            }
            
            return $usuario;
        } catch (Exception $e) {
            error_log("Error en UsuarioController::show(): " . $e->getMessage());
            return false;
        }
    }

    // Crear nuevo usuario
    public function store($data)
    {
        try {
            // Validar datos básicos
            if (empty($data['username']) || empty($data['password']) || empty($data['nombre']) || empty($data['email'])) {
                throw new Exception("Todos los campos son requeridos");
            }

            // Verificar si username ya existe
            if ($this->usernameExists($data['username'])) {
                throw new Exception("El nombre de usuario ya está en uso");
            }

            // Verificar si email ya existe
            if ($this->emailExists($data['email'])) {
                throw new Exception("El email ya está registrado");
            }

            // Configurar datos del usuario
            $this->usuario->username = $data['username'];
            $this->usuario->password_hash = $data['password']; // Se hasheará en el modelo
            $this->usuario->nombre = $data['nombre'];
            $this->usuario->email = $data['email'];
            $this->usuario->rol = $data['rol'] ?? 'usuario';
            
            // Crear usuario
            $result = $this->usuario->crear();
            
            if ($result) {
                return [
                    'success' => true, 
                    'message' => 'Usuario creado exitosamente',
                    'data' => $result
                ];
            } else {
                throw new Exception("Error al crear el usuario en la base de datos");
            }
            
        } catch (Exception $e) {
            error_log("Error en UsuarioController::store(): " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    // Actualizar usuario
    public function update($id, $data)
    {
        try {
            // Verificar que el usuario exista
            $usuarioExistente = $this->show($id);
            if (!$usuarioExistente) {
                throw new Exception("Usuario no encontrado");
            }

            // Verificar si el nuevo username ya existe (excepto para este usuario)
            if (isset($data['username']) && $this->usernameExists($data['username'], $id)) {
                throw new Exception("El nombre de usuario ya está en uso");
            }

            // Verificar si el nuevo email ya existe (excepto para este usuario)
            if (isset($data['email']) && $this->emailExists($data['email'], $id)) {
                throw new Exception("El email ya está registrado");
            }

            // Configurar datos
            $this->usuario->id = $id;
            $this->usuario->nombre = $data['nombre'] ?? $usuarioExistente['nombre'];
            $this->usuario->email = $data['email'] ?? $usuarioExistente['email'];
            $this->usuario->rol = $data['rol'] ?? $usuarioExistente['rol'];
            $this->usuario->activo = $data['activo'] ?? $usuarioExistente['activo'];

            // Actualizar usuario
            if ($this->usuario->actualizar()) {
                return [
                    'success' => true, 
                    'message' => 'Usuario actualizado exitosamente'
                ];
            } else {
                throw new Exception("Error al actualizar el usuario");
            }
            
        } catch (Exception $e) {
            error_log("Error en UsuarioController::update(): " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    // Cambiar contraseña
    public function cambiarPassword($id, $password)
    {
        try {
            // Verificar que el usuario exista
            $usuarioExistente = $this->show($id);
            if (!$usuarioExistente) {
                throw new Exception("Usuario no encontrado");
            }

            // Validar contraseña
            if (empty($password) || strlen($password) < 6) {
                throw new Exception("La contraseña debe tener al menos 6 caracteres");
            }

            // Cambiar contraseña
            $this->usuario->id = $id;
            
            if ($this->usuario->cambiarPassword($password)) {
                return [
                    'success' => true, 
                    'message' => 'Contraseña cambiada exitosamente'
                ];
            } else {
                throw new Exception("Error al cambiar la contraseña");
            }
            
        } catch (Exception $e) {
            error_log("Error en UsuarioController::cambiarPassword(): " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    // Eliminar/desactivar usuario
    public function destroy($id)
    {
        try {
            // Verificar que el usuario exista
            $usuarioExistente = $this->show($id);
            if (!$usuarioExistente) {
                throw new Exception("Usuario no encontrado");
            }

            // No permitir desactivarse a sí mismo
            session_start();
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                throw new Exception("No puedes desactivar tu propia cuenta");
            }

            $this->usuario->id = $id;
            
            if ($this->usuario->eliminar()) {
                return [
                    'success' => true, 
                    'message' => 'Usuario desactivado exitosamente'
                ];
            } else {
                throw new Exception("Error al desactivar el usuario");
            }
            
        } catch (Exception $e) {
            error_log("Error en UsuarioController::destroy(): " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    // Activar usuario
    public function activate($id)
    {
        try {
            $query = "UPDATE usuarios SET activo = true, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                return [
                    'success' => true, 
                    'message' => 'Usuario activado exitosamente'
                ];
            } else {
                throw new Exception("Error al activar el usuario");
            }
            
        } catch (Exception $e) {
            error_log("Error en UsuarioController::activate(): " . $e->getMessage());
            return [
                'success' => false, 
                'message' => $e->getMessage()
            ];
        }
    }

    // Validar datos del usuario
    public function validate($data, $isUpdate = false)
    {
        $errors = [];

        // Validar username (solo para creación)
        if (!$isUpdate) {
            if (empty($data['username']) || strlen($data['username']) < 3) {
                $errors['username'] = 'El nombre de usuario debe tener al menos 3 caracteres';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
            }
        }

        // Validar nombre
        if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        // Validar contraseña (solo para creación o cambio específico)
        if (!$isUpdate && (empty($data['password']) || strlen($data['password']) < 6)) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }

        // Validar rol
        $roles_validos = ['admin', 'usuario'];
        if (empty($data['rol']) || !in_array($data['rol'], $roles_validos)) {
            $errors['rol'] = 'Rol inválido. Debe ser "admin" o "usuario"';
        }

        return $errors;
    }

    // Verificar si username ya existe
    public function usernameExists($username, $excludeId = null)
    {
        return $this->usuario->existeUsername($username, $excludeId);
    }

    // Verificar si email ya existe
    public function emailExists($email, $excludeId = null)
    {
        return $this->usuario->existeEmail($email, $excludeId);
    }

    // Obtener estadísticas de usuarios
    public function getStats()
    {
        try {
            $stats = [];

            // Total usuarios
            $query = "SELECT COUNT(*) as total FROM usuarios";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Usuarios activos
            $query = "SELECT COUNT(*) as activos FROM usuarios WHERE activo = true";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['activos'] = $stmt->fetch(PDO::FETCH_ASSOC)['activos'];

            // Usuarios por rol
            $query = "SELECT rol, COUNT(*) as cantidad FROM usuarios GROUP BY rol";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $stats['por_rol'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;
        } catch (Exception $e) {
            error_log("Error en UsuarioController::getStats(): " . $e->getMessage());
            return [
                'total' => 0,
                'activos' => 0,
                'por_rol' => []
            ];
        }
    }

    // Buscar usuario para login
    public function buscarParaLogin($username)
    {
        try {
            return $this->usuario->buscarPorUsername($username);
        } catch (Exception $e) {
            error_log("Error en UsuarioController::buscarParaLogin(): " . $e->getMessage());
            return false;
        }
    }

    // Actualizar último login
    public function actualizarUltimoLogin($id)
    {
        try {
            $this->usuario->id = $id;
            return $this->usuario->actualizarUltimoLogin();
        } catch (Exception $e) {
            error_log("Error en UsuarioController::actualizarUltimoLogin(): " . $e->getMessage());
            return false;
        }
    }
}
?>