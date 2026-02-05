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

    public function leer($solo_activas = false)
{
    $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
              FROM " . $this->table . " v
              LEFT JOIN clientes c ON v.cliente_id = c.id
              LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id";
    
    // Añadir filtro si se solicitan solo ventas activas
    if ($solo_activas) {
        $query .= " WHERE v.cerrada_en_caja = FALSE";
    }
    
    $query .= " ORDER BY v.created_at DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt;
}

    public function leerOld()
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

    // ====================================================
    // NUEVOS MÉTODOS PARA SISTEMA DE REINICIO DIARIO
    // ====================================================

    /**
     * Obtener ventas activas del día (no cerradas en caja)
     */
    public function obtenerVentasActivasDelDia()
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE
                  ORDER BY v.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener estadísticas del día actual (solo ventas activas)
     */
    public function obtenerEstadisticasHoy()
    {
        $query = "SELECT 
                    COUNT(*) as ventas_hoy,
                    SUM(total) as ingresos_hoy_usd,
                    SUM(total_bs) as ingresos_hoy_bs,
                    AVG(total) as promedio_venta_hoy,
                    COUNT(DISTINCT cliente_id) as clientes_hoy,
                    MIN(fecha_hora) as primera_venta,
                    MAX(fecha_hora) as ultima_venta
                  FROM " . $this->table . " 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener ventas cerradas (historial completo)
     */
    public function obtenerVentasCerradas($limit = 50)
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre,
                         cc.fecha as fecha_cierre
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  LEFT JOIN cierres_caja cc ON DATE(v.fecha_hora) = cc.fecha
                  WHERE v.cerrada_en_caja = TRUE
                  AND v.estado = 'completada'
                  ORDER BY v.fecha_hora DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verificar si hay ventas activas hoy
     */
    public function hayVentasActivasHoy()
    {
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table . " 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($resultado['total'] > 0);
    }

    /**
     * Obtener ventas por rango de fechas (activas y cerradas)
     */
    public function obtenerVentasPorRango($fecha_inicio, $fecha_fin, $solo_activas = false)
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre,
                         v.cerrada_en_caja
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin";

        if ($solo_activas) {
            $query .= " AND v.cerrada_en_caja = FALSE";
        }

        $query .= " ORDER BY v.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha_inicio", $fecha_inicio);
        $stmt->bindParam(":fecha_fin", $fecha_fin);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Marcar ventas como cerradas en caja (para reinicio de contadores)
     */
    public function marcarVentasComoCerradas($fecha)
    {
        $query = "UPDATE " . $this->table . " 
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

    /**
     * Desmarcar ventas como cerradas (para corrección de errores)
     */
    public function desmarcarVentasComoCerradas($fecha)
    {
        $query = "UPDATE " . $this->table . " 
                  SET cerrada_en_caja = FALSE 
                  WHERE DATE(fecha_hora) = :fecha 
                  AND estado = 'completada'
                  AND cerrada_en_caja = TRUE
                  RETURNING id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);

        if ($stmt->execute()) {
            return $stmt->rowCount();
        }
        return 0;
    }

    /**
     * Obtener ventas por tipo de pago del día (activas)
     */
    public function obtenerVentasPorTipoPagoHoy()
    {
        $query = "SELECT 
                    tp.nombre as tipo_pago,
                    COUNT(v.id) as cantidad_ventas,
                    SUM(v.total) as total_usd,
                    SUM(v.total_bs) as total_bs,
                    AVG(v.total) as promedio_usd
                  FROM " . $this->table . " v
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

    /**
     * Obtener resumen rápido del día para dashboard
     */
    public function obtenerResumenRapidoHoy()
    {
        $query = "SELECT 
                    COUNT(*) as ventas_hoy,
                    SUM(total) as total_usd_hoy,
                    SUM(total_bs) as total_bs_hoy,
                    COUNT(DISTINCT cliente_id) as clientes_hoy,
                    (SELECT COUNT(*) FROM " . $this->table . " 
                     WHERE DATE(fecha_hora) = CURRENT_DATE 
                     AND estado != 'completada' 
                     AND cerrada_en_caja = FALSE) as ventas_pendientes,
                    (SELECT SUM(total) FROM " . $this->table . " 
                     WHERE DATE(fecha_hora) = CURRENT_DATE 
                     AND estado != 'completada' 
                     AND cerrada_en_caja = FALSE) as total_pendiente_usd
                  FROM " . $this->table . " 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener ventas del día por hora (para gráficos)
     */
    public function obtenerVentasPorHoraHoy()
    {
        $query = "SELECT 
                    EXTRACT(HOUR FROM fecha_hora) as hora,
                    COUNT(*) as cantidad_ventas,
                    SUM(total) as total_usd,
                    SUM(total_bs) as total_bs
                  FROM " . $this->table . " 
                  WHERE estado = 'completada'
                  AND DATE(fecha_hora) = CURRENT_DATE
                  AND cerrada_en_caja = FALSE
                  GROUP BY EXTRACT(HOUR FROM fecha_hora)
                  ORDER BY hora";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener top productos vendidos hoy
     */
    public function obtenerTopProductosHoy($limit = 10)
    {
        $query = "SELECT 
                    p.nombre as producto_nombre,
                    p.codigo_sku,
                    SUM(dv.cantidad) as cantidad_vendida,
                    SUM(dv.subtotal) as total_usd,
                    SUM(dv.subtotal_bs) as total_bs,
                    c.nombre as categoria_nombre
                  FROM detalle_ventas dv
                  JOIN productos p ON dv.producto_id = p.id
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  JOIN ventas v ON dv.venta_id = v.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE
                  GROUP BY p.id, p.nombre, p.codigo_sku, c.nombre
                  ORDER BY SUM(dv.subtotal_bs) DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener ventas por cliente hoy
     */
    public function obtenerVentasPorClienteHoy()
    {
        $query = "SELECT 
                    c.nombre as cliente_nombre,
                    c.numero_documento,
                    COUNT(v.id) as ventas_realizadas,
                    SUM(v.total) as total_usd,
                    SUM(v.total_bs) as total_bs,
                    AVG(v.total) as promedio_usd
                  FROM " . $this->table . " v
                  JOIN clientes c ON v.cliente_id = c.id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = CURRENT_DATE
                  AND v.cerrada_en_caja = FALSE
                  GROUP BY c.id, c.nombre, c.numero_documento
                  ORDER BY SUM(v.total_bs) DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verificar estado de una venta específica (si está cerrada o activa)
     */
    public function obtenerEstadoVenta($venta_id)
    {
        $query = "SELECT 
                    id,
                    numero_venta,
                    estado,
                    cerrada_en_caja,
                    fecha_hora,
                    CASE 
                        WHEN cerrada_en_caja = TRUE THEN 'CERRADA_EN_CAJA'
                        WHEN estado = 'completada' THEN 'ACTIVA'
                        WHEN estado = 'pendiente' THEN 'PENDIENTE'
                        ELSE estado
                    END as estado_detallado
                  FROM " . $this->table . " 
                  WHERE id = :venta_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":venta_id", $venta_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Obtener total de ventas completadas en BS (con filtro por cerrada_en_caja)
     */
    public function obtenerTotalVentasCompletadasBs($solo_activas = false)
    {
        $query = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(total) as total_usd,
                    SUM(total_bs) as total_bs,
                    AVG(total) as promedio_usd
                  FROM " . $this->table . " 
                  WHERE estado = 'completada'";

        if ($solo_activas) {
            $query .= " AND cerrada_en_caja = FALSE";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buscar ventas activas por criterios
     */
    public function buscarVentasActivas($criterio)
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  WHERE v.estado = 'completada'
                  AND v.cerrada_en_caja = FALSE
                  AND (v.numero_venta ILIKE :criterio 
                       OR c.nombre ILIKE :criterio
                       OR c.numero_documento ILIKE :criterio)
                  ORDER BY v.fecha_hora DESC
                  LIMIT 50";

        $stmt = $this->conn->prepare($query);
        $criterio_like = "%" . $criterio . "%";
        $stmt->bindParam(":criterio", $criterio_like);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener ventas pendientes de cierre (para reporte de cierre)
     */
    public function obtenerVentasPendientesCierre($fecha = null)
    {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }

        $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre,
                         COUNT(dv.id) as items,
                         SUM(dv.cantidad) as unidades
                  FROM " . $this->table . " v
                  LEFT JOIN clientes c ON v.cliente_id = c.id
                  LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                  LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = :fecha
                  AND v.cerrada_en_caja = FALSE
                  GROUP BY v.id, c.nombre, tp.nombre
                  ORDER BY v.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener resumen para cierre de caja
     */
    public function obtenerResumenParaCierre($fecha = null)
    {
        if ($fecha === null) {
            $fecha = date('Y-m-d');
        }

        $query = "SELECT 
                    COUNT(*) as total_ventas,
                    SUM(v.total) as total_usd,
                    SUM(v.total_bs) as total_bs,
                    COUNT(DISTINCT v.cliente_id) as clientes_atendidos,
                    (SELECT COUNT(*) FROM detalle_ventas dv
                     JOIN ventas v2 ON dv.venta_id = v2.id
                     WHERE DATE(v2.fecha_hora) = :fecha
                     AND v2.estado = 'completada'
                     AND v2.cerrada_en_caja = FALSE) as total_items,
                    (SELECT SUM(dv.cantidad) FROM detalle_ventas dv
                     JOIN ventas v2 ON dv.venta_id = v2.id
                     WHERE DATE(v2.fecha_hora) = :fecha
                     AND v2.estado = 'completada'
                     AND v2.cerrada_en_caja = FALSE) as total_unidades
                  FROM " . $this->table . " v
                  WHERE v.estado = 'completada'
                  AND DATE(v.fecha_hora) = :fecha
                  AND v.cerrada_en_caja = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar venta (para cambios de estado, etc.)
     */
    public function actualizar($id, $data)
    {
        $campos = [];
        $params = [":id" => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id') {
                $campos[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($campos)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " 
                  SET " . implode(', ', $campos) . "
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        return $stmt->execute();
    }

    /**
     * Eliminar venta (solo para administradores, con validaciones)
     */
    public function eliminar($id)
    {
        // Primero verificar si la venta puede eliminarse
        $venta = $this->obtenerPorId($id);

        if (!$venta) {
            return false;
        }

        // No permitir eliminar ventas cerradas en caja
        if (isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'] == true) {
            error_log("Intento de eliminar venta cerrada en caja: $id");
            return false;
        }

        // No permitir eliminar ventas completadas
        if ($venta['estado'] == 'completada') {
            error_log("Intento de eliminar venta completada: $id");
            return false;
        }

        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }
}
