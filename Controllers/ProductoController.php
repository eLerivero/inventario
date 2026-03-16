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

    public function crear($data)
    {
        try {
            // Validar datos requeridos básicos
            if (empty($data['codigo_sku'])) {
                throw new Exception("El código SKU es obligatorio");
            }

            if (empty($data['nombre'])) {
                throw new Exception("El nombre del producto es obligatorio");
            }

            // Validar SKU único
            $producto_existente = $this->producto->obtenerPorSku($data['codigo_sku']);
            if ($producto_existente) {
                throw new Exception("El código SKU ya existe");
            }

            // Determinar tipo de venta
            $tipo_venta = $data['tipo_venta'] ?? 'unidad';

            // Validaciones según tipo de venta
            if ($tipo_venta === 'peso') {
                // Para productos por peso, validar precio por kilo
                if (empty($data['precio_por_kilo_usd']) || $data['precio_por_kilo_usd'] <= 0) {
                    throw new Exception("El precio por kilo en USD es obligatorio para productos por peso");
                }

                // Si es precio fijo, validar precio por kilo en Bs
                if (isset($data['usar_precio_fijo_bs']) && $data['usar_precio_fijo_bs']) {
                    if (empty($data['precio_por_kilo_bs']) || $data['precio_por_kilo_bs'] <= 0) {
                        throw new Exception("El precio por kilo en Bs es obligatorio cuando usa precio fijo");
                    }
                }

                // Para peso, el precio unitario USD puede ser 0
                $data['precio'] = $data['precio_por_kilo_usd'] ?? 0;
            }

            // Validaciones de precio fijo (comunes)
            $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;

            if ($usarPrecioFijo) {
                if ($tipo_venta !== 'peso') {
                    // Para unidad, validar precio_bs
                    if (empty($data['precio_bs']) || $data['precio_bs'] <= 0) {
                        throw new Exception("Debe proporcionar el precio en bolívares cuando marca precio fijo");
                    }
                }

                if (empty($data['precio']) || $data['precio'] < 0) {
                    $data['precio'] = 0;
                }
                if (empty($data['precio_costo']) || $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
            } else {
                if ($tipo_venta !== 'peso') {
                    // Para unidad sin precio fijo, validar precio USD
                    if (empty($data['precio']) || $data['precio'] <= 0) {
                        throw new Exception("El precio en USD debe ser mayor a 0 para productos sin precio fijo");
                    }
                }

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

    public function actualizar($id, $data)
    {
        try {
            if (empty($data['nombre'])) {
                throw new Exception("El nombre del producto es obligatorio");
            }

            // No permitir modificar SKU
            if (isset($data['codigo_sku'])) {
                unset($data['codigo_sku']);
            }

            // Obtener producto actual para conocer tipo_venta
            $producto_actual = $this->producto->obtenerPorId($id);
            if (!$producto_actual) {
                throw new Exception("Producto no encontrado");
            }

            $tipo_venta = $producto_actual['tipo_venta'] ?? 'unidad';
            $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : (bool)($producto_actual['usar_precio_fijo_bs'] ?? false);

            // Validaciones según tipo de venta
            if ($tipo_venta === 'peso') {
                if (isset($data['precio_por_kilo_usd']) && $data['precio_por_kilo_usd'] <= 0) {
                    throw new Exception("El precio por kilo en USD debe ser mayor a 0");
                }

                if ($usarPrecioFijo && isset($data['precio_por_kilo_bs']) && $data['precio_por_kilo_bs'] <= 0) {
                    throw new Exception("El precio por kilo en Bs debe ser mayor a 0 para precio fijo");
                }
            }

            // Validaciones de precio fijo
            if ($usarPrecioFijo) {
                if ($tipo_venta !== 'peso' && isset($data['precio_bs']) && $data['precio_bs'] <= 0) {
                    throw new Exception("Debe proporcionar el precio en bolívares cuando marca precio fijo");
                }

                if (isset($data['precio']) && $data['precio'] < 0) {
                    $data['precio'] = 0;
                }
                if (isset($data['precio_costo']) && $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
                if (isset($data['precio_costo_bs']) && $data['precio_costo_bs'] < 0) {
                    $data['precio_costo_bs'] = 0;
                }
            } else {
                if ($tipo_venta !== 'peso' && isset($data['precio']) && $data['precio'] <= 0) {
                    throw new Exception("El precio en USD debe ser mayor a 0 para productos sin precio fijo");
                }
                if (isset($data['precio_costo']) && $data['precio_costo'] < 0) {
                    $data['precio_costo'] = 0;
                }
                if (isset($data['precio_costo_bs'])) {
                    unset($data['precio_costo_bs']);
                }
            }

            // Validar stock
            if (isset($data['stock_actual'])) {
                $stock_actual = floatval($data['stock_actual']);
                if ($stock_actual < 0) {
                    throw new Exception("El stock no puede ser negativo");
                }
                $data['stock_actual'] = $stock_actual;

                // Registrar en historial si cambió
                if ($producto_actual['stock_actual'] != $stock_actual) {
                    $diferencia = $stock_actual - $producto_actual['stock_actual'];
                    $historialData = [
                        'producto_id' => $id,
                        'cantidad_anterior' => $producto_actual['stock_actual'],
                        'cantidad_nueva' => $stock_actual,
                        'diferencia' => $diferencia,
                        'tipo_movimiento' => 'Edición de Productos',
                        'observaciones' => 'Ajuste manual desde edición de producto',
                        'usuario_id' => $_SESSION['usuario_id'] ?? 1
                    ];
                    $this->registrarCambioStock($historialData);
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

    private function registrarCambioStock($data)
    {
        try {
            $query = "INSERT INTO historial_stock 
                      (producto_id, cantidad_anterior, cantidad_nueva, diferencia, 
                       tipo_movimiento, observaciones, usuario_id) 
                      VALUES 
                      (:producto_id, :cantidad_anterior, :cantidad_nueva, :diferencia,
                       :tipo_movimiento, :observaciones, :usuario_id)";

            $stmt = $this->db->prepare($query);

            $stmt->bindParam(":producto_id", $data['producto_id']);
            $stmt->bindParam(":cantidad_anterior", $data['cantidad_anterior']);
            $stmt->bindParam(":cantidad_nueva", $data['cantidad_nueva']);
            $stmt->bindParam(":diferencia", $data['diferencia']);
            $stmt->bindParam(":tipo_movimiento", $data['tipo_movimiento']);
            $stmt->bindParam(":observaciones", $data['observaciones']);
            $stmt->bindParam(":usuario_id", $data['usuario_id']);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error al registrar cambio de stock: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar($id)
    {
        try {
            $producto = $this->producto->obtenerPorId($id);
            if (!$producto) {
                throw new Exception("Producto no encontrado");
            }

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
            $tasaCambio = $this->obtenerTasaCambioActual();

            $query = "SELECT 
                        p.id, p.codigo_sku, p.nombre, p.descripcion, 
                        p.precio, p.precio_bs, p.usar_precio_fijo_bs,
                        p.activo, p.categoria_id, c.nombre as categoria_nombre,
                        p.stock_actual, p.tipo_venta, p.unidad_medida,
                        p.precio_por_kilo_usd, p.precio_por_kilo_bs
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
                if ($row['tipo_venta'] === 'peso') {
                    if ($row['usar_precio_fijo_bs'] && $row['precio_por_kilo_bs'] > 0) {
                        $row['precio_bs_mostrar'] = floatval($row['precio_por_kilo_bs']);
                    } else {
                        $precio_kilo_usd = $row['precio_por_kilo_usd'] > 0 ? $row['precio_por_kilo_usd'] : $row['precio'];
                        $row['precio_bs_mostrar'] = floatval($precio_kilo_usd) * $tasaCambio;
                    }
                    $row['precio_usd_mostrar'] = floatval($row['precio_por_kilo_usd'] > 0 ? $row['precio_por_kilo_usd'] : $row['precio']);
                } else {
                    if ($row['usar_precio_fijo_bs'] && $row['precio_bs'] > 0) {
                        $row['precio_bs_mostrar'] = floatval($row['precio_bs']);
                    } else {
                        $row['precio_bs_mostrar'] = floatval($row['precio']) * $tasaCambio;
                    }
                    $row['precio_usd_mostrar'] = floatval($row['precio']);
                }

                $row['stock_actual'] = floatval($row['stock_actual']);
                $row['precio'] = floatval($row['precio']);
                $row['precio_bs'] = floatval($row['precio_bs']);
                $row['usar_precio_fijo_bs'] = (bool)$row['usar_precio_fijo_bs'];
                $row['activo'] = (bool)$row['activo'];
                $row['tipo_venta'] = $row['tipo_venta'] ?? 'unidad';

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

            return $result ? floatval($result['tasa_cambio']) : 35.00;
        } catch (Exception $e) {
            return 35.00;
        }
    }

    public function obtenerProductosConInfoCompleta()
    {
        try {
            $productos = $this->producto->obtenerProductosConInfoCompleta();
            $tasaCambio = $this->obtenerTasaCambioActual();

            foreach ($productos as &$producto) {
                if ($producto['tipo_venta'] === 'peso') {
                    if (!$producto['usar_precio_fijo_bs'] || $producto['precio_por_kilo_bs'] <= 0) {
                        $precio_kilo_usd = $producto['precio_por_kilo_usd'] > 0 ? $producto['precio_por_kilo_usd'] : $producto['precio'];
                        $producto['precio_bs_mostrar'] = floatval($precio_kilo_usd) * $tasaCambio;
                    } else {
                        $producto['precio_bs_mostrar'] = floatval($producto['precio_por_kilo_bs']);
                    }
                    $producto['precio_usd_mostrar'] = floatval($producto['precio_por_kilo_usd'] > 0 ? $producto['precio_por_kilo_usd'] : $producto['precio']);
                } else {
                    if (!$producto['usar_precio_fijo_bs'] || $producto['precio_bs'] <= 0) {
                        $producto['precio_bs_mostrar'] = floatval($producto['precio']) * $tasaCambio;
                    } else {
                        $producto['precio_bs_mostrar'] = floatval($producto['precio_bs']);
                    }
                    $producto['precio_usd_mostrar'] = floatval($producto['precio']);
                }

                $producto['stock_actual'] = floatval($producto['stock_actual']);
                $producto['precio'] = floatval($producto['precio']);
                $producto['precio_bs'] = floatval($producto['precio_bs']);
                $producto['usar_precio_fijo_bs'] = (bool)$producto['usar_precio_fijo_bs'];
                $producto['activo'] = (bool)$producto['activo'];
                $producto['tipo_venta'] = $producto['tipo_venta'] ?? 'unidad';
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
                        SUM(CASE WHEN tipo_venta = 'peso' THEN 1 ELSE 0 END) as productos_por_peso,
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

    public function calcularPrecioPorPeso($producto_id, $gramos)
    {
        try {
            $tasa_cambio = $this->obtenerTasaCambioActual();
            $resultado = $this->producto->calcularPrecioPorGramos($producto_id, $gramos, $tasa_cambio);

            if ($resultado['success']) {
                return [
                    "success" => true,
                    "data" => [
                        'producto' => $resultado['producto'],
                        'gramos' => $resultado['gramos'],
                        'kilos' => $resultado['kilos'],
                        'precio_usd' => $resultado['precio_usd'],
                        'precio_bs' => $resultado['precio_bs'],
                        'precio_kilo_usd' => $resultado['precio_kilo_usd'],
                        'precio_kilo_bs' => $resultado['precio_kilo_bs']
                    ]
                ];
            }

            return $resultado;
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al calcular precio: " . $e->getMessage()
            ];
        }
    }

    public function obtenerProductosPorPesoParaVenta()
    {
        try {
            $productos = $this->producto->obtenerProductosPorPeso();
            $tasa_cambio = $this->obtenerTasaCambioActual();

            foreach ($productos as &$producto) {
                if (!$producto['usar_precio_fijo_bs'] || $producto['precio_por_kilo_bs'] <= 0) {
                    $precio_kilo_usd = $producto['precio_por_kilo_usd'] > 0 ? $producto['precio_por_kilo_usd'] : $producto['precio'];
                    $producto['precio_por_kilo_bs'] = $precio_kilo_usd * $tasa_cambio;
                }
                $producto['precio_por_kilo_usd'] = floatval($producto['precio_por_kilo_usd'] > 0 ? $producto['precio_por_kilo_usd'] : $producto['precio']);
                $producto['stock_actual_kg'] = floatval($producto['stock_actual']);
            }

            return [
                "success" => true,
                "data" => $productos,
                "tasa_cambio" => $tasaCambio
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener productos por peso: " . $e->getMessage()
            ];
        }
    }
}
