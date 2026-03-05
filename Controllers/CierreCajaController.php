<?php
// Controllers/CierreCajaController.php
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
     * Versión SEGURA de existeCierreHoy - SIEMPRE devuelve un número
     */
    public function existeCierreHoy()
    {
        try {
            $query = "SELECT COUNT(*) as total 
                      FROM cierres_caja 
                      WHERE fecha = CURRENT_DATE 
                      AND estado = 'completado'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // SIEMPRE devolver un número, nunca null
            return ($result && isset($result['total'])) ? (int)$result['total'] : 0;
            
        } catch (Exception $e) {
            error_log("Error en existeCierreHoy: " . $e->getMessage());
            return 0; // En caso de error, devolver 0
        }
    }

    /**
     * Versión SEGURA de obtenerUltimoCierreHoy - SIEMPRE devuelve un array
     */
    public function obtenerUltimoCierreHoy()
    {
        try {
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
            
            // Si no hay resultados, devolver array vacío (NO null)
            return [];
            
        } catch (Exception $e) {
            error_log("Error en obtenerUltimoCierreHoy: " . $e->getMessage());
            return []; // En caso de error, devolver array vacío
        }
    }

    /**
     * Versión SEGURA de obtenerResumenPendientes - SIEMPRE devuelve estructura completa
     */
    public function obtenerResumenPendientes()
    {
        // Estructura por defecto (nunca null)
        $default_data = [
            'success' => true,
            'data' => [
                'total_ventas' => 0,
                'total_unidades' => 0,
                'total_usd' => '$0.00',
                'total_bs' => 'Bs 0,00',
                'totales_por_tipo' => [],
                'ventas_lista' => []
            ]
        ];

        try {
            $datos_ventas = $this->obtenerVentasPendientes();

            if (!$datos_ventas['success']) {
                return $default_data;
            }

            if (!isset($datos_ventas['data']) || !is_array($datos_ventas['data'])) {
                return $default_data;
            }

            $data = $datos_ventas['data'];

            // Construir resumen con valores por defecto
            $resumen = [
                'total_ventas' => isset($data['total_ventas']) ? (int)$data['total_ventas'] : 0,
                'total_unidades' => isset($data['total_unidades']) ? (int)$data['total_unidades'] : 0,
                'total_usd' => isset($data['total_usd']) ? TasaCambioHelper::formatearUSD($data['total_usd']) : '$0.00',
                'total_bs' => isset($data['total_bs']) ? TasaCambioHelper::formatearBS($data['total_bs']) : 'Bs 0,00',
                'totales_por_tipo' => [],
                'ventas_lista' => []
            ];

            // Agregar totales por tipo de pago
            if (isset($data['totales_por_tipo_usd']) && is_array($data['totales_por_tipo_usd'])) {
                foreach ($data['totales_por_tipo_usd'] as $tipo => $total_usd) {
                    $total_bs = isset($data['totales_por_tipo_bs'][$tipo]) ? $data['totales_por_tipo_bs'][$tipo] : 0;
                    if ($total_usd > 0 || $total_bs > 0) {
                        $resumen['totales_por_tipo'][] = [
                            'tipo' => ucwords(str_replace('_', ' ', $tipo)),
                            'total_usd' => TasaCambioHelper::formatearUSD($total_usd),
                            'total_bs' => TasaCambioHelper::formatearBS($total_bs)
                        ];
                    }
                }
            }

            // Agregar lista simplificada de ventas
            if (isset($data['ventas']) && is_array($data['ventas'])) {
                foreach ($data['ventas'] as $venta) {
                    $resumen['ventas_lista'][] = [
                        'id' => isset($venta['id']) ? (int)$venta['id'] : 0,
                        'numero_venta' => isset($venta['numero_venta']) ? $venta['numero_venta'] : 'N/A',
                        'fecha_hora' => isset($venta['fecha_hora']) ? date('d/m/Y H:i', strtotime($venta['fecha_hora'])) : 'N/A',
                        'cliente' => isset($venta['cliente_nombre']) ? $venta['cliente_nombre'] : 'Cliente no identificado',
                        'total_usd' => isset($venta['total']) ? TasaCambioHelper::formatearUSD($venta['total']) : '$0.00',
                        'total_bs' => isset($venta['total_bs']) ? TasaCambioHelper::formatearBS($venta['total_bs']) : 'Bs 0,00'
                    ];
                }
            }

            return [
                "success" => true,
                "data" => $resumen
            ];

        } catch (Exception $e) {
            error_log("Error en obtenerResumenPendientes: " . $e->getMessage());
            return $default_data;
        }
    }

    /**
     * Versión SEGURA de listarCierres
     */
    public function listarCierres($limit = 30)
    {
        try {
            $stmt = $this->cierreCaja->listar($limit);
            $cierres = $stmt->fetchAll();

            // Si no hay resultados, devolver array vacío
            if (!$cierres) {
                return [
                    "success" => true,
                    "data" => []
                ];
            }

            // Formatear datos para mostrar
            foreach ($cierres as &$cierre) {
                $cierre['total_general_usd'] = isset($cierre['total_usd']) ? 
                    TasaCambioHelper::formatearUSD($cierre['total_usd']) : '$0.00';
                    
                $cierre['total_general_bs'] = isset($cierre['total_bs']) ? 
                    TasaCambioHelper::formatearBS($cierre['total_bs']) : 'Bs 0,00';
                    
                $cierre['fecha_formateada'] = isset($cierre['fecha']) ? 
                    date('d/m/Y', strtotime($cierre['fecha'])) : 'N/A';

                // Calcular total general con valores por defecto
                $efectivo_usd = isset($cierre['efectivo_usd']) ? $cierre['efectivo_usd'] : 0;
                $efectivo_bs_usd = isset($cierre['efectivo_bs_usd']) ? $cierre['efectivo_bs_usd'] : 0;
                $efectivo_bs = isset($cierre['efectivo_bs']) ? $cierre['efectivo_bs'] : 0;
                $efectivo_bs_bs = isset($cierre['efectivo_bs_bs']) ? $cierre['efectivo_bs_bs'] : 0;
                
                $cierre['total_efectivo_usd'] = $efectivo_usd + $efectivo_bs_usd;
                $cierre['total_efectivo_bs'] = $efectivo_bs + $efectivo_bs_bs;
            }

            return [
                "success" => true,
                "data" => $cierres
            ];
            
        } catch (Exception $e) {
            error_log("Error al listar cierres de caja: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al listar cierres de caja: " . $e->getMessage(),
                "data" => []
            ];
        }
    }

    // ... (resto de métodos, asegurándote de que NUNCA devuelvan null)
    
    public function obtenerVentasPendientes()
    {
        try {
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

            $ventas_ids = array_column($ventas, 'id');
            
            require_once __DIR__ . '/../Models/PagoVenta.php';
            $pagoVentaModel = new PagoVenta($this->db);
            $totales_por_tipo = $pagoVentaModel->obtenerTotalesPorTipoPago($ventas_ids);

            // Asegurar que totes_por_tipo tenga la estructura correcta
            if (!isset($totales_por_tipo['totales_usd']) || !is_array($totales_por_tipo['totales_usd'])) {
                $totales_por_tipo['totales_usd'] = [];
            }
            if (!isset($totales_por_tipo['totales_bs']) || !is_array($totales_por_tipo['totales_bs'])) {
                $totales_por_tipo['totales_bs'] = [];
            }

            // Inicializar arrays para resúmenes
            $resumen_categorias = [];
            $resumen_productos = [];
            $resumen_clientes = [];
            $total_ventas = count($ventas);
            $total_unidades = 0;
            $total_usd = 0;
            $total_bs = 0;

            // Calcular totales generales y resúmenes
            foreach ($ventas as $venta) {
                $total_usd += isset($venta['total']) ? floatval($venta['total']) : 0;
                $total_bs += isset($venta['total_bs']) ? floatval($venta['total_bs']) : 0;

                // Obtener detalles para resúmenes
                $detalles = $this->venta->obtenerDetalles($venta['id']);

                if (is_array($detalles)) {
                    foreach ($detalles as $detalle) {
                        $cantidad = isset($detalle['cantidad']) ? intval($detalle['cantidad']) : 0;
                        $subtotal = isset($detalle['subtotal']) ? floatval($detalle['subtotal']) : 0;
                        $subtotal_bs = isset($detalle['subtotal_bs']) ? floatval($detalle['subtotal_bs']) : 0;
                        
                        $total_unidades += $cantidad;

                        // Resumen por categoría
                        $categoria_nombre = isset($detalle['categoria_nombre']) ? $detalle['categoria_nombre'] : 'Sin categoría';
                        if (!isset($resumen_categorias[$categoria_nombre])) {
                            $resumen_categorias[$categoria_nombre] = [
                                'unidades' => 0,
                                'total_usd' => 0,
                                'total_bs' => 0
                            ];
                        }
                        $resumen_categorias[$categoria_nombre]['unidades'] += $cantidad;
                        $resumen_categorias[$categoria_nombre]['total_usd'] += $subtotal;
                        $resumen_categorias[$categoria_nombre]['total_bs'] += $subtotal_bs;

                        // Resumen por producto
                        $producto_id = isset($detalle['producto_id']) ? $detalle['producto_id'] : 0;
                        if ($producto_id > 0) {
                            if (!isset($resumen_productos[$producto_id])) {
                                $resumen_productos[$producto_id] = [
                                    'nombre' => isset($detalle['producto_nombre']) ? $detalle['producto_nombre'] : 'Producto',
                                    'sku' => isset($detalle['codigo_sku']) ? $detalle['codigo_sku'] : '',
                                    'categoria' => $categoria_nombre,
                                    'unidades' => 0,
                                    'total_usd' => 0,
                                    'total_bs' => 0
                                ];
                            }
                            $resumen_productos[$producto_id]['unidades'] += $cantidad;
                            $resumen_productos[$producto_id]['total_usd'] += $subtotal;
                            $resumen_productos[$producto_id]['total_bs'] += $subtotal_bs;
                        }
                    }
                }

                // Resumen por cliente
                $cliente_id = isset($venta['cliente_id']) ? $venta['cliente_id'] : 0;
                $cliente_nombre = isset($venta['cliente_nombre']) ? $venta['cliente_nombre'] : 'Cliente no identificado';
                
                if (!isset($resumen_clientes[$cliente_id])) {
                    $resumen_clientes[$cliente_id] = [
                        'nombre' => $cliente_nombre,
                        'documento' => isset($venta['cliente_documento']) ? $venta['cliente_documento'] : '',
                        'ventas' => 0,
                        'total_usd' => 0,
                        'total_bs' => 0
                    ];
                }
                $resumen_clientes[$cliente_id]['ventas']++;
                $resumen_clientes[$cliente_id]['total_usd'] += isset($venta['total']) ? floatval($venta['total']) : 0;
                $resumen_clientes[$cliente_id]['total_bs'] += isset($venta['total_bs']) ? floatval($venta['total_bs']) : 0;
            }

            // Preparar datos para respuesta
            $datos = [
                'ventas' => $ventas,
                'total_ventas' => $total_ventas,
                'total_unidades' => $total_unidades,
                'total_usd' => $total_usd,
                'total_bs' => $total_bs,
                'totales_por_tipo_usd' => $totales_por_tipo['totales_usd'],
                'totales_por_tipo_bs' => $totales_por_tipo['totales_bs'],
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
            error_log("Error en obtenerVentasPendientes: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al obtener ventas pendientes: " . $e->getMessage(),
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
    }

    public function crearCierreAutomatico($usuario_id, $observaciones = '')
    {
        try {
            $this->db->beginTransaction();

            $fecha = date('Y-m-d');
            $hora = date('H:i:s');

            $datos_ventas = $this->obtenerVentasPendientes();

            if (!$datos_ventas['success']) {
                throw new Exception($datos_ventas['message']);
            }

            $data = $datos_ventas['data'];

            if ($data['total_ventas'] == 0) {
                throw new Exception("No hay ventas pendientes para cerrar");
            }

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

            // Inicializar todos los tipos de pago a 0
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

            // Asignar valores de los tipos de pago
            foreach ($data['totales_por_tipo_usd'] as $tipo_normalizado => $total_usd) {
                if (isset($tipos_pago[$tipo_normalizado])) {
                    $tipos_pago[$tipo_normalizado]['usd'] = $total_usd;
                    $tipos_pago[$tipo_normalizado]['bs'] = isset($data['totales_por_tipo_bs'][$tipo_normalizado]) ? 
                        $data['totales_por_tipo_bs'][$tipo_normalizado] : 0;
                }
            }

            // Asignar a cierre_data
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

            $cierre_data['resumen_categorias'] = json_encode($data['resumen_categorias']);
            $cierre_data['resumen_productos'] = json_encode($data['resumen_productos']);
            $cierre_data['resumen_clientes'] = json_encode($data['resumen_clientes']);
            $cierre_data['ventas_ids'] = '{' . implode(',', $data['ventas_ids']) . '}';

            $cierre_id = $this->cierreCaja->crear($cierre_data);

            if (!$cierre_id) {
                throw new Exception("Error al crear el cierre de caja");
            }

            // Marcar ventas como cerradas
            $ventas_cerradas = $this->marcarTodasVentasPendientesComoCerradas();

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

    private function marcarTodasVentasPendientesComoCerradas()
    {
        try {
            $query = "UPDATE ventas 
                      SET cerrada_en_caja = TRUE 
                      WHERE estado = 'completada'
                      AND cerrada_en_caja = FALSE
                      RETURNING id";

            $stmt = $this->db->prepare($query);
            $stmt->execute();

            return $stmt->rowCount();
            
        } catch (Exception $e) {
            error_log("Error al marcar ventas como cerradas: " . $e->getMessage());
            return 0;
        }
    }


    public function obtenerDetalleCierre($id)
    {
        try {
            // Validar que el ID sea válido
            $id = intval($id);
            if ($id <= 0) {
                return [
                    "success" => false,
                    "message" => "ID de cierre inválido"
                ];
            }

            // Obtener el cierre por ID
            $cierre = $this->cierreCaja->obtenerPorId($id);

            if (!$cierre || !is_array($cierre)) {
                return [
                    "success" => false,
                    "message" => "Cierre de caja no encontrado"
                ];
            }

            // Decodificar JSON (con valores por defecto)
            $cierre['resumen_categorias'] = isset($cierre['resumen_categorias']) ? 
                json_decode($cierre['resumen_categorias'], true) : [];
            $cierre['resumen_productos'] = isset($cierre['resumen_productos']) ? 
                json_decode($cierre['resumen_productos'], true) : [];
            $cierre['resumen_clientes'] = isset($cierre['resumen_clientes']) ? 
                json_decode($cierre['resumen_clientes'], true) : [];

            // Asegurar que sean arrays
            if (!is_array($cierre['resumen_categorias'])) $cierre['resumen_categorias'] = [];
            if (!is_array($cierre['resumen_productos'])) $cierre['resumen_productos'] = [];
            if (!is_array($cierre['resumen_clientes'])) $cierre['resumen_clientes'] = [];

            // Obtener reporte detallado
            $reporte = $this->cierreCaja->obtenerReporteDetallado($id);
            if (!is_array($reporte)) {
                $reporte = [];
            }

            return [
                "success" => true,
                "data" => [
                    'cierre' => $cierre,
                    'reporte' => $reporte
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error en obtenerDetalleCierre: " . $e->getMessage());
            return [
                "success" => false,
                "message" => "Error al obtener detalle del cierre: " . $e->getMessage()
            ];
        }
    }
}