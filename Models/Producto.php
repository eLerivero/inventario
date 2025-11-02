<?php
require_once __DIR__ . '/../Utils/Ayuda.php';

class Producto
{
    private $conn;
    private $table = "productos";

    public $id;
    public $codigo_sku;
    public $nombre;
    public $descripcion;
    public $precio;
    public $precio_costo;
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
                  WHERE p.activo = true 
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscar($search)
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.activo = true 
                  AND (p.nombre ILIKE :search OR p.codigo_sku ILIKE :search OR p.descripcion ILIKE :search)
                  ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $search = "%" . $search . "%";
        $stmt->bindParam(":search", $search);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorId($id)
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM " . $this->table . " p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.id = :id AND p.activo = true";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function crear()
    {
        try {
            // Validar que tenemos un SKU (siempre requerido manualmente)
            if (empty($this->codigo_sku)) {
                throw new Exception("El código SKU es requerido");
            }

            // Verificar si el SKU ya existe
            if ($this->existeSKU($this->codigo_sku)) {
                throw new Exception("El código SKU '{$this->codigo_sku}' ya existe en el sistema");
            }

            $query = "INSERT INTO " . $this->table . " 
                      (codigo_sku, nombre, descripcion, precio, precio_costo, stock_actual, stock_minimo, categoria_id) 
                      VALUES 
                      (:codigo_sku, :nombre, :descripcion, :precio, :precio_costo, :stock_actual, :stock_minimo, :categoria_id)
                      RETURNING id";

            $stmt = $this->conn->prepare($query);

            // Limpiar datos
            $this->codigo_sku = Ayuda::sanitizeInput($this->codigo_sku);
            $this->nombre = Ayuda::sanitizeInput($this->nombre);
            $this->descripcion = Ayuda::sanitizeInput($this->descripcion);

            // Validar datos requeridos
            if (empty($this->nombre)) {
                throw new Exception("El nombre del producto es requerido");
            }

            if (empty($this->precio) || $this->precio < 0) {
                throw new Exception("El precio debe ser un valor positivo");
            }

            // Vincular parámetros
            $stmt->bindParam(":codigo_sku", $this->codigo_sku);
            $stmt->bindParam(":nombre", $this->nombre);
            $stmt->bindParam(":descripcion", $this->descripcion);
            $stmt->bindParam(":precio", $this->precio);
            $stmt->bindParam(":precio_costo", $this->precio_costo);
            $stmt->bindParam(":stock_actual", $this->stock_actual);
            $stmt->bindParam(":stock_minimo", $this->stock_minimo);
            $stmt->bindParam(":categoria_id", $this->categoria_id);

            if ($stmt->execute()) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $producto_id = $row['id'];

                // Registrar en historial de stock
                $this->registrarMovimientoStock(
                    $producto_id,
                    0,
                    $this->stock_actual,
                    $this->stock_actual,
                    'entrada',
                    'Stock inicial'
                );

                return $producto_id;
            } else {
                throw new Exception("Error al ejecutar la consulta de inserción");
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    private function existeSKU($sku)
    {
        $query = "SELECT id FROM productos WHERE codigo_sku = :sku AND activo = true";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sku", $sku);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    public function actualizar($id)
    {
        $query = "UPDATE " . $this->table . " 
                  SET nombre = :nombre, descripcion = :descripcion, precio = :precio, 
                      precio_costo = :precio_costo, stock_minimo = :stock_minimo, 
                      categoria_id = :categoria_id, updated_at = CURRENT_TIMESTAMP
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->nombre = Ayuda::sanitizeInput($this->nombre);
        $this->descripcion = Ayuda::sanitizeInput($this->descripcion);

        // Validaciones
        if (empty($this->nombre)) {
            throw new Exception("El nombre del producto es requerido");
        }

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":precio", $this->precio);
        $stmt->bindParam(":precio_costo", $this->precio_costo);
        $stmt->bindParam(":stock_minimo", $this->stock_minimo);
        $stmt->bindParam(":categoria_id", $this->categoria_id);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function actualizarStock($producto_id, $nueva_cantidad, $tipo_movimiento = 'ajuste', $observaciones = '')
    {
        // Obtener stock actual
        $query = "SELECT stock_actual FROM productos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $producto_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $cantidad_anterior = $row['stock_actual'];
            $diferencia = $nueva_cantidad - $cantidad_anterior;

            // Actualizar stock
            $query_update = "UPDATE productos SET stock_actual = :stock_actual WHERE id = :id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(":stock_actual", $nueva_cantidad);
            $stmt_update->bindParam(":id", $producto_id);

            if ($stmt_update->execute()) {
                // Registrar en historial
                $this->registrarMovimientoStock(
                    $producto_id,
                    $cantidad_anterior,
                    $nueva_cantidad,
                    $diferencia,
                    $tipo_movimiento,
                    $observaciones
                );

                return true;
            }
        }
        return false;
    }

    private function registrarMovimientoStock($producto_id, $cantidad_anterior, $cantidad_nueva, $diferencia, $tipo_movimiento, $observaciones)
    {
        $query = "INSERT INTO historial_stock 
                  (producto_id, cantidad_anterior, cantidad_nueva, diferencia, tipo_movimiento, observaciones, fecha_hora, usuario) 
                  VALUES 
                  (:producto_id, :cantidad_anterior, :cantidad_nueva, :diferencia, :tipo_movimiento, :observaciones, NOW(), :usuario)";

        $stmt = $this->conn->prepare($query);

        // Obtener usuario actual (esto debería venir de la sesión)
        $usuario = 'sistema'; // Por defecto

        $stmt->bindParam(":producto_id", $producto_id);
        $stmt->bindParam(":cantidad_anterior", $cantidad_anterior);
        $stmt->bindParam(":cantidad_nueva", $cantidad_nueva);
        $stmt->bindParam(":diferencia", $diferencia);
        $stmt->bindParam(":tipo_movimiento", $tipo_movimiento);
        $stmt->bindParam(":observaciones", $observaciones);
        $stmt->bindParam(":usuario", $usuario);

        return $stmt->execute();
    }

    public function obtenerProductosBajoStock()
    {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.stock_actual <= p.stock_minimo AND p.activo = true
                  ORDER BY (p.stock_actual / p.stock_minimo) ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerEstadisticas()
    {
        $query = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(stock_actual) as total_stock,
                    COUNT(CASE WHEN stock_actual <= stock_minimo THEN 1 END) as productos_bajo_stock,
                    AVG(precio) as precio_promedio,
                    SUM(precio * stock_actual) as valor_inventario,
                    COUNT(CASE WHEN stock_actual = 0 THEN 1 END) as productos_sin_stock
                  FROM productos 
                  WHERE activo = true";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function eliminar($id)
    {
        $query = "UPDATE productos SET activo = false WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        return $stmt->execute();
    }

    public function obtenerPorSKU($sku)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE codigo_sku = :sku AND activo = true";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":sku", $sku);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    public function obtenerTodos()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerProductosActivos()
    {
        $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}