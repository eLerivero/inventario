<?php
// Models/HistorialStock.php

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
    public $referencia_tabla;
    public $observaciones;
    public $usuario_id;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT 
                    hs.id,
                    hs.producto_id,
                    hs.cantidad_anterior,
                    hs.cantidad_nueva,
                    hs.diferencia,
                    hs.tipo_movimiento,
                    hs.referencia_id,
                    hs.referencia_tabla as tipo_referencia,
                    hs.observaciones,
                    hs.usuario_id,
                    hs.created_at as fecha_hora,
                    p.nombre as producto_nombre,
                    p.codigo_sku,
                    u.nombre as usuario_nombre
                  FROM " . $this->table . " hs
                  LEFT JOIN productos p ON hs.producto_id = p.id
                  LEFT JOIN usuarios u ON hs.usuario_id = u.id
                  ORDER BY hs.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorProducto($producto_id)
    {
        $query = "SELECT 
                    hs.id,
                    hs.producto_id,
                    hs.cantidad_anterior,
                    hs.cantidad_nueva,
                    hs.diferencia,
                    hs.tipo_movimiento,
                    hs.referencia_id,
                    hs.referencia_tabla as tipo_referencia,
                    hs.observaciones,
                    hs.usuario_id,
                    hs.created_at as fecha_hora,
                    u.nombre as usuario_nombre
                  FROM " . $this->table . " hs
                  LEFT JOIN usuarios u ON hs.usuario_id = u.id
                  WHERE hs.producto_id = :producto_id
                  ORDER BY hs.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":producto_id", $producto_id);
        $stmt->execute();
        return $stmt;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (producto_id, cantidad_anterior, cantidad_nueva, diferencia, 
                   tipo_movimiento, referencia_id, referencia_tabla, observaciones, usuario_id) 
                  VALUES 
                  (:producto_id, :cantidad_anterior, :cantidad_nueva, :diferencia,
                   :tipo_movimiento, :referencia_id, :referencia_tabla, :observaciones, :usuario_id)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":producto_id", $this->producto_id);
        $stmt->bindParam(":cantidad_anterior", $this->cantidad_anterior);
        $stmt->bindParam(":cantidad_nueva", $this->cantidad_nueva);
        $stmt->bindParam(":diferencia", $this->diferencia);
        $stmt->bindParam(":tipo_movimiento", $this->tipo_movimiento);
        $stmt->bindParam(":referencia_id", $this->referencia_id);
        $stmt->bindParam(":referencia_tabla", $this->referencia_tabla);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":usuario_id", $this->usuario_id);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function obtenerMovimientosPorFecha($fecha_inicio, $fecha_fin)
    {
        $query = "SELECT 
                    hs.id,
                    hs.producto_id,
                    hs.cantidad_anterior,
                    hs.cantidad_nueva,
                    hs.diferencia,
                    hs.tipo_movimiento,
                    hs.referencia_id,
                    hs.referencia_tabla as tipo_referencia,
                    hs.observaciones,
                    hs.usuario_id,
                    hs.created_at as fecha_hora,
                    p.nombre as producto_nombre,
                    p.codigo_sku,
                    u.nombre as usuario_nombre
                  FROM " . $this->table . " hs
                  LEFT JOIN productos p ON hs.producto_id = p.id
                  LEFT JOIN usuarios u ON hs.usuario_id = u.id
                  WHERE DATE(hs.created_at) BETWEEN :fecha_inicio AND :fecha_fin
                  ORDER BY hs.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        $stmt->bindParam(":fecha_fin", $fecha_fin);
        $stmt->execute();

        $movimientos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $movimientos[] = $row;
        }

        return $movimientos;
    }

    public function obtenerResumenMovimientos($fecha_inicio = null, $fecha_fin = null)
    {
        $query = "SELECT 
                    tipo_movimiento,
                    COUNT(*) as total,
                    SUM(diferencia) as total_diferencia
                  FROM " . $this->table;

        $conditions = [];
        if ($fecha_inicio) {
            $conditions[] = "DATE(created_at) >= :fecha_inicio";
        }
        if ($fecha_fin) {
            $conditions[] = "DATE(created_at) <= :fecha_fin";
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY tipo_movimiento ORDER BY total DESC";

        $stmt = $this->conn->prepare($query);

        if ($fecha_inicio) {
            $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        }
        if ($fecha_fin) {
            $stmt->bindParam(":fecha_fin", $fecha_fin);
        }

        $stmt->execute();

        $resumen = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resumen[] = $row;
        }

        return $resumen;
    }

    public function obtenerMovimientosRecientes($limite = 10)
    {
        $query = "SELECT 
                    hs.id,
                    hs.producto_id,
                    hs.cantidad_anterior,
                    hs.cantidad_nueva,
                    hs.diferencia,
                    hs.tipo_movimiento,
                    hs.referencia_id,
                    hs.referencia_tabla as tipo_referencia,
                    hs.observaciones,
                    hs.usuario_id,
                    hs.created_at as fecha_hora,
                    p.nombre as producto_nombre,
                    p.codigo_sku,
                    u.nombre as usuario_nombre
                  FROM " . $this->table . " hs
                  LEFT JOIN productos p ON hs.producto_id = p.id
                  LEFT JOIN usuarios u ON hs.usuario_id = u.id
                  ORDER BY hs.created_at DESC
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();

        $movimientos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $movimientos[] = $row;
        }

        return $movimientos;
    }

    public function buscar($termino)
    {
        $query = "SELECT 
                    hs.id,
                    hs.producto_id,
                    hs.cantidad_anterior,
                    hs.cantidad_nueva,
                    hs.diferencia,
                    hs.tipo_movimiento,
                    hs.referencia_id,
                    hs.referencia_tabla as tipo_referencia,
                    hs.observaciones,
                    hs.usuario_id,
                    hs.created_at as fecha_hora,
                    p.nombre as producto_nombre,
                    p.codigo_sku,
                    u.nombre as usuario_nombre
                  FROM " . $this->table . " hs
                  LEFT JOIN productos p ON hs.producto_id = p.id
                  LEFT JOIN usuarios u ON hs.usuario_id = u.id
                  WHERE p.nombre ILIKE :termino 
                     OR p.codigo_sku ILIKE :termino
                     OR hs.observaciones ILIKE :termino
                     OR hs.tipo_movimiento ILIKE :termino
                  ORDER BY hs.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $termino . "%";
        $stmt->bindParam(":termino", $searchTerm);
        $stmt->execute();

        $resultados = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultados[] = $row;
        }

        return $resultados;
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

    public function obtenerAjustesManuales($fecha_inicio = null, $fecha_fin = null)
    {
        $query = "SELECT 
                    hs.id,
                    hs.producto_id,
                    hs.cantidad_anterior,
                    hs.cantidad_nueva,
                    hs.diferencia,
                    hs.tipo_movimiento,
                    hs.referencia_id,
                    hs.referencia_tabla as tipo_referencia,
                    hs.observaciones,
                    hs.usuario_id,
                    hs.created_at as fecha_hora,
                    p.nombre as producto_nombre,
                    p.codigo_sku,
                    u.nombre as usuario_nombre
                  FROM " . $this->table . " hs
                  LEFT JOIN productos p ON hs.producto_id = p.id
                  LEFT JOIN usuarios u ON hs.usuario_id = u.id
                  WHERE hs.referencia_tabla = 'ajuste_manual'";

        $conditions = [];
        if ($fecha_inicio) {
            $conditions[] = "DATE(hs.created_at) >= :fecha_inicio";
        }
        if ($fecha_fin) {
            $conditions[] = "DATE(hs.created_at) <= :fecha_fin";
        }

        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY hs.created_at DESC";

        $stmt = $this->conn->prepare($query);

        if ($fecha_inicio) {
            $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        }
        if ($fecha_fin) {
            $stmt->bindParam(":fecha_fin", $fecha_fin);
        }

        $stmt->execute();

        $ajustes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ajustes[] = $row;
        }

        return $ajustes;
    }
}
