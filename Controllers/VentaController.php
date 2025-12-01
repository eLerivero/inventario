<?php
require_once __DIR__ . '/../Models/Venta.php';
require_once __DIR__ . '/../Models/DetalleVenta.php';
require_once __DIR__ . '/../Models/Producto.php';
require_once __DIR__ . '/../Models/TasaCambio.php';
require_once __DIR__ . '/../Helpers/TasaCambioHelper.php';

class VentaController
{
    private $venta;
    private $detalleVenta;
    private $producto;
    private $tasaCambio;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->venta = new Venta($db);
        $this->detalleVenta = new DetalleVenta($db);
        $this->producto = new Producto($db);
        $this->tasaCambio = new TasaCambio($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->venta->leer();
            $ventas = $stmt->fetchAll();

            // Formatear cada venta para mostrar precios
            foreach ($ventas as &$venta) {
                $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
                $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
                $venta['tasa_formateada'] = TasaCambioHelper::formatearBS($venta['tasa_cambio_utilizada'], false);
            }

            return [
                "success" => true,
                "data" => $ventas
            ];
        } catch (Exception $e) {
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

                // Formatear precios para mostrar
                $venta['total_formateado_usd'] = TasaCambioHelper::formatearUSD($venta['total']);
                $venta['total_formateado_bs'] = TasaCambioHelper::formatearBS($venta['total_bs']);
                $venta['tasa_formateada'] = TasaCambioHelper::formatearBS($venta['tasa_cambio_utilizada'], false);

                foreach ($venta['detalles'] as &$detalle) {
                    $detalle['precio_unitario_formateado_usd'] = TasaCambioHelper::formatearUSD($detalle['precio_unitario']);
                    $detalle['precio_unitario_formateado_bs'] = TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']);
                    $detalle['subtotal_formateado_usd'] = TasaCambioHelper::formatearUSD($detalle['subtotal']);
                    $detalle['subtotal_formateado_bs'] = TasaCambioHelper::formatearBS($detalle['subtotal_bs']);
                }

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

            // Obtener tasa de cambio actual
            $tasa_actual = $this->tasaCambio->obtenerTasaActual();
            if (!$tasa_actual) {
                throw new Exception("No hay tasa de cambio configurada. Configure la tasa primero.");
            }
            $tasa_cambio = $tasa_actual['tasa_cambio'];

            // Calcular totales
            $total_usd = 0;
            $total_bs = 0;

            foreach ($data['detalles'] as $detalle) {
                $subtotal_usd = $detalle['cantidad'] * $detalle['precio_unitario'];
                $total_usd += $subtotal_usd;
            }

            $total_bs = $total_usd * $tasa_cambio;

            // Validar stock disponible
            foreach ($data['detalles'] as $detalle) {
                $producto = $this->producto->obtenerPorId($detalle['producto_id']);
                if (!$producto) {
                    throw new Exception("Producto no encontrado: " . $detalle['producto_id']);
                }

                if ($producto['stock_actual'] < $detalle['cantidad']) {
                    throw new Exception("Stock insuficiente para el producto: " . $producto['nombre']);
                }
            }

            // Crear la venta
            $ventaData = [
                'cliente_id' => $data['cliente_id'],
                'total' => $total_usd,
                'total_bs' => $total_bs,
                'tasa_cambio_utilizada' => $tasa_cambio,
                'tipo_pago_id' => $data['tipo_pago_id'],
                'estado' => $data['estado'] ?? 'pendiente',
                'fecha_hora' => $data['fecha_hora'] ?? date('Y-m-d H:i:s'),
                'observaciones' => $data['observaciones'] ?? ''
            ];

            $resultado_venta = $this->venta->crear($ventaData);

            if (!$resultado_venta) {
                throw new Exception("Error al crear la venta");
            }

            $venta_id = $resultado_venta['id'];
            $numero_venta = $resultado_venta['numero_venta'];

            // Crear detalles de venta
            $detallesData = [];
            foreach ($data['detalles'] as $detalle) {
                $subtotal_usd = $detalle['cantidad'] * $detalle['precio_unitario'];
                $subtotal_bs = $subtotal_usd * $tasa_cambio;
                $precio_unitario_bs = $detalle['precio_unitario'] * $tasa_cambio;

                $detallesData[] = [
                    'venta_id' => $venta_id,
                    'producto_id' => $detalle['producto_id'],
                    'cantidad' => $detalle['cantidad'],
                    'precio_unitario' => $detalle['precio_unitario'],
                    'precio_unitario_bs' => $precio_unitario_bs,
                    'subtotal' => $subtotal_usd,
                    'subtotal_bs' => $subtotal_bs
                ];
            }

            if (!$this->venta->crearDetalles($detallesData)) {
                throw new Exception("Error al crear los detalles de venta");
            }

            // Actualizar stock si la venta está completada
            if (($data['estado'] ?? 'pendiente') === 'completada') {
                foreach ($data['detalles'] as $detalle) {
                    $producto_actual = $this->producto->obtenerPorId($detalle['producto_id']);
                    $nuevo_stock = $producto_actual['stock_actual'] - $detalle['cantidad'];

                    $this->producto->actualizarStock(
                        $detalle['producto_id'],
                        $nuevo_stock,
                        'venta',
                        "Venta #$numero_venta"
                    );
                }
            }

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Venta creada exitosamente",
                "venta_id" => $venta_id,
                "numero_venta" => $numero_venta,
                "tasa_cambio" => $tasa_cambio,
                "total_usd" => $total_usd,
                "total_bs" => $total_bs
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
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
            if ($estado === 'completada' && $venta['estado'] !== 'completada') {
                $detalles = $this->obtenerDetalles($id);
                foreach ($detalles as $detalle) {
                    $producto_actual = $this->producto->obtenerPorId($detalle['producto_id']);
                    $nuevo_stock = $producto_actual['stock_actual'] - $detalle['cantidad'];

                    $this->producto->actualizarStock(
                        $detalle['producto_id'],
                        $nuevo_stock,
                        'venta',
                        "Venta completada #{$venta['numero_venta']}"
                    );
                }
            }

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Estado de venta actualizado exitosamente"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
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

            // Formatear las estadísticas
            if ($estadisticas) {
                $estadisticas['ingresos_totales_formateado'] = TasaCambioHelper::formatearUSD($estadisticas['ingresos_totales'] ?? 0);
                $estadisticas['ticket_promedio_formateado'] = TasaCambioHelper::formatearUSD($estadisticas['ticket_promedio'] ?? 0);
            }

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

    public function obtenerVentasPorMes()
    {
        try {
            $ventas_mes = $this->venta->obtenerVentasPorMes();
            return [
                "success" => true,
                "data" => $ventas_mes
            ];
        } catch (Exception $e) {
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

            return [
                "success" => true,
                "data" => $ventas
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar ventas: " . $e->getMessage()
            ];
        }
    }
}
