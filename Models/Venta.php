<?php
class Venta
{
    private $conn;
    private $table = "ventas";

    public $id;
    public $numero_venta;
    public $cliente_id;
    public $total;
    public $total_bs;
    public $tasa_cambio_utilizada;
    public $tipo_pago_id;
    public $estado;
    public $fecha_hora;
    public $observaciones;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  ORDER BY v.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (cliente_id, total, total_bs, tasa_cambio, tipo_pago_id, estado, fecha_hora, observaciones) 
                  VALUES 
                  (:cliente_id, :total, :total_bs, :tasa_cambio, :tipo_pago_id, :estado, :fecha_hora, :observaciones)
                  RETURNING id, numero_venta";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":cliente_id", $data['cliente_id']);
        $stmt->bindParam(":total", $data['total']);
        $stmt->bindParam(":total_bs", $data['total_bs']);
        $stmt->bindParam(":tasa_cambio", $data['tasa_cambio_utilizada']);
        $stmt->bindParam(":tipo_pago_id", $data['tipo_pago_id']);
        $stmt->bindParam(":estado", $data['estado']);
        $stmt->bindParam(":fecha_hora", $data['fecha_hora']);
        $stmt->bindParam(":observaciones", $data['observaciones']);

        if ($stmt->execute()) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function crearDetalles($detalles)
    {
        if (empty($detalles)) {
            return false;
        }

        $query = "INSERT INTO detalle_ventas 
                  (venta_id, producto_id, cantidad, precio_unitario, precio_unitario_bs, subtotal, subtotal_bs) 
                  VALUES ";

        $values = [];
        $params = [];

        foreach ($detalles as $index => $detalle) {
            $values[] = "(:venta_id_$index, :producto_id_$index, :cantidad_$index, :precio_unitario_$index, :precio_unitario_bs_$index, :subtotal_$index, :subtotal_bs_$index)";

            $params[":venta_id_$index"] = $detalle['venta_id'];
            $params[":producto_id_$index"] = $detalle['producto_id'];
            $params[":cantidad_$index"] = $detalle['cantidad'];
            $params[":precio_unitario_$index"] = $detalle['precio_unitario'];
            $params[":precio_unitario_bs_$index"] = $detalle['precio_unitario_bs'];
            $params[":subtotal_$index"] = $detalle['subtotal'];
            $params[":subtotal_bs_$index"] = $detalle['subtotal_bs'];
        }

        $query .= implode(', ', $values);
        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    public function obtenerPorId($id)
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, c.email as cliente_email, 
                    c.telefono as cliente_telefono, tp.nombre as tipo_pago_nombre
                FROM " . $this->table . " v
                LEFT JOIN clientes c ON v.cliente_id = c.id
                LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                WHERE v.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function obtenerDetalles($venta_id)
    {
        $query = "SELECT dv.*, p.nombre as producto_nombre, p.codigo_sku
                  FROM detalle_ventas dv
                  JOIN productos p ON dv.producto_id = p.id
                  WHERE dv.venta_id = :venta_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":venta_id", $venta_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerEstadisticas()
    {
        $query = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(total) as ingresos_totales,
                    AVG(total) as ticket_promedio,
                    COUNT(DISTINCT cliente_id) as clientes_activos
                  FROM ventas 
                  WHERE estado = 'completada' 
                  AND fecha_hora >= CURRENT_DATE - INTERVAL '30 days'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerVentasPorMes()
    {
        $query = "SELECT 
                    TO_CHAR(fecha_hora, 'YYYY-MM') as mes,
                    COUNT(*) as total_ventas,
                    SUM(total) as ingresos
                  FROM ventas 
                  WHERE estado = 'completada'
                  AND fecha_hora >= CURRENT_DATE - INTERVAL '12 months'
                  GROUP BY TO_CHAR(fecha_hora, 'YYYY-MM')
                  ORDER BY mes DESC
                  LIMIT 12";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
