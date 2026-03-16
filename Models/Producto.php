<?php
// Models/Producto.php
class Producto
{
    private $conn;
    private $table = "productos";

    // Propiedades existentes
    public $id;
    public $codigo_sku;
    public $nombre;
    public $descripcion;
    public $precio;
    public $precio_bs;
    public $precio_costo;
    public $precio_costo_bs;
    public $moneda_base;
    public $usar_precio_fijo_bs;
    public $stock_actual;
    public $stock_minimo;
    public $categoria_id;
    public $activo;
    public $created_at;
    public $updated_at;

    // NUEVAS PROPIEDADES para venta por peso
    public $tipo_venta;           // 'unidad' o 'peso'
    public $unidad_medida;         // 'kg', 'g', 'lb'
    public $precio_por_kilo_usd;
    public $precio_por_kilo_bs;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function leer()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  ORDER BY p.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                (codigo_sku, nombre, descripcion, precio, precio_bs, precio_costo, precio_costo_bs, 
                usar_precio_fijo_bs, stock_actual, stock_minimo, categoria_id, activo,
                tipo_venta, unidad_medida, precio_por_kilo_usd, precio_por_kilo_bs) 
                VALUES 
                (:codigo_sku, :nombre, :descripcion, :precio, :precio_bs, :precio_costo, :precio_costo_bs,
                :usar_precio_fijo_bs, :stock_actual, :stock_minimo, :categoria_id, :activo,
                :tipo_venta, :unidad_medida, :precio_por_kilo_usd, :precio_por_kilo_bs)";

        $stmt = $this->conn->prepare($query);

        // Valores por defecto para nuevos campos
        $tipo_venta = $data['tipo_venta'] ?? 'unidad';
        $unidad_medida = $data['unidad_medida'] ?? 'kg';
        $precio_por_kilo_usd = $data['precio_por_kilo_usd'] ?? 0;
        $precio_por_kilo_bs = $data['precio_por_kilo_bs'] ?? 0;

        // Lógica de precios existente
        $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;

        if ($usarPrecioFijo) {
            $precio_bs = $data['precio_bs'] ?? 0;
            $precio = $data['precio'] ?? 0;
            $precio_costo = $data['precio_costo'] ?? 0;
            $tasa_cambio = $this->obtenerTasaCambio();
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        } else {
            $precio = $data['precio'] ?? 0;
            $tasa_cambio = $this->obtenerTasaCambio();
            $precio_bs = round($precio * $tasa_cambio, 2);
            $precio_costo = $data['precio_costo'] ?? 0;
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        }

        // Bind de parámetros existentes
        $stmt->bindParam(":codigo_sku", $data['codigo_sku']);
        $stmt->bindParam(":nombre", $data['nombre']);
        $stmt->bindParam(":descripcion", $data['descripcion']);
        $stmt->bindParam(":precio", $precio);
        $stmt->bindParam(":precio_bs", $precio_bs);
        $stmt->bindParam(":precio_costo", $precio_costo);
        $stmt->bindParam(":precio_costo_bs", $precio_costo_bs);
        $stmt->bindParam(":usar_precio_fijo_bs", $usarPrecioFijo, PDO::PARAM_BOOL);

        $stock_actual = $data['stock_actual'] ?? 0;
        $stmt->bindParam(":stock_actual", $stock_actual);

        $stock_minimo = $data['stock_minimo'] ?? 5;
        $stmt->bindParam(":stock_minimo", $stock_minimo);

        $categoria_id = $data['categoria_id'] ?? null;
        $stmt->bindParam(":categoria_id", $categoria_id);

        $activo = isset($data['activo']) ? (bool)$data['activo'] : true;
        $stmt->bindParam(":activo", $activo, PDO::PARAM_BOOL);

        // NUEVOS binds
        $stmt->bindParam(":tipo_venta", $tipo_venta);
        $stmt->bindParam(":unidad_medida", $unidad_medida);
        $stmt->bindParam(":precio_por_kilo_usd", $precio_por_kilo_usd);
        $stmt->bindParam(":precio_por_kilo_bs", $precio_por_kilo_bs);

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "Producto creado exitosamente",
                "id" => $this->conn->lastInsertId()
            ];
        }

        error_log("Error en Producto->crear(): " . print_r($stmt->errorInfo(), true));
        return [
            "success" => false,
            "message" => "Error al crear producto: " . $stmt->errorInfo()[2]
        ];
    }

    public function actualizar($id, $data)
    {
        // Construir la consulta dinámicamente
        $fields = [];
        $params = [':id' => $id];

        // Campos permitidos (incluyendo los nuevos)
        $camposPermitidos = [
            'nombre',
            'descripcion',
            'precio',
            'precio_bs',
            'precio_costo',
            'precio_costo_bs',
            'usar_precio_fijo_bs',
            'stock_actual',
            'stock_minimo',
            'categoria_id',
            'activo',
            'tipo_venta',
            'unidad_medida',
            'precio_por_kilo_usd',
            'precio_por_kilo_bs'
        ];

        foreach ($camposPermitidos as $campo) {
            if (isset($data[$campo])) {
                $fields[] = "$campo = :$campo";
                $params[":$campo"] = $data[$campo];
            }
        }

        // Añadir fecha de actualización
        $fields[] = "updated_at = CURRENT_TIMESTAMP";

        if (empty($fields)) {
            return [
                "success" => false,
                "message" => "No hay campos para actualizar"
            ];
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_BOOL);
            } else if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else if (is_float($value)) {
                $stmt->bindValue($key, (string)$value, PDO::PARAM_STR);
            } else if (is_null($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue($key, $value);
            }
        }

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "Producto actualizado exitosamente"
            ];
        }

        error_log("Error en Producto->actualizar(): " . print_r($stmt->errorInfo(), true));
        return [
            "success" => false,
            "message" => "Error al actualizar producto: " . $stmt->errorInfo()[2]
        ];
    }

    public function obtenerPorId($id)
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function obtenerPorSku($sku)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE codigo_sku = :sku";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sku", $sku);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function actualizarStock($producto_id, $nuevo_stock, $tipo_movimiento = 'ajuste', $observaciones = '')
    {
        $producto = $this->obtenerPorId($producto_id);
        if (!$producto) {
            return [
                "success" => false,
                "message" => "Producto no encontrado"
            ];
        }

        $diferencia = $nuevo_stock - $producto['stock_actual'];

        $query = "UPDATE " . $this->table . " SET stock_actual = :stock_actual WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":stock_actual", $nuevo_stock);
        $stmt->bindParam(":id", $producto_id);

        if ($stmt->execute()) {
            // Registrar en historial de stock
            $historial_query = "INSERT INTO historial_stock 
                            (producto_id, cantidad_anterior, cantidad_nueva, diferencia, 
                                tipo_movimiento, observaciones, usuario_id) 
                            VALUES 
                            (:producto_id, :cantidad_anterior, :cantidad_nueva, :diferencia,
                                :tipo_movimiento, :observaciones, :usuario_id)";

            $historial_stmt = $this->conn->prepare($historial_query);

            $cantidad_anterior = $producto['stock_actual'];
            $cantidad_nueva = $nuevo_stock;
            $observaciones_final = $observaciones ?: 'Actualización de stock';
            $usuario_id = isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 1;

            $historial_stmt->bindParam(":producto_id", $producto_id);
            $historial_stmt->bindParam(":cantidad_anterior", $cantidad_anterior);
            $historial_stmt->bindParam(":cantidad_nueva", $cantidad_nueva);
            $historial_stmt->bindParam(":diferencia", $diferencia);
            $historial_stmt->bindParam(":tipo_movimiento", $tipo_movimiento);
            $historial_stmt->bindParam(":observaciones", $observaciones_final);
            $historial_stmt->bindParam(":usuario_id", $usuario_id);

            $historial_stmt->execute();

            return [
                "success" => true,
                "message" => "Stock actualizado exitosamente"
            ];
        }

        return [
            "success" => false,
            "message" => "Error al actualizar stock: " . $stmt->errorInfo()[2]
        ];
    }

    public function eliminar($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "Producto eliminado exitosamente"
            ];
        }
        return [
            "success" => false,
            "message" => "Error al eliminar producto"
        ];
    }

    public function obtenerProductosConPrecioFijo()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.usar_precio_fijo_bs = TRUE AND p.activo = TRUE
                  ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = $row;
        }

        return $productos;
    }

    public function obtenerProductosActivos()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.activo = TRUE
                  ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = $row;
        }

        return $productos;
    }

    public function obtenerProductosBajoStock()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                FROM " . $this->table . " p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.stock_actual <= p.stock_minimo AND p.activo = TRUE
                ORDER BY p.stock_actual ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = $row;
        }

        return $productos;
    }

    public function obtenerProductosPorPeso()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p
                  LEFT JOIN categorias c ON p.categoria_id = c.id
                  WHERE p.tipo_venta = 'peso' AND p.activo = TRUE
                  ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = $row;
        }

        return $productos;
    }

    public function calcularPrecioPorGramos($producto_id, $gramos, $tasa_cambio = null)
    {
        $producto = $this->obtenerPorId($producto_id);
        if (!$producto) {
            return [
                'success' => false,
                'message' => 'Producto no encontrado'
            ];
        }

        if ($producto['tipo_venta'] !== 'peso') {
            return [
                'success' => false,
                'message' => 'El producto no está configurado para venta por peso'
            ];
        }

        if ($tasa_cambio === null) {
            $tasa_cambio = $this->obtenerTasaCambio();
        }

        $kilos = $gramos / 1000;

        $precio_kilo_usd = $producto['precio_por_kilo_usd'] > 0 ? $producto['precio_por_kilo_usd'] : $producto['precio'];
        $precio_kilo_bs = $producto['precio_por_kilo_bs'] > 0 ? $producto['precio_por_kilo_bs'] : ($producto['usar_precio_fijo_bs'] ? $producto['precio_bs'] : $precio_kilo_usd * $tasa_cambio);

        $precio_usd = $precio_kilo_usd * $kilos;
        $precio_bs = $precio_kilo_bs * $kilos;

        return [
            'success' => true,
            'producto' => $producto,
            'gramos' => $gramos,
            'kilos' => $kilos,
            'precio_kilo_usd' => $precio_kilo_usd,
            'precio_kilo_bs' => $precio_kilo_bs,
            'precio_usd' => round($precio_usd, 2),
            'precio_bs' => round($precio_bs, 2),
            'tasa_cambio' => $tasa_cambio
        ];
    }

    public function actualizarStockPorPeso($producto_id, $kilos, $tipo_movimiento, $observaciones = '')
    {
        $producto = $this->obtenerPorId($producto_id);
        if (!$producto) {
            return [
                "success" => false,
                "message" => "Producto no encontrado"
            ];
        }

        $nuevo_stock = $producto['stock_actual'] - $kilos;

        if ($nuevo_stock < 0) {
            return [
                "success" => false,
                "message" => "Stock insuficiente. Disponible: " . $producto['stock_actual'] . " kg"
            ];
        }

        return $this->actualizarStock($producto_id, $nuevo_stock, $tipo_movimiento, $observaciones);
    }

    private function obtenerTasaCambio()
    {
        $query = "SELECT tasa_cambio FROM tasas_cambio WHERE activa = TRUE ORDER BY fecha_actualizacion DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['tasa_cambio'] : 1;
    }

    public function obtenerProductosConInfoCompleta()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                FROM " . $this->table . " p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.activo = TRUE
                ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $productos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $productos[] = $row;
        }

        return $productos;
    }
}
