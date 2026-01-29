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

    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (fecha, usuario_id, total_ventas, total_unidades, total_usd, total_bs,
                   efectivo_usd, efectivo_bs_usd, transferencia_usd, pago_movil_usd,
                   tarjeta_debito_usd, tarjeta_credito_usd, divisa_usd, credito_usd,
                   efectivo_bs, efectivo_bs_bs, transferencia_bs, pago_movil_bs,
                   tarjeta_debito_bs, tarjeta_credito_bs, divisa_bs, credito_bs,
                   resumen_categorias, resumen_productos, resumen_clientes,
                   ventas_ids, observaciones, estado) 
                  VALUES 
                  (:fecha, :usuario_id, :total_ventas, :total_unidades, :total_usd, :total_bs,
                   :efectivo_usd, :efectivo_bs_usd, :transferencia_usd, :pago_movil_usd,
                   :tarjeta_debito_usd, :tarjeta_credito_usd, :divisa_usd, :credito_usd,
                   :efectivo_bs, :efectivo_bs_bs, :transferencia_bs, :pago_movil_bs,
                   :tarjeta_debito_bs, :tarjeta_credito_bs, :divisa_bs, :credito_bs,
                   :resumen_categorias, :resumen_productos, :resumen_clientes,
                   :ventas_ids, :observaciones, :estado)
                  RETURNING id";

        $stmt = $this->conn->prepare($query);

        // Bindear parámetros
        $stmt->bindParam(":fecha", $data['fecha']);
        $stmt->bindParam(":usuario_id", $data['usuario_id']);
        $stmt->bindParam(":total_ventas", $data['total_ventas']);
        $stmt->bindParam(":total_unidades", $data['total_unidades']);
        $stmt->bindParam(":total_usd", $data['total_usd']);
        $stmt->bindParam(":total_bs", $data['total_bs']);
        $stmt->bindParam(":efectivo_usd", $data['efectivo_usd']);
        $stmt->bindParam(":efectivo_bs_usd", $data['efectivo_bs_usd']);
        $stmt->bindParam(":transferencia_usd", $data['transferencia_usd']);
        $stmt->bindParam(":pago_movil_usd", $data['pago_movil_usd']);
        $stmt->bindParam(":tarjeta_debito_usd", $data['tarjeta_debito_usd']);
        $stmt->bindParam(":tarjeta_credito_usd", $data['tarjeta_credito_usd']);
        $stmt->bindParam(":divisa_usd", $data['divisa_usd']);
        $stmt->bindParam(":credito_usd", $data['credito_usd']);
        $stmt->bindParam(":efectivo_bs", $data['efectivo_bs']);
        $stmt->bindParam(":efectivo_bs_bs", $data['efectivo_bs_bs']);
        $stmt->bindParam(":transferencia_bs", $data['transferencia_bs']);
        $stmt->bindParam(":pago_movil_bs", $data['pago_movil_bs']);
        $stmt->bindParam(":tarjeta_debito_bs", $data['tarjeta_debito_bs']);
        $stmt->bindParam(":tarjeta_credito_bs", $data['tarjeta_credito_bs']);
        $stmt->bindParam(":divisa_bs", $data['divisa_bs']);
        $stmt->bindParam(":credito_bs", $data['credito_bs']);
        $stmt->bindParam(":resumen_categorias", $data['resumen_categorias']);
        $stmt->bindParam(":resumen_productos", $data['resumen_productos']);
        $stmt->bindParam(":resumen_clientes", $data['resumen_clientes']);
        $stmt->bindParam(":ventas_ids", $data['ventas_ids']);
        $stmt->bindParam(":observaciones", $data['observaciones']);
        $stmt->bindParam(":estado", $data['estado']);

        if ($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }

        return false;
    }

    // MÉTODO FALTANTE: obtenerPorId
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

    public function obtenerCierreHoy()
    {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE fecha = CURRENT_DATE 
                  AND estado = 'completado'
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
                  ORDER BY cc.fecha DESC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerReporteDetallado($cierre_id)
    {
        try {
            // Primero obtener la fecha del cierre
            $cierre = $this->obtenerPorId($cierre_id);
            if (!$cierre) {
                return [];
            }
            
            $fecha = $cierre['fecha'];
            
            // Usar la función SQL existente si está disponible, o hacer la consulta manual
            $query = "SELECT 
                        COALESCE(cat.nombre, 'Sin categoría') as categoria_nombre,
                        p.nombre as producto_nombre,
                        SUM(dv.cantidad) as cantidad_vendida,
                        AVG(dv.precio_unitario_bs) as precio_unitario_bs,
                        SUM(dv.subtotal_bs) as subtotal_bs,
                        AVG(dv.precio_unitario) as precio_unitario_usd,
                        SUM(dv.subtotal) as subtotal_usd,
                        tp.nombre as tipo_pago,
                        cl.nombre as cliente_nombre
                      FROM detalle_ventas dv
                      JOIN ventas v ON dv.venta_id = v.id
                      JOIN productos p ON dv.producto_id = p.id
                      LEFT JOIN categorias cat ON p.categoria_id = cat.id
                      JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                      JOIN clientes cl ON v.cliente_id = cl.id
                      WHERE v.estado = 'completada'
                      AND DATE(v.fecha_hora) = :fecha
                      GROUP BY cat.nombre, p.nombre, tp.nombre, cl.nombre
                      ORDER BY cat.nombre, SUM(dv.subtotal_bs) DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":fecha", $fecha);
            $stmt->execute();

            return $stmt->fetchAll();
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
                  ORDER BY v.fecha_hora DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha", $fecha);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function existeCierreHoy()
    {
        return $this->obtenerCierreHoy() !== false;
    }

    // MÉTODO ADICIONAL: obtenerCierresPorRango
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

    // MÉTODO ADICIONAL: eliminar
    public function eliminar($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // MÉTODO ADICIONAL: actualizar
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

    // MÉTODO ADICIONAL: obtenerEstadisticas
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
}