<?php
class Categoria
{
    private $conn;
    private $table = "categorias";

    public $id;
    public $nombre;
    public $descripcion;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT c.*, COUNT(p.id) as total_productos
                  FROM " . $this->table . " c
                  LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = true
                  GROUP BY c.id
                  ORDER BY c.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (nombre, descripcion) 
                  VALUES 
                  (:nombre, :descripcion)
                  RETURNING id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = Ayuda::sanitizeInput($this->nombre);
        $this->descripcion = Ayuda::sanitizeInput($this->descripcion);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        }
        return false;
    }

    public function obtenerTodas()
    {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
