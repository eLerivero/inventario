<?php
// Models/Usuario.php

class Usuario
{
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password_hash;
    public $nombre;
    public $email;
    public $rol;
    public $activo;
    public $ultimo_login;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Buscar usuario por username
    public function buscarPorUsername($username)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username AND activo = true LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Buscar usuario por email
    public function buscarPorEmail($email)
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND activo = true LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Verificar contraseña
    public function verificarPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    // Actualizar último login
    public function actualizarUltimoLogin($id)
    {
        $query = "UPDATE " . $this->table_name . " SET ultimo_login = CURRENT_TIMESTAMP WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Crear nuevo usuario
    public function crear()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password_hash, nombre, email, rol) 
                  VALUES (:username, :password_hash, :nombre, :email, :rol)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password_hash = password_hash($this->password_hash, PASSWORD_BCRYPT);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        
        return $stmt->execute();
    }

    // Obtener todos los usuarios
    public function leer()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Obtener usuario por ID
    public function leerUno()
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $this->username = $row['username'];
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->rol = $row['rol'];
            $this->activo = $row['activo'];
            $this->ultimo_login = $row['ultimo_login'];
            $this->created_at = $row['created_at'];
        }
        
        return $row;
    }

    // Actualizar usuario
    public function actualizar()
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET nombre = :nombre, email = :email, rol = :rol, activo = :activo, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Cambiar contraseña
    public function cambiarPassword($nuevaPassword)
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $nuevaPasswordHash = password_hash($nuevaPassword, PASSWORD_BCRYPT);
        
        $stmt->bindParam(":password_hash", $nuevaPasswordHash);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Eliminar usuario (desactivar)
    public function eliminar()
    {
        $query = "UPDATE " . $this->table_name . " SET activo = false WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
?>