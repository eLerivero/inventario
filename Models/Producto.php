<?php
// Models/Producto.php
class Producto
{
    private $conn;
    private $table = "productos";

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

    public function crearOLD($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                (codigo_sku, nombre, descripcion, precio, precio_bs, precio_costo, precio_costo_bs, 
                usar_precio_fijo_bs, stock_actual, stock_minimo, categoria_id, activo) 
                VALUES 
                (:codigo_sku, :nombre, :descripcion, :precio, :precio_bs, :precio_costo, :precio_costo_bs,
                :usar_precio_fijo_bs, :stock_actual, :stock_minimo, :categoria_id, :activo)";

        $stmt = $this->conn->prepare($query);

        // MODIFICACIÓN: Manejar lógica según tipo de precio
        $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;

        if ($usarPrecioFijo) {
            // Para precio fijo en BS
            // Mantener el precio_bs tal como viene
            $precio_bs = $data['precio_bs'] ?? 0;
            $precio = $data['precio'] ?? 0;

            // Para precio fijo, no calcular precios en BS basados en tasa
            // Los precios en BS se mantienen fijos
            $precio_costo_bs = $data['precio_costo_bs'] ?? 0;
            $precio_costo = $data['precio_costo'] ?? 0;
        } else {
            // Para precio NO fijo
            $precio = $data['precio'] ?? 0;

            // Obtener tasa de cambio para calcular precios en BS
            $tasa_cambio = $this->obtenerTasaCambio();

            // Calcular precio_bs automáticamente
            $precio_bs = round($precio * $tasa_cambio, 2);

            $precio_costo = $data['precio_costo'] ?? 0;
            // Calcular precio_costo_bs automáticamente
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        }

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

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "Producto creado exitosamente",
                "id" => $this->conn->lastInsertId()
            ];
        }
        return [
            "success" => false,
            "message" => "Error al crear producto: " . $stmt->errorInfo()[2]
        ];
    }

    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                (codigo_sku, nombre, descripcion, precio, precio_bs, precio_costo, precio_costo_bs, 
                usar_precio_fijo_bs, stock_actual, stock_minimo, categoria_id, activo) 
                VALUES 
                (:codigo_sku, :nombre, :descripcion, :precio, :precio_bs, :precio_costo, :precio_costo_bs,
                :usar_precio_fijo_bs, :stock_actual, :stock_minimo, :categoria_id, :activo)";

        $stmt = $this->conn->prepare($query);

        // MODIFICACIÓN: Manejar lógica según tipo de precio
        $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;

        if ($usarPrecioFijo) {
            // Para precio fijo en BS
            // Mantener el precio_bs tal como viene (obligatorio para fijo)
            $precio_bs = $data['precio_bs'] ?? 0;
            // Precio USD puede ser 0 (opcional para fijo)
            $precio = $data['precio'] ?? 0;
            
            // Para precio fijo, el costo en USD puede ser 0 (opcional)
            $precio_costo = $data['precio_costo'] ?? 0;
            
            // Para precio fijo, el costo en BS se calcula con tasa actual
            $tasa_cambio = $this->obtenerTasaCambio();
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        } else {
            // Para precio NO fijo
            $precio = $data['precio'] ?? 0;

            // Obtener tasa de cambio para calcular precios en BS
            $tasa_cambio = $this->obtenerTasaCambio();

            // Calcular precio_bs automáticamente
            $precio_bs = round($precio * $tasa_cambio, 2);

            $precio_costo = $data['precio_costo'] ?? 0;
            // Calcular precio_costo_bs automáticamente
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        }

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

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "Producto creado exitosamente",
                "id" => $this->conn->lastInsertId()
            ];
        }
        return [
            "success" => false,
            "message" => "Error al crear producto: " . $stmt->errorInfo()[2]
        ];
    }

    public function actualizar($id, $data)
{
    // Construir la consulta dinámicamente basada en los campos proporcionados
    $fields = [];
    $params = [':id' => $id];
    
    // Campos que se pueden actualizar (todos excepto id y codigo_sku)
    // ¡AHORA INCLUYE stock_actual!
    $camposPermitidos = [
        'nombre', 'descripcion', 'precio', 'precio_bs', 
        'precio_costo', 'precio_costo_bs', 'usar_precio_fijo_bs',
        'stock_actual', 'stock_minimo', 'categoria_id', 'activo'
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
    
    // Asignar parámetros
    foreach ($params as $key => $value) {
        // Manejar tipos de datos
        if (is_bool($value)) {
            $stmt->bindValue($key, $value, PDO::PARAM_BOOL);
        } else if (is_int($value)) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else if (is_float($value)) {
            // Para valores decimales, usar string para mantener precisión
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
