<?php
require_once 'Models/Venta.php';
require_once 'Models/DetalleVenta.php';
require_once 'Models/Producto.php';

class VentaController
{
    private $venta;
    private $detalleVenta;
    private $producto;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->venta = new Venta($db);
        $this->detalleVenta = new DetalleVenta($db);
        $this->producto = new Producto($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->venta->leer();
            $ventas = $stmt->fetchAll();

            appLog('INFO', 'Ventas listadas', ['total' => count($ventas)]);
            return [
                "success" => true,
                "data" => $ventas
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al listar ventas', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener las ventas: " . $e->getMessage()
            ];
        }
    }

    public function obtener($id)
    {
        try {
            $venta = $this->venta->obtenerPorId($id);

            if ($venta) {
                $venta['detalles'] = $this->obtenerDetalles($id);
                appLog('INFO', 'Venta obtenida', ['id' => $id]);
                return [
                    "success" => true,
                    "data" => $venta
                ];
            } else {
                return [
                    "success" => false,
                    "message" => "Venta no encontrada"
                ];
            }
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener venta', ['id' => $id, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener la venta: " . $e->getMessage()
            ];
        }
    }

    public function obtenerDetalles($venta_id)
    {
        try {
            $detalles = $this->venta->obtenerDetalles($venta_id);
            return $detalles;
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener detalles de venta', ['venta_id' => $venta_id, 'error' => $e->getMessage()]);
            return [];
        }
    }

    public function crear($data)
    {
        try {
            $this->db->beginTransaction();

            // Validar datos requeridos
            if (empty($data['cliente_id']) || empty($data['detalles']) || !is_array($data['detalles'])) {
                throw new Exception("Datos incompletos para crear la venta");
            }

            // Validar stock disponible
            foreach ($data['detalles'] as $detalle) {
                $producto = $this->producto->obtenerPorId($detalle['producto_id']);
                if (!$producto) {
                    throw new Exception("Producto no encontrado: " . $detalle['producto_id']);
                }

                if ($producto['stock_actual'] < $detalle['cantidad'] && !ALLOW_BACKORDERS) {
                    throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
                }
            }

            // Crear la venta
            $this->venta->cliente_id = $data['cliente_id'];
            $this->venta->total = $data['total'];
            $this->venta->tipo_pago_id = $data['tipo_pago_id'];
            $this->venta->estado = $data['estado'] ?? VENTA_PENDIENTE;
            $this->venta->fecha_hora = $data['fecha_hora'] ?? date('Y-m-d H:i:s');
            $this->venta->observaciones = $data['observaciones'] ?? '';

            $resultado_venta = $this->venta->crear();

            if (!$resultado_venta) {
                throw new Exception("Error al crear la venta");
            }

            $venta_id = $resultado_venta['id'];
            $numero_venta = $resultado_venta['numero_venta'];

            // Crear detalles de venta
            $this->detalleVenta->venta_id = $venta_id;

            if (!$this->detalleVenta->crearMultiple($data['detalles'])) {
                throw new Exception("Error al crear los detalles de venta");
            }

            // Actualizar stock si la venta está completada
            if (($data['estado'] ?? VENTA_PENDIENTE) === VENTA_COMPLETADA && AUTO_UPDATE_STOCK) {
                foreach ($data['detalles'] as $detalle) {
                    $producto_actual = $this->producto->obtenerPorId($detalle['producto_id']);
                    $nuevo_stock = $producto_actual['stock_actual'] - $detalle['cantidad'];

                    $this->producto->actualizarStock(
                        $detalle['producto_id'],
                        $nuevo_stock,
                        MOVIMIENTO_VENTA,
                        "Venta #$numero_venta"
                    );
                }
            }

            $this->db->commit();

            appLog('INFO', 'Venta creada exitosamente', [
                'venta_id' => $venta_id,
                'numero_venta' => $numero_venta,
                'cliente_id' => $data['cliente_id'],
                'total' => $data['total']
            ]);

            return [
                "success" => true,
                "message" => "Venta creada exitosamente",
                "venta_id" => $venta_id,
                "numero_venta" => $numero_venta
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            appLog('ERROR', 'Error al crear venta', ['data' => $data, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al crear la venta: " . $e->getMessage()
            ];
        }
    }

    public function actualizarEstado($id, $estado)
    {
        try {
            $this->db->beginTransaction();

            $venta = $this->venta->obtenerPorId($id);
            if (!$venta) {
                throw new Exception("Venta no encontrada");
            }

            // Actualizar estado
            $query = "UPDATE ventas SET estado = :estado WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":estado", $estado);
            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar estado de la venta");
            }

            // Si se completa la venta, actualizar stock
            if ($estado === VENTA_COMPLETADA && $venta['estado'] !== VENTA_COMPLETADA && AUTO_UPDATE_STOCK) {
                $detalles = $this->obtenerDetalles($id);
                foreach ($detalles as $detalle) {
                    $producto_actual = $this->producto->obtenerPorId($detalle['producto_id']);
                    $nuevo_stock = $producto_actual['stock_actual'] - $detalle['cantidad'];

                    $this->producto->actualizarStock(
                        $detalle['producto_id'],
                        $nuevo_stock,
                        MOVIMIENTO_VENTA,
                        "Venta completada #{$venta['numero_venta']}"
                    );
                }
            }

            $this->db->commit();

            appLog('INFO', 'Estado de venta actualizado', ['id' => $id, 'estado' => $estado]);
            return [
                "success" => true,
                "message" => "Estado de venta actualizado exitosamente"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            appLog('ERROR', 'Error al actualizar estado de venta', ['id' => $id, 'estado' => $estado, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al actualizar estado: " . $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            $estadisticas = $this->venta->obtenerEstadisticas();
            appLog('DEBUG', 'Estadísticas de ventas obtenidas');
            return [
                "success" => true,
                "data" => $estadisticas
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener estadísticas de ventas', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage()
            ];
        }
    }

    public function obtenerVentasPorMes()
    {
        try {
            $ventas_mes = $this->venta->obtenerVentasPorMes();
            appLog('DEBUG', 'Ventas por mes obtenidas');
            return [
                "success" => true,
                "data" => $ventas_mes
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al obtener ventas por mes', ['error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al obtener ventas por mes: " . $e->getMessage()
            ];
        }
    }

    public function buscar($search)
    {
        try {
            $query = "SELECT v.*, c.nombre as cliente_nombre, tp.nombre as tipo_pago_nombre
                      FROM ventas v
                      LEFT JOIN clientes c ON v.cliente_id = c.id
                      LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                      WHERE v.numero_venta::text ILIKE :search 
                         OR c.nombre ILIKE :search
                         OR tp.nombre ILIKE :search
                      ORDER BY v.created_at DESC";

            $stmt = $this->db->prepare($query);
            $searchTerm = "%" . $search . "%";
            $stmt->bindParam(":search", $searchTerm);
            $stmt->execute();
            $ventas = $stmt->fetchAll();

            appLog('INFO', 'Búsqueda de ventas', ['termino' => $search, 'resultados' => count($ventas)]);
            return [
                "success" => true,
                "data" => $ventas
            ];
        } catch (Exception $e) {
            appLog('ERROR', 'Error al buscar ventas', ['termino' => $search, 'error' => $e->getMessage()]);
            return [
                "success" => false,
                "message" => "Error al buscar ventas: " . $e->getMessage()
            ];
        }
    }
}
