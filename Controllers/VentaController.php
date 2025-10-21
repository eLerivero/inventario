<?php
require_once 'Models/Venta.php';
require_once 'Models/DetalleVenta.php';

class VentaController
{
    private $venta;
    private $detalleVenta;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->venta = new Venta($db);
        $this->detalleVenta = new DetalleVenta($db);
    }

    public function listar()
    {
        $stmt = $this->venta->leer();
        return $stmt->fetchAll();
    }

    public function obtener($id)
    {
        return $this->venta->obtenerPorId($id);
    }

    public function obtenerDetalles($venta_id)
    {
        return $this->venta->obtenerDetalles($venta_id);
    }

    public function crear($data)
    {
        try {
            $this->db->beginTransaction();

            // Crear la venta
            $this->venta->cliente_id = $data['cliente_id'];
            $this->venta->total = $data['total'];
            $this->venta->tipo_pago_id = $data['tipo_pago_id'];
            $this->venta->estado = $data['estado'];
            $this->venta->fecha_hora = $data['fecha_hora'];
            $this->venta->observaciones = $data['observaciones'];

            $resultado_venta = $this->venta->crear();

            if (!$resultado_venta) {
                throw new Exception("Error al crear la venta");
            }

            $venta_id = $resultado_venta['id'];
            $numero_venta = $resultado_venta['numero_venta'];

            // Crear detalles de venta
            $this->detalleVenta->venta_id = $venta_id;

            if (!$this->detalleVenta->crearMultiple($data['detalles'])) {
                throw new Exception("Error al crear los detalles de venta");
            }

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Venta creada exitosamente",
                "venta_id" => $venta_id,
                "numero_venta" => $numero_venta
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticas()
    {
        return $this->venta->obtenerEstadisticas();
    }

    public function obtenerVentasPorMes()
    {
        return $this->venta->obtenerVentasPorMes();
    }
}
