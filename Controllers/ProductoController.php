<?php
//require_once 'Models/Producto.php';
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
        $this->producto->codigo_sku = $data['codigo_sku'];
        $this->producto->nombre = $data['nombre'];
        $this->producto->descripcion = $data['descripcion'];
        $this->producto->precio = $data['precio'];
        $this->producto->precio_costo = $data['precio_costo'];
        $this->producto->stock_actual = $data['stock_actual'];
        $this->producto->stock_minimo = $data['stock_minimo'];
        $this->producto->categoria_id = $data['categoria_id'];

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
    }

    public function actualizar($id, $data)
    {
        $this->producto->nombre = $data['nombre'];
        $this->producto->descripcion = $data['descripcion'];
        $this->producto->precio = $data['precio'];
        $this->producto->precio_costo = $data['precio_costo'];
        $this->producto->stock_minimo = $data['stock_minimo'];
        $this->producto->categoria_id = $data['categoria_id'];

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
