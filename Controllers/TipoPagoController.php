<?php
require_once 'Models/TipoPago.php';

class TipoPagoController
{
    private $tipoPago;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->tipoPago = new TipoPago($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->tipoPago->leer();
            $tiposPago = $stmt->fetchAll();

            appLog('INFO', 'Tipos de pago listados', ['total' => count($tiposPago)]);
            return [
                "success" => true,
                "data" => $tiposPago
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al listar tipos de pago', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener los tipos de pago: " . $e->getMessage()
            ];
        }
    }

    public function obtener($id)
    {
        try {
            $tipoPago = $this->tipoPago->obtenerPorId($id);

            if ($tipoPago) {
                appLog('INFO', 'Tipo de pago obtenido', ['id' => $id]);
                return [
                    "success" => true,
                    "data" => $tipoPago
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Tipo de pago no encontrado"
                ];
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener tipo de pago', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener el tipo de pago: " . $e->getMessage()
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
                    "message" => "El nombre del tipo de pago es requerido"
                ];
            }

            $this->tipoPago->nombre = $data['nombre'];
            $this->tipoPago->descripcion = $data['descripcion'] ?? '';

            $tipoPago_id = $this->tipoPago->crear();

            if ($tipoPago_id) {
                appLog('INFO', 'Tipo de pago creado exitosamente', ['id' => $tipoPago_id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Tipo de pago creado exitosamente",
                    "id" => $tipoPago_id
                ];
            } else {
                throw new Exception("No se pudo crear el tipo de pago");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al crear tipo de pago', ['data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al crear el tipo de pago: " . $e->getMessage()
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
                    "message" => "El nombre del tipo de pago es requerido"
                ];
            }

            $this->tipoPago->nombre = $data['nombre'];
            $this->tipoPago->descripcion = $data['descripcion'] ?? '';
            $this->tipoPago->activo = $data['activo'] ?? true;

            if ($this->tipoPago->actualizar($id)) {
                appLog('INFO', 'Tipo de pago actualizado', ['id' => $id, 'nombre' => $data['nombre']]);
                return [
                    "success" => true,
                    "message" => "Tipo de pago actualizado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo actualizar el tipo de pago");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al actualizar tipo de pago', ['id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al actualizar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    public function eliminar($id)
    {
        try {
            // Verificar si el tipo de pago tiene ventas asociadas
            $query = "SELECT COUNT(*) as total FROM ventas WHERE tipo_pago_id = :tipo_pago_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":tipo_pago_id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                return [
                    "success" => false,
                    "message" => "No se puede eliminar el tipo de pago porque tiene ventas asociadas"
                ];
            }

            if ($this->tipoPago->eliminar($id)) {
                appLog('INFO', 'Tipo de pago eliminado', ['id' => $id]);
                return [
                    "success" => true,
                    "message" => "Tipo de pago eliminado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo eliminar el tipo de pago");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al eliminar tipo de pago', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al eliminar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    public function obtenerTodos()
    {
        try {
            $tiposPago = $this->tipoPago->obtenerTodos();
            appLog('DEBUG', 'Todos los tipos de pago obtenidos');
            return $tiposPago;
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener todos los tipos de pago', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function desactivar($id)
    {
        try {
            $query = "UPDATE tipos_pago SET activo = false WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                appLog('INFO', 'Tipo de pago desactivado', ['id' => $id]);
                return [
                    "success" => true,
                    "message" => "Tipo de pago desactivado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo desactivar el tipo de pago");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al desactivar tipo de pago', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al desactivar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    public function activar($id)
    {
        try {
            $query = "UPDATE tipos_pago SET activo = true WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                appLog('INFO', 'Tipo de pago activado', ['id' => $id]);
                return [
                    "success" => true,
                    "message" => "Tipo de pago activado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo activar el tipo de pago");
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al activar tipo de pago', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al activar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticasUso()
    {
        try {
            $query = "SELECT tp.nombre, 
                             COUNT(v.id) as total_ventas,
                             COALESCE(SUM(v.total), 0) as monto_total,
                             ROUND((COUNT(v.id) * 100.0 / (SELECT COUNT(*) FROM ventas)), 2) as porcentaje
                      FROM tipos_pago tp
                      LEFT JOIN ventas v ON tp.id = v.tipo_pago_id AND v.estado = 'completada'
                      WHERE tp.activo = true
                      GROUP BY tp.id, tp.nombre
                      ORDER BY total_ventas DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $estadisticas = $stmt->fetchAll();

            appLog('INFO', 'Estadísticas de uso de tipos de pago obtenidas');
            return [
                "success" => true,
                "data" => $estadisticas
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener estadísticas de tipos de pago', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage()
            ];
        }
    }
}
