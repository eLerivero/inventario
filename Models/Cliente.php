<?php
require_once __DIR__ . '/../Utils/Ayuda.php';

class Cliente
{
    private $conn;
    private $table = "clientes";

    public $id;
    public $nombre;
    public $email;
    public $telefono;
    public $direccion;
    public $documento_identidad;
    public $activo;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT c.*, 
                         COUNT(v.id) as total_compras,
                         COALESCE(SUM(v.total), 0) as monto_total_compras
                  FROM " . $this->table . " c
                  LEFT JOIN ventas v ON c.id = v.cliente_id AND v.estado = 'completada'
                  GROUP BY c.id
                  ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->telefono = $row['telefono'];
            $this->direccion = $row['direccion'];
            $this->documento_identidad = $row['documento_identidad'] ?? '';
            $this->activo = $row['activo'] ?? true;
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'] ?? $row['created_at'];
            
            return $row;
        }
        return false;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (nombre, email, telefono, direccion, documento_identidad, activo) 
                  VALUES 
                  (:nombre, :email, :telefono, :direccion, :documento_identidad, :activo)
                  RETURNING id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar inputs
        $this->nombre = Ayuda::sanitizeInput($this->nombre);
        $this->email = Ayuda::sanitizeInput($this->email);
        $this->telefono = Ayuda::sanitizeInput($this->telefono);
        $this->direccion = Ayuda::sanitizeInput($this->direccion);
        $this->documento_identidad = Ayuda::sanitizeInput($this->documento_identidad);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":documento_identidad", $this->documento_identidad);
        $stmt->bindParam(":activo", $this->activo, PDO::PARAM_BOOL);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        }
        return false;
    }

    public function actualizar($id)
    {
        $query = "UPDATE " . $this->table . " 
                  SET nombre = :nombre, 
                      email = :email, 
                      telefono = :telefono, 
                      direccion = :direccion,
                      documento_identidad = :documento_identidad,
                      activo = :activo,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitizar inputs
        $this->nombre = Ayuda::sanitizeInput($this->nombre);
        $this->email = Ayuda::sanitizeInput($this->email);
        $this->telefono = Ayuda::sanitizeInput($this->telefono);
        $this->direccion = Ayuda::sanitizeInput($this->direccion);
        $this->documento_identidad = Ayuda::sanitizeInput($this->documento_identidad);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);
        $stmt->bindParam(":documento_identidad", $this->documento_identidad);
        $stmt->bindParam(":activo", $this->activo, PDO::PARAM_BOOL);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function obtenerTodos()
    {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function buscar($searchTerm)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE nombre ILIKE :search 
                     OR email ILIKE :search 
                     OR telefono ILIKE :search
                     OR documento_identidad ILIKE :search
                  ORDER BY nombre";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $searchTerm . "%";
        $stmt->bindParam(":search", $searchTerm);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerClientesActivos()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerEstadisticas()
    {
        $query = "SELECT 
                    COUNT(*) as total_clientes,
                    COUNT(CASE WHEN activo = true THEN 1 END) as clientes_activos,
                    COUNT(CASE WHEN activo = false THEN 1 END) as clientes_inactivos,
                    COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as clientes_con_email,
                    COUNT(CASE WHEN telefono IS NOT NULL AND telefono != '' THEN 1 END) as clientes_con_telefono
                  FROM " . $this->table;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerClientesTop($limite = 10)
    {
        $query = "SELECT c.*, 
                         COUNT(v.id) as total_compras,
                         COALESCE(SUM(v.total), 0) as monto_total,
                         MAX(v.created_at) as ultima_compra
                  FROM " . $this->table . " c
                  LEFT JOIN ventas v ON c.id = v.cliente_id AND v.estado = 'completada'
                  GROUP BY c.id
                  ORDER BY monto_total DESC
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}