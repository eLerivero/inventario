<?php
class PagoVenta
{
    private $conn;
    private $table = "pagos_venta";

    public $id;
    public $venta_id;
    public $tipo_pago_id;
    public $monto_usd;
    public $monto_bs;
    public $fecha_pago;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Crea un nuevo registro de pago
     */
    public function crear($data)
    {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (venta_id, tipo_pago_id, monto_usd, monto_bs, fecha_pago) 
                     VALUES 
                     (:venta_id, :tipo_pago_id, :monto_usd, :monto_bs, NOW())
                     RETURNING id";

            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":venta_id", $data['venta_id']);
            $stmt->bindParam(":tipo_pago_id", $data['tipo_pago_id']);
            $stmt->bindParam(":monto_usd", $data['monto_usd']);
            $stmt->bindParam(":monto_bs", $data['monto_bs']);

            if ($stmt->execute()) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result['id'];
            }

            error_log("Error en PagoVenta::crear: " . print_r($stmt->errorInfo(), true));
            return false;
        } catch (Exception $e) {
            error_log("Excepción en PagoVenta::crear: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los pagos de una venta (incluyendo información del tipo de pago)
     */
    public function obtenerPorVenta($venta_id)
    {
        try {
            $query = "SELECT pv.*, tp.nombre as tipo_pago_nombre, 
                             tp.descripcion as tipo_pago_descripcion,
                             CASE 
                                 WHEN tp.id IN (2, 7) THEN 'USD'
                                 WHEN tp.id = 1 THEN 'BS'
                                 ELSE 'MIXTO'
                             END as moneda_principal
                      FROM " . $this->table . " pv
                      JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                      WHERE pv.venta_id = :venta_id
                      ORDER BY pv.fecha_pago ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":venta_id", $venta_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en PagoVenta::obtenerPorVenta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene SOLO los pagos completados (con montos > 0) de una venta
     */
    public function obtenerCompletadosPorVenta($venta_id)
    {
        try {
            $query = "SELECT pv.*, tp.nombre as tipo_pago_nombre, 
                             tp.descripcion as tipo_pago_descripcion,
                             CASE 
                                 WHEN tp.id IN (2, 7) THEN 'USD'
                                 WHEN tp.id = 1 THEN 'BS'
                                 ELSE 'MIXTO'
                             END as moneda_principal
                      FROM " . $this->table . " pv
                      JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                      WHERE pv.venta_id = :venta_id
                      AND (pv.monto_usd > 0 OR pv.monto_bs > 0)
                      ORDER BY pv.fecha_pago ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":venta_id", $venta_id);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en PagoVenta::obtenerCompletadosPorVenta: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene resumen de pagos de una venta (mejorado)
     */
    public function obtenerResumenPorVenta($venta_id)
    {
        try {
            $query = "SELECT 
                        COUNT(*) as cantidad_pagos,
                        SUM(monto_usd) as total_pagado_usd,
                        SUM(monto_bs) as total_pagado_bs,
                        MAX(fecha_pago) as ultimo_pago,
                        SUM(CASE WHEN tipo_pago_id IN (2, 7) THEN monto_usd ELSE 0 END) as total_usd_recibido,
                        SUM(CASE WHEN tipo_pago_id = 1 THEN monto_bs ELSE 0 END) as total_bs_recibido,
                        COUNT(DISTINCT tipo_pago_id) as metodos_distintos
                      FROM " . $this->table . "
                      WHERE venta_id = :venta_id
                      AND (monto_usd > 0 OR monto_bs > 0)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":venta_id", $venta_id);
            $stmt->execute();

            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resultado || $resultado['cantidad_pagos'] == 0) {
                return [
                    'cantidad_pagos' => 0,
                    'total_pagado_usd' => 0,
                    'total_pagado_bs' => 0,
                    'total_usd_recibido' => 0,
                    'total_bs_recibido' => 0,
                    'metodos_distintos' => 0,
                    'ultimo_pago' => null
                ];
            }

            return $resultado;
        } catch (Exception $e) {
            error_log("Error en PagoVenta::obtenerResumenPorVenta: " . $e->getMessage());
            return [
                'cantidad_pagos' => 0,
                'total_pagado_usd' => 0,
                'total_pagado_bs' => 0,
                'total_usd_recibido' => 0,
                'total_bs_recibido' => 0,
                'metodos_distintos' => 0,
                'ultimo_pago' => null
            ];
        }
    }

    /**
     * Obtiene todos los pagos del día
     */
    public function obtenerPagosDelDia($fecha = null)
    {
        try {
            if ($fecha === null) {
                $fecha = date('Y-m-d');
            }

            $query = "SELECT pv.*, tp.nombre as tipo_pago_nombre, 
                             v.numero_venta, c.nombre as cliente_nombre
                      FROM " . $this->table . " pv
                      JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                      JOIN ventas v ON pv.venta_id = v.id
                      JOIN clientes c ON v.cliente_id = c.id
                      WHERE DATE(pv.fecha_pago) = :fecha
                      ORDER BY pv.fecha_pago DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":fecha", $fecha);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en PagoVenta::obtenerPagosDelDia: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene totales de pagos por tipo en un rango de fechas
     */
    public function obtenerTotalesPorTipo($fecha_inicio, $fecha_fin)
    {
        try {
            $query = "SELECT 
                        tp.id,
                        tp.nombre as tipo_pago,
                        COUNT(pv.id) as cantidad_pagos,
                        SUM(pv.monto_usd) as total_usd,
                        SUM(pv.monto_bs) as total_bs
                      FROM " . $this->table . " pv
                      JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                      JOIN ventas v ON pv.venta_id = v.id
                      WHERE DATE(v.fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                      AND v.estado = 'completada'
                      GROUP BY tp.id, tp.nombre
                      ORDER BY total_usd DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":fecha_inicio", $fecha_inicio);
            $stmt->bindParam(":fecha_fin", $fecha_fin);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en PagoVenta::obtenerTotalesPorTipo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Elimina todos los pagos de una venta (útil para reversiones)
     */
    public function eliminarPorVenta($venta_id)
    {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE venta_id = :venta_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":venta_id", $venta_id);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error en PagoVenta::eliminarPorVenta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validar que los pagos no excedan el total de la venta
     */
    public function validarPagosNoExcedenTotal($pagos, $total_usd)
    {
        $total_pagado = 0;
        foreach ($pagos as $pago) {
            $total_pagado += floatval($pago['monto_usd']);
        }

        if ($total_pagado > $total_usd + 0.01) { // Tolerancia de 1 centavo
            return [
                'valido' => false,
                'mensaje' => "El total pagado ($" . number_format($total_pagado, 2) .
                    ") excede el total de la venta ($" . number_format($total_usd, 2) . ")"
            ];
        }

        return ['valido' => true];
    }
}
