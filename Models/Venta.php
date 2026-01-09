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
              (cliente_id, tipo_pago_id, subtotal, total, tasa_cambio, total_bs, estado, fecha_hora, observaciones) 
              VALUES 
              (:cliente_id, :tipo_pago_id, :subtotal, :total, :tasa_cambio, :total_bs, :estado, :fecha_hora, :observaciones)
              RETURNING id, numero_venta";

        $stmt = $this->conn->prepare($query);

        // **BINDEAR VALORES EXACTAMENTE COMO VIENEN**
        $stmt->bindParam(":cliente_id", $data['cliente_id']);
        $stmt->bindParam(":tipo_pago_id", $data['tipo_pago_id']);
        $stmt->bindParam(":subtotal", $data['subtotal']);
        $stmt->bindParam(":total", $data['total']);
        $stmt->bindParam(":tasa_cambio", $data['tasa_cambio_utilizada']);
        $stmt->bindParam(":total_bs", $data['total_bs']);  // ¡ESTO DEBE SER EL TOTAL CORRECTO!
        $stmt->bindParam(":estado", $data['estado']);
        $stmt->bindParam(":fecha_hora", $data['fecha_hora']);
        $stmt->bindParam(":observaciones", $data['observaciones']);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // **DEBUG: Verificar lo que se insertó**
            error_log("INSERTADO EN 'ventas':");
            error_log("  ID: " . $result['id']);
            error_log("  Número venta: " . $result['numero_venta']);
            error_log("  Total BS insertado: " . $data['total_bs']);

            return $result;
        }

        error_log("Error en Venta->crear(): " . print_r($stmt->errorInfo(), true));
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
            $values[] = "(:venta_id_$index, :producto_id_$index, :cantidad_$index, 
                         :precio_unitario_$index, :precio_unitario_bs_$index, 
                         :subtotal_$index, :subtotal_bs_$index)";

            // VALORES EXACTOS - ESPECIALMENTE PARA PRECIOS FIJOS
            $params[":venta_id_$index"] = intval($detalle['venta_id']);
            $params[":producto_id_$index"] = intval($detalle['producto_id']);
            $params[":cantidad_$index"] = intval($detalle['cantidad']);

            // Precios - asegurar que sean exactos
            $params[":precio_unitario_$index"] = floatval($detalle['precio_unitario']);
            $params[":precio_unitario_bs_$index"] = floatval($detalle['precio_unitario_bs']); // ¡CRÍTICO!
            $params[":subtotal_$index"] = floatval($detalle['subtotal']);
            $params[":subtotal_bs_$index"] = floatval($detalle['subtotal_bs']); // ¡CRÍTICO!
        }

        $query .= implode(', ', $values);
        $stmt = $this->conn->prepare($query);

        // Bind con precisión
        foreach ($params as $key => $value) {
            // Para valores decimales, usar PDO::PARAM_STR para mantener precisión
            if (is_float($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        if (!$stmt->execute()) {
            error_log("Error en Venta->crearDetalles(): " . print_r($stmt->errorInfo(), true));
            return false;
        }

        return true;
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
        $query = "SELECT dv.*, p.nombre as producto_nombre, p.codigo_sku,
                         p.usar_precio_fijo_bs, p.precio_bs as precio_fijo_original
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
