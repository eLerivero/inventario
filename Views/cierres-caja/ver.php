<?php
// INICIAR SESIÓN PRIMERO
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// REQUERIR ACCESO A CIERRE DE CAJA
require_once __DIR__ . '/../../Utils/Auth.php';
Auth::requireAuth();
Auth::requireAccessToCierreCaja();

// Incluir controladores
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/CierreCajaController.php';
require_once __DIR__ . '/../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();
$cierreController = new CierreCajaController($db);

// IDs de tipos de pago en USD
define('TIPOS_PAGO_USD', [2, 7]); // Efectivo USD y Divisa

// Obtener ID o fecha del cierre
$cierre_id = $_GET['id'] ?? null;
$fecha = $_GET['fecha'] ?? null;

if ($cierre_id) {
    $resultado = $cierreController->obtenerDetalleCierre($cierre_id);
} elseif ($fecha) {
    // Buscar cierre por fecha
    require_once __DIR__ . '/../../Models/CierreCaja.php';
    $cierreModel = new CierreCaja($db);
    $cierre = $cierreModel->obtenerPorFecha($fecha);
    
    if ($cierre) {
        $resultado = $cierreController->obtenerDetalleCierre($cierre['id']);
    } else {
        $resultado = ['success' => false, 'message' => 'No se encontró cierre para esta fecha'];
    }
} else {
    $resultado = ['success' => false, 'message' => 'ID o fecha no especificados'];
}

if (!$resultado['success']) {
    $_SESSION['error'] = $resultado['message'];
    header("Location: index.php");
    exit();
}

$data = $resultado['data'];
$cierre = $data['cierre'];
$reporte = $data['reporte'];

// CORRECCIÓN: Verificar si los resúmenes son strings JSON o ya son arrays
$resumen_categorias = $cierre['resumen_categorias'] ?? [];
$resumen_productos = $cierre['resumen_productos'] ?? [];
$resumen_clientes = $cierre['resumen_clientes'] ?? [];

// Si son strings JSON, decodificarlos
if (is_string($resumen_categorias)) {
    $resumen_categorias = json_decode($resumen_categorias, true) ?? [];
}
if (is_string($resumen_productos)) {
    $resumen_productos = json_decode($resumen_productos, true) ?? [];
}
if (is_string($resumen_clientes)) {
    $resumen_clientes = json_decode($resumen_clientes, true) ?? [];
}

// Obtener el detalle de pagos para este cierre
$pagos_detalle = [];
$pagos_usd = [];
$pagos_bs = [];

if (!empty($cierre['ventas_ids'])) {
    // Convertir ventas_ids a array
    $ventas_ids = [];
    if (is_string($cierre['ventas_ids'])) {
        $ventas_ids = explode(',', trim($cierre['ventas_ids'], '{}'));
    } elseif (is_array($cierre['ventas_ids'])) {
        $ventas_ids = $cierre['ventas_ids'];
    }
    
    if (!empty($ventas_ids)) {
        // Consultar pagos_venta con JOIN a tipos_pago para obtener toda la información
        $placeholders = implode(',', array_fill(0, count($ventas_ids), '?'));
        $query_pagos = "SELECT 
                            pv.*,
                            tp.id as tipo_pago_id_real,
                            tp.nombre as tipo_pago_nombre,
                            tp.descripcion as tipo_pago_descripcion,
                            v.numero_venta,
                            v.fecha_hora as venta_fecha
                        FROM pagos_venta pv
                        JOIN tipos_pago tp ON pv.tipo_pago_id = tp.id
                        JOIN ventas v ON pv.venta_id = v.id
                        WHERE pv.venta_id IN ($placeholders)
                        ORDER BY v.fecha_hora DESC, pv.fecha_pago ASC";
        
        $stmt_pagos = $db->prepare($query_pagos);
        foreach ($ventas_ids as $index => $id) {
            $stmt_pagos->bindValue($index + 1, intval($id), PDO::PARAM_INT);
        }
        $stmt_pagos->execute();
        $pagos_detalle = $stmt_pagos->fetchAll();
        
        // Separar pagos por tipo de moneda
        foreach ($pagos_detalle as $pago) {
            if (in_array($pago['tipo_pago_id'], TIPOS_PAGO_USD)) {
                $pagos_usd[] = $pago;
            } else {
                $pagos_bs[] = $pago;
            }
        }
    }
}

// Calcular totales
$total_usd_pagos = array_sum(array_column($pagos_usd, 'monto_usd'));
$total_bs_pagos = array_sum(array_column($pagos_bs, 'monto_bs'));

// Resumen por método de pago en USD
$resumen_metodos_usd = [];
foreach ($pagos_usd as $pago) {
    $metodo = $pago['tipo_pago_nombre'];
    if (!isset($resumen_metodos_usd[$metodo])) {
        $resumen_metodos_usd[$metodo] = 0;
    }
    $resumen_metodos_usd[$metodo] += $pago['monto_usd'];
}

// Resumen por método de pago en BS
$resumen_metodos_bs = [];
foreach ($pagos_bs as $pago) {
    $metodo = $pago['tipo_pago_nombre'];
    if (!isset($resumen_metodos_bs[$metodo])) {
        $resumen_metodos_bs[$metodo] = 0;
    }
    $resumen_metodos_bs[$metodo] += $pago['monto_bs'];
}

$page_title = "Reporte de Cierre de Caja - " . date('d/m/Y', strtotime($cierre['fecha']));
require_once '../layouts/header.php';
?>

<style>
    .card-usd {
        border-left: 5px solid #28a745;
        border-radius: 8px;
    }
    .card-bs {
        border-left: 5px solid #ffc107;
        border-radius: 8px;
    }
    .badge-usd {
        background-color: #d4edda;
        color: #155724;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .badge-bs {
        background-color: #fff3cd;
        color: #856404;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 20px;
    }
    .total-usd {
        color: #28a745;
        font-weight: bold;
        font-size: 1.2em;
    }
    .total-bs {
        color: #ffc107;
        font-weight: bold;
        font-size: 1.2em;
    }
    .table-pagos-usd tbody tr {
        background-color: #f8fff8;
    }
    .table-pagos-usd tbody tr:hover {
        background-color: #e8f5e8;
    }
    .table-pagos-bs tbody tr {
        background-color: #fffaf0;
    }
    .table-pagos-bs tbody tr:hover {
        background-color: #fff3e0;
    }
    .separator-pagos {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 30px 0;
    }
    .separator-pagos::before,
    .separator-pagos::after {
        content: '';
        flex: 1;
        border-bottom: 2px dashed #dee2e6;
    }
    .separator-pagos span {
        padding: 0 15px;
        font-weight: 600;
        color: #6c757d;
    }
</style>

<div class="content-wrapper">
    <!-- Header con opciones de impresión -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">
                <i class="fas fa-file-alt me-2"></i>
                Reporte de Cierre de Caja
            </h1>
            <p class="text-muted mb-0">
                Fecha: <?php echo date('d/m/Y', strtotime($cierre['fecha'])); ?> 
                | Usuario: <?php echo htmlspecialchars($cierre['usuario_nombre'] ?? 'N/A'); ?>
                | Cierre #<?php echo $cierre['numero_cierre'] ?? $cierre['id']; ?>
            </p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <button onclick="window.print()" class="btn btn-outline-secondary me-2">
                <i class="fas fa-print me-1"></i> Imprimir
            </button>
            <a href="index.php" class="btn btn-outline-primary ms-2">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    <!-- RESUMEN GENERAL - Solo números totales -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card card-usd">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-dollar-sign me-2"></i>
                        TOTAL EN DÓLARES (USD)
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-4 total-usd"><?php echo TasaCambioHelper::formatearUSD($total_usd_pagos); ?></h1>
                    <p class="text-muted">Monto total recibido en efectivo USD y divisas</p>
                   
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-bs">
                <div class="card-header bg-warning text-dark">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        TOTAL EN BOLÍVARES (BS)
                    </h5>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-4 total-bs"><?php echo TasaCambioHelper::formatearBS($total_bs_pagos); ?></h1>
                    <p class="text-muted">Monto total recibido en bolívares</p>
                    
                </div>
            </div>
        </div>
    </div>


<!-- Reporte Detallado de Productos - MEJORADO -->
<div class="card mt-4">
    <div class="card-header bg-primary text-white">
        <h5 class="card-title mb-0">
            <i class="fas fa-boxes me-2"></i>
            DETALLE DE PRODUCTOS VENDIDOS POR TIPO DE PAGO
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($reporte)): ?>
            
            <?php
            // Obtener los pagos por venta para asociarlos con los productos
            $pagos_por_venta = [];
            foreach ($pagos_detalle as $pago) {
                $venta_id = $pago['venta_id'];
                if (!isset($pagos_por_venta[$venta_id])) {
                    $pagos_por_venta[$venta_id] = [];
                }
                $pagos_por_venta[$venta_id][] = $pago;
            }
            
            // Reorganizar el reporte por venta para mostrar los pagos asociados
            $ventas_con_productos = [];
            foreach ($reporte as $item) {
                $venta_id = null; // Necesitamos obtener el venta_id de alguna manera
                // Por ahora, usaremos el número de venta del reporte si está disponible
                $numero_venta = $item['numero_venta'] ?? 'N/A';
                
                if (!isset($ventas_con_productos[$numero_venta])) {
                    $ventas_con_productos[$numero_venta] = [
                        'productos' => [],
                        'pagos' => []
                    ];
                }
                $ventas_con_productos[$numero_venta]['productos'][] = $item;
            }
            
            // Asociar pagos a las ventas
            foreach ($pagos_por_venta as $venta_id => $pagos) {
                // Buscar el número de venta correspondiente
                $numero_venta = 'N/A';
                foreach ($pagos as $pago) {
                    if (isset($pago['numero_venta'])) {
                        $numero_venta = $pago['numero_venta'];
                        break;
                    }
                }
                
                if (isset($ventas_con_productos[$numero_venta])) {
                    $ventas_con_productos[$numero_venta]['pagos'] = $pagos;
                }
            }
            ?>
            
            <div class="accordion" id="accordionProductos">
                <?php 
                $total_unidades = 0;
                $total_usd_por_tipo = [];
                $total_bs_por_tipo = [];
                $venta_index = 0;
                
                foreach ($ventas_con_productos as $numero_venta => $data): 
                    if (empty($data['productos'])) continue;
                    
                    $venta_index++;
                    $total_venta_usd = 0;
                    $total_venta_bs = 0;
                    $metodos_pago_venta = [];
                    
                    // Calcular totales de la venta y métodos de pago
                    foreach ($data['pagos'] as $pago) {
                        if (in_array($pago['tipo_pago_id'], TIPOS_PAGO_USD)) {
                            $total_venta_usd += $pago['monto_usd'];
                            $metodo = $pago['tipo_pago_nombre'];
                            if (!isset($metodos_pago_venta[$metodo])) {
                                $metodos_pago_venta[$metodo] = ['usd' => 0, 'bs' => 0];
                            }
                            $metodos_pago_venta[$metodo]['usd'] += $pago['monto_usd'];
                            
                            // Acumular totales por tipo
                            if (!isset($total_usd_por_tipo[$metodo])) {
                                $total_usd_por_tipo[$metodo] = 0;
                            }
                            $total_usd_por_tipo[$metodo] += $pago['monto_usd'];
                            
                        } else {
                            $total_venta_bs += $pago['monto_bs'];
                            $metodo = $pago['tipo_pago_nombre'];
                            if (!isset($metodos_pago_venta[$metodo])) {
                                $metodos_pago_venta[$metodo] = ['usd' => 0, 'bs' => 0];
                            }
                            $metodos_pago_venta[$metodo]['bs'] += $pago['monto_bs'];
                            
                            // Acumular totales por tipo
                            if (!isset($total_bs_por_tipo[$metodo])) {
                                $total_bs_por_tipo[$metodo] = 0;
                            }
                            $total_bs_por_tipo[$metodo] += $pago['monto_bs'];
                        }
                    }
                ?>
                
                <div class="accordion-item mb-3 border rounded">
                    <h2 class="accordion-header" id="heading<?php echo $venta_index; ?>">
                        <button class="accordion-button <?php echo $venta_index > 1 ? 'collapsed' : ''; ?>" 
                                type="button" data-bs-toggle="collapse" 
                                data-bs-target="#collapse<?php echo $venta_index; ?>" 
                                aria-expanded="<?php echo $venta_index == 1 ? 'true' : 'false'; ?>" 
                                aria-controls="collapse<?php echo $venta_index; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-receipt me-2"></i>
                                    <strong>Venta: <?php echo htmlspecialchars($numero_venta); ?></strong>
                                    <span class="badge bg-secondary ms-2"><?php echo count($data['productos']); ?> producto(s)</span>
                                </div>
                                <div class="me-3">
                                    <?php if ($total_venta_usd > 0): ?>
                                        <span class="badge bg-success me-2">
                                            <i class="fas fa-dollar-sign"></i> <?php echo TasaCambioHelper::formatearUSD($total_venta_usd); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($total_venta_bs > 0): ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-bolt"></i> <?php echo TasaCambioHelper::formatearBS($total_venta_bs); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse<?php echo $venta_index; ?>" 
                         class="accordion-collapse collapse <?php echo $venta_index == 1 ? 'show' : ''; ?>" 
                         aria-labelledby="heading<?php echo $venta_index; ?>" 
                         data-bs-parent="#accordionProductos">
                        <div class="accordion-body p-0">
                            <!-- Métodos de pago de esta venta -->
                            <?php if (!empty($metodos_pago_venta)): ?>
                            <div class="bg-light p-3 border-bottom">
                                <h6 class="mb-2"><i class="fas fa-credit-card me-2"></i>Métodos de pago utilizados:</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($metodos_pago_venta as $metodo => $montos): ?>
                                        <div class="p-2 rounded <?php echo $montos['usd'] > 0 ? 'bg-success text-white' : 'bg-warning'; ?>">
                                            <span class="fw-bold"><?php echo htmlspecialchars($metodo); ?>:</span>
                                            <?php if ($montos['usd'] > 0): ?>
                                                <span class="ms-1"><?php echo TasaCambioHelper::formatearUSD($montos['usd']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($montos['bs'] > 0): ?>
                                                <span class="ms-1"><?php echo TasaCambioHelper::formatearBS($montos['bs']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Tabla de productos de esta venta -->
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Categoría</th>
                                            <th>Producto</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio Unitario</th>
                                            <th class="text-end">Subtotal</th>
                                            <th>Cliente</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['productos'] as $item): 
                                            $total_unidades += $item['cantidad_vendida'];
                                            $precio_unitario = $item['precio_unitario_usd'] ?? 0;
                                            $subtotal = $item['subtotal_usd'] ?? 0;
                                            $moneda = 'USD';
                                            $clase_moneda = 'text-success';
                                            
                                            // Determinar si el producto se pagó en BS (por el contexto de la venta)
                                            $producto_en_bs = false;
                                            foreach ($data['pagos'] as $pago) {
                                                if (!in_array($pago['tipo_pago_id'], TIPOS_PAGO_USD)) {
                                                    $producto_en_bs = true;
                                                    break;
                                                }
                                            }
                                            
                                            // Si la venta tiene pagos en BS y el producto tiene precio en BS
                                            if ($producto_en_bs && ($item['precio_unitario_bs'] ?? 0) > 0) {
                                                $precio_unitario = $item['precio_unitario_bs'];
                                                $subtotal = $item['subtotal_bs'];
                                                $moneda = 'BS';
                                                $clase_moneda = 'text-warning';
                                            }
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['categoria_nombre'] ?? 'Sin categoría'); ?></td>
                                            <td><?php echo htmlspecialchars($item['producto_nombre']); ?></td>
                                            <td class="text-center"><?php echo $item['cantidad_vendida']; ?></td>
                                            <td class="text-end <?php echo $clase_moneda; ?>">
                                                <?php if ($moneda == 'USD'): ?>
                                                    <span class="fw-bold"><?php echo TasaCambioHelper::formatearUSD($precio_unitario); ?></span>
                                                <?php else: ?>
                                                    <span class="fw-bold"><?php echo TasaCambioHelper::formatearBS($precio_unitario); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end <?php echo $clase_moneda; ?>">
                                                <?php if ($moneda == 'USD'): ?>
                                                    <span class="fw-bold"><?php echo TasaCambioHelper::formatearUSD($subtotal); ?></span>
                                                <?php else: ?>
                                                    <span class="fw-bold"><?php echo TasaCambioHelper::formatearBS($subtotal); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['cliente_nombre'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total Venta:</strong></td>
                                            <td colspan="2" class="text-end">
                                                <?php if ($total_venta_usd > 0): ?>
                                                    <span class="text-success fw-bold me-3"><?php echo TasaCambioHelper::formatearUSD($total_venta_usd); ?></span>
                                                <?php endif; ?>
                                                <?php if ($total_venta_bs > 0): ?>
                                                    <span class="text-warning fw-bold"><?php echo TasaCambioHelper::formatearBS($total_venta_bs); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Resumen Global por Tipo de Pago -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                RESUMEN GLOBAL POR TIPO DE PAGO
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="fas fa-dollar-sign me-2"></i>PAGOS EN USD</h6>
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-success">
                                            <tr>
                                                <th>Método de Pago</th>
                                                <th class="text-end">Total USD</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_usd_global = 0;
                                            foreach ($total_usd_por_tipo as $metodo => $total): 
                                                $total_usd_global += $total;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($metodo); ?></td>
                                                <td class="text-end fw-bold text-success"><?php echo TasaCambioHelper::formatearUSD($total); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($total_usd_por_tipo)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center">No hay pagos en USD</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <?php if (!empty($total_usd_por_tipo)): ?>
                                        <tfoot class="table-success">
                                            <tr>
                                                <th>TOTAL USD</th>
                                                <th class="text-end"><?php echo TasaCambioHelper::formatearUSD($total_usd_global); ?></th>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-warning"><i class="fas fa-bolt me-2"></i>PAGOS EN BS</h6>
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-warning">
                                            <tr>
                                                <th>Método de Pago</th>
                                                <th class="text-end">Total BS</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $total_bs_global = 0;
                                            foreach ($total_bs_por_tipo as $metodo => $total): 
                                                $total_bs_global += $total;
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($metodo); ?></td>
                                                <td class="text-end fw-bold text-warning"><?php echo TasaCambioHelper::formatearBS($total); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($total_bs_por_tipo)): ?>
                                            <tr>
                                                <td colspan="2" class="text-center">No hay pagos en BS</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <?php if (!empty($total_bs_por_tipo)): ?>
                                        <tfoot class="table-warning">
                                            <tr>
                                                <th>TOTAL BS</th>
                                                <th class="text-end"><?php echo TasaCambioHelper::formatearBS($total_bs_global); ?></th>
                                            </tr>
                                        </tfoot>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No hay productos vendidos en este cierre</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

    <!-- Observaciones -->
    <?php if (!empty($cierre['observaciones'])): ?>
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h6 class="card-title mb-0">
                <i class="fas fa-sticky-note me-2"></i>
                OBSERVACIONES
            </h6>
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($cierre['observaciones'])); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pie del Reporte -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6>REALIZADO POR</h6>
                    <p class="mb-0">________________________________</p>
                    <p class="mb-0"><?php echo htmlspecialchars($cierre['usuario_nombre'] ?? ''); ?></p>
                    <p class="text-muted">Usuario del Sistema</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h6>FECHA Y HORA</h6>
                    <p class="mb-0">________________________________</p>
                    <p class="mb-0"><?php echo isset($cierre['created_at']) ? date('d/m/Y H:i:s', strtotime($cierre['created_at'])) : date('d/m/Y H:i:s'); ?></p>
                    <p class="text-muted">Fecha del Cierre</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DataTables CSS y JS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configurar DataTables para el reporte de productos (opcional)
        if (typeof $.fn.DataTable !== 'undefined' && $('#tablaReporte').length) {
            $('#tablaReporte').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                order: [[0, 'asc']],
                pageLength: 20,
                responsive: true,
                paging: true,
                searching: true
            });
        }

        // Estilos para impresión
        const printStyles = `
            @media print {
                .btn, .btn-toolbar, .dataTables_length, .dataTables_filter, 
                .dataTables_info, .dataTables_paginate {
                    display: none !important;
                }
                .card {
                    border: 1px solid #000 !important;
                    break-inside: avoid;
                    page-break-inside: avoid;
                }
                .table-bordered th, .table-bordered td {
                    border: 1px solid #000 !important;
                }
                .content-wrapper {
                    margin: 0 !important;
                    padding: 0 !important;
                }
                .badge-usd {
                    background-color: #d4edda !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .badge-bs {
                    background-color: #fff3cd !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .table-success {
                    background-color: #d4edda !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .table-warning {
                    background-color: #fff3cd !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                h1, h2, h3, h4, h5, h6 {
                    page-break-after: avoid;
                }
                table {
                    page-break-inside: auto;
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
            }
        `;
        
        const styleSheet = document.createElement("style");
        styleSheet.innerText = printStyles;
        document.head.appendChild(styleSheet);
    });
</script>

<!-- <?php require_once '../layouts/footer.php'; ?> -->