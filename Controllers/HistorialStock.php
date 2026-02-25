<?php


require_once __DIR__ . '/../Models/HistorialStock.php';
require_once __DIR__ . '/../Models/Producto.php';


class HistorialStockController
{
    private $historialStock;
    private $producto;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->historialStock = new HistorialStock($db);
        $this->producto = new Producto($db);
    }

    public function listar()
    {
        try {
            $stmt = $this->historialStock->leer();
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener el historial de stock: " . $e->getMessage()
            ];
        }
    }

    public function obtenerPorProducto($producto_id)
    {
        try {
            if (empty($producto_id)) {
                return [
                    "success" => false,
                    "message" => "ID de producto no especificado"
                ];
            }

            // Verificar si el producto existe
            $producto = $this->producto->obtenerPorId($producto_id);
            if (!$producto) {
                return [
                    "success" => false,
                    "message" => "Producto no encontrado"
                ];
            }

            $stmt = $this->historialStock->obtenerPorProducto($producto_id);
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "data" => $movimientos,
                "producto" => $producto
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener el historial del producto: " . $e->getMessage()
            ];
        }
    }

    public function crearAjusteManual($data)
    {
        try {
            // Validar datos requeridos
            if (empty($data['producto_id']) || !isset($data['cantidad_nueva'])) {
                return [
                    "success" => false,
                    "message" => "Datos incompletos para el ajuste de stock"
                ];
            }

            // Obtener producto actual
            $producto_actual = $this->producto->obtenerPorId($data['producto_id']);
            if (!$producto_actual) {
                return [
                    "success" => false,
                    "message" => "Producto no encontrado"
                ];
            }

            $cantidad_anterior = $producto_actual['stock_actual'];
            $cantidad_nueva = intval($data['cantidad_nueva']);
            $diferencia = $cantidad_nueva - $cantidad_anterior;

            // Validar que la cantidad nueva sea válida
            if ($cantidad_nueva < 0) {
                return [
                    "success" => false,
                    "message" => "La cantidad no puede ser negativa"
                ];
            }

            // Determinar tipo de movimiento
            $tipo_movimiento = $diferencia > 0 ? 'entrada' : ($diferencia < 0 ? 'salida' : 'sin_cambio');

            // Crear registro en historial
            $this->historialStock->producto_id = $data['producto_id'];
            $this->historialStock->cantidad_anterior = $cantidad_anterior;
            $this->historialStock->cantidad_nueva = $cantidad_nueva;
            $this->historialStock->diferencia = $diferencia;
            $this->historialStock->tipo_movimiento = $tipo_movimiento;
            $this->historialStock->referencia_id = null;
            $this->historialStock->tipo_referencia = 'ajuste_manual';
            $this->historialStock->observaciones = $data['observaciones'] ?? 'Ajuste manual de stock';
            $this->historialStock->usuario_id = $data['usuario_id'] ?? 1;
            $this->historialStock->fecha_hora = date('Y-m-d H:i:s');

            $historial_id = $this->historialStock->crear();

            if ($historial_id) {
                // Actualizar stock del producto
                $update_result = $this->producto->actualizarStock($data['producto_id'], $cantidad_nueva);

                if ($update_result) {
                    return [
                        "success" => true,
                        "message" => "Stock ajustado exitosamente",
                        "data" => [
                            "historial_id" => $historial_id,
                            "cantidad_anterior" => $cantidad_anterior,
                            "cantidad_nueva" => $cantidad_nueva,
                            "diferencia" => $diferencia
                        ]
                    ];
                } else {
                    // Revertir el registro de historial si falla la actualización
                    $this->historialStock->eliminar($historial_id);
                    throw new Exception("Error al actualizar el stock del producto");
                }
            } else {
                throw new Exception("No se pudo crear el registro en el historial");
            }
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al realizar el ajuste de stock: " . $e->getMessage()
            ];
        }
    }

    public function obtenerMovimientosPorFecha($fecha_inicio, $fecha_fin)
    {
        try {
            if (empty($fecha_inicio) || empty($fecha_fin)) {
                return [
                    "success" => false,
                    "message" => "Fechas de inicio y fin son requeridas"
                ];
            }

            $movimientos = $this->historialStock->obtenerMovimientosPorFecha($fecha_inicio, $fecha_fin);

            return [
                "success" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener movimientos por fecha: " . $e->getMessage()
            ];
        }
    }

    public function obtenerResumenMovimientos($fecha_inicio = null, $fecha_fin = null)
    {
        try {
            $resumen = $this->historialStock->obtenerResumenMovimientos($fecha_inicio, $fecha_fin);

            return [
                "success" => true,
                "data" => $resumen
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener resumen de movimientos: " . $e->getMessage()
            ];
        }
    }

    public function obtenerMovimientosRecientes($limite = 10)
    {
        try {
            $movimientos = $this->historialStock->obtenerMovimientosRecientes($limite);

            return [
                "success" => true,
                "data" => $movimientos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener movimientos recientes: " . $e->getMessage()
            ];
        }
    }

    public function buscar($termino)
    {
        try {
            if (empty($termino)) {
                return [
                    "success" => false,
                    "message" => "Término de búsqueda requerido"
                ];
            }

            $resultados = $this->historialStock->buscar($termino);

            return [
                "success" => true,
                "data" => $resultados
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al buscar en el historial: " . $e->getMessage()
            ];
        }
    }

    public function obtenerEstadisticas()
    {
        try {
            // Obtener total de movimientos
            $query_total = "SELECT COUNT(*) as total FROM historial_stock";
            $stmt_total = $this->db->prepare($query_total);
            $stmt_total->execute();
            $total_movimientos = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

            // Obtener movimientos del mes actual
            $mes_actual = date('Y-m-01');
            $query_mes = "SELECT COUNT(*) as total FROM historial_stock WHERE fecha_hora >= :mes_actual";
            $stmt_mes = $this->db->prepare($query_mes);
            $stmt_mes->bindParam(":mes_actual", $mes_actual);
            $stmt_mes->execute();
            $movimientos_mes = $stmt_mes->fetch(PDO::FETCH_ASSOC)['total'];

            // Obtener productos con más movimientos
            $query_top = "SELECT p.nombre, COUNT(hs.id) as total_movimientos 
                         FROM historial_stock hs 
                         JOIN productos p ON hs.producto_id = p.id 
                         GROUP BY p.id, p.nombre 
                         ORDER BY total_movimientos DESC 
                         LIMIT 5";
            $stmt_top = $this->db->prepare($query_top);
            $stmt_top->execute();
            $top_productos = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

            return [
                "success" => true,
                "data" => [
                    "total_movimientos" => $total_movimientos,
                    "movimientos_mes" => $movimientos_mes,
                    "top_productos" => $top_productos
                ]
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener estadísticas: " . $e->getMessage()
            ];
        }
    }

    public function obtenerAjustesManuales($fecha_inicio = null, $fecha_fin = null)
    {
        try {
            $ajustes = $this->historialStock->obtenerAjustesManuales($fecha_inicio, $fecha_fin);

            return [
                "success" => true,
                "data" => $ajustes
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener ajustes manuales: " . $e->getMessage()
            ];
        }
    }
}