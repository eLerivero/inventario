<?php
// Models/PagoVenta.php
class PagoVenta
{
    private $conn;
    private $table = "pagos_venta";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Crea un nuevo registro de pago para una venta.
     */
    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . "
                  (venta_id, tipo_pago_id, monto_usd, monto_bs)
                  VALUES
                  (:venta_id, :tipo_pago_id, :monto_usd, :monto_bs)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":venta_id", $data['venta_id']);
        $stmt->bindParam(":tipo_pago_id", $data['tipo_pago_id']);
        $stmt->bindParam(":monto_usd", $data['monto_usd']);
        $stmt->bindParam(":monto_bs", $data['monto_bs']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }

        error_log("Error en PagoVenta->crear(): " . print_r($stmt->errorInfo(), true));
        return false;
    }

    /**
     * Obtiene TODOS los pagos de una venta específica.
     */
    public function obtenerPorVenta($venta_id)
    {
        $query = "SELECT pv.*, tp.nombre as tipo_pago_nombre
                  FROM " . $this->table . " pv
                  JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                  WHERE pv.venta_id = :venta_id
                  ORDER BY pv.fecha_pago ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":venta_id", $venta_id);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Obtiene un resumen de pagos de una venta (totales y cantidad)
     */
    public function obtenerResumenPorVenta($venta_id)
    {
        $query = "SELECT
                    COALESCE(SUM(monto_usd), 0) as total_pagado_usd,
                    COALESCE(SUM(monto_bs), 0) as total_pagado_bs,
                    COUNT(*) as cantidad_pagos,
                    COUNT(DISTINCT tipo_pago_id) as metodos_distintos
                  FROM " . $this->table . "
                  WHERE venta_id = :venta_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":venta_id", $venta_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el total de pagos agrupados por tipo de pago para un conjunto de ventas
     */
    public function obtenerTotalesPorTipoPago($ventas_ids = [])
    {
        $resultado = [
            'totales_usd' => [],
            'totales_bs' => []
        ];

        if (empty($ventas_ids)) {
            return $resultado;
        }

        $placeholders = implode(',', array_fill(0, count($ventas_ids), '?'));

        $query = "SELECT
                    tp.id as tipo_pago_id,
                    tp.nombre as tipo_pago_nombre,
                    SUM(pv.monto_usd) as total_usd,
                    SUM(pv.monto_bs) as total_bs
                  FROM pagos_venta pv
                  JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                  WHERE pv.venta_id IN ($placeholders)
                  GROUP BY tp.id, tp.nombre
                  ORDER BY tp.nombre";

        $stmt = $this->conn->prepare($query);
        foreach ($ventas_ids as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }

        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Normalizar nombre para usar como clave
            $nombre_normalizado = strtolower(str_replace(
                [' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'],
                ['_', 'a', 'e', 'i', 'o', 'u', 'n'],
                $row['tipo_pago_nombre']
            ));
            
            $resultado['totales_usd'][$nombre_normalizado] = floatval($row['total_usd']);
            $resultado['totales_bs'][$nombre_normalizado] = floatval($row['total_bs']);
        }

        return $resultado;
    }
}