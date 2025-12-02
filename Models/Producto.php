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

    public function crear($data)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (codigo_sku, nombre, descripcion, precio, precio_bs, precio_costo, precio_costo_bs, 
                   usar_precio_fijo_bs, stock_actual, stock_minimo, categoria_id, activo) 
                  VALUES 
                  (:codigo_sku, :nombre, :descripcion, :precio, :precio_bs, :precio_costo, :precio_costo_bs,
                   :usar_precio_fijo_bs, :stock_actual, :stock_minimo, :categoria_id, :activo)";

        $stmt = $this->conn->prepare($query);

        // Obtener tasa de cambio actual para calcular precios en BS si no son fijos
        $tasa_cambio = $this->obtenerTasaCambio();

        // Calcular precios en BS si no se proporcionan y no es precio fijo
        if (empty($data['precio_bs']) && (!isset($data['usar_precio_fijo_bs']) || !$data['usar_precio_fijo_bs'])) {
            $data['precio_bs'] = round($data['precio'] * $tasa_cambio, 2);
        }

        // Asignar valores por defecto a variables
        $precio_costo = $data['precio_costo'] ?? 0;
        $precio_costo_bs = $data['precio_costo_bs'] ?? 0;
        
        // Si no se proporciona precio_costo_bs, calcularlo
        if (empty($precio_costo_bs) && (!isset($data['usar_precio_fijo_bs']) || !$data['usar_precio_fijo_bs'])) {
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        }

        $stmt->bindParam(":codigo_sku", $data['codigo_sku']);
        $stmt->bindParam(":nombre", $data['nombre']);
        $stmt->bindParam(":descripcion", $data['descripcion']);
        $stmt->bindParam(":precio", $data['precio']);
        $stmt->bindParam(":precio_bs", $data['precio_bs']);
        $stmt->bindParam(":precio_costo", $precio_costo);
        $stmt->bindParam(":precio_costo_bs", $precio_costo_bs);
        
        $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;
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
        $query = "UPDATE " . $this->table . " SET
                  nombre = :nombre,
                  descripcion = :descripcion,
                  precio = :precio,
                  precio_bs = :precio_bs,
                  precio_costo = :precio_costo,
                  precio_costo_bs = :precio_costo_bs,
                  usar_precio_fijo_bs = :usar_precio_fijo_bs,
                  stock_minimo = :stock_minimo,
                  categoria_id = :categoria_id,
                  activo = :activo
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Si no es precio fijo y no se proporciona precio_bs, calcularlo
        if ((!isset($data['usar_precio_fijo_bs']) || !$data['usar_precio_fijo_bs']) && empty($data['precio_bs'])) {
            $tasa_cambio = $this->obtenerTasaCambio();
            $data['precio_bs'] = round($data['precio'] * $tasa_cambio, 2);
        }

        // Asignar valores por defecto a variables
        $precio_costo = $data['precio_costo'] ?? 0;
        $precio_costo_bs = $data['precio_costo_bs'] ?? 0;
        
        // Si no se proporciona precio_costo_bs, calcularlo
        if (empty($precio_costo_bs) && (!isset($data['usar_precio_fijo_bs']) || !$data['usar_precio_fijo_bs'])) {
            $precio_costo_bs = round($precio_costo * $tasa_cambio, 2);
        }

        $stmt->bindParam(":nombre", $data['nombre']);
        $stmt->bindParam(":descripcion", $data['descripcion']);
        $stmt->bindParam(":precio", $data['precio']);
        $stmt->bindParam(":precio_bs", $data['precio_bs']);
        $stmt->bindParam(":precio_costo", $precio_costo);
        $stmt->bindParam(":precio_costo_bs", $precio_costo_bs);
        
        $usarPrecioFijo = isset($data['usar_precio_fijo_bs']) ? (bool)$data['usar_precio_fijo_bs'] : false;
        $stmt->bindParam(":usar_precio_fijo_bs", $usarPrecioFijo, PDO::PARAM_BOOL);
        
        $stock_minimo = $data['stock_minimo'] ?? 5;
        $stmt->bindParam(":stock_minimo", $stock_minimo);
        
        $categoria_id = $data['categoria_id'] ?? null;
        $stmt->bindParam(":categoria_id", $categoria_id);
        
        $activo = isset($data['activo']) ? (bool)$data['activo'] : true;
        $stmt->bindParam(":activo", $activo, PDO::PARAM_BOOL);
        
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return [
                "success" => true,
                "message" => "Producto actualizado exitosamente"
            ];
        }
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
            // Registrar en historial - VERSIÓN CORREGIDA
            $historial_query = "INSERT INTO historial_stock 
                               (producto_id, cantidad_anterior, cantidad_nueva, diferencia, 
                                tipo_movimiento, observaciones, usuario) 
                               VALUES 
                               (:producto_id, :cantidad_anterior, :cantidad_nueva, :diferencia,
                                :tipo_movimiento, :observaciones, :usuario)";

            $historial_stmt = $this->conn->prepare($historial_query);
            
            // Asignar a variables primero para evitar problemas de referencia
            $cantidad_anterior = $producto['stock_actual'];
            $cantidad_nueva = $nuevo_stock;
            $observaciones_final = $observaciones ?: 'Actualización de stock';
            $usuario = isset($_SESSION['usuario_nombre']) ? $_SESSION['usuario_nombre'] : 'Sistema';
            
            $historial_stmt->bindParam(":producto_id", $producto_id);
            $historial_stmt->bindParam(":cantidad_anterior", $cantidad_anterior);
            $historial_stmt->bindParam(":cantidad_nueva", $cantidad_nueva);
            $historial_stmt->bindParam(":diferencia", $diferencia);
            $historial_stmt->bindParam(":tipo_movimiento", $tipo_movimiento);
            $historial_stmt->bindParam(":observaciones", $observaciones_final);
            $historial_stmt->bindParam(":usuario", $usuario);

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