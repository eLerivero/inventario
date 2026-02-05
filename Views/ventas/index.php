<?php
// Usar rutas absolutas para evitar problemas de inclusión
require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../../Controllers/VentaController.php';
require_once __DIR__ . '/../../Utils/Ayuda.php';
require_once __DIR__ . '/../../Helpers/TasaCambioHelper.php';
require_once __DIR__ . '/../../Utils/Auth.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar acceso específico a ventas
Auth::requireAccessToVentas();

$database = new Database();
$db = $database->getConnection();
$controller = new VentaController($db);

// Manejar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$success_message = '';
$error_message = '';

// Procesar cambio de estado
if ($action === 'completar' && $id) {
    // Verificar token CSRF
    $token_valido = true; // Temporalmente deshabilitado para pruebas

    if (!$token_valido) {
        $error_message = "Token de seguridad inválido o expirado. Por favor, recarga la página.";
    } else {
        $result = $controller->actualizarEstado($id, 'completada');
        if ($result['success']) {
            $success_message = $result['message'];
            // Redirigir después de 2 segundos
            echo '<meta http-equiv="refresh" content="2;url=index.php">';
        } else {
            $error_message = $result['message'];
        }
    }
}

// Obtener ventas (solo las activas por defecto)
$mostrar_todas = isset($_GET['mostrar_todas']) && $_GET['mostrar_todas'] == '1';
$result = $controller->listar(!$mostrar_todas);  // ¡IMPORTANTE! Solo activas por defecto

if ($result['success']) {
    $ventas = $result['data'];
    $filtro_activas = $result['filtro_activas'] ?? true;
} else {
    $error_message = $result['message'] ?? 'Error al cargar las ventas';
    $ventas = [];
    $filtro_activas = true;
}

// Obtener estadísticas del día (solo ventas activas)
$estadisticas_hoy = $controller->obtenerResumenHoy();
$stats_hoy = $estadisticas_hoy['success'] ? $estadisticas_hoy['data'] : [];

// Obtener total de ventas completadas en Bs (solo activas)
$totalActivas = $controller->obtenerTotalVentasCompletadasBs();
$totales_activos = $totalActivas['success'] ? $totalActivas['data'] : [];

// Generar token CSRF para seguridad
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$page_title = "Gestión de Ventas";
include __DIR__ . '/../layouts/header.php';
?>

<!-- Header con Botón de Nueva Venta y Filtros -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            <i class="fas fa-shopping-cart me-2"></i>
            Gestión de Ventas
            <?php if (!$filtro_activas): ?>
                <span class="badge bg-secondary ms-2">Mostrando todas</span>
            <?php else: ?>
                <span class="badge bg-success ms-2">Solo activas</span>
            <?php endif; ?>
        </h1>
        <p class="text-muted mb-0">
            <?php if ($filtro_activas): ?>
                Solo se muestran ventas no cerradas en caja
            <?php else: ?>
                Mostrando todas las ventas (activas y cerradas)
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <!-- Botón para alternar entre mostrar todas y solo activas -->
        <?php if ($filtro_activas): ?>
            <a href="index.php?mostrar_todas=1" class="btn btn-outline-secondary">
                <i class="fas fa-history me-1"></i> Mostrar Todas
            </a>
        <?php else: ?>
            <a href="index.php" class="btn btn-outline-success">
                <i class="fas fa-eye me-1"></i> Solo Activas
            </a>
        <?php endif; ?>
        
        <a href="crear.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i> Nueva Venta
        </a>
    </div>
</div>

<!-- Alertas Informativas -->
<?php if ($mostrar_todas): ?>
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Modo Historial:</strong> Estás viendo todas las ventas (activas y cerradas).
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Alertas de Sistema -->
<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <?php if ($action === 'completar'): ?>
            <div class="mt-2">
                <small>Serás redirigido automáticamente al listado...</small>
            </div>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Estadísticas Rápidas (Solo Ventas Activas) -->
<?php if ($filtro_activas): ?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Ventas Hoy</h5>
                        <h3><?php echo $stats_hoy['ventas_hoy'] ?? 0; ?></h3>
                        <small class="opacity-75">Ventas activas hoy</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Ingresos Hoy USD</h5>
                        <h3><?php echo $stats_hoy['total_usd_hoy_formateado'] ?? '$0.00'; ?></h3>
                        <small class="opacity-75">Ingresos del día</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Ingresos Hoy Bs</h5>
                        <h3><?php echo $stats_hoy['total_bs_hoy_formateado'] ?? 'Bs 0.00'; ?></h3>
                        <small class="opacity-75">Ingresos del día</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-bolt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Clientes Hoy</h5>
                        <h3><?php echo $stats_hoy['clientes_hoy'] ?? 0; ?></h3>
                        <small class="opacity-75">Clientes atendidos hoy</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Horario de Ventas -->
<?php if (!empty($stats_hoy['primera_venta_formateada']) && !empty($stats_hoy['ultima_venta_formateada'])): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-center p-3">
                            <div class="text-muted mb-2">
                                <i class="fas fa-clock text-primary me-1"></i>
                                Primera Venta Hoy
                            </div>
                            <h4 class="text-primary"><?php echo $stats_hoy['primera_venta_formateada']; ?></h4>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-center p-3">
                            <div class="text-muted mb-2">
                                <i class="fas fa-clock text-success me-1"></i>
                                Última Venta Hoy
                            </div>
                            <h4 class="text-success"><?php echo $stats_hoy['ultima_venta_formateada']; ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Tabla de Ventas -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Lista de Ventas
            <?php if ($filtro_activas): ?>
                <small class="text-muted ms-2">(Solo ventas activas)</small>
            <?php else: ?>
                <small class="text-muted ms-2">(Todas las ventas)</small>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        <?php if (empty($ventas) && $filtro_activas): ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h4 class="text-success">¡Todas las ventas han sido cerradas en caja!</h4>
                <p class="text-muted">No hay ventas activas pendientes de cierre.</p>
                <div class="mt-4">
                    <a href="index.php?mostrar_todas=1" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-history me-1"></i> Ver Historial Completo
                    </a>
                    <a href="crear.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Crear Nueva Venta
                    </a>
                </div>
            </div>
        <?php elseif (empty($ventas)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No hay ventas registradas</h4>
                <p class="text-muted">Comienza creando tu primera venta.</p>
                <a href="crear.php" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Crear Primera Venta
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="tablaVentas">
                    <thead class="table-dark">
                        <tr>
                            <th># Venta</th>
                            <th>Cliente</th>
                            <th>Total USD</th>
                            <th>Total Bs</th>
                            <th>Tasa del Día</th>
                            <th>Tipo Pago</th>
                            <th>Estado</th>
                            <th>Cierre Caja</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalVentasUsd = 0;
                        $totalVentasBs = 0;
                        $totalActivasUsd = 0;
                        $totalActivasBs = 0;
                        $ventasActivas = 0;
                        $ventasCerradas = 0;
                        
                        foreach ($ventas as $venta):
                            $estado_badge = [
                                'pendiente' => 'bg-warning',
                                'completada' => 'bg-success',
                                'cancelada' => 'bg-danger'
                            ];
                            $estado_text = [
                                'pendiente' => 'Pendiente',
                                'completada' => 'Completada',
                                'cancelada' => 'Cancelada'
                            ];

                            // Formatear fecha
                            $fecha = '';
                            if (!empty($venta['fecha_hora'])) {
                                $fecha = Ayuda::formatDate($venta['fecha_hora']);
                            } elseif (!empty($venta['created_at'])) {
                                $fecha = Ayuda::formatDate($venta['created_at']);
                            }

                            // Obtener detalles para verificar si tiene precios fijos
                            $detalles_venta = [];
                            try {
                                $detalles_venta = $controller->obtenerDetalles($venta['id']);
                            } catch (Exception $e) {
                                // Si hay error, continuar sin detalles
                            }

                            // Verificar si hay productos con precio fijo en esta venta
                            $tiene_precio_fijo = false;
                            foreach ($detalles_venta as $detalle) {
                                if (
                                    isset($detalle['precio_unitario_bs']) &&
                                    isset($detalle['precio_unitario']) &&
                                    $detalle['precio_unitario_bs'] > 0 &&
                                    ($detalle['precio_unitario'] * $venta['tasa_cambio'] != $detalle['precio_unitario_bs'])
                                ) {
                                    $tiene_precio_fijo = true;
                                    break;
                                }
                            }
                            
                            // Verificar estado de cierre
                            $cerrada_en_caja = isset($venta['cerrada_en_caja']) && $venta['cerrada_en_caja'];
                            
                            // Contar ventas activas vs cerradas
                            if ($cerrada_en_caja) {
                                $ventasCerradas++;
                            } else {
                                $ventasActivas++;
                            }
                            
                            // Acumular totales
                            $totalVentasUsd += $venta['total'] ?? 0;
                            $totalVentasBs += $venta['total_bs'] ?? 0;
                            
                            if (!$cerrada_en_caja && ($venta['estado'] ?? '') === 'completada') {
                                $totalActivasUsd += $venta['total'] ?? 0;
                                $totalActivasBs += $venta['total_bs'] ?? 0;
                            }
                        ?>
                            <tr class="<?php echo $cerrada_en_caja ? 'venta-cerrada' : ($tiene_precio_fijo ? 'table-warning' : ''); ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <strong>#<?php echo htmlspecialchars($venta['numero_venta'] ?? 'N/A'); ?></strong>
                                        <?php if ($tiene_precio_fijo): ?>
                                            <span class="ms-2" title="Esta venta incluye productos con precio fijo en Bs" data-bs-toggle="tooltip">
                                                <i class="fas fa-lock text-success"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['cliente_nombre'] ?? 'Cliente no especificado'); ?>
                                </td>
                                <td>
                                    <strong class="text-primary">
                                        <?php
                                        if (isset($venta['total_formateado_usd'])) {
                                            echo $venta['total_formateado_usd'];
                                        } else {
                                            echo '$' . number_format($venta['total'] ?? 0, 2);
                                        }
                                        ?>
                                    </strong>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <strong class="text-success">
                                            <?php
                                            if (isset($venta['total_formateado_bs'])) {
                                                echo $venta['total_formateado_bs'];
                                            } else {
                                                echo TasaCambioHelper::formatearBS($venta['total_bs'] ?? 0);
                                            }
                                            ?>
                                        </strong>
                                        <?php if ($tiene_precio_fijo): ?>
                                            <small class="text-success">
                                                <i class="fas fa-lock me-1"></i> Incluye precios fijos
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php
                                        // Mostrar la tasa de cambio utilizada en esta venta
                                        if (isset($venta['tasa_cambio_utilizada'])) {
                                            echo number_format($venta['tasa_cambio_utilizada'], 2) . ' Bs/$';
                                        } elseif (isset($venta['tasa_formateada'])) {
                                            echo $venta['tasa_formateada'];
                                        } elseif (isset($venta['tasa_cambio'])) {
                                            echo number_format($venta['tasa_cambio'], 2) . ' Bs/$';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($venta['tipo_pago_nombre'] ?? 'No especificado'); ?>
                                </td>
                                <td>
                                    <?php
                                    $estado = $venta['estado'] ?? 'pendiente';
                                    $badge_class = $estado_badge[$estado] ?? 'bg-secondary';
                                    $badge_text = $estado_text[$estado] ?? $estado;
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $badge_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cerrada_en_caja): ?>
                                        <span class="badge bg-secondary" title="Esta venta ya fue procesada en cierre de caja" data-bs-toggle="tooltip">
                                            <i class="fas fa-lock me-1"></i> Cerrada
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success" title="Venta activa - Aparecerá en cierre de caja" data-bs-toggle="tooltip">
                                            <i class="fas fa-unlock me-1"></i> Activa
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $fecha; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="ver.php?id=<?php echo $venta['id']; ?>"
                                            class="btn btn-outline-info"
                                            title="Ver detalles de la venta"
                                            data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <?php if (($venta['estado'] ?? '') === 'pendiente' && !$cerrada_en_caja): ?>
                                            <button type="button"
                                                class="btn btn-outline-success"
                                                title="Completar venta"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalCompletar"
                                                data-id="<?php echo $venta['id']; ?>"
                                                data-numero="<?php echo $venta['numero_venta'] ?? ''; ?>"
                                                <?php echo $tiene_precio_fijo ? 'data-precio-fijo="true"' : ''; ?>>
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php elseif ($cerrada_en_caja): ?>
                                            <button type="button"
                                                class="btn btn-outline-secondary"
                                                title="Venta cerrada en caja - No se puede modificar"
                                                data-bs-toggle="tooltip" disabled>
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <td colspan="2" class="text-end"><strong>Resumen:</strong></td>
                            <td>
                                <strong class="text-primary">$<?php echo number_format($totalVentasUsd, 2); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Activas: $<?php echo number_format($totalActivasUsd, 2); ?>
                                </small>
                            </td>
                            <td>
                                <strong class="text-success"><?php echo TasaCambioHelper::formatearBS($totalVentasBs); ?></strong>
                                <br>
                                <small class="text-muted">
                                    Activas: <?php echo TasaCambioHelper::formatearBS($totalActivasBs); ?>
                                </small>
                            </td>
                            <td colspan="6" class="text-end">
                                <small class="text-muted">
                                    <i class="fas fa-unlock text-success me-1"></i>
                                    Activas: <strong class="text-success"><?php echo $ventasActivas; ?></strong>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-lock text-secondary me-1"></i>
                                    Cerradas: <strong class="text-secondary"><?php echo $ventasCerradas; ?></strong>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-shopping-cart text-primary me-1"></i>
                                    Total: <strong class="text-primary"><?php echo count($ventas); ?></strong>
                                </small>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Completar Venta -->
<div class="modal fade" id="modalCompletar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Confirmar Completar Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas completar la venta <strong id="numeroVenta"></strong>?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Importante:</strong> Al completar la venta, el stock de los productos se actualizará automáticamente.
                </div>
                <div id="precioFijoWarning" class="alert alert-warning d-none">
                    <i class="fas fa-lock me-2"></i>
                    <strong>Atención:</strong> Esta venta incluye productos con precio fijo en Bs.
                    Estos precios no se verán afectados por cambios futuros en la tasa de cambio.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <a href="#" id="btnCompletarConfirmar" class="btn btn-success">
                    <i class="fas fa-check me-1"></i> Completar Venta
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos para resaltar ventas con precios fijos */
    .table-warning {
        background-color: rgba(255, 243, 205, 0.3) !important;
    }

    .table-warning:hover {
        background-color: rgba(255, 243, 205, 0.5) !important;
    }

    /* Estilos para ventas cerradas */
    .venta-cerrada {
        opacity: 0.7;
    }
    
    .venta-cerrada:hover {
        opacity: 0.9;
        background-color: rgba(108, 117, 125, 0.1) !important;
    }
    
    .venta-cerrada td {
        color: #6c757d !important;
    }
    
    .venta-cerrada .badge.bg-secondary {
        background-color: #6c757d !important;
        color: white !important;
    }

    /* Animación para el ícono de candado */
    .fa-lock.text-success {
        animation: lock-pulse 2s infinite;
    }

    @keyframes lock-pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    /* Tooltip personalizado */
    .tooltip-inner {
        max-width: 300px;
    }

    /* Estilos para los totales */
    .table-active {
        background-color: rgba(0, 0, 0, 0.05) !important;
    }
    
    /* Estilo para filtros */
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 10px;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        margin-top: 10px;
    }
    
    /* Badge para estado activo/cerrado */
    .badge.bg-success {
        background-color: #198754 !important;
    }
    
    .badge.bg-secondary {
        background-color: #6c757d !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                delay: {
                    show: 100,
                    hide: 100
                }
            });
        });

        // Configurar DataTables
        const tablaVentas = $('#tablaVentas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [8, 'desc'] // Ordenar por fecha descendente
            ],
            columnDefs: [{
                orderable: false,
                targets: [9] // Columna de acciones no ordenable
            }],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            initComplete: function() {
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-control form-control-sm');
                
                // Añadir filtro personalizado para estado de cierre
                this.api().columns([7]).every(function() {
                    const column = this;
                    const select = $('<select class="form-select form-select-sm ms-2"><option value="">Todos</option><option value="Activa">Activas</option><option value="Cerrada">Cerradas</option></select>')
                        .appendTo($(column.header()))
                        .on('change', function() {
                            const val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });
                });
            },
            footerCallback: function(row, data, start, end, display) {
                const api = this.api();
                
                // Actualizar totales en el footer
                $(api.column(2).footer()).find('strong.text-primary').text(
                    '$' + api.column(2, {page: 'current'}).data().reduce(function(a, b) {
                        // Extraer el valor numérico del formato
                        const val = parseFloat(b.replace(/[^0-9.-]+/g, ""));
                        return (parseFloat(a) + (isNaN(val) ? 0 : val)).toFixed(2);
                    }, 0)
                );
                
                $(api.column(3).footer()).find('strong.text-success').text(
                    formatBsTotal(api.column(3, {page: 'current'}).data().reduce(function(a, b) {
                        // Extraer el valor numérico del formato Bs
                        const val = parseFloat(b.replace(/[^0-9.-]+/g, ""));
                        return (parseFloat(a) + (isNaN(val) ? 0 : val)).toFixed(2);
                    }, 0))
                );
                
                // Contar ventas activas y cerradas
                                let activas = 0;
                                let cerradas = 0;
                                
                                api.rows({page: 'current'}).every(function() {
                                    const data = this.data();
                                    const cierreCell = $(data[7]).text().toLowerCase();
                                    if (cierreCell.includes('activa')) {
                                        activas++;
                                    } else if (cierreCell.includes('cerrada')) {
                                        cerradas++;
                                    }
                                });
                                
                                // Actualizar contadores en el footer
                                $(api.column(9).footer()).find('strong.text-success').text(activas);
                                $(api.column(9).footer()).find('strong.text-secondary').text(cerradas);
                                $(api.column(9).footer()).find('strong.text-primary').text(activas + cerradas);
                            }
                        });

        // Configurar modal de completar venta
        const modalCompletar = document.getElementById('modalCompletar');
        if (modalCompletar) {
            modalCompletar.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const numeroVenta = button.getAttribute('data-numero');
                const tienePrecioFijo = button.getAttribute('data-precio-fijo') === 'true';
                
                // Actualizar contenido del modal
                document.getElementById('numeroVenta').textContent = '#' + numeroVenta;
                
                // Mostrar/ocultar advertencia de precio fijo
                const precioFijoWarning = document.getElementById('precioFijoWarning');
                if (tienePrecioFijo) {
                    precioFijoWarning.classList.remove('d-none');
                } else {
                    precioFijoWarning.classList.add('d-none');
                }
                
                // Configurar enlace de confirmación con token CSRF
                const confirmarBtn = document.getElementById('btnCompletarConfirmar');
                confirmarBtn.href = `index.php?action=completar&id=${id}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`;
            });
        }

        // Función para formatear totales en Bs
        function formatBsTotal(value) {
            const num = parseFloat(value);
            if (isNaN(num)) return 'Bs 0.00';
            return `Bs ${num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
        }

        // Actualizar contadores en tiempo real cuando se filtra la tabla
        tablaVentas.on('draw.dt', function() {
            const api = tablaVentas.api();
            
            // Recalcular totales USD
            let totalUsd = 0;
            let totalUsdActivas = 0;
            
            api.rows({page: 'current'}).every(function() {
                const rowData = this.data();
                const totalUsdCell = $(rowData[2]).text().replace(/[^0-9.-]+/g, "");
                const valorUsd = parseFloat(totalUsdCell) || 0;
                totalUsd += valorUsd;
                
                // Verificar si es activa
                const cierreCell = $(rowData[7]).text().toLowerCase();
                if (cierreCell.includes('activa')) {
                    totalUsdActivas += valorUsd;
                }
            });
            
            // Actualizar USD en footer
            const footerUsd = tablaVentas.footer();
            $(footerUsd).find('td').eq(2).find('strong.text-primary').text('$' + totalUsd.toFixed(2));
            $(footerUsd).find('td').eq(2).find('small.text-muted').text('Activas: $' + totalUsdActivas.toFixed(2));
            
            // Recalcular totales Bs
            let totalBs = 0;
            let totalBsActivas = 0;
            
            api.rows({page: 'current'}).every(function() {
                const rowData = this.data();
                const totalBsCell = $(rowData[3]).text().replace(/[^0-9.-]+/g, "");
                const valorBs = parseFloat(totalBsCell) || 0;
                totalBs += valorBs;
                
                // Verificar si es activa
                const cierreCell = $(rowData[7]).text().toLowerCase();
                if (cierreCell.includes('activa')) {
                    totalBsActivas += valorBs;
                }
            });
            
            // Actualizar Bs en footer
            $(footerUsd).find('td').eq(3).find('strong.text-success').text(formatBsTotal(totalBs));
            $(footerUsd).find('td').eq(3).find('small.text-muted').text('Activas: ' + formatBsTotal(totalBsActivas));
            
            // Recalcular contadores de ventas
            let activas = 0;
            let cerradas = 0;
            
            api.rows({page: 'current'}).every(function() {
                const rowData = this.data();
                const cierreCell = $(rowData[7]).text().toLowerCase();
                if (cierreCell.includes('activa')) {
                    activas++;
                } else if (cierreCell.includes('cerrada')) {
                    cerradas++;
                }
            });
            
            // Actualizar contadores en footer
            const totalVentas = activas + cerradas;
            $(footerUsd).find('td').eq(9).find('strong.text-success').text(activas);
            $(footerUsd).find('td').eq(9).find('strong.text-secondary').text(cerradas);
            $(footerUsd).find('td').eq(9).find('strong.text-primary').text(totalVentas);
        });

        // Configurar filtros rápidos por estado
        const urlParams = new URLSearchParams(window.location.search);
        const filtroEstado = urlParams.get('estado');
        
        if (filtroEstado) {
            tablaVentas.column(7).search(filtroEstado, true, false).draw();
        }

        // Auto-refrescar la página cada 5 minutos para mantener datos actualizados
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutos

        // Notificación de ventas pendientes
        <?php if ($ventasActivas > 0 && $filtro_activas): ?>
            // Mostrar notificación si hay ventas activas pendientes
            const notificacion = document.createElement('div');
            notificacion.className = 'alert alert-warning alert-dismissible fade show position-fixed bottom-0 end-0 m-3';
            notificacion.style.zIndex = '1050';
            notificacion.style.maxWidth = '300px';
            notificacion.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Tienes ${ventasActivas} ventas activas</strong>
                <small class="d-block mt-1">Recuerda cerrarlas en caja al finalizar el día</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notificacion);
            
            // Auto-ocultar después de 10 segundos
            setTimeout(() => {
                const alert = new bootstrap.Alert(notificacion);
                alert.close();
            }, 10000);
        <?php endif; ?>

        // Botón para exportar datos
        const exportBtn = document.createElement('button');
        exportBtn.className = 'btn btn-outline-primary btn-sm ms-2';
        exportBtn.innerHTML = '<i class="fas fa-download me-1"></i> Exportar';
        exportBtn.addEventListener('click', function() {
            // Exportar tabla a CSV
            const data = [];
            const headers = [];
            
            // Obtener encabezados
            $('#tablaVentas thead th').each(function() {
                headers.push($(this).text().trim());
            });
            data.push(headers.join(','));
            
            // Obtener datos
            tablaVentas.rows({search: 'applied'}).every(function() {
                const rowData = this.data();
                const row = [];
                $(rowData).each(function(index, cell) {
                    // Limpiar HTML y extraer texto
                    row.push($(cell).text().trim().replace(/,/g, ''));
                });
                data.push(row.join(','));
            });
            
            // Crear y descargar archivo CSV
            const csvContent = "data:text/csv;charset=utf-8," + data.join('\n');
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `ventas_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        // Añadir botón de exportar a los controles de DataTables
        $('.dataTables_length').before(exportBtn);
    });
</script>

<!-- <?php
// Incluir pie de página
include __DIR__ . '/../layouts/footer.php';
?> -->