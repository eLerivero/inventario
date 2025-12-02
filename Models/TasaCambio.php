<?php
class TasaCambio
{
    private $conn;
    private $table = "tasas_cambio";

    public $id;
    public $moneda_origen;
    public $moneda_destino;
    public $tasa_cambio;
    public $fecha_actualizacion;
    public $activa;
    public $usuario_id;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerTasaActual()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE activa = TRUE 
                  ORDER BY fecha_actualizacion DESC 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function crear($tasa, $usuario = 1)
    {
        // Primero desactivar todas las tasas anteriores
        $query_desactivar = "UPDATE " . $this->table . " SET activa = FALSE WHERE activa = TRUE";
        $stmt_desactivar = $this->conn->prepare($query_desactivar);
        $stmt_desactivar->execute();

        // Crear nueva tasa activa
        $query = "INSERT INTO " . $this->table . " 
                  (moneda_origen, moneda_destino, tasa_cambio, usuario_id) 
                  VALUES 
                  ('USD', 'VES', :tasa, :usuario)
                  RETURNING *";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tasa", $tasa);
        $stmt->bindParam(":usuario", $usuario);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function obtenerHistorial($limite = 30)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  ORDER BY fecha_actualizacion DESC 
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function leer()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  ORDER BY fecha_actualizacion DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}