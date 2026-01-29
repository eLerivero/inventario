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

    public function obtenerDatosDia($fecha = null)
    {
        try {
            if ($fecha === null) {
                $fecha = date('Y-m-d');
            }

            // Verificar si ya existe cierre para esta fecha
            $cierre_existente = $this->cierreCaja->obtenerPorFecha($fecha);
            if ($cierre_existente) {
                return [
                    "success" => false,
                    "message" => "Ya existe un cierre de caja para esta fecha",
                    "data" => $cierre_existente
                ];
            }

            // Obtener ventas del día
            $ventas = $this->cierreCaja->obtenerVentasDelDia($fecha);
            
            if (empty($ventas)) {
                return [
                    "success" => false,
                    "message" => "No hay ventas completadas para el día de hoy",
                    "data" => []
                ];
            }

            // Obtener todos los tipos de pago usando el método leer() en lugar de listar()
            $tipos_pago = $this->tipoPago->leer();  // ¡CORRECCIÓN AQUÍ!
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
                'fecha' => $fecha,
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
                "message" => "Datos del día obtenidos correctamente",
                "data" => $datos
            ];

        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener datos del día: " . $e->getMessage()
            ];
        }
    }

    public function crearCierre($usuario_id, $observaciones = '')
    {
        try {
            $this->db->beginTransaction();

            // Obtener datos del día
            $datos_dia = $this->obtenerDatosDia();
            
            if (!$datos_dia['success']) {
                throw new Exception($datos_dia['message']);
            }

            $data = $datos_dia['data'];
            $fecha = $data['fecha'];

            // Preparar datos para el cierre
            $cierre_data = [
                'fecha' => $fecha,
                'usuario_id' => $usuario_id,
                'total_ventas' => $data['total_ventas'],
                'total_unidades' => $data['total_unidades'],
                'total_usd' => $data['total_usd'],
                'total_bs' => $data['total_bs'],
                'observaciones' => $observaciones,
                'estado' => 'completado'
            ];

            // Asignar totales por tipo de pago
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

            foreach ($data['totales_por_tipo_usd'] as $tipo => $total_usd) {
                if (isset($tipos_pago[$tipo])) {
                    $tipos_pago[$tipo]['usd'] = $total_usd;
                    $tipos_pago[$tipo]['bs'] = $data['totales_por_tipo_bs'][$tipo] ?? 0;
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

            // Convertir resúmenes a JSON
            $cierre_data['resumen_categorias'] = json_encode($data['resumen_categorias']);
            $cierre_data['resumen_productos'] = json_encode($data['resumen_productos']);
            $cierre_data['resumen_clientes'] = json_encode($data['resumen_clientes']);
            $cierre_data['ventas_ids'] = '{' . implode(',', $data['ventas_ids']) . '}';

            // Crear el cierre
            $cierre_id = $this->cierreCaja->crear($cierre_data);
            
            if (!$cierre_id) {
                throw new Exception("Error al crear el cierre de caja");
            }

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Cierre de caja creado exitosamente",
                "cierre_id" => $cierre_id,
                "data" => $cierre_data
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "message" => "Error al crear cierre de caja: " . $e->getMessage()
            ];
        }
    }

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

    public function existeCierreHoy()
    {
        return $this->cierreCaja->existeCierreHoy();
    }

    public function obtenerResumenHoy()
    {
        try {
            $datos_dia = $this->obtenerDatosDia();
            
            if (!$datos_dia['success']) {
                return $datos_dia;
            }

            $data = $datos_dia['data'];
            
            // Formatear para mostrar
            $resumen = [
                'fecha' => $data['fecha'],
                'fecha_formateada' => date('d/m/Y', strtotime($data['fecha'])),
                'total_ventas' => $data['total_ventas'],
                'total_unidades' => $data['total_unidades'],
                'total_usd' => TasaCambioHelper::formatearUSD($data['total_usd']),
                'total_bs' => TasaCambioHelper::formatearBS($data['total_bs']),
                'totales_por_tipo' => []
            ];

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

            return [
                "success" => true,
                "data" => $resumen
            ];

        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => "Error al obtener resumen del día: " . $e->getMessage()
            ];
        }
    }
}