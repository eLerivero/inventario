<?php
class HistorialStock
{
    private $conn;
    private $table = "historial_stock";

    public $id;
    public $producto_id;
    public $cantidad_anterior;
    public $cantidad_nueva;
    public $diferencia;
    public $tipo_movimiento;
    public $referencia_id;
    public $tipo_referencia;
    public $observaciones;
    public $usuario;
    public $fecha_hora;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorProducto($producto_id)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  WHERE hs.producto_id = :producto_id
                  ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":producto_id", $producto_id);
        $stmt->execute();
        return $stmt;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                  SET producto_id = :producto_id,
                      cantidad_anterior = :cantidad_anterior,
                      cantidad_nueva = :cantidad_nueva,
                      diferencia = :diferencia,
                      tipo_movimiento = :tipo_movimiento,
                      referencia_id = :referencia_id,
                      tipo_referencia = :tipo_referencia,
                      observaciones = :observaciones,
                      usuario = :usuario,
                      fecha_hora = :fecha_hora,
                      created_at = NOW()";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":producto_id", $this->producto_id);
        $stmt->bindParam(":cantidad_anterior", $this->cantidad_anterior);
        $stmt->bindParam(":cantidad_nueva", $this->cantidad_nueva);
        $stmt->bindParam(":diferencia", $this->diferencia);
        $stmt->bindParam(":tipo_movimiento", $this->tipo_movimiento);
        $stmt->bindParam(":referencia_id", $this->referencia_id);
        $stmt->bindParam(":tipo_referencia", $this->tipo_referencia);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":usuario", $this->usuario);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    public function obtenerMovimientosPorFecha($fecha_inicio, $fecha_fin)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  WHERE hs.fecha_hora BETWEEN :fecha_inicio AND :fecha_fin
                  ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        $stmt->bindParam(":fecha_fin", $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerResumenMovimientos($fecha_inicio = null, $fecha_fin = null)
    {
        $query = "SELECT 
                    tipo_movimiento,
                    COUNT(*) as total_movimientos,
                    SUM(CASE WHEN diferencia > 0 THEN diferencia ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN diferencia < 0 THEN ABS(diferencia) ELSE 0 END) as total_salidas
                  FROM " . $this->table;

        $params = [];
        
        if ($fecha_inicio && $fecha_fin) {
            $query .= " WHERE fecha_hora BETWEEN :fecha_inicio AND :fecha_fin";
            $params[':fecha_inicio'] = $fecha_inicio;
            $params[':fecha_fin'] = $fecha_fin;
        }

        $query .= " GROUP BY tipo_movimiento ORDER BY total_movimientos DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerMovimientosRecientes($limite = 10)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  ORDER BY hs.fecha_hora DESC
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscar($search)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  WHERE p.nombre LIKE :search 
                     OR p.codigo_sku LIKE :search
                     OR hs.tipo_movimiento LIKE :search
                     OR hs.observaciones LIKE :search
                  ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $search . "%";
        $stmt->bindParam(":search", $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAjustesManuales($fecha_inicio = null, $fecha_fin = null)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  WHERE hs.tipo_referencia = 'ajuste_manual'";

        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $query .= " AND hs.fecha_hora BETWEEN :fecha_inicio AND :fecha_fin";
            $params[':fecha_inicio'] = $fecha_inicio;
            $params[':fecha_fin'] = $fecha_fin;
        }

        $query .= " ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}