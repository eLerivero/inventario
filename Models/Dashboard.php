<?php
class Dashboard
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerResumen()
    {
        $data = [];

        // Total productos
        $query = "SELECT COUNT(*) as total FROM productos WHERE activo = true";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $data['total_productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total clientes
        $query = "SELECT COUNT(*) as total FROM clientes";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $data['total_clientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Ventas del mes
        $query = "SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as ingresos 
                  FROM ventas 
                  WHERE estado = 'completada' 
                  AND fecha_hora >= DATE_TRUNC('month', CURRENT_DATE)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $ventas_mes = $stmt->fetch(PDO::FETCH_ASSOC);
        $data['ventas_mes'] = $ventas_mes['total'];
        $data['ingresos_mes'] = $ventas_mes['ingresos'];

        // Productos bajo stock
        $query = "SELECT COUNT(*) as total FROM productos WHERE stock_actual <= stock_minimo AND activo = true";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $data['productos_bajo_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        return $data;
    }

    public function obtenerVentasRecientes($limite = 5)
    {
        $query = "SELECT v.*, c.nombre as cliente_nombre 
                  FROM ventas v 
                  LEFT JOIN clientes c ON v.cliente_id = c.id 
                  WHERE v.estado = 'completada' 
                  ORDER BY v.created_at DESC 
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerProductosPopulares($limite = 5)
    {
        $query = "SELECT p.nombre, p.codigo_sku, SUM(dv.cantidad) as total_vendido
                  FROM detalle_ventas dv
                  JOIN productos p ON dv.producto_id = p.id
                  JOIN ventas v ON dv.venta_id = v.id
                  WHERE v.estado = 'completada'
                  GROUP BY p.id, p.nombre, p.codigo_sku
                  ORDER BY total_vendido DESC
                  LIMIT :limite";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
