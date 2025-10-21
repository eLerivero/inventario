<?php
require_once 'Models/Dashboard.php';
require_once 'Models/Producto.php';
require_once 'Models/Venta.php';

class DashboardController
{
    private $dashboard;
    private $producto;
    private $venta;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->dashboard = new Dashboard($db);
        $this->producto = new Producto($db);
        $this->venta = new Venta($db);
    }

    public function obtenerResumen()
    {
        return $this->dashboard->obtenerResumen();
    }

    public function obtenerVentasRecientes()
    {
        return $this->dashboard->obtenerVentasRecientes();
    }

    public function obtenerProductosPopulares()
    {
        return $this->dashboard->obtenerProductosPopulares();
    }

    public function obtenerProductosBajoStock()
    {
        $stmt = $this->producto->obtenerProductosBajoStock();
        return $stmt->fetchAll();
    }

    public function obtenerEstadisticasCompletas()
    {
        $data = [];

        // Resumen general
        $data['resumen'] = $this->dashboard->obtenerResumen();

        // Ventas recientes
        $data['ventas_recientes'] = $this->dashboard->obtenerVentasRecientes();

        // Productos populares
        $data['productos_populares'] = $this->dashboard->obtenerProductosPopulares();

        // Productos bajo stock
        $data['productos_bajo_stock'] = $this->obtenerProductosBajoStock();

        // Ventas por mes
        $data['ventas_por_mes'] = $this->venta->obtenerVentasPorMes();

        return $data;
    }
}
