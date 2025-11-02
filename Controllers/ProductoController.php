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
        try {
            $stmt = $this->producto->leer();
            $productos = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $productos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener los productos: " . $e->getMessage()
            ];
        }
    }

    public function buscar($searchTerm)
    {
        try {
            $stmt = $this->producto->buscar($searchTerm);
            $productos = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $productos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar productos: " . $e->getMessage()
            ];
        }
    }

    public function obtener($id)
    {
        try {
            $producto = $this->producto->obtenerPorId($id);

            if ($producto) {
                return [
                    "success" => true,
                    "data" => $producto
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Producto no encontrado"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener el producto: " . $e->getMessage()
            ];
        }
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
        try {
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
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function obtenerProductosBajoStock()
    {
        try {
            $stmt = $this->producto->obtenerProductosBajoStock();
            $productos = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $productos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener productos bajo stock: " . $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $estadisticas = $this->producto->obtenerEstadisticas();
            return [
                "success" => true,
                "data" => $estadisticas
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage()
            ];
        }
    }

    public function actualizarStock($producto_id, $nueva_cantidad, $tipo_movimiento = 'ajuste', $observaciones = '')
    {
        try {
            $result = $this->producto->actualizarStock($producto_id, $nueva_cantidad, $tipo_movimiento, $observaciones);
            
            if ($result) {
                return [
                    "success" => true,
                    "message" => "Stock actualizado exitosamente"
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al actualizar stock"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function obtenerProductosActivos()
    {
        try {
            $query = "SELECT * FROM productos WHERE activo = true ORDER BY nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $productos = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $productos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener productos activos: " . $e->getMessage()
            ];
        }
    }


    public function obtenerTodos()
{
    try {
        $query = "SELECT * FROM productos WHERE activo = true ORDER BY nombre";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $productos = $stmt->fetchAll();

        return [
            "success" => true,
            "data" => $productos
        ];
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => "Error al obtener productos: " . $e->getMessage(),
            "data" => []
        ];
    }
}
}