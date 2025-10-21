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
                  (producto_id, cantidad_anterior, cantidad_nueva, diferencia, 
                   tipo_movimiento, referencia_id, tipo_referencia, observaciones, usuario, fecha_hora) 
                  VALUES 
                  (:producto_id, :cantidad_anterior, :cantidad_nueva, :diferencia, 
                   :tipo_movimiento, :referencia_id, :tipo_referencia, :observaciones, :usuario, :fecha_hora)
                  RETURNING id";

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
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['id'];
        }
        return false;
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
        return $stmt->fetchAll();
    }

    public function obtenerResumenMovimientos($fecha_inicio = null, $fecha_fin = null)
    {
        $where = "";
        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $where = "WHERE hs.fecha_hora BETWEEN :fecha_inicio AND :fecha_fin";
            $params[':fecha_inicio'] = $fecha_inicio;
            $params[':fecha_fin'] = $fecha_fin;
        }

        $query = "SELECT 
                    hs.tipo_movimiento,
                    COUNT(*) as total_movimientos,
                    SUM(CASE WHEN hs.diferencia > 0 THEN hs.diferencia ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN hs.diferencia < 0 THEN ABS(hs.diferencia) ELSE 0 END) as total_salidas
                  FROM " . $this->table . " hs
                  $where
                  GROUP BY hs.tipo_movimiento
                  ORDER BY total_movimientos DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll();
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
        return $stmt->fetchAll();
    }

    public function buscar($search)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  WHERE p.nombre ILIKE :search 
                     OR p.codigo_sku ILIKE :search
                     OR hs.tipo_movimiento ILIKE :search
                     OR hs.observaciones ILIKE :search
                  ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $search . "%";
        $stmt->bindParam(":search", $searchTerm);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerMovimientosPorTipo($tipo_movimiento, $limite = 50)
    {
        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  WHERE hs.tipo_movimiento = :tipo_movimiento
                  ORDER BY hs.fecha_hora DESC
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tipo_movimiento", $tipo_movimiento);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerAjustesManuales($fecha_inicio = null, $fecha_fin = null)
    {
        $where = "WHERE hs.tipo_referencia = 'ajuste_manual'";
        $params = [];

        if ($fecha_inicio && $fecha_fin) {
            $where .= " AND hs.fecha_hora BETWEEN :fecha_inicio AND :fecha_fin";
            $params[':fecha_inicio'] = $fecha_inicio;
            $params[':fecha_fin'] = $fecha_fin;
        }

        $query = "SELECT hs.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM " . $this->table . " hs
                  JOIN productos p ON hs.producto_id = p.id
                  $where
                  ORDER BY hs.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
}
