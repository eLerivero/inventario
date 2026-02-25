<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/VentaController.php';
require_once '../../Utils/Ayuda.php';
require_once '../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();

$controller = new VentaController($db);

// Obtener ID de la venta
$id = $_GET['id'] ?? '';
if (!$id) {
    header('Location: index.php');
    exit();
}

// Obtener datos de la venta
$result = $controller->obtener($id);
if (!$result['success']) {
    $error_message = $result['message'];
    $venta = null;
} else {
    $venta = $result['data'];
    
    // Obtener pagos específicos de la venta
    $pagos_result = $controller->obtenerPagosVenta($id);
    $pagos = $pagos_result['success'] ? $pagos_result['data'] : [];
    $venta['pagos'] = $pagos;
    
    // Calcular totales por moneda
    $total_usd_pagado = 0;
    $total_bs_pagado = 0;
    foreach ($pagos as $pago) {
        $total_usd_pagado += $pago['monto_usd'];
        $total_bs_pagado += $pago['monto_bs'];
    }
    $venta['total_pagado_usd'] = $total_usd_pagado;
    $venta['total_pagado_bs'] = $total_bs_pagado;
    $venta['saldo_pendiente_usd'] = $venta['total'] - $total_usd_pagado;
    $venta['saldo_pendiente_bs'] = $venta['total_bs'] - $total_bs_pagado;
}
?>

<?php
$page_title = "Detalles de Venta";
include '../layouts/header.php';
?>

<!-- ================================================================== -->
<!-- ESTILOS ADICIONALES PARA PAGOS MÚLTIPLES -->
<!-- ================================================================== -->
<style>
    .timeline {
        position: relative;
        padding-left: 0;
    }
    
    .timeline li {
        position: relative;
        padding-left: 0;
    }
    
    .timeline li:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 48px;
        bottom: -20px;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    
    .badge.bg-opacity-10 {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid #e9ecef;
    }
    
    .card-header {
        border-bottom: 1px solid #e9ecef;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Estilos para pagos */
    .pago-item {
        border-left: 4px solid transparent;
        transition: all 0.2s ease;
    }
    
    .pago-item.usd {
        border-left-color: #0d9488;
        background: #f0fdfa;
    }
    
    .pago-item.bs {
        border-left-color: #b45309;
        background: #fff7ed;
    }
    
    .pago-item.mixto {
        border-left-color: #7e22ce;
        background: #f3e8ff;
    }
    
    .pago-monto-usd {
        color: #0d9488;
        font-weight: 600;
    }
    
    .pago-monto-bs {
        color: #b45309;
        font-weight: 600;
    }
    
    .resumen-pago {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 1.5rem;
    }
    
    .badge-multiple {
        background: #f3e8ff;
        color: #7e22ce;
    }
</style>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-receipt me-2"></i>
            Detalles de Venta
        </h1>
        <p class="text-muted mb-0">
            <?php if ($venta): ?>
                Información completa de la venta #<?php echo $venta['numero_venta']; ?>
                <?php if (isset($venta['pagos']) && count($venta['pagos']) > 1): ?>
                    <span class="badge badge-multiple ms-2">
                        <i class="fas fa-layer-group me-1"></i>
                        <?php echo count($venta['pagos']); ?> métodos de pago
                    </span>
                <?php endif; ?>
            <?php else: ?>
                Venta no encontrada
            <?php endif; ?>
        </p>
    </div>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver al Listado
        </a>
        <?php if ($venta && $venta['estado'] === 'pendiente'): ?>
            <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#modalCompletarVenta">
                <i class="fas fa-check-circle me-1"></i> Completar Venta
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!$venta): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        No se encontró la venta solicitada.
        <div class="mt-2">
            <a href="index.php" class="btn btn-sm btn-warning">
                <i class="fas fa-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Tarjeta Resumen Principal -->
    <div class="card mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="card-title mb-0">
                        <i class="fas fa-file-invoice me-2"></i>
                        Venta #<?php echo $venta['numero_venta']; ?>
                    </h5>
                    <small class="opacity-75">
                        <i class="fas fa-calendar me-1"></i>
                        <?php echo !empty($venta['fecha_hora']) ? Ayuda::formatDate($venta['fecha_hora'], 'd/m/Y H:i:s') : Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                    </small>
                </div>
                <div>
                    <?php
                    $estado_badge = [
                        'pendiente' => ['bg-warning text-dark', 'Pendiente', 'fas fa-clock'],
                        'completada' => ['bg-success', 'Completada', 'fas fa-check-circle'],
                        'cancelada' => ['bg-danger', 'Cancelada', 'fas fa-times-circle']
                    ];
                    $estado_info = $estado_badge[$venta['estado']] ?? ['bg-secondary', $venta['estado'], 'fas fa-question-circle'];
                    ?>
                    <span class="badge <?php echo $estado_info[0]; ?> p-2">
                        <i class="<?php echo $estado_info[2]; ?> me-1"></i>
                        <?php echo $estado_info[1]; ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="fas fa-user fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">Cliente</h6>
                            <h5 class="mb-0"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></h5>
                            <?php if (!empty($venta['cliente_documento_identidad'])): ?>
                                <small class="text-muted">DNI: <?php echo htmlspecialchars($venta['cliente_documento_identidad']); ?></small>
                            <?php endif; ?>
                            <?php if (!empty($venta['cliente_telefono'])): ?>
                                <div class="mt-1">
                                    <i class="fas fa-phone text-muted me-1"></i>
                                    <small class="text-muted"><?php echo htmlspecialchars($venta['cliente_telefono']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="fas fa-credit-card fa-2x text-success"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">Método de Pago Principal</h6>
                            <h5 class="mb-0"><?php echo htmlspecialchars($venta['tipo_pago_nombre']); ?></h5>
                            <?php if (isset($venta['pagos']) && count($venta['pagos']) > 1): ?>
                                <small class="text-muted">
                                    <i class="fas fa-layer-group me-1"></i>
                                    + <?php echo count($venta['pagos']) - 1; ?> método(s) adicional(es)
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="d-flex align-items-start mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="fas fa-exchange-alt fa-2x text-info"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 text-muted">Tasa de Cambio</h6>
                            <h5 class="mb-0"><?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$</h5>
                            <small class="text-muted">Aplicada al momento de la venta</small>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($venta['observaciones'])): ?>
                <div class="alert alert-light mt-3">
                    <h6 class="alert-heading mb-2">
                        <i class="fas fa-sticky-note me-1"></i> Observaciones
                    </h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($venta['observaciones'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ================================================================== -->
    <!-- NUEVA SECCIÓN: DETALLE DE PAGOS MÚLTIPLES -->
    <!-- ================================================================== -->
    <?php if (isset($venta['pagos']) && !empty($venta['pagos'])): ?>
    <div class="card mb-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">
                <i class="fas fa-money-bill-wave me-2"></i>
                Detalle de Pagos
                <span class="badge bg-light text-dark ms-2"><?php echo count($venta['pagos']); ?> pago(s)</span>
            </h5>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="resumen-pago">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 text-white-50">Resumen de Pagos</h6>
                            <i class="fas fa-receipt fa-2x opacity-50"></i>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <small class="text-white-50 d-block">Total USD</small>
                                <h3 class="text-white mb-0"><?php echo TasaCambioHelper::formatearUSD($venta['total_pagado_usd'] ?? $venta['total']); ?></h3>
                            </div>
                            <div class="col-6">
                                <small class="text-white-50 d-block">Total Bs</small>
                                <h3 class="text-white mb-0"><?php echo TasaCambioHelper::formatearBS($venta['total_pagado_bs'] ?? $venta['total_bs']); ?></h3>
                            </div>
                        </div>
                        <?php if (($venta['saldo_pendiente_usd'] ?? 0) > 0.01): ?>
                        <div class="mt-3 pt-3 border-top border-white-50">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Saldo Pendiente:</span>
                                <span class="text-white fw-bold">
                                    <?php echo TasaCambioHelper::formatearUSD($venta['saldo_pendiente_usd']); ?> / 
                                    <?php echo TasaCambioHelper::formatearBS($venta['saldo_pendiente_bs']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="bg-light p-3 rounded-3 h-100 d-flex align-items-center">
                        <div class="w-100">
                            <h6 class="text-muted mb-3">Distribución por Moneda</h6>
                            <?php
                            $total_usd = $venta['total_pagado_usd'] ?? $venta['total'];
                            $total_bs = $venta['total_pagado_bs'] ?? $venta['total_bs'];
                            $porcentaje_usd = $venta['total'] > 0 ? ($total_usd / $venta['total']) * 100 : 0;
                            $porcentaje_bs = $venta['total'] > 0 ? (($total_bs / $venta['total_bs']) * 100) : 0;
                            ?>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fas fa-dollar-sign text-success"></i> USD</span>
                                    <span class="fw-bold"><?php echo number_format($porcentaje_usd, 1); ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $porcentaje_usd; ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span><i class="fas fa-bolt text-warning"></i> Bs</span>
                                    <span class="fw-bold"><?php echo number_format($porcentaje_bs, 1); ?>%</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-warning" style="width: <?php echo $porcentaje_bs; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de pagos -->
            <h6 class="mb-3"><i class="fas fa-list-ul me-2"></i>Detalle por Método de Pago</h6>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Método de Pago</th>
                            <th class="text-end">Monto USD</th>
                            <th class="text-end">Monto Bs</th>
                            <th class="text-center">Tasa Aplicada</th>
                            <th class="text-center">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $contador_pago = 1;
                        foreach ($venta['pagos'] as $pago): 
                            $tasa_aplicada = $pago['monto_usd'] > 0 ? $pago['monto_bs'] / $pago['monto_usd'] : 0;
                            $clase_pago = $pago['monto_usd'] > 0 && $pago['monto_bs'] > 0 ? 'mixto' : ($pago['monto_usd'] > 0 ? 'usd' : 'bs');
                        ?>
                            <tr class="pago-item <?php echo $clase_pago; ?>">
                                <td><?php echo $contador_pago++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($pago['monto_usd'] > 0 && $pago['monto_bs'] > 0): ?>
                                            <i class="fas fa-layer-group me-2 text-purple"></i>
                                        <?php elseif ($pago['monto_usd'] > 0): ?>
                                            <i class="fas fa-dollar-sign me-2 text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-bolt me-2 text-warning"></i>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($pago['tipo_pago_nombre']); ?></strong>
                                            <?php if ($pago['monto_usd'] > 0 && $pago['monto_bs'] > 0): ?>
                                                <br><small class="text-muted">Pago mixto</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end pago-monto-usd">
                                    <?php echo TasaCambioHelper::formatearUSD($pago['monto_usd']); ?>
                                </td>
                                <td class="text-end pago-monto-bs">
                                    <?php echo TasaCambioHelper::formatearBS($pago['monto_bs']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($tasa_aplicada > 0): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?php echo number_format($tasa_aplicada, 2); ?> Bs/$
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <small>
                                        <?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'] ?? $pago['created_at'] ?? $venta['fecha_hora'])); ?>
                                    </small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end">Totales:</th>
                            <th class="text-end pago-monto-usd"><?php echo TasaCambioHelper::formatearUSD($venta['total_pagado_usd']); ?></th>
                            <th class="text-end pago-monto-bs"><?php echo TasaCambioHelper::formatearBS($venta['total_pagado_bs']); ?></th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Resumen de Totales -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-success bg-opacity-10 rounded-circle p-3 mb-3">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total en Dólares</h6>
                        <h3 class="text-success mb-0"><?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></h3>
                        <small class="text-muted">Monto total en USD</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3 mb-3">
                            <i class="fas fa-bolt fa-2x text-warning"></i>
                        </div>
                        <h6 class="text-muted mb-2">Total en Bolívares</h6>
                        <h3 class="text-warning mb-0"><?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></h3>
                        <small class="text-muted">Monto total en Bs</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <div class="d-flex flex-column align-items-center">
                        <div class="bg-info bg-opacity-10 rounded-circle p-3 mb-3">
                            <i class="fas fa-boxes fa-2x text-info"></i>
                        </div>
                        <h6 class="text-muted mb-2">Productos Vendidos</h6>
                        <h3 class="text-info mb-0"><?php echo count($venta['detalles']); ?></h3>
                        <small class="text-muted">Items en esta venta</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles de Productos -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-shopping-basket me-2"></i>
                Productos Vendidos
                <span class="badge bg-primary rounded-pill ms-2"><?php echo count($venta['detalles']); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($venta['detalles'])): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay productos en esta venta</h5>
                    <p class="text-muted mb-0">Esta venta no contiene productos registrados</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">
                                    <i class="fas fa-hashtag"></i>
                                </th>
                                <th width="40%">Producto</th>
                                <th width="10%" class="text-center">Cantidad</th>
                                <th width="20%" class="text-center">Precio Unitario</th>
                                <th width="25%" class="text-center">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $contador = 1;
                            $subtotal_usd = 0;
                            $subtotal_bs = 0;
                            foreach ($venta['detalles'] as $detalle):
                                $subtotal_usd += $detalle['subtotal'];
                                $subtotal_bs += $detalle['subtotal_bs'];
                                $es_precio_fijo = $detalle['es_precio_fijo'] ?? false;
                            ?>
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark"><?php echo $contador++; ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 me-3">
                                                <i class="fas fa-box text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($detalle['producto_nombre']); ?></h6>
                                                <div class="small text-muted">
                                                    <span class="me-3">
                                                        <i class="fas fa-barcode me-1"></i>
                                                        <?php echo htmlspecialchars($detalle['codigo_sku']); ?>
                                                    </span>
                                                    <?php if ($es_precio_fijo): ?>
                                                        <span class="badge bg-warning bg-opacity-10 text-warning">
                                                            <i class="fas fa-lock me-1"></i> Precio Fijo
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info bg-opacity-10 text-info">
                                                            <i class="fas fa-calculator me-1"></i> Conversión
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-primary rounded-pill px-3 py-2">
                                            <?php echo $detalle['cantidad']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column">
                                            <strong class="text-primary">
                                                <?php echo $detalle['precio_unitario_formateado_usd'] ?? '$' . number_format($detalle['precio_unitario'], 2); ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?php echo $detalle['precio_unitario_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column">
                                            <strong class="text-success">
                                                <?php echo $detalle['subtotal_formateado_usd'] ?? '$' . number_format($detalle['subtotal'], 2); ?>
                                            </strong>
                                            <small class="text-muted">
                                                <?php echo $detalle['subtotal_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['subtotal_bs']); ?>
                                            </small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end">
                                    <h6 class="mb-0 pt-3">Totales:</h6>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column pt-3">
                                        <strong class="text-primary">
                                            <?php echo '$' . number_format(array_sum(array_column($venta['detalles'], 'precio_unitario')), 2); ?>
                                        </strong>
                                        <small class="text-muted">Precios unitarios</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column pt-3">
                                        <strong class="text-success fs-5">
                                            <?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?>
                                        </strong>
                                        <span class="text-warning">
                                            <?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer bg-light">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 rounded p-2 me-3">
                            <i class="fas fa-info-circle text-info"></i>
                        </div>
                        <div>
                            <small class="text-muted d-block">Venta registrada el</small>
                            <small class="text-dark">
                                <?php echo Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        <i class="fas fa-box me-1"></i>
                        <?php echo count($venta['detalles']); ?> productos |
                        <i class="fas fa-dollar-sign me-1 ms-2"></i>
                        <?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Sistema -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historial de la Venta
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled timeline">
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="timeline-icon bg-success text-white rounded-circle p-2 me-3">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Venta Creada</h6>
                                    <p class="text-muted mb-0 small">
                                        <?php echo Ayuda::formatDate($venta['created_at'], 'd/m/Y H:i:s'); ?>
                                    </p>
                                </div>
                            </div>
                        </li>
                        <?php if ($venta['estado'] === 'completada'): ?>
                            <li class="mb-3">
                                <div class="d-flex">
                                    <div class="timeline-icon bg-info text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Venta Completada</h6>
                                        <p class="text-muted mb-0 small">
                                            Stock actualizado y venta finalizada
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($venta['updated_at']) && $venta['updated_at'] !== $venta['created_at']): ?>
                            <li>
                                <div class="d-flex">
                                    <div class="timeline-icon bg-warning text-white rounded-circle p-2 me-3">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">Última Actualización</h6>
                                        <p class="text-muted mb-0 small">
                                            <?php echo Ayuda::formatDate($venta['updated_at'], 'd/m/Y H:i:s'); ?>
                                        </p>
                                    </div>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        Acciones Disponibles
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> Ver Todas las Ventas
                        </a>
                        <?php if ($venta['estado'] === 'pendiente'): ?>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCompletarVenta">
                                <i class="fas fa-check-circle me-1"></i> Completar Venta
                            </button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-info" onclick="imprimirRecibo()">
                            <i class="fas fa-print me-1"></i> Imprimir Recibo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Completar Venta -->
    <?php if ($venta['estado'] === 'pendiente'): ?>
        <div class="modal fade" id="modalCompletarVenta" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>
                            Completar Venta
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>¡Atención!</strong> Esta acción no se puede deshacer.
                        </div>
                        <p>¿Estás seguro de que deseas completar esta venta?</p>
                        <ul class="text-muted">
                            <li>El estado cambiará a "Completada"</li>
                            <li>El stock de los productos se actualizará</li>
                            <li>La venta será registrada como finalizada</li>
                        </ul>
                        
                        <?php if (isset($venta['pagos']) && count($venta['pagos']) > 1): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-layer-group me-2"></i>
                            <strong>Pagos Múltiples:</strong> Esta venta tiene <?php echo count($venta['pagos']); ?> métodos de pago registrados.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>
                        <a href="index.php?action=completar&id=<?php echo $venta['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>"
                            class="btn btn-success">
                            <i class="fas fa-check me-1"></i> Sí, Completar Venta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
    function imprimirRecibo() {
        // Crear contenido para imprimir con el detalle de pagos
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Recibo Venta #<?php echo $venta['numero_venta']; ?></title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 14px; margin: 20px; }
                    .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
                    .info { margin-bottom: 20px; }
                    .info-item { margin-bottom: 5px; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f8f9fa; }
                    .total { margin-top: 20px; text-align: right; font-size: 16px; font-weight: bold; }
                    .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
                    .pagos-section { margin: 20px 0; padding: 10px; background: #f8f9fa; border-radius: 5px; }
                    .pago-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dashed #ddd; }
                    .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
                    .badge-pendiente { background-color: #ffc107; color: #000; }
                    .badge-completada { background-color: #198754; color: #fff; }
                    .badge-cancelada { background-color: #dc3545; color: #fff; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Recibo de Venta</h2>
                    <h3>Venta #<?php echo $venta['numero_venta']; ?></h3>
                    <p><?php echo date('d/m/Y H:i:s'); ?></p>
                </div>
                
                <div class="info">
                    <div class="info-item"><strong>Cliente:</strong> <?php echo htmlspecialchars($venta['cliente_nombre']); ?></div>
                    <?php if (!empty($venta['cliente_documento_identidad'])): ?>
                        <div class="info-item"><strong>DNI:</strong> <?php echo htmlspecialchars($venta['cliente_documento_identidad']); ?></div>
                    <?php endif; ?>
                    <div class="info-item"><strong>Estado:</strong> 
                        <span class="badge badge-<?php echo $venta['estado']; ?>">
                            <?php echo ucfirst($venta['estado']); ?>
                        </span>
                    </div>
                    <div class="info-item"><strong>Tasa de Cambio:</strong> <?php echo $venta['tasa_formateada'] ?? number_format($venta['tasa_cambio_utilizada'], 2); ?> Bs/$</div>
                </div>
                
                <?php if (isset($venta['pagos']) && !empty($venta['pagos'])): ?>
                <div class="pagos-section">
                    <h4>Detalle de Pagos</h4>
                    <?php 
                    foreach ($venta['pagos'] as $pago): 
                        $tasa_aplicada = $pago['monto_usd'] > 0 ? $pago['monto_bs'] / $pago['monto_usd'] : 0;
                    ?>
                    <div class="pago-row">
                        <div>
                            <strong><?php echo htmlspecialchars($pago['tipo_pago_nombre']); ?></strong>
                            <?php if ($pago['monto_usd'] > 0 && $pago['monto_bs'] > 0): ?>
                                <br><small>Pago mixto</small>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <div><?php echo TasaCambioHelper::formatearUSD($pago['monto_usd']); ?></div>
                            <div><?php echo TasaCambioHelper::formatearBS($pago['monto_bs']); ?></div>
                            <?php if ($tasa_aplicada > 0): ?>
                                <small>Tasa: <?php echo number_format($tasa_aplicada, 2); ?> Bs/$</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="pago-row" style="font-weight: bold; border-bottom: 2px solid #000; margin-top: 10px;">
                        <div>TOTAL PAGADO</div>
                        <div class="text-end">
                            <div><?php echo TasaCambioHelper::formatearUSD($venta['total_pagado_usd'] ?? $venta['total']); ?></div>
                            <div><?php echo TasaCambioHelper::formatearBS($venta['total_pagado_bs'] ?? $venta['total_bs']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $contador = 1;
                        foreach ($venta['detalles'] as $detalle):
                        ?>
                            <tr>
                                <td><?php echo $contador++; ?></td>
                                <td><?php echo htmlspecialchars($detalle['producto_nombre']); ?> (<?php echo htmlspecialchars($detalle['codigo_sku']); ?>)</td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>
                                    <?php echo $detalle['precio_unitario_formateado_usd'] ?? '$' . number_format($detalle['precio_unitario'], 2); ?><br>
                                    <small><?php echo $detalle['precio_unitario_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['precio_unitario_bs']); ?></small>
                                </td>
                                <td>
                                    <?php echo $detalle['subtotal_formateado_usd'] ?? '$' . number_format($detalle['subtotal'], 2); ?><br>
                                    <small><?php echo $detalle['subtotal_formateado_bs'] ?? TasaCambioHelper::formatearBS($detalle['subtotal_bs']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total">
                    <div><strong>Total USD:</strong> <?php echo $venta['total_formateado_usd'] ?? '$' . number_format($venta['total'], 2); ?></div>
                    <div><strong>Total Bs:</strong> <?php echo $venta['total_formateado_bs'] ?? TasaCambioHelper::formatearBS($venta['total_bs']); ?></div>
                </div>
                
                <?php if (!empty($venta['observaciones'])): ?>
                <div class="info-item">
                    <strong>Observaciones:</strong> <?php echo htmlspecialchars($venta['observaciones']); ?>
                </div>
                <?php endif; ?>
                
                <div class="footer">
                    <p>-----------------------------------</p>
                    <p>Recibo generado el <?php echo date('d/m/Y H:i:s'); ?></p>
                    <p>Sistema de Ventas</p>
                </div>
            </body>
            </html>
        `;
        
        // Abrir ventana de impresión
        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        
        // Esperar a que cargue el contenido
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 250);
    }
</script>

<!-- <?php include '../layouts/footer.php'; ?> -->
