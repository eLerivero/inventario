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

    /**
     * Lista todos los tipos de pago, con opción de filtrar solo activos
     * @param bool $soloActivos Si es true, solo muestra tipos de pago activos
     * @return array
     */
    public function listar($soloActivos = false)
    {
        try {
            if ($soloActivos) {
                $tiposPago = $this->tipoPago->obtenerActivos();
            } else {
                $tiposPago = $this->tipoPago->leer();
            }
            
            return [
                "success" => true,
                "data" => $tiposPago,
                "total" => count($tiposPago)
            ];
        } catch (Exception $e) {
            error_log("Error en TipoPagoController::listar: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al obtener los tipos de pago: " . $e->getMessage(),
                "data" => []
            ];
        }
    }

    /**
     * Obtiene un tipo de pago por su ID
     * @param int $id
     * @return array
     */
    public function obtener($id)
    {
        try {
            if (empty($id)) {
                return [
                    "success" => false,
                    "message" => "ID no proporcionado"
                ];
            }

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
            error_log("Error en TipoPagoController::obtener: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al obtener el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    /**
     * Crea un nuevo tipo de pago
     * @param array $data
     * @return array
     */
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

            // Limpiar y validar nombre
            $nombre = trim($data['nombre']);
            if (strlen($nombre) < 3) {
                return [
                    "success" => false,
                    "message" => "El nombre debe tener al menos 3 caracteres"
                ];
            }
            if (strlen($nombre) > 50) {
                return [
                    "success" => false,
                    "message" => "El nombre no puede exceder los 50 caracteres"
                ];
            }

            // Verificar si ya existe un tipo de pago con el mismo nombre
            $existe = $this->tipoPago->verificarNombreExistente($nombre);
            if ($existe) {
                return [
                    "success" => false,
                    "message" => "Ya existe un tipo de pago con ese nombre"
                ];
            }

            // Asignar propiedades
            $this->tipoPago->nombre = $nombre;
            $this->tipoPago->descripcion = trim($data['descripcion'] ?? '');
            $this->tipoPago->activo = true; // Por defecto activo al crear

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
            error_log("Error en TipoPagoController::crear: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al crear el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    /**
     * Actualiza un tipo de pago existente
     * @param int $id
     * @param array $data
     * @return array
     */
    public function actualizar($id, $data)
    {
        try {
            // Validar ID
            if (empty($id)) {
                return [
                    "success" => false,
                    "message" => "ID no proporcionado"
                ];
            }

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

            // Limpiar nombre
            $nombre = trim($data['nombre']);
            if (strlen($nombre) < 3) {
                return [
                    "success" => false,
                    "message" => "El nombre debe tener al menos 3 caracteres"
                ];
            }
            if (strlen($nombre) > 50) {
                return [
                    "success" => false,
                    "message" => "El nombre no puede exceder los 50 caracteres"
                ];
            }

            // Verificar si ya existe otro tipo de pago con el mismo nombre (excluyendo el actual)
            $existe = $this->tipoPago->verificarNombreExistente($nombre, $id);
            if ($existe) {
                return [
                    "success" => false,
                    "message" => "Ya existe otro tipo de pago con ese nombre"
                ];
            }

            // Asignar propiedades
            $this->tipoPago->nombre = $nombre;
            $this->tipoPago->descripcion = trim($data['descripcion'] ?? '');
            $this->tipoPago->activo = isset($data['activo']) ? (bool)$data['activo'] : true;

            if ($this->tipoPago->actualizar($id)) {
                return [
                    "success" => true,
                    "message" => "Tipo de pago actualizado exitosamente"
                ];
            } else {
                throw new Exception("No se pudo actualizar el tipo de pago");
            }
        } catch (Exception $e) {
            error_log("Error en TipoPagoController::actualizar: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al actualizar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    /**
     * Elimina un tipo de pago (solo si no tiene ventas asociadas)
     * @param int $id
     * @return array
     */
    public function eliminar($id)
    {
        try {
            if (empty($id)) {
                return [
                    "success" => false,
                    "message" => "ID no proporcionado"
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

            // Verificar si el tipo de pago tiene ventas asociadas
            $query = "SELECT COUNT(*) as total FROM ventas WHERE tipo_pago_id = :tipo_pago_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":tipo_pago_id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total'] > 0) {
                return [
                    "success" => false,
                    "message" => "No se puede eliminar el tipo de pago porque tiene {$result['total']} venta(s) asociada(s)"
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
            error_log("Error en TipoPagoController::eliminar: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al eliminar el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    /**
     * Desactiva un tipo de pago
     * @param int $id
     * @return array
     */
    public function desactivar($id)
    {
        return $this->cambiarEstado($id, false);
    }

    /**
     * Activa un tipo de pago
     * @param int $id
     * @return array
     */
    public function activar($id)
    {
        return $this->cambiarEstado($id, true);
    }

    /**
     * Cambia el estado de un tipo de pago
     * @param int $id
     * @param bool $estado
     * @return array
     */
    private function cambiarEstado($id, $estado)
    {
        try {
            if (empty($id)) {
                return [
                    "success" => false,
                    "message" => "ID no proporcionado"
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

            $query = "UPDATE tipos_pago SET activo = :activo, updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":activo", $estado, PDO::PARAM_BOOL);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $accion = $estado ? 'activado' : 'desactivado';
                return [
                    "success" => true,
                    "message" => "Tipo de pago {$accion} exitosamente"
                ];
            } else {
                throw new Exception("No se pudo cambiar el estado del tipo de pago");
            }
        } catch (Exception $e) {
            error_log("Error en TipoPagoController::cambiarEstado: " . $e->getMessage());
            $accion = $estado ? 'activar' : 'desactivar';
            return [
                "success" => false,
                "message" => "Error al {$accion} el tipo de pago: " . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene todos los tipos de pago activos (método simplificado)
     * @return array
     */
    public function obtenerTodos()
    {
        try {
            $tiposPago = $this->tipoPago->obtenerActivos();
            return $tiposPago;
        } catch (Exception $e) {
            error_log("Error en TipoPagoController::obtenerTodos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas de uso de los tipos de pago
     * @return array
     */
    public function obtenerEstadisticasUso()
    {
        try {
            $query = "SELECT 
                        tp.id,
                        tp.nombre, 
                        COUNT(v.id) as total_ventas,
                        COALESCE(SUM(v.total), 0) as monto_total,
                        ROUND((COUNT(v.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM ventas WHERE estado = 'completada'), 0)), 2) as porcentaje
                      FROM tipos_pago tp
                      LEFT JOIN ventas v ON tp.id = v.tipo_pago_id AND v.estado = 'completada'
                      GROUP BY tp.id, tp.nombre
                      ORDER BY total_ventas DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $estadisticas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "data" => $estadisticas
            ];
        } catch (Exception $e) {
            error_log("Error en TipoPagoController::obtenerEstadisticasUso: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage(),
                "data" => []
            ];
        }
    }

    /**
     * Busca tipos de pago por término
     * @param string $termino
     * @return array
     */
    public function buscar($termino)
    {
        try {
            $tiposPago = $this->tipoPago->buscar($termino);
            return [
                "success" => true,
                "data" => $tiposPago
            ];
        } catch (Exception $e) {
            error_log("Error en TipoPagoController::buscar: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al buscar tipos de pago: " . $e->getMessage(),
                "data" => []
            ];
        }
    }
}