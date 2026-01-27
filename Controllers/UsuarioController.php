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
        $stmt = $this->usuario->leer();
        $usuarios = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $usuarios[] = $row;
        }
        
        return $usuarios;
    }

    // Obtener usuario por ID
    public function show($id)
    {
        $this->usuario->id = $id;
        return $this->usuario->leerUno();
    }

    // Crear nuevo usuario
    public function store($data)
    {
        $this->usuario->username = $data['username'];
        $this->usuario->password_hash = $data['password']; // Se hasheará en el modelo
        $this->usuario->nombre = $data['nombre'];
        $this->usuario->email = $data['email'];
        $this->usuario->rol = $data['rol'] ?? 'usuario';
        
        if ($this->usuario->crear()) {
            return ['success' => true, 'message' => 'Usuario creado exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al crear usuario'];
    }

    // Actualizar usuario
    public function update($id, $data)
    {
        $this->usuario->id = $id;
        $this->usuario->nombre = $data['nombre'];
        $this->usuario->email = $data['email'];
        $this->usuario->rol = $data['rol'];
        $this->usuario->activo = $data['activo'] ?? true;
        
        if ($this->usuario->actualizar()) {
            return ['success' => true, 'message' => 'Usuario actualizado exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al actualizar usuario'];
    }

    // Cambiar contraseña
    public function cambiarPassword($id, $password)
    {
        $this->usuario->id = $id;
        
        if ($this->usuario->cambiarPassword($password)) {
            return ['success' => true, 'message' => 'Contraseña cambiada exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al cambiar contraseña'];
    }

    // Eliminar/desactivar usuario
    public function destroy($id)
    {
        $this->usuario->id = $id;
        
        if ($this->usuario->eliminar()) {
            return ['success' => true, 'message' => 'Usuario desactivado exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al desactivar usuario'];
    }

    // Activar usuario
    public function activate($id)
    {
        $query = "UPDATE usuarios SET activo = true WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Usuario activado exitosamente'];
        }
        
        return ['success' => false, 'message' => 'Error al activar usuario'];
    }

    // Validar datos del usuario
    public function validate($data, $isUpdate = false)
    {
        $errors = [];

        // Validar username
        if (!$isUpdate && (empty($data['username']) || strlen($data['username']) < 3)) {
            $errors['username'] = 'El nombre de usuario debe tener al menos 3 caracteres';
        }

        // Validar nombre
        if (empty($data['nombre']) || strlen($data['nombre']) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email inválido';
        }

        // Validar contraseña (solo para creación o cambio)
        if (!$isUpdate && (empty($data['password']) || strlen($data['password']) < 6)) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }

        // Validar rol
        $roles_validos = ['admin', 'usuario'];
        if (empty($data['rol']) || !in_array($data['rol'], $roles_validos)) {
            $errors['rol'] = 'Rol inválido';
        }

        return $errors;
    }

    // Verificar si username ya existe
    public function usernameExists($username, $excludeId = null)
    {
        $query = "SELECT id FROM usuarios WHERE username = :username";
        if ($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":username", $username);
        
        if ($excludeId) {
            $stmt->bindParam(":id", $excludeId);
        }
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Verificar si email ya existe
    public function emailExists($email, $excludeId = null)
    {
        $query = "SELECT id FROM usuarios WHERE email = :email";
        if ($excludeId) {
            $query .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":email", $email);
        
        if ($excludeId) {
            $stmt->bindParam(":id", $excludeId);
        }
        
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Obtener estadísticas de usuarios
    public function getStats()
    {
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
    }
}
?>