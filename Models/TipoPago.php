<?php
class TipoPago
{
    private $conn;
    private $table = "tipos_pago";

    public $id;
    public $nombre;
    public $descripcion;
    public $activo;
    public $created_at;
    public $updated_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Lee todos los tipos de pago (incluyendo inactivos)
     * @return array
     */
    public function leer()
    {
        try {
            $query = "SELECT * FROM " . $this->table . " ORDER BY nombre";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en TipoPago::leer: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los tipos de pago activos
     * @return array
     */
    public function obtenerActivos()
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE activo = true ORDER BY nombre";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en TipoPago::obtenerActivos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un tipo de pago por su ID
     * @param int $id
     * @return array|false
     */
    public function obtenerPorId($id)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Asignar propiedades
                $this->id = $row['id'];
                $this->nombre = $row['nombre'];
                $this->descripcion = $row['descripcion'];
                $this->activo = $row['activo'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'] ?? null;
                
                return $row;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error en TipoPago::obtenerPorId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea un nuevo tipo de pago
     * @return int|false
     */
    public function crear()
    {
        try {
            $query = "INSERT INTO " . $this->table . " 
                     (nombre, descripcion, activo, created_at) 
                     VALUES 
                     (:nombre, :descripcion, :activo, NOW())";

            $stmt = $this->conn->prepare($query);

            // Limpiar y sanitizar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

            $stmt->bindParam(":nombre", $this->nombre);
            $stmt->bindParam(":descripcion", $this->descripcion);
            $stmt->bindParam(":activo", $this->activo, PDO::PARAM_BOOL);

            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
            return false;
        } catch (Exception $e) {
            error_log("Error en TipoPago::crear: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un tipo de pago existente
     * @param int $id
     * @return bool
     */
    public function actualizar($id)
    {
        try {
            $query = "UPDATE " . $this->table . " 
                     SET nombre = :nombre, 
                         descripcion = :descripcion, 
                         activo = :activo,
                         updated_at = NOW()
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);

            // Limpiar y sanitizar datos
            $this->nombre = htmlspecialchars(strip_tags($this->nombre));
            $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

            $stmt->bindParam(":nombre", $this->nombre);
            $stmt->bindParam(":descripcion", $this->descripcion);
            $stmt->bindParam(":activo", $this->activo, PDO::PARAM_BOOL);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error en TipoPago::actualizar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un tipo de pago
     * @param int $id
     * @return bool
     */
    public function eliminar($id)
    {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error en TipoPago::eliminar: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si ya existe un tipo de pago con el mismo nombre
     * @param string $nombre
     * @param int|null $excluirId ID a excluir de la verificación
     * @return bool
     */
    public function verificarNombreExistente($nombre, $excluirId = null)
    {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE nombre = :nombre";
            
            if ($excluirId) {
                $query .= " AND id != :excluir_id";
            }

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            
            if ($excluirId) {
                $stmt->bindParam(":excluir_id", $excluirId, PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total'] > 0;
        } catch (Exception $e) {
            error_log("Error en TipoPago::verificarNombreExistente: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Busca tipos de pago por término en nombre o descripción
     * @param string $termino
     * @return array
     */
    public function buscar($termino)
    {
        try {
            $query = "SELECT * FROM " . $this->table . " 
                     WHERE (nombre ILIKE :termino OR descripcion ILIKE :termino) 
                     ORDER BY nombre";
            
            $stmt = $this->conn->prepare($query);
            $terminoBusqueda = "%{$termino}%";
            $stmt->bindParam(":termino", $terminoBusqueda);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en TipoPago::buscar: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cuenta el total de tipos de pago
     * @return int
     */
    public function contarTotal()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table;
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (Exception $e) {
            error_log("Error en TipoPago::contarTotal: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cuenta el total de tipos de pago activos
     * @return int
     */
    public function contarActivos()
    {
        try {
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE activo = true";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (Exception $e) {
            error_log("Error en TipoPago::contarActivos: " . $e->getMessage());
            return 0;
        }
    }
}