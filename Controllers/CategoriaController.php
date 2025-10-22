<?php

require_once '../../Models/Categoria.php';


class CategoriaController
{
    private $categoria;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->categoria = new Categoria($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->categoria->leer();
            $categorias = $stmt->fetchAll();

            appLog('INFO', 'Categorías listadas', ['total' => count($categorias)]);
            return [
                "success" => true,
                "data" => $categorias
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al listar categorías', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener las categorías: " . $e->getMessage()
            ];
        }
    }

    public function obtener($id)
    {
        try {
            $categoria = $this->categoria->obtenerPorId($id);

            if ($categoria) {
                appLog('INFO', 'Categoría obtenida', ['id' => $id]);
                return [
                    "success" => true,
                    "data" => $categoria
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Categoría no encontrada"
                ];
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener categoría', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener la categoría: " . $e->getMessage()
            ];
        }
    }

    public function crear($data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['nombre'])) {
                return [
                    "success" => false,
                    "message" => "El nombre de la categoría es requerido"
                ];
            }

            $this->categoria->nombre = $data['nombre'];
            $this->categoria->descripcion = $data['descripcion'] ?? '';

            $categoria_id = $this->categoria->crear();

            if ($categoria_id) {
                appLog('INFO', 'Categoría creada exitosamente', ['id' => $categoria_id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Categoría creada exitosamente",
                    "id" => $categoria_id
                ];
            } else {
                throw new Exception("No se pudo crear la categoría");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al crear categoría', ['data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al crear la categoría: " . $e->getMessage()
            ];
        }
    }

    public function actualizar($id, $data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['nombre'])) {
                return [
                    "success" => false,
                    "message" => "El nombre de la categoría es requerido"
                ];
            }

            $this->categoria->nombre = $data['nombre'];
            $this->categoria->descripcion = $data['descripcion'] ?? '';

            if ($this->categoria->actualizar($id)) {
                appLog('INFO', 'Categoría actualizada', ['id' => $id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Categoría actualizada exitosamente"
                ];
            } else {
                throw new Exception("No se pudo actualizar la categoría");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al actualizar categoría', ['id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al actualizar la categoría: " . $e->getMessage()
            ];
        }
    }

    public function eliminar($id)
    {
        try {
            // Verificar si la categoría tiene productos asociados
            $query = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = :categoria_id AND activo = true";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":categoria_id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                return [
                    "success" => false,
                    "message" => "No se puede eliminar la categoría porque tiene productos asociados"
                ];
            }

            if ($this->categoria->eliminar($id)) {
                appLog('INFO', 'Categoría eliminada', ['id' => $id]);
                return [
                    "success" => true,
                    "message" => "Categoría eliminada exitosamente"
                ];
            } else {
                throw new Exception("No se pudo eliminar la categoría");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al eliminar categoría', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al eliminar la categoría: " . $e->getMessage()
            ];
        }
    }

    public function obtenerTodas()
    {
        try {
            $categorias = $this->categoria->obtenerTodas();
            appLog('DEBUG', 'Todas las categorías obtenidas');
            return $categorias;
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener todas las categorías', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function obtenerConEstadisticas()
    {
        try {
            $query = "SELECT c.*, 
                             COUNT(p.id) as total_productos,
                             SUM(p.stock_actual) as total_stock,
                             COUNT(CASE WHEN p.stock_actual <= p.stock_minimo THEN 1 END) as productos_bajo_stock
                      FROM categorias c
                      LEFT JOIN productos p ON c.id = p.categoria_id AND p.activo = true
                      GROUP BY c.id
                      ORDER BY c.nombre";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $categorias = $stmt->fetchAll();

            appLog('INFO', 'Estadísticas de categorías obtenidas');
            return [
                "success" => true,
                "data" => $categorias
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener estadísticas de categorías', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage()
            ];
        }
    }
}
