<?php
class Cliente
{
    private $conn;
    private $table = "clientes";

    public $id;
    public $nombre;
    public $email;
    public $telefono;
    public $direccion;
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

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (nombre, email, telefono, direccion) 
                  VALUES 
                  (:nombre, :email, :telefono, :direccion)
                  RETURNING id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = Ayuda::sanitizeInput($this->nombre);
        $this->email = Ayuda::sanitizeInput($this->email);
        $this->telefono = Ayuda::sanitizeInput($this->telefono);
        $this->direccion = Ayuda::sanitizeInput($this->direccion);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":telefono", $this->telefono);
        $stmt->bindParam(":direccion", $this->direccion);

        if ($stmt->execute()) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        }
        return false;
    }

    public function obtenerTodos()
    {
        $query = "SELECT * FROM " . $this->table . " ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
