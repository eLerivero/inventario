<?php
require_once __DIR__ . '/../Models/TipoPago.php';

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
            $tiposPago = $this->tipoPago->leer();
            return [
                "success" => true,
                "data" => $tiposPago
            ];
        } catch (Exception $e) {
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

            // Verificar si ya existe un tipo de pago con el mismo nombre
            $existe = $this->tipoPago->verificarNombreExistente($data['nombre']);
            if ($existe) {
                return [
                    "success" => false,
                    "message" => "Ya existe un tipo de pago con ese nombre"
                ];
            }

            $this->tipoPago->nombre = $data['nombre'];
            $this->tipoPago->descripcion = $data['descripcion'] ?? '';

            $tipoPago_id = $this->tipoPago->crear();

            if ($tipoPago_id) {
                return [
                    "success" => true,
                    "message" => "Tipo de pago creado exitosamente",
                    "id" => $tipoPago_id
                ];
            } else {
                throw new Exception("No se pudo crear el tipo de pago");
            }
        } catch (Exception $e) {
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

            // Verificar si el tipo de pago existe
            $tipoPagoExistente = $this->tipoPago->obtenerPorId($id);
            if (!$tipoPagoExistente) {
                return [
                    "success" => false,
                    "message" => "Tipo de pago no encontrado"
                ];
            }

            // Verificar si ya existe otro tipo de pago con el mismo nombre (excluyendo el actual)
            $existe = $this->tipoPago->verificarNombreExistente($data['nombre'], $id);
            if ($existe) {
                return [
                    "success" => false,
                    "message" => "Ya existe otro tipo de pago con ese nombre"
                ];
            }

            $this->tipoPago->nombre = $data['nombre'];
            $this->tipoPago->descripcion = $data['descripcion'] ?? '';
            $this->tipoPago->activo = $data['activo'] ?? true;

            if ($this->tipoPago->actualizar($id)) {
                return [
                    "success" => true,
                    "message" => "Tipo de pago actualizado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo actualizar el tipo de pago");
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al actualizar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    public function eliminar($id)
    {
        try {
            // Verificar si el tipo de pago existe
            $tipoPagoExistente = $this->tipoPago->obtenerPorId($id);
            if (!$tipoPagoExistente) {
                return [
                    "success" => false,
                    "message" => "Tipo de pago no encontrado"
                ];
            }

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
                return [
                    "success" => true,
                    "message" => "Tipo de pago eliminado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo eliminar el tipo de pago");
            }
        } catch (Exception $e) {
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
            return $tiposPago;
        } catch (Exception $e) {
            return [];
        }
    }

    public function desactivar($id)
    {
        try {
            // Verificar si el tipo de pago existe
            $tipoPagoExistente = $this->tipoPago->obtenerPorId($id);
            if (!$tipoPagoExistente) {
                return [
                    "success" => false,
                    "message" => "Tipo de pago no encontrado"
                ];
            }

            $query = "UPDATE tipos_pago SET activo = false WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                return [
                    "success" => true,
                    "message" => "Tipo de pago desactivado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo desactivar el tipo de pago");
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al desactivar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    public function activar($id)
    {
        try {
            // Verificar si el tipo de pago existe
            $tipoPagoExistente = $this->tipoPago->obtenerPorId($id);
            if (!$tipoPagoExistente) {
                return [
                    "success" => false,
                    "message" => "Tipo de pago no encontrado"
                ];
            }

            $query = "UPDATE tipos_pago SET activo = true WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                return [
                    "success" => true,
                    "message" => "Tipo de pago activado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo activar el tipo de pago");
            }
        } catch (Exception $e) {
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
                             ROUND((COUNT(v.id) * 100.0 / (SELECT COUNT(*) FROM ventas WHERE estado = 'completada')), 2) as porcentaje
                      FROM tipos_pago tp
                      LEFT JOIN ventas v ON tp.id = v.tipo_pago_id AND v.estado = 'completada'
                      WHERE tp.activo = true
                      GROUP BY tp.id, tp.nombre
                      ORDER BY total_ventas DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $estadisticas = $stmt->fetchAll();

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

    public function buscar($termino)
    {
        try {
            $tiposPago = $this->tipoPago->buscar($termino);
            return [
                "success" => true,
                "data" => $tiposPago
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar tipos de pago: " . $e->getMessage()
            ];
        }
    }
}