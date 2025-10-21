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

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerTodos()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
