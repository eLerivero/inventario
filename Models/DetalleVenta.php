<?php
class DetalleVenta
{
    private $conn;
    private $table = "detalle_ventas";

    public $id;
    public $venta_id;
    public $producto_id;
    public $cantidad;
    public $precio_unitario;
    public $subtotal;
    public $created_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function crear()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                  VALUES 
                  (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":venta_id", $this->venta_id);
        $stmt->bindParam(":producto_id", $this->producto_id);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $stmt->bindParam(":precio_unitario", $this->precio_unitario);
        $stmt->bindParam(":subtotal", $this->subtotal);

        return $stmt->execute();
    }

    public function crearMultiple($detalles)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                  VALUES ";

        $values = [];
        $params = [];

        foreach ($detalles as $index => $detalle) {
            $values[] = "(:venta_id, :producto_id_$index, :cantidad_$index, :precio_unitario_$index, :subtotal_$index)";

            $params[":producto_id_$index"] = $detalle['producto_id'];
            $params[":cantidad_$index"] = $detalle['cantidad'];
            $params[":precio_unitario_$index"] = $detalle['precio_unitario'];
            $params[":subtotal_$index"] = $detalle['subtotal'];
        }

        $query .= implode(', ', $values);
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":venta_id", $this->venta_id);

        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }

        return $stmt->execute();
    }
}
