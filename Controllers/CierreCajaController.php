<?php
require_once __DIR__ . '/../Models/CierreCaja.php';
require_once __DIR__ . '/../Models/Venta.php';
require_once __DIR__ . '/../Models/TipoPago.php';
require_once __DIR__ . '/../Helpers/TasaCambioHelper.php';

class CierreCajaController
{
    private $cierreCaja;
    private $venta;
    private $tipoPago;
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->cierreCaja = new CierreCaja($db);
        $this->venta = new Venta($db);
        $this->tipoPago = new TipoPago($db);
    }

    /**
     * Obtener todas las ventas pendientes de cierre (cerrada_en_caja = FALSE)
     */
    public function obtenerVentasPendientes()
    {
        try {
            // Obtener todas las ventas completadas que no están cerradas en caja
            $query = "SELECT v.*, c.nombre as cliente_nombre, c.numero_documento as cliente_documento,
                             u.nombre as vendedor_nombre, tp.nombre as tipo_pago_nombre
                      FROM ventas v
                      LEFT JOIN clientes c ON v.cliente_id = c.id
                      LEFT JOIN usuarios u ON v.usuario_id = u.id
                      LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
                      WHERE v.estado = 'completada' 
                      AND v.cerrada_en_caja = FALSE
                      ORDER BY v.fecha_hora DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $ventas = $stmt->fetchAll();

            if (empty($ventas)) {
                return [
                    "success" => true,
                    "message" => "No hay ventas pendientes de cierre",
                    "data" => [
                        'ventas' => [],
                        'total_ventas' => 0,
                        'total_unidades' => 0,
                        'total_usd' => 0,
                        'total_bs' => 0,
                        'totales_por_tipo_usd' => [],
                        'totales_por_tipo_bs' => [],
                        'resumen_categorias' => [],
                        'resumen_productos' => [],
                        'resumen_clientes' => [],
                        'ventas_ids' => []
                    ]
                ];
            }

            // Obtener todos los tipos de pago
            $tipos_pago = $this->tipoPago->leer();
            $tipos_pago_map = [];
            foreach ($tipos_pago as $tp) {
                $tipos_pago_map[$tp['id']] = $tp['nombre'];
            }

            // Inicializar arrays para resúmenes
            $resumen_categorias = [];
            $resumen_productos = [];
            $resumen_clientes = [];
            $ventas_ids = [];

            // Inicializar totales
            $total_ventas = 0;
            $total_unidades = 0;
            $total_usd = 0;
            $total_bs = 0;

            // Inicializar totales por tipo de pago
            $totales_por_tipo_usd = [];
            $totales_por_tipo_bs = [];

            foreach ($tipos_pago as $tp) {
                $nombre_normalizado = strtolower(str_replace(' ', '_', $tp['nombre']));
                $totales_por_tipo_usd[$nombre_normalizado] = 0;
                $totales_por_tipo_bs[$nombre_normalizado] = 0;
            }

            foreach ($ventas as $venta) {
                $total_ventas++;
                $ventas_ids[] = $venta['id'];
                $total_usd += $venta['total'];
                $total_bs += $venta['total_bs'];

                // Acumular por tipo de pago
                if (isset($venta['tipo_pago_id']) && isset($tipos_pago_map[$venta['tipo_pago_id']])) {
                    $tipo_pago_nombre = $tipos_pago_map[$venta['tipo_pago_id']];
                    $nombre_normalizado = strtolower(str_replace(' ', '_', $tipo_pago_nombre));

                    $totales_por_tipo_usd[$nombre_normalizado] += $venta['total'];
                    $totales_por_tipo_bs[$nombre_normalizado] += $venta['total_bs'];
                }

                // Obtener detalles para resúmenes
                $detalles = $this->venta->obtenerDetalles($venta['id']);

                foreach ($detalles as $detalle) {
                    $total_unidades += $detalle['cantidad'];

                    // Resumen por categoría
                    $categoria_id = $detalle['categoria_id'] ?? 0;
                    $categoria_nombre = $detalle['categoria_nombre'] ?? 'Sin categoría';

                    if (!isset($resumen_categorias[$categoria_nombre])) {
                        $resumen_categorias[$categoria_nombre] = [
                            'unidades' => 0,
                            'total_usd' => 0,
                            'total_bs' => 0
                        ];
                    }
                    $resumen_categorias[$categoria_nombre]['unidades'] += $detalle['cantidad'];
                    $resumen_categorias[$categoria_nombre]['total_usd'] += $detalle['subtotal'];
                    $resumen_categorias[$categoria_nombre]['total_bs'] += $detalle['subtotal_bs'];

                    // Resumen por producto
                    $producto_id = $detalle['producto_id'];
                    $producto_nombre = $detalle['producto_nombre'];

                    if (!isset($resumen_productos[$producto_id])) {
                        $resumen_productos[$producto_id] = [
                            'nombre' => $producto_nombre,
                            'sku' => $detalle['codigo_sku'] ?? '',
                            'categoria' => $categoria_nombre,
                            'unidades' => 0,
                            'total_usd' => 0,
                            'total_bs' => 0
                        ];
                    }
                    $resumen_productos[$producto_id]['unidades'] += $detalle['cantidad'];
                    $resumen_productos[$producto_id]['total_usd'] += $detalle['subtotal'];
                    $resumen_productos[$producto_id]['total_bs'] += $detalle['subtotal_bs'];
                }

                // Resumen por cliente
                $cliente_id = $venta['cliente_id'];
                $cliente_nombre = $venta['cliente_nombre'] ?? 'Cliente no identificado';

                if (!isset($resumen_clientes[$cliente_id])) {
                    $resumen_clientes[$cliente_id] = [
                        'nombre' => $cliente_nombre,
                        'documento' => $venta['cliente_documento'] ?? '',
                        'ventas' => 0,
                        'total_usd' => 0,
                        'total_bs' => 0
                    ];
                }
                $resumen_clientes[$cliente_id]['ventas']++;
                $resumen_clientes[$cliente_id]['total_usd'] += $venta['total'];
                $resumen_clientes[$cliente_id]['total_bs'] += $venta['total_bs'];
            }

            // Preparar datos para respuesta
            $datos = [
                'ventas' => $ventas,
                'total_ventas' => $total_ventas,
                'total_unidades' => $total_unidades,
                'total_usd' => $total_usd,
                'total_bs' => $total_bs,
                'totales_por_tipo_usd' => $totales_por_tipo_usd,
                'totales_por_tipo_bs' => $totales_por_tipo_bs,
                'resumen_categorias' => $resumen_categorias,
                'resumen_productos' => array_values($resumen_productos),
                'resumen_clientes' => array_values($resumen_clientes),
                'ventas_ids' => $ventas_ids
            ];

            return [
                "success" => true,
                "message" => "Ventas pendientes obtenidas correctamente",
                "data" => $datos
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener ventas pendientes: " . $e->getMessage()
            ];
        }
    }

    /**
    /**
     * Crear cierre automáticamente con TODAS las ventas pendientes
     */
    public function crearCierreAutomatico($usuario_id, $observaciones = '')
    {
        try {
            $this->db->beginTransaction();

            $fecha = date('Y-m-d');
            $hora = date('H:i:s');

            // Obtener todas las ventas pendientes automáticamente
            $datos_ventas = $this->obtenerVentasPendientes();
            
            if (!$datos_ventas['success']) {
                throw new Exception($datos_ventas['message']);
            }

            $data = $datos_ventas['data'];

            if ($data['total_ventas'] == 0) {
                throw new Exception("No hay ventas pendientes para cerrar");
            }

            // Preparar datos para el cierre - AHORA COMPLETOS
            $cierre_data = [
                'fecha' => $fecha,
                'usuario_id' => $usuario_id,
                'total_ventas' => $data['total_ventas'],
                'total_unidades' => $data['total_unidades'],
                'total_usd' => $data['total_usd'],
                'total_bs' => $data['total_bs'],
                'observaciones' => $observaciones ?: "Cierre automático realizado a las $hora",
                'estado' => 'completado'
            ];

            // Asignar totales por tipo de pago - INICIALIZAR TODOS LOS CAMPOS
            $tipos_pago = [
                'efectivo' => ['usd' => 0, 'bs' => 0],
                'efectivo_usd' => ['usd' => 0, 'bs' => 0],
                'transferencia' => ['usd' => 0, 'bs' => 0],
                'pago_móvil' => ['usd' => 0, 'bs' => 0],
                'tarjeta_de_débito' => ['usd' => 0, 'bs' => 0],
                'tarjeta_de_crédito' => ['usd' => 0, 'bs' => 0],
                'divisa' => ['usd' => 0, 'bs' => 0],
                'crédito' => ['usd' => 0, 'bs' => 0]
            ];

            // Asignar valores de los tipos de pago que existen
            foreach ($data['totales_por_tipo_usd'] as $tipo => $total_usd) {
                if (isset($tipos_pago[$tipo])) {
                    $tipos_pago[$tipo]['usd'] = $total_usd;
                    $tipos_pago[$tipo]['bs'] = $data['totales_por_tipo_bs'][$tipo] ?? 0;
                }
            }

            // Asignar a cierre_data - TODOS LOS CAMPOS REQUERIDOS
            $cierre_data['efectivo_usd'] = $tipos_pago['efectivo']['usd'];
            $cierre_data['efectivo_bs'] = $tipos_pago['efectivo']['bs'];
            $cierre_data['efectivo_bs_usd'] = $tipos_pago['efectivo_usd']['usd'];
            $cierre_data['efectivo_bs_bs'] = $tipos_pago['efectivo_usd']['bs'];
            $cierre_data['transferencia_usd'] = $tipos_pago['transferencia']['usd'];
            $cierre_data['transferencia_bs'] = $tipos_pago['transferencia']['bs'];
            $cierre_data['pago_movil_usd'] = $tipos_pago['pago_móvil']['usd'];
            $cierre_data['pago_movil_bs'] = $tipos_pago['pago_móvil']['bs'];
            $cierre_data['tarjeta_debito_usd'] = $tipos_pago['tarjeta_de_débito']['usd'];
            $cierre_data['tarjeta_debito_bs'] = $tipos_pago['tarjeta_de_débito']['bs'];
            $cierre_data['tarjeta_credito_usd'] = $tipos_pago['tarjeta_de_crédito']['usd'];
            $cierre_data['tarjeta_credito_bs'] = $tipos_pago['tarjeta_de_crédito']['bs'];
            $cierre_data['divisa_usd'] = $tipos_pago['divisa']['usd'];
            $cierre_data['divisa_bs'] = $tipos_pago['divisa']['bs'];
            $cierre_data['credito_usd'] = $tipos_pago['crédito']['usd'];
            $cierre_data['credito_bs'] = $tipos_pago['crédito']['bs'];

            // Convertir resúmenes a JSON
            $cierre_data['resumen_categorias'] = json_encode($data['resumen_categorias']);
            $cierre_data['resumen_productos'] = json_encode($data['resumen_productos']);
            $cierre_data['resumen_clientes'] = json_encode($data['resumen_clientes']);
            $cierre_data['ventas_ids'] = '{' . implode(',', $data['ventas_ids']) . '}';

            // Log para debug
            error_log("Datos del cierre a insertar: " . print_r($cierre_data, true));

            // Crear el cierre
            $cierre_id = $this->cierreCaja->crear($cierre_data);

            if (!$cierre_id) {
                throw new Exception("Error al crear el cierre de caja");
            }

            // Marcar TODAS las ventas pendientes como cerradas
            $ventas_cerradas = $this->marcarTodasVentasPendientesComoCerradas();

            error_log("Cierre de caja automático #$cierre_id creado a las $hora: Marcadas $ventas_cerradas ventas como cerradas");

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Cierre de caja realizado exitosamente. Se cerraron $ventas_cerradas ventas pendientes.",
                "cierre_id" => $cierre_id,
                "ventas_cerradas" => $ventas_cerradas,
                "data" => $cierre_data
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en crearCierreAutomatico: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al crear cierre de caja: " . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener número de cierre
     */
    private function obtenerNumeroCierre($cierre_id)
    {
        $query = "SELECT numero_cierre FROM cierres_caja WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $cierre_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['numero_cierre'] ?? $cierre_id;
    }

    /**
     * Modificar método para verificar si hay cierre hoy
     * Ahora solo verifica el último cierre, pero permite múltiples
     */
    public function existeCierreHoy()
    {
        // Solo verifica si hay ALGÚN cierre hoy, pero permite múltiples
        $query = "SELECT COUNT(*) as total 
                  FROM cierres_caja 
                  WHERE fecha = CURRENT_DATE 
                  AND estado = 'completado'";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] > 0;
    }

    /**
     * Obtener el último cierre del día
     */
    public function obtenerUltimoCierreHoy()
    {
        $query = "SELECT cc.*, u.nombre as usuario_nombre 
                  FROM cierres_caja cc
                  JOIN usuarios u ON cc.usuario_id = u.id
                  WHERE cc.fecha = CURRENT_DATE 
                  AND cc.estado = 'completado'
                  ORDER BY cc.created_at DESC 
                  LIMIT 1";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }


    /**
     * Marcar TODAS las ventas pendientes como cerradas
     */
    private function marcarTodasVentasPendientesComoCerradas()
    {
        $query = "UPDATE ventas 
                  SET cerrada_en_caja = TRUE 
                  WHERE estado = 'completada'
                  AND cerrada_en_caja = FALSE
                  RETURNING id";

        $stmt = $this->db->prepare($query);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Método original para compatibilidad (ahora usa el automático)
     */
    public function crearCierre($usuario_id, $observaciones = '')
    {
        return $this->crearCierreAutomatico($usuario_id, $observaciones);
    }

    /**
     * Verificar si hay ventas pendientes
     */
    public function hayVentasPendientes()
    {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM ventas 
                      WHERE estado = 'completada'
                      AND cerrada_en_caja = FALSE";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

            return $resultado['total'] > 0;
        } catch (Exception $e) {
            error_log("Error al verificar ventas pendientes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener resumen de ventas pendientes
     */
    public function obtenerResumenPendientes()
    {
        try {
            $datos_ventas = $this->obtenerVentasPendientes();

            if (!$datos_ventas['success']) {
                return $datos_ventas;
            }

            $data = $datos_ventas['data'];

            // Formatear para mostrar
            $resumen = [
                'total_ventas' => $data['total_ventas'],
                'total_unidades' => $data['total_unidades'],
                'total_usd' => TasaCambioHelper::formatearUSD($data['total_usd']),
                'total_bs' => TasaCambioHelper::formatearBS($data['total_bs']),
                'totales_por_tipo' => [],
                'ventas_lista' => []
            ];

            // Agregar totales por tipo de pago
            foreach ($data['totales_por_tipo_usd'] as $tipo => $total_usd) {
                $total_bs = $data['totales_por_tipo_bs'][$tipo] ?? 0;
                if ($total_usd > 0 || $total_bs > 0) {
                    $resumen['totales_por_tipo'][] = [
                        'tipo' => ucwords(str_replace('_', ' ', $tipo)),
                        'total_usd' => TasaCambioHelper::formatearUSD($total_usd),
                        'total_bs' => TasaCambioHelper::formatearBS($total_bs)
                    ];
                }
            }

            // Agregar lista simplificada de ventas
            foreach ($data['ventas'] as $venta) {
                $resumen['ventas_lista'][] = [
                    'id' => $venta['id'],
                    'numero_venta' => $venta['numero_venta'],
                    'fecha_hora' => date('d/m/Y H:i', strtotime($venta['fecha_hora'])),
                    'cliente' => $venta['cliente_nombre'] ?? 'Cliente no identificado',
                    'total_usd' => TasaCambioHelper::formatearUSD($venta['total']),
                    'total_bs' => TasaCambioHelper::formatearBS($venta['total_bs'])
                ];
            }

            return [
                "success" => true,
                "data" => $resumen
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener resumen pendiente: " . $e->getMessage()
            ];
        }
    }

    // Métodos existentes que se mantienen...
    public function listarCierres($limit = 30)
    {
        try {
            $stmt = $this->cierreCaja->listar($limit);
            $cierres = $stmt->fetchAll();

            // Formatear datos para mostrar
            foreach ($cierres as &$cierre) {
                $cierre['total_general_usd'] = TasaCambioHelper::formatearUSD(
                    $cierre['total_usd']
                );
                $cierre['total_general_bs'] = TasaCambioHelper::formatearBS(
                    $cierre['total_bs']
                );
                $cierre['fecha_formateada'] = date('d/m/Y', strtotime($cierre['fecha']));

                // Calcular total general
                $cierre['total_efectivo_usd'] = $cierre['efectivo_usd'] + $cierre['efectivo_bs_usd'];
                $cierre['total_efectivo_bs'] = $cierre['efectivo_bs'] + $cierre['efectivo_bs_bs'];
            }

            return [
                "success" => true,
                "data" => $cierres
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al listar cierres de caja: " . $e->getMessage()
            ];
        }
    }

    public function obtenerDetalleCierre($id)
    {
        try {
            $cierre = $this->cierreCaja->obtenerPorId($id);

            if (!$cierre) {
                return [
                    "success" => false,
                    "message" => "Cierre de caja no encontrado"
                ];
            }

            // Decodificar JSON
            $cierre['resumen_categorias'] = json_decode($cierre['resumen_categorias'], true);
            $cierre['resumen_productos'] = json_decode($cierre['resumen_productos'], true);
            $cierre['resumen_clientes'] = json_decode($cierre['resumen_clientes'], true);

            // Obtener reporte detallado
            $reporte = $this->cierreCaja->obtenerReporteDetallado($id);

            return [
                "success" => true,
                "data" => [
                    'cierre' => $cierre,
                    'reporte' => $reporte
                ]
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener detalle del cierre: " . $e->getMessage()
            ];
        }
    }

}