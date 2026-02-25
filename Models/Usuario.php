<?php
// Models/Usuario.php

class Usuario
{
    private $conn;
    private $table = "usuarios";

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

    // Crear nuevo usuario
    public function crear()
    {
        try {
            $query = "INSERT INTO " . $this->table . " 
                      (username, password_hash, nombre, email, rol) 
                      VALUES 
                      (:username, :password_hash, :nombre, :email, :rol)
                      RETURNING id, username, nombre, email, rol, activo, created_at";

            $stmt = $this->conn->prepare($query);

            // Limpiar y validar datos
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->rol = htmlspecialchars(strip_tags($this->rol));

            // Hash de contraseña si existe
            if (!empty($this->password_hash)) {
                $hashedPassword = password_hash($this->password_hash, PASSWORD_BCRYPT);
            } else {
                throw new Exception("La contraseña es requerida");
            }

            // Vincular parámetros
            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":password_hash", $hashedPassword);
            $stmt->bindParam(":nombre", $this->nombre);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":rol", $this->rol);

            if ($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $this->id = $result['id'];
                return $result;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error en Usuario::crear(): " . $e->getMessage());
            return false;
        }
    }

    // Leer todos los usuarios
    public function leer()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE activo = TRUE 
                  ORDER BY id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Leer un usuario por ID
    public function leerUno()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id = :id 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // No retornar el hash de la contraseña por seguridad
            unset($row['password_hash']);
            
            return $row;
        }

        return false;
    }

    // Actualizar usuario
    public function actualizar()
    {
        $query = "UPDATE " . $this->table . " 
                  SET nombre = :nombre,
                      email = :email,
                      rol = :rol,
                      activo = :activo,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->rol = htmlspecialchars(strip_tags($this->rol));

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Cambiar contraseña
    public function cambiarPassword($nueva_password)
    {
        try {
            $hashedPassword = password_hash($nueva_password, PASSWORD_BCRYPT);

            $query = "UPDATE " . $this->table . " 
                      SET password_hash = :password_hash,
                          updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password_hash", $hashedPassword);
            $stmt->bindParam(":id", $this->id);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error en Usuario::cambiarPassword(): " . $e->getMessage());
            return false;
        }
    }

    // "Eliminar" (desactivar) usuario
    public function eliminar()
    {
        $query = "UPDATE " . $this->table . " 
                  SET activo = FALSE,
                      updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Buscar usuario por username (para login)
    public function buscarPorUsername($username)
    {
        $query = "SELECT id, username, password_hash, nombre, email, rol, activo 
                  FROM " . $this->table . " 
                  WHERE username = :username 
                  AND activo = TRUE
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return false;
    }

    // Actualizar último login
    public function actualizarUltimoLogin()
    {
        $query = "UPDATE " . $this->table . " 
                  SET ultimo_login = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Verificar si username existe
    public function existeUsername($username, $excluir_id = null)
    {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE username = :username";

        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);

        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Verificar si email existe
    public function existeEmail($email, $excluir_id = null)
    {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE email = :email";

        if ($excluir_id) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);

        if ($excluir_id) {
            $stmt->bindParam(":excluir_id", $excluir_id);
        }

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
}
?>