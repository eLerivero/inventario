<?php
require_once '../../Config/Database.php';
require_once '../../Controllers/VentaController.php';
require_once '../../Utils/Ayuda.php';
require_once '../../Helpers/TasaCambioHelper.php';

$database = new Database();
$db = $database->getConnection();

$controller = new VentaController($db);

// ====================================================
// CONSTANTES DE TIPOS DE PAGO - MISMA LÓGICA QUE EN INDEX
// ====================================================
define('TIPOS_PAGO_USD', [2, 7]);

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

    // Los pagos vienen del controlador
    $pagos = $venta['pagos'] ?? [];

    // ====================================================
    // RECALCULAR TOTALES POR MONEDA CON LA MISMA LÓGICA
    // ====================================================
    $total_usd_pagado = 0;  // Total USD (solo IDs 2 y 7)
    $total_bs_pagado = 0;   // Total Bs (todos los demás IDs)
    $pagos_clasificados = [
        'usd' => [],   // Pagos en USD (IDs 2 y 7)
        'bs' => []     // Pagos en Bs (todos los demás)
    ];
    
    foreach ($pagos as $pago) {
        $tipo_pago_id = $pago['tipo_pago_id'];
        
        if (in_array($tipo_pago_id, TIPOS_PAGO_USD)) {
            // Es pago en USD - usar monto_usd
            $monto_usd = floatval($pago['monto_usd']);
            $total_usd_pagado += $monto_usd;
            
            $pagos_clasificados['usd'][] = [
                'id' => $pago['id'],
                'tipo_pago_id' => $tipo_pago_id,
                'nombre' => $pago['tipo_pago_nombre'] ?? 'Desconocido',
                'monto' => $monto_usd,
                'monto_bs' => floatval($pago['monto_bs']),
                'fecha' => $pago['fecha_pago'] ?? $pago['created_at'] ?? $venta['fecha_hora']
            ];
        } else {
            // Es pago en Bs - usar monto_bs
            $monto_bs = floatval($pago['monto_bs']);
            $total_bs_pagado += $monto_bs;
            
            $pagos_clasificados['bs'][] = [
                'id' => $pago['id'],
                'tipo_pago_id' => $tipo_pago_id,
                'nombre' => $pago['tipo_pago_nombre'] ?? 'Desconocido',
                'monto' => $monto_bs,
                'monto_usd' => floatval($pago['monto_usd']),
                'fecha' => $pago['fecha_pago'] ?? $pago['created_at'] ?? $venta['fecha_hora']
            ];
        }
    }
    
    // Calcular total de pagos y métodos únicos
    $total_pagos = count($pagos);
    $metodos_utilizados = array_unique(array_column($pagos, 'tipo_pago_nombre'));
    $cantidad_metodos = count($metodos_utilizados);
    
    // Determinar si tiene pagos mixtos
    $tiene_pagos_usd = $total_usd_pagado > 0;
    $tiene_pagos_bs = $total_bs_pagado > 0;
    $es_pago_mixto = $tiene_pagos_usd && $tiene_pagos_bs;
}

/**
 * Función para formatear números a 2 decimales SIN REDONDEAR (truncando)
 * Ejemplo: 10.375 -> 10.37, 45.12345678 -> 45.12
 */
function formato_2_decimales($numero)
{
    if ($numero == 0 || $numero === null) return '0.00';
    
    // Manejar números negativos
    $negativo = $numero < 0;
    $numero = abs($numero);
    
    // Separar parte entera y decimal
    $numero_str = (string)$numero;
    if (strpos($numero_str, '.') === false) {
        $resultado = $numero_str . '.00';
    } else {
        list($entero, $decimal) = explode('.', $numero_str);
        
        // Tomar solo los primeros 2 decimales (sin redondear)
        $decimal = substr($decimal, 0, 2);
        
        // Si el decimal tiene menos de 2 dígitos, rellenar con ceros
        $decimal = str_pad($decimal, 2, '0');
        
        $resultado = $entero . '.' . $decimal;
    }
    
    return $negativo ? '-' . $resultado : $resultado;
}

/**
 * Función para formatear porcentajes a 2 decimales SIN REDONDEAR
 */
function formato_porcentaje_2_decimales($numero)
{
    return formato_2_decimales($numero) . '%';
}

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

    /* Estilos para pagos - CLASIFICADOS POR MONEDA */
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

    .pago-item.mixto-individual {
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

    .badge-completado {
        background: #dcfce7;
        color: #059669;
    }

    .text-success-dark {
        color: #059669;
    }

    /* Secciones separadas por moneda */
    .section-usd {
        border-top: 3px solid #0d9488;
    }

    .section-bs {
        border-top: 3px solid #b45309;
    }

    .section-header-usd {
        background: #f0fdfa;
        color: #0d9488;
        padding: 0.75rem 1rem;
        border-radius: 8px 8px 0 0;
        font-weight: 600;
    }

    .section-header-bs {
        background: #fff7ed;
        color: #b45309;
        padding: 0.75rem 1rem;
        border-radius: 8px 8px 0 0;
        font-weight: 600;
    }

    /* Animaciones */
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Tooltip personalizado */
    .tooltip-custom {
        position: relative;
        display: inline-block;
        cursor: help;
    }

    .tooltip-custom:hover::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
    }

    /* Estilo para montos con 2 decimales fijos */
    .monto-2dec {
        font-family: 'Courier New', monospace;
        font-size: 0.95em;
    }
</style>

<!-- Header con Botón de Volver -->
<div class="d-flex justify-content-between align-items-center mb-4 fade-in">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-receipt me-2"></i>
            Detalles de Venta
        </h1>
        <p class="text-muted mb-0">
            <?php if ($venta): ?>
                Información completa de la venta #<?php echo $venta['numero_venta']; ?>
                <?php if ($cantidad_metodos > 1): ?>
                    <span class="badge badge-multiple ms-2">
                        <i class="fas fa-layer-group me-1"></i>
                        <?php echo $cantidad_metodos; ?> métodos de pago
                    </span>
                <?php endif; ?>
                
                <?php if ($es_pago_mixto): ?>
                    <span class="badge ms-2" style="background: #f3e8ff; color: #7e22ce;">
                        <i class="fas fa-dollar-sign me-1"></i><i class="fas fa-bolt me-1"></i>
                        Pago Mixto
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
        <button type="button" class="btn btn-outline-info ms-2" onclick="imprimirRecibo()">
            <i class="fas fa-print me-1"></i> Imprimir
        </button>
    </div>
</div>

<?php if (!$venta): ?>
    <div class="alert alert-warning fade-in">
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
    <div class="card mb-4 border-primary fade-in">
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

                    <?php if ($venta['estado'] === 'completada'): ?>
                        <span class="badge bg-light text-dark ms-2 p-2">
                            <i class="fas fa-lock me-1"></i>
                            Cerrada en Caja: <?php echo isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'] ? 'Sí' : 'No'; ?>
                        </span>
                    <?php endif; ?>
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
                            <h6 class="mb-1 text-muted">
                                <?php echo $cantidad_metodos > 1 ? 'Métodos de Pago' : 'Método de Pago Principal'; ?>
                            </h6>
                            <h5 class="mb-0"><?php echo htmlspecialchars($venta['tipo_pago_nombre']); ?></h5>
                            <?php if ($cantidad_metodos > 1): ?>
                                <small class="text-muted">
                                    <i class="fas fa-layer-group me-1"></i>
                                    <?php echo $cantidad_metodos - 1; ?> método(s) adicional(es)
                                </small>
                                <div class="mt-1">
                                    <span class="badge bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-list me-1"></i>
                                        <?php echo implode(', ', array_slice($metodos_utilizados, 0, 3)); ?>
                                        <?php if (count($metodos_utilizados) > 3): ?> ...<?php endif; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="mt-1">
                                <span class="badge bg-success bg-opacity-10 text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <?php echo $total_pagos; ?> pago(s) completado(s)
                                </span>
                            </div>
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
                            <h5 class="mb-0 monto-2dec"><?php echo formato_2_decimales($venta['tasa_cambio_utilizada']); ?> Bs/$</h5>
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
    <!-- SECCIÓN: DETALLE DE PAGOS - CLASIFICADOS POR MONEDA -->
    <!-- ================================================================== -->
    <?php if (!empty($pagos)): ?>
        <div class="card mb-4 border-success fade-in">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    Detalle de Pagos Completados
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_pagos; ?> pago(s)</span>
                    <?php if ($cantidad_metodos > 1): ?>
                        <span class="badge bg-white text-success ms-2">
                            <i class="fas fa-layer-group me-1"></i> <?php echo $cantidad_metodos; ?> métodos
                        </span>
                    <?php endif; ?>
                    <?php if ($es_pago_mixto): ?>
                        <span class="badge bg-white text-success ms-2" style="color: #7e22ce !important;">
                            <i class="fas fa-dollar-sign me-1"></i><i class="fas fa-bolt me-1"></i> Pago Mixto
                        </span>
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <!-- Resumen de Totales por Moneda -->
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
                                    <h3 class="text-white mb-0 monto-2dec">$<?php echo formato_2_decimales($total_usd_pagado); ?></h3>
                                    <small class="text-white-50"><?php echo count($pagos_clasificados['usd']); ?> pago(s) en USD</small>
                                </div>
                                <div class="col-6">
                                    <small class="text-white-50 d-block">Total Bs</small>
                                    <h3 class="text-white mb-0 monto-2dec">Bs <?php echo formato_2_decimales($total_bs_pagado); ?></h3>
                                    <small class="text-white-50"><?php echo count($pagos_clasificados['bs']); ?> pago(s) en Bs</small>
                                </div>
                            </div>

                            <!-- Mostrar comparación con total de venta -->
                            <div class="mt-3 pt-3 border-top border-white-50">
                                <div class="d-flex justify-content-between">
                                    <span class="text-white-50">Total Venta:</span>
                                    <span class="text-white monto-2dec">
                                        $<?php echo formato_2_decimales($venta['total']); ?> /
                                        Bs <?php echo formato_2_decimales($venta['total_bs']); ?>
                                    </span>
                                </div>

                                <?php if (($venta['saldo_pendiente_usd'] ?? 0) > 0 && $venta['estado'] !== 'completada'): ?>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-white-50">Saldo Pendiente:</span>
                                        <span class="text-white fw-bold monto-2dec">
                                            $<?php echo formato_2_decimales($venta['saldo_pendiente_usd']); ?> /
                                            Bs <?php echo formato_2_decimales($venta['saldo_pendiente_bs']); ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-white-50">Estado de Pago:</span>
                                        <span class="text-white fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> Completado
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 h-100 d-flex align-items-center">
                            <div class="w-100">
                                <h6 class="text-muted mb-3">Distribución por Moneda</h6>
                                <?php
                                $porcentaje_usd = '0';
                                $porcentaje_bs = '0';

                                if ($total_usd_pagado > 0 || $total_bs_pagado > 0) {
                                    $tasa = isset($venta['tasa_cambio_utilizada']) && $venta['tasa_cambio_utilizada'] > 0
                                        ? $venta['tasa_cambio_utilizada']
                                        : 1;

                                    $bs_en_usd = $total_bs_pagado / $tasa;
                                    $total_combinado = $total_usd_pagado + $bs_en_usd;

                                    if ($total_combinado > 0) {
                                        $porcentaje_usd = ($total_usd_pagado / $total_combinado) * 100;
                                        $porcentaje_bs = ($bs_en_usd / $total_combinado) * 100;
                                    }
                                }
                                ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><i class="fas fa-dollar-sign text-success"></i> USD</span>
                                        <span class="fw-bold monto-2dec"><?php echo formato_2_decimales($porcentaje_usd); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: <?php echo $porcentaje_usd; ?>%"></div>
                                    </div>
                                    <small class="text-muted monto-2dec">$<?php echo formato_2_decimales($total_usd_pagado); ?></small>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span><i class="fas fa-bolt text-warning"></i> Bs</span>
                                        <span class="fw-bold monto-2dec"><?php echo formato_2_decimales($porcentaje_bs); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: <?php echo $porcentaje_bs; ?>%"></div>
                                    </div>
                                    <small class="text-muted monto-2dec">Bs <?php echo formato_2_decimales($total_bs_pagado); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- PAGOS EN USD (SOLO IDs 2 y 7) -->
                <!-- ==================================================== -->
                <?php if (!empty($pagos_clasificados['usd'])): ?>
                    <div class="section-usd mb-4">
                        <div class="section-header-usd d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-dollar-sign me-2"></i>
                                Pagos en Dólares (USD) - Tipo de Pago IDs: 2 y 7
                            </span>
                            <span class="badge bg-white text-success">
                                Total: $<?php echo formato_2_decimales($total_usd_pagado); ?>
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Método de Pago</th>
                                        <th class="text-end">Monto USD</th>
                                        <th class="text-end">Monto Bs (Equivalente)</th>
                                        <th class="text-center">Tasa Aplicada</th>
                                        <th class="text-center">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador_usd = 1;
                                    foreach ($pagos_clasificados['usd'] as $pago):
                                        $tasa_aplicada = $pago['monto'] > 0 ? $pago['monto_bs'] / $pago['monto'] : 0;
                                    ?>
                                        <tr class="pago-item usd">
                                            <td><?php echo $contador_usd++; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-dollar-sign me-2 text-success"></i>
                                                    <strong><?php echo htmlspecialchars($pago['nombre']); ?></strong>
                                                    <span class="badge bg-success bg-opacity-10 text-success ms-2">
                                                        <i class="fas fa-check-circle"></i> ID: <?php echo $pago['tipo_pago_id']; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end pago-monto-usd monto-2dec">
                                                $<?php echo formato_2_decimales($pago['monto']); ?>
                                            </td>
                                            <td class="text-end text-muted monto-2dec">
                                                Bs <?php echo formato_2_decimales($pago['monto_bs']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-10 text-info monto-2dec">
                                                    <?php echo formato_2_decimales($tasa_aplicada); ?> Bs/$
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small>
                                                    <?php echo date('d/m/Y H:i', strtotime($pago['fecha'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Total USD:</th>
                                        <th class="text-end pago-monto-usd monto-2dec">
                                            $<?php echo formato_2_decimales($total_usd_pagado); ?>
                                        </th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ==================================================== -->
                <!-- PAGOS EN Bs (TODOS LOS DEMÁS IDs) -->
                <!-- ==================================================== -->
                <?php if (!empty($pagos_clasificados['bs'])): ?>
                    <div class="section-bs">
                        <div class="section-header-bs d-flex justify-content-between align-items-center">
                            <span>
                                <i class="fas fa-bolt me-2"></i>
                                Pagos en Bolívares (Bs) - Tipo de Pago IDs: Todos excepto 2 y 7
                            </span>
                            <span class="badge bg-white text-warning">
                                Total: Bs <?php echo formato_2_decimales($total_bs_pagado); ?>
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Método de Pago</th>
                                        <th class="text-end">Monto Bs</th>
                                        <th class="text-end">Monto USD (Equivalente)</th>
                                        <th class="text-center">Tasa Aplicada</th>
                                        <th class="text-center">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contador_bs = 1;
                                    foreach ($pagos_clasificados['bs'] as $pago):
                                        $tasa_aplicada = $pago['monto_usd'] > 0 ? $pago['monto'] / $pago['monto_usd'] : 0;
                                    ?>
                                        <tr class="pago-item bs">
                                            <td><?php echo $contador_bs++; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-bolt me-2 text-warning"></i>
                                                    <strong><?php echo htmlspecialchars($pago['nombre']); ?></strong>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning ms-2">
                                                        <i class="fas fa-check-circle"></i> ID: <?php echo $pago['tipo_pago_id']; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end pago-monto-bs monto-2dec">
                                                Bs <?php echo formato_2_decimales($pago['monto']); ?>
                                            </td>
                                            <td class="text-end text-muted monto-2dec">
                                                $<?php echo formato_2_decimales($pago['monto_usd']); ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info bg-opacity-10 text-info monto-2dec">
                                                    <?php echo formato_2_decimales($tasa_aplicada); ?> Bs/$
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <small>
                                                    <?php echo date('d/m/Y H:i', strtotime($pago['fecha'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">Total Bs:</th>
                                        <th class="text-end pago-monto-bs monto-2dec">
                                            Bs <?php echo formato_2_decimales($total_bs_pagado); ?>
                                        </th>
                                        <th colspan="3"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Mensaje si no hay pagos en alguna categoría -->
                <?php if (empty($pagos_clasificados['usd']) && empty($pagos_clasificados['bs'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay pagos registrados para esta venta.
                    </div>
                <?php endif; ?>
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
                        <h3 class="text-success mb-0 monto-2dec">$<?php echo formato_2_decimales($venta['total']); ?></h3>
                        <small class="text-muted">Monto total de la venta</small>
                        <?php if ($total_usd_pagado < $venta['total'] && $venta['estado'] !== 'completada'): ?>
                            <span class="badge bg-warning mt-2 monto-2dec">Pagado: $<?php echo formato_2_decimales($total_usd_pagado); ?></span>
                        <?php else: ?>
                            <span class="badge bg-success mt-2">Completado</span>
                        <?php endif; ?>
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
                        <h3 class="text-warning mb-0 monto-2dec">Bs <?php echo formato_2_decimales($venta['total_bs']); ?></h3>
                        <small class="text-muted">Monto total de la venta</small>
                        <?php if ($total_bs_pagado < $venta['total_bs'] && $venta['estado'] !== 'completada'): ?>
                            <span class="badge bg-warning mt-2 monto-2dec">Pagado: Bs <?php echo formato_2_decimales($total_bs_pagado); ?></span>
                        <?php else: ?>
                            <span class="badge bg-success mt-2">Completado</span>
                        <?php endif; ?>
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
    <div class="card fade-in">
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
                                            <strong class="text-primary monto-2dec">
                                                $<?php echo formato_2_decimales($detalle['precio_unitario']); ?>
                                            </strong>
                                            <small class="text-muted monto-2dec">
                                                Bs <?php echo formato_2_decimales($detalle['precio_unitario_bs']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column">
                                            <strong class="text-success monto-2dec">
                                                $<?php echo formato_2_decimales($detalle['subtotal']); ?>
                                            </strong>
                                            <small class="text-muted monto-2dec">
                                                Bs <?php echo formato_2_decimales($detalle['subtotal_bs']); ?>
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
                                        <strong class="text-primary monto-2dec">
                                            $<?php echo formato_2_decimales(array_sum(array_column($venta['detalles'], 'precio_unitario'))); ?>
                                        </strong>
                                        <small class="text-muted">Precios unitarios</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-column pt-3">
                                        <strong class="text-success fs-5 monto-2dec">
                                            $<?php echo formato_2_decimales($venta['total']); ?>
                                        </strong>
                                        <span class="text-warning monto-2dec">
                                            Bs <?php echo formato_2_decimales($venta['total_bs']); ?>
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
                        $<?php echo formato_2_decimales($venta['total']); ?> |
                        <i class="fas fa-bolt me-1"></i>
                        Bs <?php echo formato_2_decimales($venta['total_bs']); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de Sistema -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card fade-in">
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

                        <!-- Mostrar eventos de pago CLASIFICADOS -->
                        <?php if (!empty($pagos)): ?>
                            <?php foreach ($pagos as $index => $pago): 
                                $tipo_pago_id = $pago['tipo_pago_id'];
                                $es_usd = in_array($tipo_pago_id, TIPOS_PAGO_USD);
                                $icono = $es_usd ? 'fa-dollar-sign' : 'fa-bolt';
                                $color = $es_usd ? 'text-success' : 'text-warning';
                                $bg_color = $es_usd ? 'bg-success' : 'bg-warning';
                                $monto_mostrar = $es_usd 
                                    ? '$' . formato_2_decimales($pago['monto_usd'])
                                    : 'Bs ' . formato_2_decimales($pago['monto_bs']);
                            ?>
                                <li class="mb-3">
                                    <div class="d-flex">
                                        <div class="timeline-icon <?php echo $bg_color; ?> text-white rounded-circle p-2 me-3">
                                            <i class="fas <?php echo $icono; ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Pago #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($pago['tipo_pago_nombre']); ?></h6>
                                            <p class="text-muted mb-0 small monto-2dec">
                                                <span class="<?php echo $color; ?> fw-bold"><?php echo $monto_mostrar; ?></span>
                                                <?php if ($es_usd && $pago['monto_bs'] > 0): ?>
                                                    <br><small>(Equivale a Bs <?php echo formato_2_decimales($pago['monto_bs']); ?>)</small>
                                                <?php elseif (!$es_usd && $pago['monto_usd'] > 0): ?>
                                                    <br><small>(Equivale a $<?php echo formato_2_decimales($pago['monto_usd']); ?>)</small>
                                                <?php endif; ?>
                                                <br>
                                                <?php echo date('d/m/Y H:i:s', strtotime($pago['fecha_pago'] ?? $pago['created_at'] ?? $venta['fecha_hora'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>

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
            <div class="card fade-in">
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

                        <?php if (($venta['saldo_pendiente_usd'] ?? 0) > 0): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <strong>Saldo Pendiente:</strong>
                                $<?php echo formato_2_decimales($venta['saldo_pendiente_usd']); ?> /
                                Bs <?php echo formato_2_decimales($venta['saldo_pendiente_bs']); ?>
                                <br>
                                No se puede completar la venta con saldo pendiente.
                            </div>
                        <?php else: ?>
                            <p>¿Estás seguro de que deseas completar esta venta?</p>
                            <ul class="text-muted">
                                <li>El estado cambiará a "Completada"</li>
                                <li>El stock de los productos se actualizará</li>
                                <li>La venta será registrada como finalizada</li>
                            </ul>

                            <?php if ($cantidad_metodos > 1): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-layer-group me-2"></i>
                                    <strong>Pagos Múltiples:</strong> Esta venta tiene <?php echo $cantidad_metodos; ?> métodos de pago diferentes (<?php echo $total_pagos; ?> pagos en total).
                                    <br>
                                    <small>
                                        USD: $<?php echo formato_2_decimales($total_usd_pagado); ?> |
                                        Bs: <?php echo formato_2_decimales($total_bs_pagado); ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <?php if ($es_pago_mixto): ?>
                                <div class="alert alert-info mt-3" style="background: #f3e8ff; color: #7e22ce;">
                                    <i class="fas fa-dollar-sign me-1"></i><i class="fas fa-bolt me-1"></i>
                                    <strong>Pago Mixto:</strong> Esta venta tiene pagos en ambas monedas.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cancelar
                        </button>

                        <?php if (!isset($venta['saldo_pendiente_usd']) || $venta['saldo_pendiente_usd'] <= 0): ?>
                            <a href="index.php?action=completar&id=<?php echo $venta['id']; ?>&csrf_token=<?php echo $_SESSION['csrf_token'] ?? ''; ?>"
                                class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Sí, Completar Venta
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
    function imprimirRecibo() {
        <?php
        // Generar el HTML para los pagos en el recibo - CLASIFICADO
        $pagos_usd_html = '';
        $pagos_bs_html = '';
        
        foreach ($pagos_clasificados['usd'] as $pago) {
            $pagos_usd_html .= '<tr>';
            $pagos_usd_html .= '<td>' . htmlspecialchars($pago['nombre']) . '</td>';
            $pagos_usd_html .= '<td class="text-end">$' . formato_2_decimales($pago['monto']) . '</td>';
            $pagos_usd_html .= '<td class="text-end">Bs ' . formato_2_decimales($pago['monto_bs']) . '</td>';
            $pagos_usd_html .= '<td class="text-center">USD (ID ' . $pago['tipo_pago_id'] . ')</td>';
            $pagos_usd_html .= '</tr>';
        }
        
        foreach ($pagos_clasificados['bs'] as $pago) {
            $pagos_bs_html .= '<tr>';
            $pagos_bs_html .= '<td>' . htmlspecialchars($pago['nombre']) . '</td>';
            $pagos_bs_html .= '<td class="text-end">Bs ' . formato_2_decimales($pago['monto']) . '</td>';
            $pagos_bs_html .= '<td class="text-end">$' . formato_2_decimales($pago['monto_usd']) . '</td>';
            $pagos_bs_html .= '<td class="text-center">Bs (ID ' . $pago['tipo_pago_id'] . ')</td>';
            $pagos_bs_html .= '</tr>';
        }
        ?>

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
                    .pagos-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
                    .pagos-section h4 { margin-top: 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                    .section-usd { background: #f0fdfa; padding: 10px; margin: 10px 0; border-left: 4px solid #0d9488; }
                    .section-bs { background: #fff7ed; padding: 10px; margin: 10px 0; border-left: 4px solid #b45309; }
                    .monto-2dec { font-family: 'Courier New', monospace; }
                    .badge { padding: 3px 8px; border-radius: 3px; font-size: 12px; }
                    .text-end { text-align: right; }
                    .text-center { text-align: center; }
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
                    <div class="info-item"><strong>Estado:</strong> <?php echo ucfirst($venta['estado']); ?></div>
                    <div class="info-item"><strong>Tasa de Cambio:</strong> <?php echo formato_2_decimales($venta['tasa_cambio_utilizada']); ?> Bs/$</div>
                    <?php if ($es_pago_mixto): ?>
                        <div class="info-item"><strong>Tipo de Pago:</strong> Mixto (USD + Bs)</div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($pagos)): ?>
                <div class="pagos-section">
                    <h4>Detalle de Pagos Completados</h4>
                    
                    <?php if (!empty($pagos_clasificados['usd'])): ?>
                    <div class="section-usd">
                        <h5 style="margin-top:0; color:#0d9488;">Pagos en Dólares (USD) - IDs: 2 y 7</h5>
                        <table>
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th class="text-end">Monto USD</th>
                                    <th class="text-end">Monto Bs</th>
                                    <th class="text-center">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $pagos_usd_html; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($pagos_clasificados['bs'])): ?>
                    <div class="section-bs">
                        <h5 style="margin-top:0; color:#b45309;">Pagos en Bolívares (Bs) - Todos los demás IDs</h5>
                        <table>
                            <thead>
                                <tr>
                                    <th>Método</th>
                                    <th class="text-end">Monto Bs</th>
                                    <th class="text-end">Monto USD</th>
                                    <th class="text-center">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo $pagos_bs_html; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px; font-weight: bold; text-align: right;">
                        <div>Total USD: $<?php echo formato_2_decimales($total_usd_pagado); ?></div>
                        <div>Total Bs: Bs <?php echo formato_2_decimales($total_bs_pagado); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <h4>Productos Vendidos</h4>
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
                                <td class="text-center"><?php echo $detalle['cantidad']; ?></td>
                                <td class="text-end monto-2dec">
                                    $<?php echo formato_2_decimales($detalle['precio_unitario']); ?><br>
                                    <small>Bs <?php echo formato_2_decimales($detalle['precio_unitario_bs']); ?></small>
                                </td>
                                <td class="text-end monto-2dec">
                                    $<?php echo formato_2_decimales($detalle['subtotal']); ?><br>
                                    <small>Bs <?php echo formato_2_decimales($detalle['subtotal_bs']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="total monto-2dec">
                    <div><strong>Total USD:</strong> $<?php echo formato_2_decimales($venta['total']); ?></div>
                    <div><strong>Total Bs:</strong> Bs <?php echo formato_2_decimales($venta['total_bs']); ?></div>
                    <hr>
                    <div><strong>Pagado USD:</strong> $<?php echo formato_2_decimales($total_usd_pagado); ?></div>
                    <div><strong>Pagado Bs:</strong> Bs <?php echo formato_2_decimales($total_bs_pagado); ?></div>
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

    // Inicializar tooltips
    document.addEventListener('DOMContentLoaded', function() {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(el => new bootstrap.Tooltip(el));
    });
</script>

<!-- <?php include '../layouts/footer.php'; ?> -->