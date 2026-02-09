<?php
class CierreCaja
{
    private $conn;
    private $table = "cierres_caja";

    public $id;
    public $fecha;
    public $usuario_id;
    public $total_ventas;
    public $total_unidades;
    public $total_usd;
    public $total_bs;
    public $efectivo_usd;
    public $efectivo_bs_usd;
    public $transferencia_usd;
    public $pago_movil_usd;
    public $tarjeta_debito_usd;
    public $tarjeta_credito_usd;
    public $divisa_usd;
    public $credito_usd;
    public $efectivo_bs;
    public $efectivo_bs_bs;
    public $transferencia_bs;
    public $pago_movil_bs;
    public $tarjeta_debito_bs;
    public $tarjeta_credito_bs;
    public $divisa_bs;
    public $credito_bs;
    public $resumen_categorias;
    public $resumen_productos;
    public $resumen_clientes;
    public $ventas_ids;
    public $observaciones;
    public $estado;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerReporteDetalladoPorVentas($ventas_ids_array)
    {
        try {
            if (empty($ventas_ids_array)) {
                return [];
            }

            // Convertir array de IDs a string
            if (is_array($ventas_ids_array)) {
                $ventas_ids = implode(',', array_map('intval', $ventas_ids_array));
            } else if (is_string($ventas_ids_array) && strpos($ventas_ids_array, '{') === 0) {
                // Si es un string en formato PostgreSQL array, limpiarlo
                $ventas_ids = str_replace(['{', '}'], '', $ventas_ids_array);
            } else {
                $ventas_ids = $ventas_ids_array;
            }

            $query = "SELECT 
                        COALESCE(cat.nombre, 'Sin categoría') as categoria_nombre,
                        p.nombre as producto_nombre,
                        SUM(dv.cantidad) as cantidad_vendida,
                        AVG(dv.precio_unitario_bs) as precio_unitario_bs,
                        SUM(dv.subtotal_bs) as subtotal_bs,
                        AVG(dv.precio_unitario) as precio_unitario_usd,
                        SUM(dv.subtotal) as subtotal_usd,
                        tp.nombre as tipo_pago,
                        cl.nombre as cliente_nombre,
                        v.numero_venta,
                        v.fecha_hora
                      FROM detalle_ventas dv
                      JOIN ventas v ON dv.venta_id = v.id
                      JOIN productos p ON dv.producto_id = p.id
                      LEFT JOIN categorias cat ON p.categoria_id = cat.id
                      JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                      JOIN clientes cl ON v.cliente_id = cl.id
                      WHERE v.id IN ($ventas_ids)
                      AND v.estado = 'completada'
                      GROUP BY cat.nombre, p.nombre, tp.nombre, cl.nombre, v.numero_venta, v.fecha_hora
                      ORDER BY v.fecha_hora DESC, cat.nombre, SUM(dv.subtotal_bs) DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error al obtener reporte detallado por ventas: " . $e->getMessage());
            return [];
        }
    }

     public function crear($data)
    {
        // Generar número de cierre único
        $numero_cierre = $this->generarNumeroCierre();
        
        $query = "INSERT INTO " . $this->table . " 
                  (numero_cierre, fecha, usuario_id, total_ventas, total_unidades, total_usd, total_bs,
                   efectivo_usd, efectivo_bs_usd, transferencia_usd, pago_movil_usd,
                   tarjeta_debito_usd, tarjeta_credito_usd, divisa_usd, credito_usd,
                   efectivo_bs, efectivo_bs_bs, transferencia_bs, pago_movil_bs,
                   tarjeta_debito_bs, tarjeta_credito_bs, divisa_bs, credito_bs,
                   resumen_categorias, resumen_productos, resumen_clientes,
                   ventas_ids, observaciones, estado) 
                  VALUES 
                  (:numero_cierre, :fecha, :usuario_id, :total_ventas, :total_unidades, :total_usd, :total_bs,
                   :efectivo_usd, :efectivo_bs_usd, :transferencia_usd, :pago_movil_usd,
                   :tarjeta_debito_usd, :tarjeta_credito_usd, :divisa_usd, :credito_usd,
                   :efectivo_bs, :efectivo_bs_bs, :transferencia_bs, :pago_movil_bs,
                   :tarjeta_debito_bs, :tarjeta_credito_bs, :divisa_bs, :credito_bs,
                   :resumen_categorias, :resumen_productos, :resumen_clientes,
                   :ventas_ids, :observaciones, :estado)
                  RETURNING id";

        $stmt = $this->conn->prepare($query);

        // Bindear todos los parámetros - CORREGIDO
        $stmt->bindParam(":numero_cierre", $numero_cierre);
        $stmt->bindParam(":fecha", $data['fecha']);
        $stmt->bindParam(":usuario_id", $data['usuario_id']);
        $stmt->bindParam(":total_ventas", $data['total_ventas'], PDO::PARAM_INT);
        $stmt->bindParam(":total_unidades", $data['total_unidades'], PDO::PARAM_INT);
        $stmt->bindParam(":total_usd", $data['total_usd']);
        $stmt->bindParam(":total_bs", $data['total_bs']);
        
        // Totales USD
        $stmt->bindParam(":efectivo_usd", $data['efectivo_usd']);
        $stmt->bindParam(":efectivo_bs_usd", $data['efectivo_bs_usd']);
        $stmt->bindParam(":transferencia_usd", $data['transferencia_usd']);
        $stmt->bindParam(":pago_movil_usd", $data['pago_movil_usd']);
        $stmt->bindParam(":tarjeta_debito_usd", $data['tarjeta_debito_usd']);
        $stmt->bindParam(":tarjeta_credito_usd", $data['tarjeta_credito_usd']);
        $stmt->bindParam(":divisa_usd", $data['divisa_usd']);
        $stmt->bindParam(":credito_usd", $data['credito_usd']);
        
        // Totales BS
        $stmt->bindParam(":efectivo_bs", $data['efectivo_bs']);
        $stmt->bindParam(":efectivo_bs_bs", $data['efectivo_bs_bs']);
        $stmt->bindParam(":transferencia_bs", $data['transferencia_bs']);
        $stmt->bindParam(":pago_movil_bs", $data['pago_movil_bs']);
        $stmt->bindParam(":tarjeta_debito_bs", $data['tarjeta_debito_bs']);
        $stmt->bindParam(":tarjeta_credito_bs", $data['tarjeta_credito_bs']);
        $stmt->bindParam(":divisa_bs", $data['divisa_bs']);
        $stmt->bindParam(":credito_bs", $data['credito_bs']);
        
        // Resúmenes JSON
        $stmt->bindParam(":resumen_categorias", $data['resumen_categorias']);
        $stmt->bindParam(":resumen_productos", $data['resumen_productos']);
        $stmt->bindParam(":resumen_clientes", $data['resumen_clientes']);
        
        // Ventas IDs y observaciones
        $stmt->bindParam(":ventas_ids", $data['ventas_ids']);
        $stmt->bindParam(":observaciones", $data['observaciones']);
        $stmt->bindParam(":estado", $data['estado']);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }

        // Para debug
        error_log("Error al crear cierre: " . print_r($stmt->errorInfo(), true));
        return false;
    }

     /**
     * Generar número de cierre único
     */
    private function generarNumeroCierre()
    {
        $fecha = date('Y-m-d');
        
        // Contar cierres del día
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE fecha = :fecha";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $secuencia = $result['total'] + 1;
        
        return 'CC-' . date('Ymd') . '-' . str_pad($secuencia, 3, '0', STR_PAD_LEFT);
    }

     private function obtenerSecuenciaDelDia()
    {
        $fecha = date('Y-m-d');
        
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE fecha = :fecha";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] + 1;
    }


     public function obtenerPorId($id)
    {
        $query = "SELECT cc.*, u.nombre as usuario_nombre, u.username as usuario_username 
                  FROM " . $this->table . " cc
                  JOIN usuarios u ON cc.usuario_id = u.id
                  WHERE cc.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Obtener último cierre del día
     */
    public function obtenerCierreHoy()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE fecha = CURRENT_DATE 
                  AND estado = 'completado'
                  ORDER BY created_at DESC 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function obtenerPorFecha($fecha)
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE fecha = :fecha 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

     public function listar($limit = 30)
    {
        $query = "SELECT cc.*, u.nombre as usuario_nombre 
                  FROM " . $this->table . " cc
                  JOIN usuarios u ON cc.usuario_id = u.id
                  ORDER BY cc.created_at DESC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

public function obtenerReporteDetallado($cierre_id)
    {
        try {
            // Primero obtener el cierre
            $cierre = $this->obtenerPorId($cierre_id);
            if (!$cierre) {
                return [];
            }

            // Convertir ventas_ids de string a array
            $ventas_ids = [];
            if (isset($cierre['ventas_ids']) && !empty($cierre['ventas_ids'])) {
                // Limpiar el string PostgreSQL array
                $ventas_str = str_replace(['{', '}'], '', $cierre['ventas_ids']);
                $ventas_ids = explode(',', $ventas_str);
            }

            // Usar el nuevo método
            return $this->obtenerReporteDetalladoPorVentas($ventas_ids);
        } catch (Exception $e) {
            error_log("Error al obtener reporte detallado: " . $e->getMessage());
            return [];
        }
    }

public function obtenerVentasDelDia($fecha)
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, c.numero_documento as cliente_documento,
                         u.nombre as vendedor_nombre, tp.nombre as tipo_pago_nombre
                  FROM ventas v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN usuarios u ON v.usuario_id = u.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada' 
                  AND DATE(v.fecha_hora) = :fecha
                  AND v.cerrada_en_caja = FALSE
                  ORDER BY v.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();

        return $stmt->fetchAll();
    }

      public function existeCierreHoy()
    {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE fecha = CURRENT_DATE 
                  AND estado = 'completado'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'] > 0;
    }

    /**
     * Obtener número total de cierres hoy
     */
    public function contarCierresHoy()
    {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE fecha = CURRENT_DATE 
                  AND estado = 'completado'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['total'];
    }

    // NUEVO: Obtener ventas activas (para dashboard)
    public function obtenerVentasActivasHoy()
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                  FROM ventas v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE
                  ORDER BY v.fecha_hora DESC
                  LIMIT 20";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // NUEVO: Obtener estadísticas de ventas activas
    public function obtenerEstadisticasActivasHoy()
    {
        $query = "SELECT 
                    COUNT(*) as ventas_hoy,
                    SUM(v.total) as total_usd_hoy,
                    SUM(v.total_bs) as total_bs_hoy,
                    COUNT(DISTINCT v.cliente_id) as clientes_hoy,
                    MIN(v.fecha_hora) as primera_venta,
                    MAX(v.fecha_hora) as ultima_venta
                  FROM ventas v
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // NUEVO: Marcar ventas como cerradas (para reinicio de contadores)
    public function marcarVentasComoCerradas($fecha)
    {
        $query = "UPDATE ventas 
                  SET cerrada_en_caja = TRUE 
                  WHERE DATE(fecha_hora) = :fecha 
                  AND estado = 'completada'
                  AND cerrada_en_caja = FALSE
                  RETURNING id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);

        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        return 0;
    }

    // NUEVO: Verificar si hay ventas activas hoy
    public function hayVentasActivasHoy()
    {
        $query = "SELECT COUNT(*) as total 
                  FROM ventas 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($resultado['total'] > 0);
    }

    // NUEVO: Obtener resumen rápido del día (para dashboard)
    public function obtenerResumenRapidoHoy()
    {
        $query = "SELECT 
                    COUNT(*) as ventas_hoy,
                    SUM(total) as total_usd_hoy,
                    SUM(total_bs) as total_bs_hoy,
                    COUNT(DISTINCT cliente_id) as clientes_hoy,
                    (SELECT COUNT(*) FROM ventas WHERE DATE(fecha_hora) = CURRENT_DATE 
                     AND estado != 'completada' AND cerrada_en_caja = FALSE) as ventas_pendientes
                  FROM ventas 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // NUEVO: Obtener cierres por rango con ventas cerradas
    public function obtenerCierresConVentasCerradas($fecha_inicio, $fecha_fin)
    {
        $query = "SELECT 
                    cc.*,
                    u.nombre as usuario_nombre,
                    (SELECT COUNT(*) FROM ventas v 
                     WHERE DATE(v.fecha_hora) = cc.fecha 
                     AND v.cerrada_en_caja = TRUE) as ventas_cerradas,
                    (SELECT SUM(v.total_bs) FROM ventas v 
                     WHERE DATE(v.fecha_hora) = cc.fecha 
                     AND v.cerrada_en_caja = TRUE) as total_bs_cerradas
                  FROM " . $this->table . " cc
                  JOIN usuarios u ON cc.usuario_id = u.id
                  WHERE cc.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  AND cc.estado = 'completado'
                  ORDER BY cc.fecha DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        $stmt->bindParam(":fecha_fin", $fecha_fin);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerCierresPorRango($fecha_inicio, $fecha_fin)
    {
        $query = "SELECT cc.*, u.nombre as usuario_nombre 
                  FROM " . $this->table . " cc
                  JOIN usuarios u ON cc.usuario_id = u.id
                  WHERE cc.fecha BETWEEN :fecha_inicio AND :fecha_fin
                  AND cc.estado = 'completado'
                  ORDER BY cc.fecha DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        $stmt->bindParam(":fecha_fin", $fecha_fin);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function actualizar($id, $data)
    {
        $query = "UPDATE " . $this->table . " 
                  SET observaciones = :observaciones,
                      estado = :estado,
                      updated_at = NOW()
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":observaciones", $data['observaciones']);
        $stmt->bindParam(":estado", $data['estado']);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function obtenerEstadisticas()
    {
        $query = "SELECT 
                    COUNT(*) as total_cierres,
                    SUM(total_ventas) as total_ventas_cerradas,
                    SUM(total_unidades) as total_unidades_cerradas,
                    SUM(total_usd) as total_usd_cerrado,
                    SUM(total_bs) as total_bs_cerrado,
                    MIN(fecha) as primera_fecha,
                    MAX(fecha) as ultima_fecha
                  FROM " . $this->table . " 
                  WHERE estado = 'completado'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // NUEVO: Obtener ventas por tipo de pago del día (activas)
    public function obtenerVentasPorTipoPagoHoy()
    {
        $query = "SELECT 
                    tp.nombre as tipo_pago,
                    COUNT(v.id) as cantidad_ventas,
                    SUM(v.total) as total_usd,
                    SUM(v.total_bs) as total_bs
                  FROM ventas v
                  JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE
                  GROUP BY tp.nombre
                  ORDER BY SUM(v.total_bs) DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // NUEVO: Verificar estado de caja actual
    public function obtenerEstadoCajaActual()
    {
        $estado = [
            'hay_cierre_hoy' => false,
            'ventas_activas' => 0,
            'total_activo_usd' => 0,
            'total_activo_bs' => 0,
            'ultima_venta' => null
        ];

        // Verificar si hay cierre hoy
        $cierre_hoy = $this->obtenerCierreHoy();
        if ($cierre_hoy) {
            $estado['hay_cierre_hoy'] = true;
            $estado['cierre_id'] = $cierre_hoy['id'];
            $estado['hora_cierre'] = $cierre_hoy['created_at'];
        }

        // Obtener ventas activas del día
        $ventas_activas = $this->obtenerVentasActivasHoy();
        $estado['ventas_activas'] = count($ventas_activas);

        // Calcular totales
        foreach ($ventas_activas as $venta) {
            $estado['total_activo_usd'] += $venta['total'];
            $estado['total_activo_bs'] += $venta['total_bs'];

            // Obtener última venta
            if (!$estado['ultima_venta'] || strtotime($venta['fecha_hora']) > strtotime($estado['ultima_venta'])) {
                $estado['ultima_venta'] = $venta['fecha_hora'];
            }
        }

        // Formatear última venta
        if ($estado['ultima_venta']) {
            $estado['ultima_venta_formateada'] = date('H:i', strtotime($estado['ultima_venta']));
        }

        return $estado;
    }

    // NUEVO: Reiniciar cierre (solo para administradores, en caso de error)
    public function reiniciarCierre($fecha)
    {
        $this->conn->beginTransaction();

        try {
            // 1. Eliminar el cierre
            $query1 = "DELETE FROM " . $this->table . " WHERE fecha = :fecha";
            $stmt1 = $this->conn->prepare($query1);
            $stmt1->bindParam(":fecha", $fecha);
            $stmt1->execute();

            // 2. Desmarcar ventas como cerradas
            $query2 = "UPDATE ventas 
                      SET cerrada_en_caja = FALSE 
                      WHERE DATE(fecha_hora) = :fecha 
                      AND estado = 'completada'";

            $stmt2 = $this->conn->prepare($query2);
            $stmt2->bindParam(":fecha", $fecha);
            $stmt2->execute();
            $ventas_reiniciadas = $stmt2->rowCount();

            $this->conn->commit();

            return [
                'success' => true,
                'ventas_reiniciadas' => $ventas_reiniciadas,
                'message' => "Cierre reiniciado exitosamente. $ventas_reiniciadas ventas marcadas como activas."
            ];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return [
                'success' => false,
                'message' => "Error al reiniciar cierre: " . $e->getMessage()
            ];
        }
    }

    public function CierreCajaVentas(){

    }
}