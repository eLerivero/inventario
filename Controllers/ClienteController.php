<?php
require_once 'Models/Cliente.php';

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

            appLog('INFO', 'Clientes listados', ['total' => count($clientes)]);
            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al listar clientes', ['error' => $e->getMessage()]);
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
                appLog('INFO', 'Cliente obtenido', ['id' => $id]);
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
            appLog('ERROR', 'Error al obtener cliente', ['id' => $id, 'error' => $e->getMessage()]);
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

            $cliente_id = $this->cliente->crear();

            if ($cliente_id) {
                appLog('INFO', 'Cliente creado exitosamente', ['id' => $cliente_id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Cliente creado exitosamente",
                    "id" => $cliente_id
                ];
            } else {
                throw new Exception("No se pudo crear el cliente");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al crear cliente', ['data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al crear el cliente: " . $e->getMessage()
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

            if ($this->cliente->actualizar($id)) {
                appLog('INFO', 'Cliente actualizado', ['id' => $id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Cliente actualizado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo actualizar el cliente");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al actualizar cliente', ['id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al actualizar el cliente: " . $e->getMessage()
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
                appLog('INFO', 'Cliente eliminado', ['id' => $id]);
                return [
                    "success" => true,
                    "message" => "Cliente eliminado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo eliminar el cliente");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al eliminar cliente', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al eliminar el cliente: " . $e->getMessage()
            ];
        }
    }

    public function obtenerTodos()
    {
        try {
            $clientes = $this->cliente->obtenerTodos();
            appLog('DEBUG', 'Todos los clientes obtenidos');
            return $clientes;
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener todos los clientes', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function buscar($search)
    {
        try {
            $query = "SELECT * FROM clientes 
                      WHERE nombre ILIKE :search 
                         OR email ILIKE :search 
                         OR telefono ILIKE :search
                      ORDER BY nombre";

            $stmt = $this->db->prepare($query);
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(":search", $searchTerm);
            $stmt->execute();
            $clientes = $stmt->fetchAll();

            appLog('INFO', 'Búsqueda de clientes', ['termino' => $search, 'resultados' => count($clientes)]);
            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al buscar clientes', ['termino' => $search, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al buscar clientes: " . $e->getMessage()
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

            appLog('INFO', 'Clientes top obtenidos', ['limite' => $limite]);
            return [
                "success" => true,
                "data" => $clientes
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener clientes top', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener clientes top: " . $e->getMessage()
            ];
        }
    }
}
