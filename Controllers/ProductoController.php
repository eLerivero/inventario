<?php
require_once __DIR__ . '/../Models/Producto.php';

class ProductoController
{
    private $producto;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->producto = new Producto($db);
    }

    public function listar()
    {
        $stmt = $this->producto->leer();
        return $stmt->fetchAll();
    }

    public function buscar($searchTerm)
    {
        $stmt = $this->producto->buscar($searchTerm);
        return $stmt->fetchAll();
    }

    public function obtener($id)
    {
        return $this->producto->obtenerPorId($id);
    }

    public function crear($data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['codigo_sku']) || empty($data['nombre']) || empty($data['precio'])) {
                return [
                    "success" => false,
                    "message" => "SKU, nombre y precio son campos requeridos"
                ];
            }

            $this->producto->codigo_sku = $data['codigo_sku'];
            $this->producto->nombre = $data['nombre'];
            $this->producto->descripcion = $data['descripcion'] ?? '';
            $this->producto->precio = $data['precio'];
            $this->producto->precio_costo = $data['precio_costo'] ?? 0;
            $this->producto->stock_actual = $data['stock_actual'] ?? 0;
            $this->producto->stock_minimo = $data['stock_minimo'] ?? 0;
            $this->producto->categoria_id = $data['categoria_id'] ?? null;

            $producto_id = $this->producto->crear();

            if ($producto_id) {
                return [
                    "success" => true,
                    "message" => "Producto creado exitosamente",
                    "id" => $producto_id
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al crear producto"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function actualizar($id, $data)
    {
        try {
            $this->producto->nombre = $data['nombre'];
            $this->producto->descripcion = $data['descripcion'] ?? '';
            $this->producto->precio = $data['precio'];
            $this->producto->precio_costo = $data['precio_costo'] ?? 0;
            $this->producto->stock_minimo = $data['stock_minimo'] ?? 0;
            $this->producto->categoria_id = $data['categoria_id'] ?? null;

            if ($this->producto->actualizar($id)) {
                return [
                    "success" => true,
                    "message" => "Producto actualizado exitosamente"
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al actualizar producto"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function eliminar($id)
    {
        if ($this->producto->eliminar($id)) {
            return [
                "success" => true,
                "message" => "Producto eliminado exitosamente"
            ];
        } else {
            return [
                "success" => false,
                "message" => "Error al eliminar producto"
            ];
        }
    }

    public function obtenerProductosBajoStock()
    {
        $stmt = $this->producto->obtenerProductosBajoStock();
        return $stmt->fetchAll();
    }

    public function obtenerEstadisticas()
    {
        return $this->producto->obtenerEstadisticas();
    }

    public function actualizarStock($producto_id, $nueva_cantidad, $tipo_movimiento = 'ajuste', $observaciones = '')
    {
        return $this->producto->actualizarStock($producto_id, $nueva_cantidad, $tipo_movimiento, $observaciones);
    }
}