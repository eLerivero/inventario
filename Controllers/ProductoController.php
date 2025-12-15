<?php
// Controllers/ProductoController.php
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
                "message" => "Error al obtener productos: " . $e->getMessage()
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
                "message" => "Error al obtener producto: " . $e->getMessage()
            ];
        }
    }

    public function crearOLD($data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['codigo_sku'])) {
                throw new Exception("El código SKU es obligatorio");
            }

            if (empty($data['nombre'])) {
                throw new Exception("El nombre del producto es obligatorio");
            }

            // Validar que el SKU sea único
            $producto_existente = $this->producto->obtenerPorSku($data['codigo_sku']);
            if ($producto_existente) {
                throw new Exception("El código SKU ya existe");
            }

            if (isset($data['usar_precio_fijo_bs']) && $data['usar_precio_fijo_bs']) {
                // Para precio fijo en Bs, el precio_bs es obligatorio
                if (empty($data['precio_bs']) || $data['precio_bs'] <= 0) {
                    throw new Exception("Debe proporcionar el precio en bolívares cuando marca precio fijo");
                }
                // Para precio fijo, los precios en USD no son obligatorios
                // Si no se proporcionan, se pueden establecer en 0
                if (empty($data['precio']) || $data['precio'] < 0) {
                    $data['precio'] = 0;
                }
            } else {
                // Para precio NO fijo, el precio en USD es obligatorio
                if (empty($data['precio']) || $data['precio'] <= 0) {
                    throw new Exception("El precio en USD debe ser mayor a 0 para productos sin precio fijo");
                }
            }

            return $this->producto->crear($data);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al crear producto: " . $e->getMessage()
            ];
        }
    }

        public function crear($data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['codigo_sku'])) {
                throw new Exception("El código SKU es obligatorio");
            }

            if (empty($data['nombre'])) {
                throw new Exception("El nombre del producto es obligatorio");
            }

            // Validar que el SKU sea único
            $producto_existente = $this->producto->obtenerPorSku($data['codigo_sku']);
            if ($producto_existente) {
                throw new Exception("El código SKU ya existe");
            }

            $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;

            if ($usarPrecioFijo) {
                // Para precio fijo en Bs, el precio_bs es obligatorio
                if (empty($data['precio_bs']) || $data['precio_bs'] <= 0) {
                    throw new Exception("Debe proporcionar el precio en bolívares cuando marca precio fijo");
                }
                // Para precio fijo, los precios en USD son OPCIONALES
                // Si no se proporcionan, se establecen en 0
                if (empty($data['precio']) || $data['precio'] < 0) {
                    $data['precio'] = 0;
                }
                if (empty($data['precio_costo']) || $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
            } else {
                // Para precio NO fijo, el precio en USD es obligatorio
                if (empty($data['precio']) || $data['precio'] <= 0) {
                    throw new Exception("El precio en USD debe ser mayor a 0 para productos sin precio fijo");
                }
                // El precio de costo es opcional
                if (empty($data['precio_costo']) || $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
            }

            return $this->producto->crear($data);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al crear producto: " . $e->getMessage()
            ];
        }
    }

    public function actualizarOLD($id, $data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['nombre'])) {
                throw new Exception("El nombre del producto es obligatorio");
            }

            // MODIFICACIÓN: Manejar validación según tipo de precio
            if (isset($data['usar_precio_fijo_bs']) && $data['usar_precio_fijo_bs']) {
                // Para precio fijo en Bs, el precio_bs es obligatorio
                if (empty($data['precio_bs']) || $data['precio_bs'] <= 0) {
                    throw new Exception("Debe proporcionar el precio en bolívares cuando marca precio fijo");
                }
                // Para precio fijo, el precio en USD puede ser 0
                if (empty($data['precio']) || $data['precio'] < 0) {
                    $data['precio'] = 0;
                }
            } else {
                // Para precio NO fijo, el precio en USD es obligatorio
                if (empty($data['precio']) || $data['precio'] <= 0) {
                    throw new Exception("El precio en USD debe ser mayor a 0 para productos sin precio fijo");
                }
            }

            return $this->producto->actualizar($id, $data);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al actualizar producto: " . $e->getMessage()
            ];
        }
    }

    public function actualizar($id, $data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['nombre'])) {
                throw new Exception("El nombre del producto es obligatorio");
            }

            $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;

            if ($usarPrecioFijo) {
                // Para precio fijo en Bs, el precio_bs es obligatorio
                if (empty($data['precio_bs']) || $data['precio_bs'] <= 0) {
                    throw new Exception("Debe proporcionar el precio en bolívares cuando marca precio fijo");
                }
                // Para precio fijo, los precios en USD son OPCIONALES
                if (empty($data['precio']) || $data['precio'] < 0) {
                    $data['precio'] = 0;
                }
                if (empty($data['precio_costo']) || $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
            } else {
                // Para precio NO fijo, el precio en USD es obligatorio
                if (empty($data['precio']) || $data['precio'] <= 0) {
                    throw new Exception("El precio en USD debe ser mayor a 0 para productos sin precio fijo");
                }
                if (empty($data['precio_costo']) || $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
            }

            return $this->producto->actualizar($id, $data);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al actualizar producto: " . $e->getMessage()
            ];
        }
    }


    public function eliminar($id)
    {
        try {
            // Verificar si el producto existe
            $producto = $this->producto->obtenerPorId($id);
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }

            // Verificar si el producto tiene ventas asociadas
            $query = "SELECT COUNT(*) as total FROM detalle_ventas WHERE producto_id = :producto_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":producto_id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                throw new Exception("No se puede eliminar el producto porque tiene ventas asociadas");
            }

            return $this->producto->eliminar($id);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al eliminar producto: " . $e->getMessage()
            ];
        }
    }

    public function actualizarStock($producto_id, $nuevo_stock, $tipo_movimiento = 'ajuste', $observaciones = '')
    {
        try {
            if ($nuevo_stock < 0) {
                throw new Exception("El stock no puede ser negativo");
            }

            return $this->producto->actualizarStock($producto_id, $nuevo_stock, $tipo_movimiento, $observaciones);
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al actualizar stock: " . $e->getMessage()
            ];
        }
    }

    public function obtenerProductosActivos()
    {
        try {
            $productos = $this->producto->obtenerProductosActivos();
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

    public function obtenerProductosBajoStock()
    {
        try {
            $productos = $this->producto->obtenerProductosBajoStock();
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

    public function obtenerProductosConPrecioFijo()
    {
        try {
            $productos = $this->producto->obtenerProductosConPrecioFijo();
            return [
                "success" => true,
                "data" => $productos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener productos con precio fijo: " . $e->getMessage()
            ];
        }
    }

    // Método de búsqueda existente (mantener compatibilidad)
    public function buscar($search)
    {
        try {
            $query = "SELECT p.*, c.nombre as categoria_nombre 
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE p.codigo_sku ILIKE :search 
                        OR p.nombre ILIKE :search 
                        OR p.descripcion ILIKE :search
                    ORDER BY p.nombre";

            $stmt = $this->db->prepare($query);
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(":search", $searchTerm);
            $stmt->execute();
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

    public function buscarProductosAvanzado($searchTerm = '', $categoria = '')
    {
        try {
            // Obtener tasa de cambio actual
            $tasaCambio = $this->obtenerTasaCambioActual();

            $query = "SELECT 
                        p.id,
                        p.codigo_sku,
                        p.nombre,
                        p.descripcion,
                        p.precio,
                        p.precio_bs,
                        p.usar_precio_fijo_bs,
                        p.activo,
                        p.categoria_id,
                        c.nombre as categoria_nombre,
                        p.stock_actual
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE p.activo = TRUE";

            $conditions = [];
            $params = [];

            if (!empty($searchTerm)) {
                $conditions[] = "(p.nombre ILIKE :searchTerm OR 
                            p.codigo_sku ILIKE :searchTerm OR 
                            p.descripcion ILIKE :searchTerm)";
                $params[':searchTerm'] = "%" . $searchTerm . "%";
            }

            if (!empty($categoria)) {
                $conditions[] = "c.nombre = :categoria";
                $params[':categoria'] = $categoria;
            }

            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            $query .= " ORDER BY p.nombre ASC";

            $stmt = $this->db->prepare($query);

            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val);
            }

            $stmt->execute();

            $productos = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Calcular precio en bolívares según tipo
                if ($row['usar_precio_fijo_bs'] && $row['precio_bs'] > 0) {
                    // Precio fijo en bolívares (ya viene del modelo)
                    $row['precio_bs'] = floatval($row['precio_bs']);
                } else {
                    // Conversión automática con tasa actual
                    $row['precio_bs'] = floatval($row['precio']) * $tasaCambio;
                }

                // Asegurar tipos de datos correctos
                $row['stock_actual'] = intval($row['stock_actual']);
                $row['precio'] = floatval($row['precio']);
                $row['usar_precio_fijo_bs'] = (bool)$row['usar_precio_fijo_bs'];
                $row['activo'] = (bool)$row['activo'];

                $productos[] = $row;
            }

            return [
                "success" => true,
                "data" => $productos,
                "count" => count($productos),
                "tasa_cambio" => $tasaCambio
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar productos: " . $e->getMessage()
            ];
        }
    }

    // Método auxiliar para obtener tasa de cambio actual
    private function obtenerTasaCambioActual()
    {
        try {
            $query = "SELECT tasa_cambio 
                    FROM tasas_cambio 
                    WHERE activa = TRUE 
                    ORDER BY fecha_actualizacion DESC 
                    LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && isset($result['tasa_cambio'])) {
                return floatval($result['tasa_cambio']);
            }

            // Si no hay tasa activa, usar un valor por defecto
            return 35.00;
        } catch (Exception $e) {
            // En caso de error, retornar un valor por defecto
            return 35.00;
        }
    }

    // Método para obtener productos con información completa (usado en ventas)
    public function obtenerProductosConInfoCompleta()
    {
        try {
            // Usar el método del modelo
            $productos = $this->producto->obtenerProductosConInfoCompleta();

            // Obtener tasa de cambio para calcular precios en bolívares
            $tasaCambio = $this->obtenerTasaCambioActual();

            // Procesar productos para calcular precios en BS si no son fijos
            foreach ($productos as &$producto) {
                if (!$producto['usar_precio_fijo_bs'] || $producto['precio_bs'] <= 0) {
                    $producto['precio_bs'] = floatval($producto['precio']) * $tasaCambio;
                }

                // Asegurar tipos de datos
                $producto['stock_actual'] = intval($producto['stock_actual']);
                $producto['precio'] = floatval($producto['precio']);
                $producto['precio_bs'] = floatval($producto['precio_bs']);
                $producto['usar_precio_fijo_bs'] = (bool)$producto['usar_precio_fijo_bs'];
                $producto['activo'] = (bool)$producto['activo'];
            }

            return [
                "success" => true,
                "data" => $productos,
                "count" => count($productos),
                "tasa_cambio" => $tasaCambio
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener productos: " . $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_productos,
                        SUM(CASE WHEN activo = TRUE THEN 1 ELSE 0 END) as productos_activos,
                        SUM(CASE WHEN stock_actual = 0 THEN 1 ELSE 0 END) as productos_sin_stock,
                        SUM(CASE WHEN stock_actual <= stock_minimo AND stock_actual > 0 THEN 1 ELSE 0 END) as productos_bajo_stock,
                        SUM(CASE WHEN usar_precio_fijo_bs = TRUE THEN 1 ELSE 0 END) as productos_precio_fijo,
                        AVG(precio) as precio_promedio,
                        SUM(stock_actual * precio_costo) as valor_inventario_costo,
                        SUM(stock_actual * precio) as valor_inventario_venta
                    FROM productos";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

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
}
