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
            // Método temporal hasta que implementemos obtenerPorId en el modelo
            $query = "SELECT * FROM categorias WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($categoria) {
                appLog('INFO', 'Categoría obtenida', ['id' => $id]);
                return [
                    "success" => true,
                    "data" => $categoria
                ];
            } else {
                appLog('WARNING', 'Categoría no encontrada', ['id' => $id]);
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
                appLog('WARNING', 'Intento de crear categoría sin nombre');
                return [
                    "success" => false,
                    "message" => "El nombre de la categoría es requerido"
                ];
            }

            // Sanitizar datos
            $nombre = trim($data['nombre']);
            $descripcion = trim($data['descripcion'] ?? '');

            // Verificar si ya existe una categoría con el mismo nombre
            $query = "SELECT COUNT(*) as total FROM categorias WHERE LOWER(nombre) = LOWER(:nombre)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":nombre", $nombre);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                appLog('WARNING', 'Intento de crear categoría con nombre duplicado', ['nombre' => $nombre]);
                return [
                    "success" => false,
                    "message" => "Ya existe una categoría con ese nombre"
                ];
            }

            $this->categoria->nombre = $nombre;
            $this->categoria->descripcion = $descripcion;

            $categoria_id = $this->categoria->crear();

            if ($categoria_id) {
                appLog('INFO', 'Categoría creada exitosamente', [
                    'id' => $categoria_id,
                    'nombre' => $nombre
                ]);
                return [
                    "success" => true,
                    "message" => "Categoría creada exitosamente",
                    "id" => $categoria_id
                ];
            } else {
                throw new Exception("No se pudo crear la categoría en la base de datos");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al crear categoría', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
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
                appLog('WARNING', 'Intento de actualizar categoría sin nombre', ['id' => $id]);
                return [
                    "success" => false,
                    "message" => "El nombre de la categoría es requerido"
                ];
            }

            // Método temporal para actualizar
            $query = "UPDATE categorias SET nombre = :nombre, descripcion = :descripcion, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);

            // Usar bindValue() en lugar de bindParam()
            $stmt->bindValue(":nombre", $data['nombre']);
            $stmt->bindValue(":descripcion", $data['descripcion'] ?? '');
            $stmt->bindValue(":id", $id);

            if ($stmt->execute()) {
                appLog('INFO', 'Categoría actualizada', ['id' => $id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Categoría actualizada exitosamente"
                ];
            } else {
                throw new Exception("No se pudo actualizar la categoría");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al actualizar categoría', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return [
                "success" => false,
                "message" => "Error al actualizar la categoría: " . $e->getMessage()
            ];
        }
    }

    public function eliminar($id)
    {
        try {
            // Verificar si la categoría existe
            $queryCheck = "SELECT COUNT(*) as total FROM categorias WHERE id = :id";
            $stmtCheck = $this->db->prepare($queryCheck);
            $stmtCheck->bindValue(":id", $id);
            $stmtCheck->execute();
            $categoriaExists = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($categoriaExists['total'] == 0) {
                appLog('WARNING', 'Intento de eliminar categoría inexistente', ['id' => $id]);
                return [
                    "success" => false,
                    "message" => "La categoría no existe"
                ];
            }

            // Verificar si la categoría tiene productos asociados
            $query = "SELECT COUNT(*) as total FROM productos WHERE categoria_id = :categoria_id AND activo = true";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":categoria_id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                appLog('WARNING', 'Intento de eliminar categoría con productos', [
                    'id' => $id,
                    'productos_asociados' => $result['total']
                ]);
                return [
                    "success" => false,
                    "message" => "No se puede eliminar la categoría porque tiene productos asociados"
                ];
            }

            // Eliminar la categoría
            $query = "DELETE FROM categorias WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(":id", $id);

            if ($stmt->execute()) {
                $rowCount = $stmt->rowCount();
                if ($rowCount > 0) {
                    appLog('INFO', 'Categoría eliminada', ['id' => $id]);
                    return [
                        "success" => true,
                        "message" => "Categoría eliminada exitosamente"
                    ];
                } else {
                    throw new Exception("No se afectaron filas al eliminar");
                }
            } else {
                throw new Exception("Error en la ejecución de la consulta");
            }
        } catch (PDOException $e) {
            // Manejar error de integridad referencial
            if ($e->getCode() == '23000') {
                appLog('ERROR', 'Error de integridad referencial al eliminar categoría', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
                return [
                    "success" => false,
                    "message" => "No se puede eliminar la categoría porque tiene productos asociados"
                ];
            }

            appLog('ERROR', 'Error al eliminar categoría', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return [
                "success" => false,
                "message" => "Error al eliminar la categoría: " . $e->getMessage()
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al eliminar categoría', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
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
            appLog('DEBUG', 'Todas las categorías obtenidas', ['total' => count($categorias)]);
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

            appLog('INFO', 'Estadísticas de categorías obtenidas', ['total' => count($categorias)]);
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
