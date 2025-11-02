<?php
class TipoPago
{
    private $conn;
    private $table = "tipos_pago";

    public $id;
    public $nombre;
    public $descripcion;
    public $activo;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerTodos()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Asignar propiedades
            $this->id = $row['id'];
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];
            $this->activo = $row['activo'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'] ?? null;
            
            return $row;
        }
        return false;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                 SET nombre = :nombre, 
                     descripcion = :descripcion, 
                     activo = true, 
                     created_at = NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function actualizar($id)
    {
        $query = "UPDATE " . $this->table . " 
                 SET nombre = :nombre, 
                     descripcion = :descripcion, 
                     activo = :activo,
                     updated_at = NOW()
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":activo", $this->activo);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function verificarNombreExistente($nombre, $excluirId = null)
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE nombre = :nombre";
        
        if ($excluirId) {
            $query .= " AND id != :excluir_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        
        if ($excluirId) {
            $stmt->bindParam(":excluir_id", $excluirId);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] > 0;
    }

    public function buscar($termino)
    {
        $query = "SELECT * FROM " . $this->table . " 
                 WHERE (nombre LIKE :termino OR descripcion LIKE :termino) 
                 ORDER BY nombre";
        
        $stmt = $this->conn->prepare($query);
        $termino = "%$termino%";
        $stmt->bindParam(":termino", $termino);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActivos()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function contarTotal()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function contarActivos()
    {
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE activo = true";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}