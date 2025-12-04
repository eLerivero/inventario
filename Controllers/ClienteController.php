<?php
require_once __DIR__ . '/../Models/Cliente.php';

class ClienteController
{
    private $cliente;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cliente = new Cliente($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->cliente->leer();
            $clientes = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener los clientes: " . $e->getMessage()
            ];
        }
    }

    public function obtener($id)
    {
        try {
            $cliente = $this->cliente->obtenerPorId($id);

            if ($cliente) {
                return [
                    "success" => true,
                    "data" => $cliente
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Cliente no encontrado"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener el cliente: " . $e->getMessage()
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
                    "message" => "El nombre del cliente es requerido"
                ];
            }

            $this->cliente->nombre = $data['nombre'];
            $this->cliente->email = $data['email'] ?? '';
            $this->cliente->telefono = $data['telefono'] ?? '';
            $this->cliente->direccion = $data['direccion'] ?? '';
            $this->cliente->numero_documento = $data['numero_documento'] ?? '';
            $this->cliente->activo = $data['activo'] ?? 1;

            $cliente_id = $this->cliente->crear();

            if ($cliente_id) {
                return [
                    "success" => true,
                    "message" => "Cliente creado exitosamente",
                    "id" => $cliente_id
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al crear cliente"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
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
                    "message" => "El nombre del cliente es requerido"
                ];
            }

            $this->cliente->nombre = $data['nombre'];
            $this->cliente->email = $data['email'] ?? '';
            $this->cliente->telefono = $data['telefono'] ?? '';
            $this->cliente->direccion = $data['direccion'] ?? '';
            $this->cliente->numero_documento = $data['numero_documento'] ?? '';
            $this->cliente->activo = $data['activo'] ?? 1;

            if ($this->cliente->actualizar($id)) {
                return [
                    "success" => true,
                    "message" => "Cliente actualizado exitosamente"
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al actualizar cliente"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function eliminar($id)
    {
        try {
            // Verificar si el cliente tiene ventas asociadas
            $query = "SELECT COUNT(*) as total FROM ventas WHERE cliente_id = :cliente_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":cliente_id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                return [
                    "success" => false,
                    "message" => "No se puede eliminar el cliente porque tiene ventas asociadas"
                ];
            }

            if ($this->cliente->eliminar($id)) {
                return [
                    "success" => true,
                    "message" => "Cliente eliminado exitosamente"
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al eliminar cliente"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }

    public function buscar($searchTerm)
    {
        try {
            $stmt = $this->cliente->buscar($searchTerm);
            $clientes = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar clientes: " . $e->getMessage()
            ];
        }
    }

    public function obtenerTodos()
    {
        try {
            $clientes = $this->cliente->obtenerTodos();
            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener todos los clientes: " . $e->getMessage()
            ];
        }
    }

    public function obtenerClientesActivos()
    {
        try {
            $query = "SELECT * FROM clientes WHERE activo = true ORDER BY nombre";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $clientes = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener clientes activos: " . $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total_clientes,
                        COUNT(CASE WHEN activo = true THEN 1 END) as clientes_activos,
                        COUNT(CASE WHEN activo = false THEN 1 END) as clientes_inactivos,
                        COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as clientes_con_email,
                        COUNT(CASE WHEN telefono IS NOT NULL AND telefono != '' THEN 1 END) as clientes_con_telefono
                      FROM clientes";

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

    public function obtenerClientesTop($limite = 10)
    {
        try {
            $query = "SELECT c.*, 
                             COUNT(v.id) as total_compras,
                             COALESCE(SUM(v.total), 0) as monto_total,
                             MAX(v.created_at) as ultima_compra
                      FROM clientes c
                      LEFT JOIN ventas v ON c.id = v.cliente_id AND v.estado = 'completada'
                      GROUP BY c.id
                      ORDER BY monto_total DESC
                      LIMIT :limite";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":limite", $limite, PDO::PARAM_INT);
            $stmt->execute();
            $clientes = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener clientes top: " . $e->getMessage()
            ];
        }
    }

    public function cambiarEstado($id, $activo)
    {
        try {
            $query = "UPDATE clientes SET activo = :activo, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":activo", $activo, PDO::PARAM_BOOL);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $estado = $activo ? 'activado' : 'desactivado';
                return [
                    "success" => true,
                    "message" => "Cliente {$estado} exitosamente"
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Error al cambiar estado del cliente"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }
    }
}