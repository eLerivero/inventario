<?php
require_once __DIR__ . '/../Models/TasaCambio.php';
require_once __DIR__ . '/../Models/Producto.php';

class TasaCambioController
{
    private $tasaCambio;
    private $producto;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->tasaCambio = new TasaCambio($db);
        $this->producto = new Producto($db);
    }

    public function obtenerTasaActual()
    {
        try {
            $tasa = $this->tasaCambio->obtenerTasaActual();
            if ($tasa) {
                return [
                    "success" => true,
                    "data" => $tasa
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "No hay tasa de cambio configurada"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener tasa de cambio: " . $e->getMessage()
            ];
        }
    }

    public function obtenerTasaPorId($id)
    {
        try {
            $query = "SELECT * FROM tasas_cambio WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return [
                    "success" => true,
                    "data" => $stmt->fetch(PDO::FETCH_ASSOC)
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Tasa de cambio no encontrada"
                ];
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener tasa: " . $e->getMessage()
            ];
        }
    }

    public function actualizarTasa($tasa, $usuario = 'Sistema')
    {
        try {
            $this->db->beginTransaction();

            // Validar tasa
            if (!is_numeric($tasa) || $tasa <= 0) {
                throw new Exception("La tasa de cambio debe ser un número mayor a 0");
            }

            // Desactivar todas las tasas anteriores y crear nueva
            $resultado = $this->tasaCambio->crear($tasa, $usuario);

            if (!$resultado) {
                throw new Exception("Error al crear nueva tasa de cambio");
            }

            // Actualizar precios en Bs de todos los productos
            $this->actualizarPreciosProductos($tasa);

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Tasa de cambio actualizada exitosamente",
                "data" => $resultado
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "message" => "Error al actualizar tasa: " . $e->getMessage()
            ];
        }
    }

    public function crearTasa($data)
    {
        try {
            $this->db->beginTransaction();

            // Validar datos
            if (!isset($data['tasa_cambio']) || !is_numeric($data['tasa_cambio']) || $data['tasa_cambio'] <= 0) {
                throw new Exception("La tasa de cambio debe ser un número mayor a 0");
            }

            // Extraer valores en variables separadas para bindParam
            $moneda_origen = $data['moneda_origen'] ?? 'USD';
            $moneda_destino = $data['moneda_destino'] ?? 'VES';
            $tasa_cambio = $data['tasa_cambio'];
            $usuario_actualizacion = $data['usuario_actualizacion'] ?? 'Sistema';
            $activa = isset($data['activa']) && $data['activa'] ? 1 : 0;

            // Si se marca como activa, desactivar todas las anteriores
            if ($activa == 1) {
                $query = "UPDATE tasas_cambio SET activa = FALSE WHERE activa = TRUE";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            }

            // Crear nueva tasa
            $query = "INSERT INTO tasas_cambio 
                      (moneda_origen, moneda_destino, tasa_cambio, usuario_actualizacion, activa) 
                      VALUES 
                      (:origen, :destino, :tasa, :usuario, :activa)
                      RETURNING *";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":origen", $moneda_origen);
            $stmt->bindParam(":destino", $moneda_destino);
            $stmt->bindParam(":tasa", $tasa_cambio);
            $stmt->bindParam(":usuario", $usuario_actualizacion);
            $stmt->bindParam(":activa", $activa, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $nuevaTasa = $stmt->fetch(PDO::FETCH_ASSOC);

                // Si es activa, actualizar precios de productos
                if ($nuevaTasa['activa'] == 1) {
                    $this->actualizarPreciosProductos($nuevaTasa['tasa_cambio']);
                }

                $this->db->commit();
                return [
                    "success" => true,
                    "message" => "Tasa de cambio creada exitosamente",
                    "data" => $nuevaTasa
                ];
            } else {
                throw new Exception("Error al crear la tasa de cambio");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "message" => "Error al crear tasa: " . $e->getMessage()
            ];
        }
    }

    public function editarTasa($id, $data)
    {
        try {
            $this->db->beginTransaction();

            // Verificar si la tasa existe
            $tasaExistente = $this->obtenerTasaPorId($id);
            if (!$tasaExistente['success']) {
                throw new Exception($tasaExistente['message']);
            }

            // Validar datos
            if (!isset($data['tasa_cambio']) || !is_numeric($data['tasa_cambio']) || $data['tasa_cambio'] <= 0) {
                throw new Exception("La tasa de cambio debe ser un número mayor a 0");
            }

            // Extraer valores en variables separadas para bindParam
            $tasa_cambio = $data['tasa_cambio'];
            $usuario_actualizacion = $data['usuario_actualizacion'] ?? 'Sistema';
            $activa = isset($data['activa']) && $data['activa'] ? 1 : 0;

            // Si se marca como activa, desactivar todas las anteriores
            if ($activa == 1) {
                $query = "UPDATE tasas_cambio SET activa = FALSE WHERE activa = TRUE";
                $stmt = $this->db->prepare($query);
                $stmt->execute();
            }

            // Actualizar tasa
            $query = "UPDATE tasas_cambio SET 
                      tasa_cambio = :tasa,
                      usuario_actualizacion = :usuario,
                      activa = :activa,
                      fecha_actualizacion = CURRENT_TIMESTAMP,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE id = :id
                      RETURNING *";

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":tasa", $tasa_cambio);
            $stmt->bindParam(":usuario", $usuario_actualizacion);
            $stmt->bindParam(":activa", $activa, PDO::PARAM_INT);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $tasaActualizada = $stmt->fetch(PDO::FETCH_ASSOC);

                // Si es activa, actualizar precios de productos
                if ($tasaActualizada['activa'] == 1) {
                    $this->actualizarPreciosProductos($tasaActualizada['tasa_cambio']);
                }

                $this->db->commit();
                return [
                    "success" => true,
                    "message" => "Tasa de cambio actualizada exitosamente",
                    "data" => $tasaActualizada
                ];
            } else {
                throw new Exception("Error al actualizar la tasa de cambio");
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "message" => "Error al editar tasa: " . $e->getMessage()
            ];
        }
    }

    public function eliminarTasa($id)
    {
        try {
            // Verificar si es la única tasa activa
            $query = "SELECT COUNT(*) as total_activas FROM tasas_cambio WHERE activa = TRUE";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['total_activas'] == 1) {
                $query = "SELECT id FROM tasas_cambio WHERE activa = TRUE AND id = :id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":id", $id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    throw new Exception("No se puede eliminar la única tasa activa. Primero active otra tasa.");
                }
            }

            // Eliminar tasa
            $query = "DELETE FROM tasas_cambio WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                return [
                    "success" => true,
                    "message" => "Tasa de cambio eliminada exitosamente"
                ];
            } else {
                throw new Exception("Error al eliminar la tasa de cambio");
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al eliminar tasa: " . $e->getMessage()
            ];
        }
    }

    private function actualizarPreciosProductos($nuevaTasa)
    {
        $query = "UPDATE productos 
                  SET precio_bs = ROUND(precio * :tasa, 2),
                      precio_costo_bs = ROUND(precio_costo * :tasa, 2),
                      updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":tasa", $nuevaTasa);
        return $stmt->execute();
    }

    public function listarHistorial($limite = 50)
    {
        try {
            $historial = $this->tasaCambio->obtenerHistorial($limite);
            return [
                "success" => true,
                "data" => $historial
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener historial: " . $e->getMessage()
            ];
        }
    }

    public function listar()
    {
        try {
            $stmt = $this->tasaCambio->leer();
            $tasas = $stmt->fetchAll();

            return [
                "success" => true,
                "data" => $tasas
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener las tasas de cambio: " . $e->getMessage()
            ];
        }
    }
}
